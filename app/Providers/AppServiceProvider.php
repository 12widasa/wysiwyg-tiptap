<?php

namespace App\Providers;

use HTMLPurifier;
use HTMLPurifier_Config;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(HTMLPurifier::class, function () {
            $config = HTMLPurifier_Config::createDefault();

            $config->set('HTML.Doctype', 'HTML 4.01 Transitional');

            // Task list (<ul data-type="taskList">) tidak ikut di-purify —
            // ditangani secara terpisah di ContentController::sanitize()
            // menggunakan DOMDocument agar <label>, <input>, <div> di dalam
            // <li> tidak di-strip oleh content model HTML 4.01 yang ketat.
            $config->set('HTML.Allowed', implode(',', [
                'p',
                'br',
                'strong',
                'em',
                'u',
                's',
                'h1',
                'h2',
                'h3',
                'h4',
                'h5',
                'h6',
                'ul[data-type]',
                'ol',
                'li[data-type|data-checked]',
                'blockquote',
                'a[href|target|rel]',
                'img[src|alt|width|style|class]',
                'figure[class|data-type|data-src|data-alt|data-width|data-caption|data-align]',
                'figcaption[class]',
                'table',
                'thead',
                'tbody',
                'tr',
                'th[scope|style]',
                'td[colspan|rowspan|style]',
                'hr',
                'span[style]',
                'code',
                'pre',
            ]));

            $config->set('CSS.AllowedProperties',    'margin-left,text-align,background-color,color,font-size,width,max-width');
            $config->set('AutoFormat.AutoParagraph', false);
            $config->set('AutoFormat.RemoveEmpty',   false);
            $config->set('Core.EscapeInvalidTags',   false);
            $config->set('URI.AllowedSchemes',       ['http' => true, 'https' => true, 'mailto' => true]);
            $config->set('Cache.SerializerPath',     storage_path('app/purifier'));
            $config->set('HTML.DefinitionID',        'wysiwyg-tiptap');
            $config->set('HTML.DefinitionRev',       7);

            if ($def = $config->maybeGetRawHTMLDefinition()) {
                $def->addElement(
                    'figure',
                    'Block',
                    'Optional: (figcaption, Flow) | (Flow, figcaption?) | Flow',
                    'Common',
                    [
                        'class'        => 'CDATA',
                        'data-type'    => 'CDATA',
                        'data-src'     => 'CDATA',
                        'data-alt'     => 'CDATA',
                        'data-width'   => 'CDATA',
                        'data-caption' => 'CDATA',
                        'data-align'   => 'CDATA',
                    ]
                );
                $def->addElement('figcaption', 'Block', 'Flow', 'Common', ['class' => 'CDATA']);
                $def->addAttribute('ul', 'data-type',    'CDATA');
                $def->addAttribute('li', 'data-type',    'CDATA');
                $def->addAttribute('li', 'data-checked', 'CDATA');
            }

            return new HTMLPurifier($config);
        });
    }

    public function boot(): void {}
}

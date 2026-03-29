<?php

namespace App\Providers;

use HTMLPurifier;
use HTMLPurifier_Config;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // HTMLPurifier di-bind sebagai singleton agar config dan cache
        // hanya dibangun sekali per lifecycle aplikasi, bukan per-request.
        $this->app->singleton(HTMLPurifier::class, function () {
            $config = HTMLPurifier_Config::createDefault();

            $config->set('HTML.Doctype', 'HTML 4.01 Transitional');
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
                'li',
                'blockquote',
                'a[href|target|rel]',
                'img[src|alt|width|style|class]',
                'figure[class]',
                'figcaption[class]',
                'table',
                'thead',
                'tbody',
                'tr',
                'th[scope|style]',
                'td[colspan|rowspan|style]',
                'hr',
                'span[style]',
            ]));
            $config->set('CSS.AllowedProperties',    'margin-left,text-align,background-color,color,font-size,width,max-width');
            $config->set('AutoFormat.AutoParagraph', false);
            $config->set('AutoFormat.RemoveEmpty',   false);
            $config->set('Core.EscapeInvalidTags',   true);
            $config->set('URI.AllowedSchemes',       ['http' => true, 'https' => true, 'mailto' => true]);
            $config->set('Cache.SerializerPath',     storage_path('app/purifier'));
            $config->set('HTML.DefinitionID',        'wysiwyg-tiptap');
            $config->set('HTML.DefinitionRev',       3);

            if ($def = $config->maybeGetRawHTMLDefinition()) {
                $def->addElement('figure',     'Block', 'Optional: (figcaption, Flow) | (Flow, figcaption?) | Flow', 'Common', ['class' => 'CDATA']);
                $def->addElement('figcaption', 'Block', 'Flow', 'Common', ['class' => 'CDATA']);
                $def->addAttribute('ul', 'data-type', 'CDATA');
                $def->addElement('input', 'Inline', 'Empty', 'Common', [
                    'type'     => 'Enum#checkbox',
                    'checked'  => 'Bool#checked',
                    'disabled' => 'Bool#disabled',
                ]);
            }

            return new HTMLPurifier($config);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}

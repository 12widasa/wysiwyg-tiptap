<?php

namespace App\Http\Controllers;

use App\Models\Content;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use HTMLPurifier;
use HTMLPurifier_Config;

class ContentController extends Controller
{
    public function create()
    {
        return view('content.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'description' => ['required', 'string', 'max:500000'],
        ]);

        $clean = $this->sanitize($request->input('description'));

        $content = Content::create(['description' => $clean]);

        return redirect()->route('content.show', $content->id);
    }

    public function show(Content $content)
    {
        return view('content.show', compact('content'));
    }

    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => [
                'required',
                'image',
                'mimes:jpeg,png,jpg,gif,webp',
                'max:5120',
                'dimensions:max_width=4096,max_height=4096',
            ],
        ]);

        $ext      = strtolower($request->file('image')->getClientOriginalExtension());
        $filename = Str::uuid() . '.' . $ext;
        $path     = $request->file('image')->storeAs('content-images', $filename, 'public');

        return response()->json([
            'url' => asset('storage/' . $path),
        ]);
    }

    // ── HTML Sanitizer ────────────────────────────────────────────────────────
    // HTMLPurifier tidak support figure/figcaption via config saja —
    // elemen HTML5 harus didefinisikan manual via HTMLPurifier_HTMLDefinition.
    // Ini cara resmi yang direkomendasikan di dokumentasi HTMLPurifier.
    private function sanitize(string $dirty): string
    {
        $config = HTMLPurifier_Config::createDefault();

        // HTML5 doctype — wajib agar figure, figcaption, input[type=checkbox] dikenali
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

        // ── Daftarkan figure & figcaption sebagai elemen HTML5 custom ──
        // Ini satu-satunya cara yang didukung HTMLPurifier untuk elemen
        // yang tidak ada di HTML 4.01 (figure, figcaption, article, dll.)
        $config->set('HTML.DefinitionID',  'wysiwyg-tiptap');
        $config->set('HTML.DefinitionRev', 3);

        if ($def = $config->maybeGetRawHTMLDefinition()) {
            // figure & figcaption — tidak ada di HTML 4.01, harus didaftarkan manual
            $def->addElement('figure',     'Block', 'Optional: (figcaption, Flow) | (Flow, figcaption?) | Flow', 'Common', ['class' => 'CDATA']);
            $def->addElement('figcaption', 'Block', 'Flow', 'Common', ['class' => 'CDATA']);

            // input[checkbox] untuk task list (readonly di output, hanya display)
            $def->addAttribute('ul', 'data-type', 'CDATA');
            $def->addElement('input', 'Inline', 'Empty', 'Common', [
                'type'     => 'Enum#checkbox',
                'checked'  => 'Bool#checked',
                'disabled' => 'Bool#disabled',
            ]);
        }

        $purifier = new HTMLPurifier($config);
        return $purifier->purify($dirty);
    }
}

<?php

return [
    'encoding'      => 'UTF-8',
    'finalize'      => true,
    'cachePath'     => storage_path('app/purifier'),
    'cacheFileMode' => 0755,
    'settings'      => [
        'default' => [
            'HTML.Doctype'             => 'XHTML 1.0 Transitional',

            // figure & figcaption ditambahkan agar tidak perlu bypass purifier
            // data-* di-handle via HTML.Allowed dengan AllowDataAttributes
            'HTML.Allowed' => implode(',', [
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
                'ul',
                'ol',
                'li',
                'blockquote',
                'a[href|target|rel]',
                'img[src|alt|width|style|class]',
                'figure[data-type|data-src|data-alt|data-width|data-caption|data-align|class]',
                'figcaption[class]',
                'table',
                'thead',
                'tbody',
                'tr',
                'th[scope|style]',
                'td[colspan|rowspan|style]',
                'hr',
                'span[style]',
                'input[type|checked|disabled]', // task list checkbox (readonly di output)
                'label',
            ]),

            'CSS.AllowedProperties'    => 'margin-left,text-align,background-color,color,font-size,width,max-width',
            'AutoFormat.AutoParagraph' => false,
            'AutoFormat.RemoveEmpty'   => false,
            'Core.EscapeInvalidTags'   => true,

            // Blokir javascript: dan data: URI di href/src
            'URI.SafeIframeRegexp'     => null,
            'URI.AllowedSchemes'       => ['http' => true, 'https' => true, 'mailto' => true],
        ],
    ],
];

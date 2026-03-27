<?php

return [
    'encoding'      => 'UTF-8',
    'finalize'      => true,
    'cachePath'     => storage_path('app/purifier'),
    'cacheFileMode' => 0755,
    'settings'      => [
        'default' => [
            'HTML.Doctype'             => 'XHTML 1.0 Transitional',
            'HTML.Allowed'             => 'p,br,strong,em,u,s,h1,h2,h3,h4,h5,h6,ul,ol,li,blockquote,a[href|target|rel],img[src|alt|width|style],table,thead,tbody,tr,th[scope],td[colspan|rowspan],hr,span[style]',
            'CSS.AllowedProperties'    => 'margin-left,text-align,background-color,color',
            'AutoFormat.AutoParagraph' => false,
            'AutoFormat.RemoveEmpty'   => false,
            'Core.EscapeInvalidTags'   => true,
        ],
    ],
];
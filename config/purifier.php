<?php

/**
 * HTMLPurifier config — hanya setting dasar.
 *
 * PENTING: figure & figcaption TIDAK bisa didaftarkan di sini karena
 * HTMLPurifier memerlukan PHP code (maybeGetRawHTMLDefinition) untuk
 * mendefinisikan elemen HTML5 custom. Sanitasi lengkap dilakukan di
 * ContentController::sanitize() menggunakan HTMLPurifier_Config langsung.
 *
 * File ini hanya dipakai jika helper clean() dipanggil di tempat lain.
 */
return [
    'encoding'      => 'UTF-8',
    'finalize'      => true,
    'cachePath'     => storage_path('app/purifier'),
    'cacheFileMode' => 0755,
    'settings'      => [
        'default' => [
            'HTML.Doctype'             => 'HTML 4.01 Transitional',
            'HTML.Allowed'             => 'p,br,strong,em,u,s,h1,h2,h3,h4,h5,h6,ul,ol,li,blockquote,a[href|target|rel],img[src|alt|width|style|class],table,thead,tbody,tr,th[scope|style],td[colspan|rowspan|style],hr,span[style],input[type|checked|disabled],label',
            'CSS.AllowedProperties'    => 'margin-left,text-align,background-color,color,font-size,width,max-width',
            'AutoFormat.AutoParagraph' => false,
            'AutoFormat.RemoveEmpty'   => false,
            'Core.EscapeInvalidTags'   => true,
            'URI.AllowedSchemes'       => ['http' => true, 'https' => true, 'mailto' => true],
        ],
    ],
];

<?php

namespace App\View\Components;

use Illuminate\View\Component;

class Wysiwyg extends Component
{
    /**
     * @param string      $name        name attribute untuk hidden input
     * @param string|null $value       initial HTML content (untuk halaman edit)
     * @param string      $placeholder placeholder teks di editor
     * @param string      $uploadUrl   endpoint upload gambar
     * @param int         $height      min-height editor area (px)
     * @param string|null $id          override id unik (auto-generate jika null)
     */
    public function __construct(
        public string  $name        = 'content',
        public ?string $value       = null,
        public string  $placeholder = 'Mulai menulis di sini…',
        public string  $uploadUrl   = '/content/upload-image',
        public int     $height      = 480,
        public ?string $id          = null,
    ) {
        // Auto-generate id unik agar bisa pakai banyak editor dalam 1 halaman
        if ($this->id === null) {
            $this->id = 'wysiwyg-' . uniqid();
        }
    }

    public function render()
    {
        return view('components.wysiwyg');
    }
}
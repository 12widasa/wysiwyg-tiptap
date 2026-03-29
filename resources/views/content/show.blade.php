{{-- resources/views/content/show.blade.php --}}
<x-layouts.app title="Lihat Konten">

    <div class="max-w-4xl mx-auto py-8 px-4">

        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-800">Hasil Konten</h1>
            <a href="{{ route('content.create') }}"
                class="px-4 py-2 bg-brand hover:bg-brand-dark text-white text-sm font-semibold rounded-lg transition">
                + Buat Baru
            </a>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-8">
            @if (trim(strip_tags($content->description)))
                <div class="prose-content wysiwyg-prose">
                    {{--
                        SAFE — {!! !!} digunakan secara sengaja di sini.
                        $content->description sudah disanitasi via HTMLPurifier
                        di ContentController::sanitize() sebelum disimpan ke database.
                        JANGAN ganti ke {{ }} karena akan meng-escape tag HTML yang valid.
                        JANGAN tampilkan kolom HTML lain di sini tanpa melewati sanitize() terlebih dahulu.
                    --}}
                    {!! $content->description !!}
                </div>
            @else
                <p class="text-sm text-gray-400 text-center py-8">Konten kosong.</p>
            @endif
        </div>

        <p class="mt-3 text-xs text-gray-400 text-right font-mono">
            ID: {{ $content->id }} · {{ $content->created_at->diffForHumans() }}
        </p>

    </div>

</x-layouts.app>

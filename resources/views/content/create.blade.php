{{-- resources/views/content/create.blade.php --}}
<x-layouts.app title="Buat Konten">

    <div class="max-w-4xl mx-auto py-8 px-4">

        {{-- Header --}}
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Buat Konten</h1>
                <p class="text-sm text-gray-500 mt-1">Tulis dan format konten menggunakan editor di bawah</p>
            </div>
        </div>

        {{-- Validation errors --}}
        @if ($errors->any())
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                <ul class="text-sm text-red-600 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>• {{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Form --}}
        <form action="{{ route('content.store') }}" method="POST" x-data="{ submitting: false, editorHtml: '' }"
            @wysiwyg-update.window="editorHtml = $event.detail.html" @submit="submitting = true">
            @csrf

            {{-- WYSIWYG Editor component --}}
            <x-wysiwyg name="description" />

            {{-- Submit area --}}
            <div class="mt-4 flex items-center justify-end gap-3">
                <button type="submit" :disabled="submitting || !editorHtml.replace(/<[^>]*>/g, '').trim()"
                    :class="(submitting || !editorHtml.replace(/<[^>]*>/g, '').trim()) ? 'opacity-50 cursor-not-allowed' :
                    'hover:bg-brand-dark'"
                    class="px-5 py-2 bg-brand text-white text-sm font-semibold rounded-lg transition">
                    <span x-show="!submitting">Simpan Konten</span>
                    <span x-show="submitting">Menyimpan...</span>
                </button>
            </div>
        </form>

    </div>

</x-layouts.app>

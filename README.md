<p align="center">
  <h1 align="center">WYSIWYG Editor (Reusable)</h1>
  <p align="center">
    Lightweight, reusable WYSIWYG editor component for Laravel projects.
  </p>
</p>

---

## 🚀 About This Project

Project ini adalah implementasi **WYSIWYG (What You See Is What You Get) editor** yang dirancang agar:

- ✅ Mudah diintegrasikan ke berbagai project Laravel
- ✅ Bisa **copy-paste (plug & play)** antar project
- ✅ Struktur clean & maintainable
- ✅ Siap digunakan untuk kebutuhan production

Fokus utama project ini adalah **reusability**, sehingga kamu tidak perlu membuat editor dari nol di setiap project baru.

---

**Fitur:**
- Bold, Italic, Underline, Strikethrough
- Heading 1–6, Paragraph, Blockquote
- Bullet list, Ordered list, Task list (checkbox)
- Text align (left / center / right / justify)
- Indent / Outdent
- Highlight color & Text color
- Insert Link (bubble dialog)
- Insert Image (upload drag & drop, resize, caption, align)
- Insert Table (add/delete row & column)
- Insert Divider (HR)
- Word count & char count di status bar
- Clear formatting
- HTML sanitizer di backend (mews/purifier)
- Multi-instance support (banyak editor dalam 1 halaman)
- Set content dari luar via custom event (untuk modal edit)

---

## 📦 Panduan Integrasi ke Project Laravel Lain

### Langkah 1 — Copy file-file ini

| File sumber | Tujuan di project kamu |
|---|---|
| `resources/js/wysiwyg.js` | `resources/js/wysiwyg.js` |
| `resources/css/wysiwyg.css` | `resources/css/wysiwyg.css` |
| `resources/views/components/wysiwyg.blade.php` | `resources/views/components/wysiwyg.blade.php` |
| `resources/views/components/wysiwyg-toolbar.blade.php` | `resources/views/components/wysiwyg-toolbar.blade.php` |
| `app/View/Components/Wysiwyg.php` | `app/View/Components/Wysiwyg.php` |
| `app/Services/HtmlSanitizer.php` | `app/Services/HtmlSanitizer.php` |

> ⚠️ **Jangan skip `HtmlSanitizer.php`** — file ini menangani task list khusus yang tidak bisa di-handle HTMLPurifier standar.

---

### Langkah 2 — Install dependencies JS

Tambahkan ke `package.json` lalu jalankan `npm install` / `pnpm install`:

```json
"dependencies": {
    "@tiptap/core": "^3.x",
    "@tiptap/starter-kit": "^3.x",
    "@tiptap/extension-underline": "^3.x",
    "@tiptap/extension-text-align": "^3.x",
    "@tiptap/extension-link": "^3.x",
    "@tiptap/extension-color": "^3.x",
    "@tiptap/extension-text-style": "^3.x",
    "@tiptap/extension-highlight": "^3.x",
    "@tiptap/extension-table": "^3.x",
    "@tiptap/extension-table-row": "^3.x",
    "@tiptap/extension-table-cell": "^3.x",
    "@tiptap/extension-table-header": "^3.x",
    "@tiptap/extension-placeholder": "^3.x",
    "@tiptap/extension-task-list": "^3.x",
    "@tiptap/extension-task-item": "^3.x",
    "@tiptap/extension-image": "^3.x",
    "alpinejs": "^3.x",
    "prosemirror-state": "^1.x",
    "prosemirror-tables": "^1.x"
}
```

---

### Langkah 3 — Install dependency PHP

```bash
composer require mews/purifier
```

---

### Langkah 4 — Daftarkan HTMLPurifier di AppServiceProvider

Tambahkan method `registerHtmlPurifier()` ke `AppServiceProvider.php`. Konfigurasi ini **wajib lengkap** — terutama bagian `addElement('figure', ...)` agar atribut `data-src`, `data-caption`, dll tidak di-strip saat sanitasi.

```php
use App\Services\HtmlSanitizer;
use HTMLPurifier;
use HTMLPurifier_Config;

private function registerHtmlPurifier(): void
{
    $this->app->singleton(HTMLPurifier::class, function () {
        $config = HTMLPurifier_Config::createDefault();

        $config->set('HTML.Doctype', 'HTML 4.01 Transitional');
        $config->set('HTML.Allowed', implode(',', [
            'p', 'br', 'strong', 'em', 'u', 's',
            'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
            'ul[data-type]', 'ol', 'li[data-type|data-checked]',
            'blockquote',
            'a[href|target|rel]',
            'img[src|alt|width|style|class]',
            'figure[class|data-type|data-src|data-alt|data-width|data-caption|data-align]',
            'figcaption[class]',
            'table', 'thead', 'tbody', 'tr',
            'th[scope|style]', 'td[colspan|rowspan|style]',
            'hr', 'span[style]', 'code', 'pre',
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
            $def->addElement('figure', 'Block',
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

    $this->app->singleton(HtmlSanitizer::class, function ($app) {
        return new HtmlSanitizer($app->make(HTMLPurifier::class));
    });
}
```

Panggil di `register()`:

```php
public function register(): void
{
    $this->registerHtmlPurifier();
    // ... singleton lainnya
}
```

Buat folder cache purifier:

```bash
mkdir -p storage/app/purifier
```

---

### Langkah 5 — Buat endpoint upload gambar di controller

Tambahkan method `uploadImage()` ke controller yang relevan. **Sesuaikan nama folder** sesuai fitur kamu.

```php
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

public function uploadImage(Request $request)
{
    $request->validate([
        'image' => [
            'required', 'image',
            'mimes:jpeg,png,jpg,gif,webp',
            'max:5120',
            'dimensions:max_width=4096,max_height=4096',
        ],
    ]);

    // Gunakan ekstensi dari MIME type server — bukan dari nama file user
    $mimeToExt = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
    ];

    try {
        $mime     = $request->file('image')->getMimeType();
        $ext      = $mimeToExt[$mime] ?? 'jpg';
        $filename = Str::uuid() . '.' . $ext;

        // ⚠️ Sesuaikan nama folder: 'nama-fitur-images'
        $path = $request->file('image')->storeAs('nama-fitur-images', $filename, 'public');

        if (! $path) {
            throw new \RuntimeException('File gagal disimpan ke storage.');
        }

        return response()->json(['url' => asset('storage/' . $path)]);

    } catch (\Throwable $e) {
        Log::error('uploadImage failed', ['error' => $e->getMessage()]);
        return response()->json(['message' => 'Upload gagal. Coba lagi.'], 500);
    }
}
```

Daftarkan route (dengan rate limiting):

```php
// ⚠️ Sesuaikan nama route dan controller
Route::post('/admin/nama-fitur/upload-image', [NamaFiturController::class, 'uploadImage'])
    ->middleware('throttle:20,1')
    ->name('admin.nama-fitur.upload-image');
```

Pastikan storage link sudah dibuat:

```bash
php artisan storage:link
```

---

### Langkah 6 — Sanitasi & simpan di controller (store/update)

```php
use App\Services\HtmlSanitizer;

// Inject di constructor
public function __construct(private readonly HtmlSanitizer $sanitizer) {}

// Di method store():
public function store(Request $request)
{
    $request->validate([
        // ⚠️ Sesuaikan nama field
        'content' => ['required', 'string', 'max:500000'],
    ]);

    $clean = $this->sanitizer->sanitize($request->input('content'));

    NamaModel::create([
        'content' => $clean,
        // field lain...
    ]);
}

// Di method update():
public function update(Request $request, NamaModel $model)
{
    $request->validate([
        'content' => ['required', 'string', 'max:500000'],
    ]);

    $clean = $this->sanitizer->sanitize($request->input('content'));

    $model->update(['content' => $clean]);
}
```

---

### Langkah 7 — Import CSS & JS

Di `resources/css/app.css`:

```css
@import './wysiwyg.css';
```

Di `resources/js/app.js`:

```js
import './wysiwyg.js';
```

Pastikan layout punya:

```html
<meta name="csrf-token" content="{{ csrf_token() }}">
@stack('scripts')
```

---

### Langkah 8 — Gunakan component di form biasa (halaman create/edit)

**Minimal:**
```blade
<x-wysiwyg name="content" />
```

**Dengan semua props (halaman edit):**
```blade
<x-wysiwyg
    name="content"
    :value="$model->content"
    placeholder="Tulis konten di sini…"
    uploadUrl="{{ route('admin.nama-fitur.upload-image') }}"
    :height="480"
/>
```

**Props yang tersedia:**

| Prop | Default | Keterangan |
|---|---|---|
| `name` | `content` | Name attribute pada hidden input |
| `value` | `null` | Initial HTML (untuk halaman edit) |
| `placeholder` | `Mulai menulis di sini…` | Placeholder teks editor |
| `uploadUrl` | `/content/upload-image` | Endpoint upload gambar — **sesuaikan!** |
| `height` | `480` | Min-height area editor (px) |
| `id` | auto-generate | Override id unik jika perlu |

---

### Langkah 9 — Gunakan component di dalam modal (Alpine/DaisyUI)

Karena modal di-render sekali saat halaman load, konten edit harus di-inject via custom event setelah modal dibuka.

**Di blade modal:**

```blade
<dialog class="modal" x-ref="formModal"
    @close="
        window.dispatchEvent(new CustomEvent('wysiwyg-set-content:wysiwyg-NAMAFITUR', { detail: { html: '' } }))
    ">
    <div class="modal-box ...">
        <form ...>
            {{-- field lain --}}

            <x-wysiwyg
                name="content"
                :value="null"
                placeholder="Tulis konten di sini…"
                uploadUrl="{{ route('admin.nama-fitur.upload-image') }}"
                :height="400"
                id="wysiwyg-NAMAFITUR"
            />
        </form>
    </div>
</dialog>

{{-- Patch openEdit() agar inject content ke editor saat modal dibuka --}}
<script>
    document.addEventListener('alpine:initialized', () => {
        // ⚠️ Sesuaikan selector dengan x-data component induk yang punya openEdit()
        const rootEl = document.querySelector('[x-data*="crudTable"]');
        if (!rootEl) return;

        const component = Alpine.$data(rootEl);
        if (!component || typeof component.openEdit !== 'function') return;

        const _originalOpenEdit = component.openEdit.bind(component);

        component.openEdit = function(data) {
            _originalOpenEdit(data);

            // ⚠️ Sesuaikan nama event dan nama field content
            setTimeout(() => {
                window.dispatchEvent(new CustomEvent('wysiwyg-set-content:wysiwyg-NAMAFITUR', {
                    detail: { html: data.content ?? '' }
                }));
            }, 100);
        };
    });
</script>
```

> **Checklist saat copy untuk fitur baru:**
> - Ganti `wysiwyg-NAMAFITUR` → nama unik per fitur (contoh: `wysiwyg-announcement`, `wysiwyg-article`)
> - Ganti `[x-data*="crudTable"]` → selector Alpine component induk yang punya `openEdit()`
> - Ganti `data.content` → nama field konten di data kamu (misal `data.body`, `data.description`)
> - Ganti nama event di `@close` dialog agar sama dengan `id` editor

---

### Langkah 10 — Tampilkan konten (halaman show/detail)

```blade
<div class="wysiwyg-prose">
    {!! $model->content !!}
</div>
```

> ✅ `{!! !!}` aman di sini **hanya karena** konten sudah melewati `HtmlSanitizer::sanitize()` sebelum disimpan ke DB.

---

## ⚠️ Hal yang Wajib Disesuaikan Per Fitur

| Yang perlu disesuaikan | Di mana |
|---|---|
| Nama folder upload gambar | `uploadImage()` di controller — `storeAs('nama-fitur-images', ...)` |
| Route name upload | `web.php` dan prop `uploadUrl` di blade |
| `id` editor | Prop `id="wysiwyg-NAMAFITUR"` di blade |
| Nama custom event | `wysiwyg-set-content:wysiwyg-NAMAFITUR` — harus sama di 3 tempat: `id` editor, `@close` dialog, dan script `openEdit` |
| Selector Alpine induk | `[x-data*="crudTable"]` di script patch |
| Nama field content di data | `data.content` di script patch |
| Nama field di `validate()` | Di controller store/update |
| Nama field di `sanitizer->sanitize()` | Di controller store/update |

---

## 🐛 Troubleshooting

### Gambar "Image failed to load" saat modal edit dibuka

**Penyebab:** `addAttributes()` di `wysiwyg.js` tidak punya `parseHTML` per-atribut sehingga Tiptap tidak bisa membaca `data-src` dari `<figure>` yang tersimpan di DB.

**Fix:** Pastikan `addAttributes()` di `ImageFigureNode` dalam `wysiwyg.js` sudah seperti ini:

```js
addAttributes() {
    return {
        src: {
            default: null,
            parseHTML: (el) =>
                el.getAttribute('data-src') ||
                el.querySelector('img')?.getAttribute('src') ||
                null,
        },
        alt: {
            default: '',
            parseHTML: (el) =>
                el.getAttribute('data-alt') ||
                el.querySelector('img')?.getAttribute('alt') ||
                '',
        },
        width: {
            default: null,
            parseHTML: (el) => el.getAttribute('data-width') || null,
        },
        caption: {
            default: '',
            parseHTML: (el) => el.getAttribute('data-caption') || '',
        },
        align: {
            default: 'left',
            parseHTML: (el) => el.getAttribute('data-align') || 'left',
        },
    };
},
```

Lalu rebuild: `npm run build`

---

### Gambar tidak muncul sama sekali (404)

```bash
php artisan storage:link
ls -la public/storage
# Harus: public/storage -> /absolute/path/ke/storage/app/public
```

---

### Gambar ter-strip setelah disimpan (data-src hilang dari DB)

Purifier di `AppServiceProvider` belum punya `addElement('figure', ...)`. Pastikan seluruh blok `if ($def = $config->maybeGetRawHTMLDefinition())` ada, lalu hapus cache purifier:

```bash
rm -rf storage/app/purifier/*
php artisan cache:clear
```

---

### Editor duplikat / dua instance wysiwygEditor di halaman

Cek apakah ada file blade duplikat (misal `_form-modal.blade copy.php`) yang ikut di-render Laravel. Hapus file duplikat tersebut.

---

### Konten tidak ter-set saat modal edit dibuka

Timing issue — event dikirim sebelum editor selesai init. Pastikan script patch menggunakan `setTimeout(..., 100)`, bukan `requestAnimationFrame`.

---

## 🧱 Struktur File Component

```
resources/
├── js/
│   └── wysiwyg.js                   ← Core Tiptap editor (window.initWysiwyg)
├── css/
│   └── wysiwyg.css                  ← Styles editor (prose, toolbar, dropzone, figure)
└── views/components/
    ├── wysiwyg.blade.php             ← Shell component + Alpine wysiwygEditor data
    └── wysiwyg-toolbar.blade.php    ← Toolbar HTML (di-include oleh wysiwyg.blade.php)

app/
├── View/Components/
│   └── Wysiwyg.php                  ← Blade component class (props, auto-id)
└── Services/
    └── HtmlSanitizer.php            ← Sanitasi HTML + task list handler
```

---

## 🔑 Public API `initWysiwyg()`

```js
const instance = window.initWysiwyg({
    editorEl,          // HTMLElement — container editor
    initialContent,    // string HTML awal (opsional)
    placeholder,       // string placeholder
    uploadUrl,         // string endpoint upload gambar
    onUpdate,          // function(html, text) — dipanggil saat konten berubah
    onSelectionUpdate  // function(editor) — dipanggil saat selection berubah
});

// instance yang dikembalikan:
instance.editor           // Tiptap Editor object
instance.getFigureAlign() // align imageFigure yang sedang di-select
instance.getFigurePos()   // posisi imageFigure yang sedang di-select
instance.sanitizeUrl(url) // validasi & sanitasi URL
instance.escapeHtml(str)  // escape karakter HTML berbahaya
instance.destroy()        // cleanup editor
```

---

## 📡 Custom Events

| Event | Arah | Payload | Keterangan |
|---|---|---|---|
| `wysiwyg-update` | editor → window | `{ html }` | Dipanggil setiap kali konten berubah |
| `wysiwyg-set-content:ID` | window → editor | `{ html }` | Set konten dari luar (untuk modal edit) |
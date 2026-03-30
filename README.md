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

## 📦 Cara Integrasi ke Project Laravel Lain

Cukup copy-paste beberapa file, tambahkan sedikit konfigurasi, dan editor langsung siap dipakai — tanpa perlu install package tambahan dari nol.

### 1. Copy file-file berikut ke project kamu

| File sumber | Tujuan di project kamu |
|---|---|
| `resources/js/wysiwyg.js` | `resources/js/wysiwyg.js` |
| `resources/css/wysiwyg.css` | `resources/css/wysiwyg.css` |
| `resources/views/components/wysiwyg.blade.php` | `resources/views/components/wysiwyg.blade.php` |
| `resources/views/components/wysiwyg-toolbar.blade.php` | `resources/views/components/wysiwyg-toolbar.blade.php` |
| `app/View/Components/Wysiwyg.php` | `app/View/Components/Wysiwyg.php` |

### 2. Install dependencies JS

Pastikan project kamu sudah punya dependensi berikut di `package.json`, lalu jalankan `npm install` / `pnpm install`:

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
    "prosemirror-state": "^1.x"
}
```

### 3. Install dependency PHP

```bash
composer require mews/purifier
```

### 4. Import di entry point JS & CSS

Di `resources/js/app.js`:

```js
import './wysiwyg.js';
```

Di `resources/css/app.css`:

```css
@import './wysiwyg.css';
```

### 5. Pastikan layout punya meta CSRF & Alpine

```html
<meta name="csrf-token" content="{{ csrf_token() }}">
```

Alpine.js harus sudah ter-load di layout. Jika belum:

```js
import Alpine from 'alpinejs';
window.Alpine = Alpine;
Alpine.start();
```

### 6. Gunakan component di form

Semudah ini:

```blade
<x-wysiwyg name="description" />
```

Props yang tersedia:

| Prop | Default | Keterangan |
|---|---|---|
| `name` | `content` | Name attribute pada hidden input |
| `value` | `null` | Initial HTML (untuk halaman edit) |
| `placeholder` | `Mulai menulis di sini…` | Placeholder teks editor |
| `uploadUrl` | `/content/upload-image` | Endpoint upload gambar |
| `height` | `480` | Min-height area editor (px) |
| `id` | auto-generate | Override id unik jika perlu |

### 7. Sanitasi & simpan di controller

Salin method `sanitize()` dan `sanitizeTaskList()` dari `ContentController` ke controller kamu, lalu panggil sebelum menyimpan:

```php
$clean = $this->sanitize($request->input('description'));
YourModel::create(['description' => $clean]);
```

Jangan lupa inject `HTMLPurifier` di constructor dan salin `config/purifier.php` ke project kamu.

### 8. Tampilkan konten

```blade
<div class="wysiwyg-prose">
    {!! $model->description !!}
</div>
```

> **Catatan:** `{!! !!}` aman digunakan di sini **hanya karena** konten sudah melewati `sanitize()` sebelum disimpan. Jangan tampilkan HTML dari sumber lain tanpa sanitasi terlebih dahulu.

---
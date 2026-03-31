{{--
    WYSIWYG Editor Component
    Props:
      $id          — id unik untuk instance ini
      $name        — name attribute hidden input
      $value       — initial HTML (nullable, untuk edit)
      $placeholder — placeholder teks
      $uploadUrl   — endpoint upload gambar
      $height      — min-height editor (px)
--}}

<div class="w-full relative bg-white border border-gray-200 rounded-xl shadow-sm mt-4" x-data="wysiwygEditor({
    id: '{{ $id }}',
    name: '{{ $name }}',
    value: {{ json_encode($value) }},
    placeholder: {{ json_encode($placeholder) }},
    uploadUrl: '{{ $uploadUrl }}',
    height: {{ $height }},
})" x-ref="shell">

    {{-- ── Toolbar (sticky di dalam scroll container card ini) ── --}}
    @include('components.wysiwyg-toolbar')

    {{-- ── Scroll container: hanya area ini yang scroll ── --}}
    <div class="overflow-y-auto" style="max-height: {{ $height }}px;">

        {{-- ── Editor Area ── --}}
        <div id="{{ $id }}"
            class="wysiwyg-editor wysiwyg-prose outline-none px-12 py-7 text-[15px] leading-[1.8] text-gray-900"
            style="min-height: {{ $height }}px; caret-color: var(--color-brand)" aria-label="Editor"
            aria-multiline="true">
        </div>

    </div>

    {{-- ── Status Bar ── --}}
    <div
        class="flex items-center justify-between px-4 py-1.5 border-t border-gray-200 bg-white rounded-b-xl font-mono text-[11px] text-gray-400">
        <div class="flex items-center gap-2.5">
            <span x-text="statWords">0 words</span>
            <span class="text-gray-300">·</span>
            <span x-text="statChars">0 chars</span>
        </div>
        <span class="bg-gray-100 text-gray-400 rounded px-1.5 py-px text-[10px] font-medium"
            x-text="statNode">paragraph</span>
    </div>

    {{-- ── Hidden Input ── --}}
    <input type="hidden" name="{{ $name }}" id="{{ $id }}-input" :value="editorHtml">

    {{-- ── Link Bubble ── --}}
    {{-- Overlay transparan: klik di luar bubble → tutup --}}
    <div x-show="linkBubble.visible" x-cloak class="absolute inset-0 z-[9998]" @click="closeLinkBubble()"></div>

    <div x-show="linkBubble.visible" x-cloak x-transition
        class="absolute left-1/2 -translate-x-1/2 top-12 z-[9999] bg-white border border-gray-200 rounded-lg shadow-xl p-3 flex flex-col gap-2 w-80">
        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Insert Link</p>
        <div
            class="flex items-center gap-1.5 bg-gray-50 border border-gray-200 rounded px-2.5 py-1.5 focus-within:border-[var(--color-brand)] transition-colors">
            <svg class="w-3 h-3 text-gray-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="4 7 4 4 20 4 20 7" />
                <line x1="9" y1="20" x2="15" y2="20" />
                <line x1="12" y1="4" x2="12" y2="20" />
            </svg>
            <input x-model="linkBubble.title" type="text" placeholder="Title (opsional)"
                class="flex-1 min-w-0 border-none outline-none bg-transparent text-sm text-gray-800 placeholder-gray-300"
                @keydown.enter="$refs.linkUrlInput.focus()" @keydown.escape="closeLinkBubble()">
        </div>
        <div
            class="flex items-center gap-1.5 bg-gray-50 border border-gray-200 rounded px-2.5 py-1.5 focus-within:border-[var(--color-brand)] transition-colors">
            <svg class="w-3 h-3 text-gray-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71" />
                <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" />
            </svg>
            <input x-model="linkBubble.url" x-ref="linkUrlInput" type="url" placeholder="https://example.com"
                class="flex-1 min-w-0 border-none outline-none bg-transparent text-sm text-gray-800 placeholder-gray-300"
                @keydown.enter="applyLink()" @keydown.escape="closeLinkBubble()">
        </div>
        <div class="flex justify-end">
            <button type="button" @click="applyLink()"
                class="flex items-center gap-1.5 px-3 py-1.5 bg-[var(--color-brand)] hover:bg-[var(--color-brand-dark)] text-white rounded text-xs font-semibold transition-colors">
                <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                    stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12" />
                </svg>
                Apply
            </button>
        </div>
    </div>

</div>

@once
    @push('scripts')
        @vite('resources/js/wysiwyg.js')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('wysiwygEditor', (config) => {
                    // instance store — TIDAK disimpan di Alpine reactive state
                    // agar Proxy Alpine tidak wrap object Tiptap (→ performa & bug)
                    let _instance = null;

                    // Helper: ambil editor Tiptap dari instance
                    const _ed = () => _instance?.editor ?? null;

                    return {
                        id: config.id,
                        name: config.name,
                        editorHtml: config.value ?? '',
                        placeholder: config.placeholder,
                        uploadUrl: config.uploadUrl,
                        height: config.height,

                        // ── Toolbar state ──
                        structLabel: 'Paragraph',
                        inTable: false,
                        statWords: '0 words',
                        statChars: '0 chars',
                        statNode: 'paragraph',

                        // ── Colors ──
                        hlColors: [
                            '#fef08a', '#fde68a', '#fed7aa', '#fecaca', '#d9f99d', '#bbf7d0', '#bae6fd',
                            '#c7d2fe', '#f5d0fe', '#fbcfe8', '#e0f2fe', '#ccfbf1', '#fff7ed', '#f0fdf4',
                            '#eff6ff', '#fdf4ff',
                        ],
                        txtColors: [
                            '#111827', '#374151', '#6b7280', '#9ca3af', '#ef4444', '#f97316', '#eab308',
                            '#22c55e', '#3b82f6', '#8b5cf6', '#ec4899', '#14b8a6', '#92400e', '#1e3a5f',
                            '#4d7c0f', '#ffffff',
                        ],

                        // ── Link bubble ──
                        linkBubble: {
                            visible: false,
                            title: '',
                            url: ''
                        },
                        _savedSelection: null,

                        // ── Lifecycle ──
                        init() {
                            this.$nextTick(() => {
                                _instance = window.initWysiwyg({
                                    editorEl: document.getElementById(this.id),
                                    initialContent: this.editorHtml,
                                    placeholder: this.placeholder,
                                    uploadUrl: this.uploadUrl,
                                    onUpdate: (html, txt) => {
                                        this.editorHtml = html;
                                        window.dispatchEvent(new CustomEvent(
                                            'wysiwyg-update', {
                                                detail: {
                                                    html
                                                }
                                            }));
                                        const trimmed = txt.trim();
                                        this.statWords = (trimmed ? trimmed.split(/\s+/)
                                            .length : 0) + ' words';
                                        this.statChars = txt.length + ' chars';
                                    },
                                    onSelectionUpdate: () => this._syncToolbar(),
                                });
                                this._syncToolbar();
                            });
                        },

                        // destroy dipanggil Alpine saat component di-remove dari DOM
                        // (Livewire navigate, Turbo, SPA routing)
                        destroy() {
                            _instance?.destroy();
                            _instance = null;
                        },

                        // ── Editor commands ──
                        cmd(name, ...args) {
                            const ed = _ed();
                            if (!ed) return;
                            let chain = ed.chain();
                            chain = args.length ? chain[name](...args) : chain[name]();
                            chain.run();
                            ed.view.focus();
                        },

                        isActive(name, attrs) {
                            return _ed()?.isActive(name, attrs) ?? false;
                        },

                        setAlign(align) {
                            const ed = _ed();
                            if (!ed) return;
                            const figurePos = _instance.getFigurePos();
                            if (figurePos !== null) {
                                const figNode = ed.state.doc.nodeAt(figurePos);
                                if (figNode) {
                                    const {
                                        tr
                                    } = ed.state;
                                    tr.setNodeMarkup(figurePos, undefined, {
                                        ...figNode.attrs,
                                        align
                                    });
                                    ed.view.dispatch(tr);
                                }
                            } else {
                                ed.chain().setTextAlign(align).run();
                            }
                            ed.view.focus();
                        },

                        alignActive(align) {
                            if (!_instance) return false;
                            const figureAlign = _instance.getFigureAlign();
                            if (figureAlign !== null) return figureAlign === align;
                            return _ed()?.isActive({
                                textAlign: align
                            }) ?? false;
                        },

                        openLinkBubble() {
                            const ed = _ed();
                            if (!ed) return;
                            const {
                                from,
                                to,
                                empty
                            } = ed.state.selection;
                            this._savedSelection = {
                                from,
                                to,
                                empty
                            };
                            let title = '';
                            if (!empty) {
                                const slice = ed.state.doc.slice(from, to);
                                title = slice.content.textBetween(0, slice.content.size, ' ');
                            }
                            this.linkBubble.title = title;
                            this.linkBubble.url = '';
                            this.linkBubble.visible = true;
                            this.$nextTick(() => this.$refs.linkUrlInput.focus());
                        },

                        closeLinkBubble() {
                            this.linkBubble.visible = false;
                            this.linkBubble.title = '';
                            this.linkBubble.url = '';
                            this._savedSelection = null;
                        },

                        applyLink() {
                            const ed = _ed();
                            if (!ed) return;

                            const url = _instance.sanitizeUrl(this.linkBubble.url.trim());
                            if (!url) return this.closeLinkBubble();

                            const sel = this._savedSelection;
                            const title = _instance.escapeHtml(this.linkBubble.title
                                .trim()); // cegah XSS via title input

                            if (sel && !sel.empty && !title) {
                                // Ada selection, tidak ada custom title → wrap teks existing dengan link
                                ed.chain()
                                    .setTextSelection({
                                        from: sel.from,
                                        to: sel.to
                                    })
                                    .setLink({
                                        href: url,
                                        target: '_blank',
                                        rel: 'noopener noreferrer'
                                    })
                                    .run();
                            } else {
                                // Tidak ada selection, atau ada custom title → insert/ganti dengan teks link baru
                                const text = title || url;
                                const chain = (sel && !sel.empty) ?
                                    ed.chain().setTextSelection({
                                        from: sel.from,
                                        to: sel.to
                                    }) :
                                    ed.chain();
                                chain.insertContent(
                                    `<a href="${url}" target="_blank" rel="noopener noreferrer">${text}</a>`
                                ).run();
                            }

                            this.closeLinkBubble();
                        },

                        insertDropzone() {
                            const ed = _ed();
                            if (!ed) return;
                            ed.chain().insertContent({
                                type: 'imageDropzone'
                            }).run();
                            ed.view.focus();
                        },

                        // ── Sync toolbar state dari editor ──
                        _syncToolbar() {
                            const ed = _ed();
                            if (!ed) return;

                            // Baca state sekali, tidak 8x isActive()
                            const node = ed.state.selection.$anchor.parent;
                            const name = node.type.name;

                            this.structLabel = name === 'heading' ? `Heading ${node.attrs.level}` :
                                name === 'blockquote' ? 'Quote' :
                                'Paragraph';

                            this.inTable = ed.isActive(
                                'table'); // table masih perlu isActive karena cek ancestor
                            this.statNode = name === 'heading' ? 'h' + node.attrs.level : name;
                        },
                    };
                });
            });
        </script>
    @endpush
@endonce
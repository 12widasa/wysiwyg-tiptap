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

<div class="w-full bg-white border border-gray-200 rounded-xl shadow-sm" x-data="wysiwygEditor({
    id: '{{ $id }}',
    name: '{{ $name }}',
    value: {{ json_encode($value) }},
    placeholder: {{ json_encode($placeholder) }},
    uploadUrl: '{{ $uploadUrl }}',
    height: {{ $height }},
})" x-ref="shell">

    {{-- ── Toolbar ── --}}
    @include('components.wysiwyg-toolbar')

    {{-- ── Table Toolbar ── --}}
    <div x-show="inTable" x-cloak x-transition
        class="flex flex-wrap justify-center items-center gap-1 px-3 py-2 border-b border-gray-200 bg-gray-50">
        <span class="text-xs text-gray-400 font-mono mr-2">Table:</span>
        <button type="button" @click="cmd('addColumnBefore')" title="Tambah Kolom Kiri"
            class="flex items-center justify-center w-[30px] h-[30px] rounded text-gray-600 hover:bg-gray-100 transition-colors">
            <svg class="w-[15px] h-[15px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="18" height="18" rx="2" />
                <path d="M9 3v18" />
                <line x1="6" y1="6" x2="6" y2="15" />
                <line x1="3" y1="9" x2="9" y2="9" />
            </svg>
        </button>
        <button type="button" @click="cmd('addColumnAfter')" title="Tambah Kolom Kanan"
            class="flex items-center justify-center w-[30px] h-[30px] rounded text-gray-600 hover:bg-gray-100 transition-colors">
            <svg class="w-[15px] h-[15px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="18" height="18" rx="2" />
                <path d="M15 3v18" />
                <line x1="18" y1="9" x2="18" y2="15" />
                <line x1="15" y1="12" x2="21" y2="12" />
            </svg>
        </button>
        <button type="button" @click="cmd('deleteColumn')" title="Hapus Kolom"
            class="flex items-center justify-center w-[30px] h-[30px] rounded text-gray-600 hover:bg-gray-100 transition-colors">
            <svg class="w-[15px] h-[15px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="18" height="18" rx="2" />
                <path d="M9 3v18" />
                <line x1="4" y1="9" x2="8" y2="13" />
                <line x1="8" y1="9" x2="4" y2="13" />
            </svg>
        </button>
        <div class="w-px h-[18px] bg-gray-300 mx-1 shrink-0"></div>
        <button type="button" @click="cmd('addRowBefore')" title="Tambah Baris Atas"
            class="flex items-center justify-center w-[30px] h-[30px] rounded text-gray-600 hover:bg-gray-100 transition-colors">
            <svg class="w-[15px] h-[15px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="18" height="18" rx="2" />
                <path d="M3 9h18" />
                <line x1="12" y1="3" x2="12" y2="9" />
                <line x1="9" y1="6" x2="15" y2="6" />
            </svg>
        </button>
        <button type="button" @click="cmd('addRowAfter')" title="Tambah Baris Bawah"
            class="flex items-center justify-center w-[30px] h-[30px] rounded text-gray-600 hover:bg-gray-100 transition-colors">
            <svg class="w-[15px] h-[15px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="18" height="18" rx="2" />
                <path d="M3 15h18" />
                <line x1="12" y1="15" x2="12" y2="21" />
                <line x1="9" y1="18" x2="15" y2="18" />
            </svg>
        </button>
        <button type="button" @click="cmd('deleteRow')" title="Hapus Baris"
            class="flex items-center justify-center w-[30px] h-[30px] rounded text-gray-600 hover:bg-gray-100 transition-colors">
            <svg class="w-[15px] h-[15px]" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="18" height="18" rx="2" />
                <path d="M3 9h18" />
                <line x1="9" y1="6" x2="13" y2="10" />
                <line x1="13" y1="6" x2="9" y2="10" />
            </svg>
        </button>
        <div class="w-px h-[18px] bg-gray-300 mx-1 shrink-0"></div>
        <button type="button" @click="cmd('deleteTable')" title="Hapus Table"
            class="flex items-center justify-center w-[30px] h-[30px] rounded text-red-400 hover:bg-red-50 transition-colors">
            <svg class="w-[15px] h-[15px]" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="3 6 5 6 21 6" />
                <path d="M19 6l-1 14H6L5 6" />
                <path d="M10 11v6" />
                <path d="M14 11v6" />
                <path d="M9 6V4h6v2" />
            </svg>
        </button>
    </div>

    {{-- ── Editor Area ── --}}
    <div id="{{ $id }}"
        class="wysiwyg-editor wysiwyg-prose outline-none px-12 py-7 text-[15px] leading-[1.8] text-gray-900"
        style="min-height: {{ $height }}px; caret-color: #6b4fbb" aria-label="Editor" aria-multiline="true">
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
    <div x-show="linkBubble.visible" x-cloak x-transition
        class="fixed z-[9999] bg-white border border-gray-200 rounded-lg shadow-xl p-3 flex flex-col gap-2 w-80"
        :style="`top: ${linkBubble.top}px; left: ${linkBubble.left}px; transform: translateX(-50%)`">
        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Insert Link</p>
        <div
            class="flex items-center gap-1.5 bg-gray-50 border border-gray-200 rounded px-2.5 py-1.5 focus-within:border-violet-500 transition-colors">
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
            class="flex items-center gap-1.5 bg-gray-50 border border-gray-200 rounded px-2.5 py-1.5 focus-within:border-violet-500 transition-colors">
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
                class="flex items-center gap-1.5 px-3 py-1.5 bg-violet-600 hover:bg-violet-700 text-white rounded text-xs font-semibold transition-colors">
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
                        fontSize: '15px',
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
                            top: 0,
                            left: 0,
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
                                    onSelectionUpdate: () => {
                                        this._syncToolbar();
                                    },
                                });
                                this._syncToolbar();
                            });
                        },

                        // FIX: destroy dipanggil Alpine saat component di-remove dari DOM
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

                        setFontSize(size) {
                            const ed = _ed();
                            if (!ed) return;
                            ed.chain().setMark('textStyle', {
                                fontSize: size + 'px'
                            }).run();
                            ed.view.focus();
                            this.fontSize = size + 'px';
                        },

                        setAlign(align) {
                            const ed = _ed();
                            if (!ed) return;
                            // FIX: gunakan getFigurePos dari instance, bukan window._getFigurePos
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
                            // FIX: gunakan getFigureAlign dari instance, bukan window._getFigureAlign
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
                            const shell = this.$refs.shell;
                            const rect = shell.getBoundingClientRect();
                            const toolbarH = shell.querySelector('[role="toolbar"]')?.getBoundingClientRect()
                                .height ?? 48;
                            this.linkBubble.top = rect.top + toolbarH + 8;
                            this.linkBubble.left = rect.left + rect.width / 2;
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

                            const safeUrl = window._sanitizeUrl(this.linkBubble.url.trim());
                            if (!safeUrl) {
                                this.closeLinkBubble();
                                return;
                            }

                            const sel = this._savedSelection;
                            // FIX: escape title agar tidak bisa inject HTML via link bubble
                            const safeTitle = window._escapeHtml(this.linkBubble.title.trim());
                            const linkAttrs = {
                                href: safeUrl,
                                target: '_blank',
                                rel: 'noopener noreferrer'
                            };

                            if (sel && !sel.empty) {
                                if (safeTitle) {
                                    // Ada teks selection + title custom → ganti teks selection
                                    ed.chain()
                                        .setTextSelection({
                                            from: sel.from,
                                            to: sel.to
                                        })
                                        .insertContent(
                                            `<a href="${safeUrl}" target="_blank" rel="noopener noreferrer">${safeTitle}</a>`
                                        )
                                        .run();
                                } else {
                                    // Wrap selection dengan link, teks tidak berubah
                                    ed.chain()
                                        .setTextSelection({
                                            from: sel.from,
                                            to: sel.to
                                        })
                                        .setLink(linkAttrs)
                                        .run();
                                }
                            } else {
                                // Tidak ada selection → insert link baru
                                ed.chain()
                                    .insertContent(
                                        `<a href="${safeUrl}" target="_blank" rel="noopener noreferrer">${safeTitle || safeUrl}</a>`
                                    )
                                    .run();
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
                        _syncStructLabel(ed) {
                            let label = 'Paragraph';
                            for (let i = 1; i <= 6; i++) {
                                if (ed.isActive('heading', {
                                        level: i
                                    })) {
                                    label = `Heading ${i}`;
                                    break;
                                }
                            }
                            if (ed.isActive('blockquote')) label = 'Quote';
                            this.structLabel = label;
                        },

                        _syncNodeName(ed) {
                            const node = ed.state.selection.$anchor.parent;
                            let nodeName = node.type.name;
                            if (nodeName === 'heading') nodeName = 'h' + node.attrs.level;
                            this.statNode = nodeName;
                        },

                        _syncToolbar() {
                            const ed = _ed();
                            if (!ed) return;
                            this._syncStructLabel(ed);
                            this.fontSize = ed.getAttributes('textStyle').fontSize || '15px';
                            this.inTable = ed.isActive('table');
                            this._syncNodeName(ed);
                        },
                    };
                });
            });
        </script>
    @endpush
@endonce

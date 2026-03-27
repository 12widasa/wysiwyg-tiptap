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

@once
    @push('styles')
        @vite('resources/css/wysiwyg.css')
    @endpush
@endonce

<div class="w-full bg-white border border-gray-200 rounded-xl shadow-sm" x-data="wysiwygEditor({
    id: '{{ $id }}',
    name: '{{ $name }}',
    value: {{ json_encode($value) }},
    placeholder: {{ json_encode($placeholder) }},
    uploadUrl: '{{ $uploadUrl }}',
    height: {{ $height }},
})" x-ref="shell">
    {{-- ── Toolbar ── --}}
    <div role="toolbar" aria-label="Text formatting"
        class="flex flex-wrap items-center gap-px px-2 py-1.5 border-b border-gray-200 bg-white rounded-t-xl sticky top-0 z-20">
        {{-- Text Structure --}}
        <div class="relative" x-data="{ open: false }" @click.outside="open = false">
            <button type="button" @click="open = !open" :aria-expanded="open"
                class="flex items-center gap-1 h-[30px] px-2 rounded text-xs font-medium text-gray-600 hover:bg-gray-100 transition-colors whitespace-nowrap">
                <span x-text="structLabel">Paragraph</span>
                <svg class="w-2.5 h-2.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 12 15 18 9" />
                </svg>
            </button>
            <div x-show="open" x-cloak x-transition
                class="absolute top-full left-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-lg z-50 min-w-[156px] p-1">
                <button type="button" @click="cmd('setParagraph'); open=false"
                    :class="isActive('paragraph') && 'bg-violet-50 text-violet-700'"
                    class="dd-item w-full flex items-center gap-2 px-2.5 py-1.5 text-sm text-gray-700 hover:bg-gray-100 rounded transition-colors">Paragraph</button>
                <div class="h-px bg-gray-100 my-1"></div>
                <button type="button" @click="cmd('toggleHeading',{level:1}); open=false"
                    :class="isActive('heading', { level: 1 }) && 'bg-violet-50 text-violet-700'"
                    class="w-full flex items-center gap-2 px-2.5 py-1.5 hover:bg-gray-100 rounded transition-colors text-left font-bold text-[17px]">Heading
                    1</button>
                <button type="button" @click="cmd('toggleHeading',{level:2}); open=false"
                    :class="isActive('heading', { level: 2 }) && 'bg-violet-50 text-violet-700'"
                    class="w-full flex items-center gap-2 px-2.5 py-1.5 hover:bg-gray-100 rounded transition-colors text-left font-bold text-[15px]">Heading
                    2</button>
                <button type="button" @click="cmd('toggleHeading',{level:3}); open=false"
                    :class="isActive('heading', { level: 3 }) && 'bg-violet-50 text-violet-700'"
                    class="w-full flex items-center gap-2 px-2.5 py-1.5 hover:bg-gray-100 rounded transition-colors text-left font-semibold text-[13px]">Heading
                    3</button>
                <button type="button" @click="cmd('toggleHeading',{level:4}); open=false"
                    :class="isActive('heading', { level: 4 }) && 'bg-violet-50 text-violet-700'"
                    class="w-full flex items-center gap-2 px-2.5 py-1.5 hover:bg-gray-100 rounded transition-colors text-left font-semibold text-[12px]">Heading
                    4</button>
                <button type="button" @click="cmd('toggleHeading',{level:5}); open=false"
                    :class="isActive('heading', { level: 5 }) && 'bg-violet-50 text-violet-700'"
                    class="w-full flex items-center gap-2 px-2.5 py-1.5 hover:bg-gray-100 rounded transition-colors text-left font-semibold text-[11px]">Heading
                    5</button>
                <button type="button" @click="cmd('toggleHeading',{level:6}); open=false"
                    :class="isActive('heading', { level: 6 }) && 'bg-violet-50 text-violet-700'"
                    class="w-full flex items-center gap-2 px-2.5 py-1.5 hover:bg-gray-100 rounded transition-colors text-left font-semibold text-[11px] text-gray-400">Heading
                    6</button>
                <div class="h-px bg-gray-100 my-1"></div>
                <button type="button" @click="cmd('toggleBlockquote'); open=false"
                    :class="isActive('blockquote') && 'bg-violet-50 text-violet-700'"
                    class="w-full flex items-center gap-2 px-2.5 py-1.5 text-sm text-gray-700 hover:bg-gray-100 rounded transition-colors text-left">
                    <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path
                            d="M3 21c3 0 7-1 7-8V5c0-1.25-.756-2.017-2-2H4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2 1 0 1 0 1 1v1c0 1-1 2-2 2s-1 .008-1 1.031V20c0 1 0 1 1 1z" />
                        <path
                            d="M15 21c3 0 7-1 7-8V5c0-1.25-.757-2.017-2-2h-4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2h.75c0 2.25.25 4-2.75 4v3c0 1 0 1 1 1z" />
                    </svg>
                    Quote
                </button>
            </div>
        </div>

        {{-- Font Size --}}
        <div class="relative" x-data="{ open: false }" @click.outside="open = false">
            <button type="button" @click="open = !open" :aria-expanded="open"
                class="flex items-center justify-between gap-1 h-[30px] px-2 min-w-[52px] border border-gray-200 rounded text-xs font-mono text-gray-600 hover:bg-gray-100 hover:border-gray-300 transition-colors">
                <span x-text="fontSize">15px</span>
                <svg class="w-2.5 h-2.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 12 15 18 9" />
                </svg>
            </button>
            <div x-show="open" x-cloak x-transition
                class="absolute top-full left-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-lg z-50 min-w-[80px] p-1">
                <template x-for="size in [12,13,14,15,16,18,20,24,28,32,36]" :key="size">
                    <button type="button" @click="setFontSize(size); open=false"
                        :class="fontSize === size + 'px' && 'bg-violet-50 text-violet-700'"
                        class="w-full flex items-center px-2.5 py-1.5 font-mono text-xs text-gray-700 hover:bg-gray-100 rounded transition-colors"
                        x-text="size+'px'"></button>
                </template>
            </div>
        </div>

        <div class="w-px h-[18px] bg-gray-300 mx-1 shrink-0"></div>

        {{-- Bold, Italic, Underline, Strike --}}
        <button type="button" @click="cmd('toggleBold')" :class="isActive('bold') && 'bg-violet-50 text-violet-700'"
            title="Bold" aria-pressed="false"
            class="flex items-center justify-center w-[30px] h-[30px] rounded text-gray-600 hover:bg-gray-100 transition-colors shrink-0">
            <svg class="w-[15px] h-[15px] pointer-events-none" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 12a4 4 0 0 0 0-8H6v8" />
                <path d="M15 20a4 4 0 0 0 0-8H6v8Z" />
            </svg>
        </button>
        <button type="button" @click="cmd('toggleItalic')"
            :class="isActive('italic') && 'bg-violet-50 text-violet-700'" title="Italic" aria-pressed="false"
            class="flex items-center justify-center w-[30px] h-[30px] rounded text-gray-600 hover:bg-gray-100 transition-colors shrink-0">
            <svg class="w-[15px] h-[15px] pointer-events-none" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="19" y1="4" x2="10" y2="4" />
                <line x1="14" y1="20" x2="5" y2="20" />
                <line x1="15" y1="4" x2="9" y2="20" />
            </svg>
        </button>
        <button type="button" @click="cmd('toggleUnderline')"
            :class="isActive('underline') && 'bg-violet-50 text-violet-700'" title="Underline" aria-pressed="false"
            class="flex items-center justify-center w-[30px] h-[30px] rounded text-gray-600 hover:bg-gray-100 transition-colors shrink-0">
            <svg class="w-[15px] h-[15px] pointer-events-none" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M6 4v6a6 6 0 0 0 12 0V4" />
                <line x1="4" y1="20" x2="20" y2="20" />
            </svg>
        </button>
        <button type="button" @click="cmd('toggleStrike')"
            :class="isActive('strike') && 'bg-violet-50 text-violet-700'" title="Strikethrough" aria-pressed="false"
            class="flex items-center justify-center w-[30px] h-[30px] rounded text-gray-600 hover:bg-gray-100 transition-colors shrink-0">
            <svg class="w-[15px] h-[15px] pointer-events-none" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M16 4H9a3 3 0 0 0-2.83 4" />
                <path d="M14 12a4 4 0 0 1 0 8H6" />
                <line x1="4" y1="12" x2="20" y2="12" />
            </svg>
        </button>

        <div class="w-px h-[18px] bg-gray-300 mx-1 shrink-0"></div>

        {{-- Highlight --}}
        <div class="relative" x-data="{ open: false }" @click.outside="open = false">
            <button type="button" @click="open = !open" :aria-expanded="open"
                class="flex items-center gap-1 h-[30px] px-2 rounded text-gray-600 hover:bg-gray-100 transition-colors">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="m9 11-6 6v3h9l3-3" />
                    <path d="m22 12-4.6 4.6a2 2 0 0 1-2.8 0l-5.2-5.2a2 2 0 0 1 0-2.8L14 4" />
                </svg>
                <svg class="w-2.5 h-2.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 12 15 18 9" />
                </svg>
            </button>
            <div x-show="open" x-cloak x-transition
                class="absolute top-full left-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-lg z-50 p-1 min-w-[196px]">
                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide px-2.5 pt-1.5 pb-1">Highlight
                    Color</p>
                <div class="grid grid-cols-8 gap-1 px-2.5 pb-2">
                    <template x-for="hex in hlColors" :key="hex">
                        <button type="button" @click="cmd('toggleHighlight',{color:hex}); open=false"
                            class="w-5 h-5 rounded cursor-pointer border-[1.5px] border-transparent hover:scale-110 transition-transform"
                            :style="`background-color:${hex}`"></button>
                    </template>
                </div>
                <div class="h-px bg-gray-100 my-1"></div>
                <button type="button" @click="cmd('unsetHighlight'); open=false"
                    class="w-full flex items-center gap-2 px-2.5 py-1.5 text-sm text-gray-700 hover:bg-gray-100 rounded transition-colors">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18" />
                        <line x1="6" y1="6" x2="18" y2="18" />
                    </svg>
                    Remove Highlight
                </button>
            </div>
        </div>

        {{-- Text Color --}}
        <div class="relative" x-data="{ open: false }" @click.outside="open = false">
            <button type="button" @click="open = !open" :aria-expanded="open"
                class="flex items-center gap-1 h-[30px] px-2 rounded text-gray-600 hover:bg-gray-100 transition-colors">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="4 7 4 4 20 4 20 7" />
                    <line x1="9" y1="20" x2="15" y2="20" />
                    <line x1="12" y1="4" x2="12" y2="20" />
                </svg>
                <svg class="w-2.5 h-2.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 12 15 18 9" />
                </svg>
            </button>
            <div x-show="open" x-cloak x-transition
                class="absolute top-full left-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-lg z-50 p-1 min-w-[196px]">
                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide px-2.5 pt-1.5 pb-1">Text
                    Color</p>
                <div class="grid grid-cols-8 gap-1 px-2.5 pb-2">
                    <template x-for="hex in txtColors" :key="hex">
                        <button type="button" @click="cmd('setColor',hex); open=false"
                            class="w-5 h-5 rounded cursor-pointer border-[1.5px] border-transparent hover:scale-110 transition-transform"
                            :style="`background-color:${hex}; ${hex==='#ffffff' ? 'border-color:#ccc' : ''}`"></button>
                    </template>
                </div>
                <div class="h-px bg-gray-100 my-1"></div>
                <button type="button" @click="cmd('unsetColor'); open=false"
                    class="w-full flex items-center gap-2 px-2.5 py-1.5 text-sm text-gray-700 hover:bg-gray-100 rounded transition-colors">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18" />
                        <line x1="6" y1="6" x2="18" y2="18" />
                    </svg>
                    Default Color
                </button>
            </div>
        </div>

        {{-- Clear Formatting --}}
        <button type="button" @click="cmd('clearNodes'); cmd('unsetAllMarks')" title="Clear Formatting"
            class="flex items-center justify-center w-[30px] h-[30px] rounded text-gray-600 hover:bg-gray-100 transition-colors shrink-0">
            <svg class="w-[15px] h-[15px] pointer-events-none" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M4 7V4h16v3" />
                <path d="M5 20h6" />
                <path d="M13 4 8 20" />
                <line x1="15" y1="15" x2="21" y2="21" />
                <line x1="21" y1="15" x2="15" y2="21" />
            </svg>
        </button>

        <div class="w-px h-[18px] bg-gray-300 mx-1 shrink-0"></div>

        {{-- Lists --}}
        <button type="button" @click="cmd('toggleBulletList')"
            :class="isActive('bulletList') && 'bg-violet-50 text-violet-700'" title="Bullet List"
            aria-pressed="false"
            class="flex items-center justify-center w-[30px] h-[30px] rounded text-gray-600 hover:bg-gray-100 transition-colors shrink-0">
            <svg class="w-[15px] h-[15px] pointer-events-none" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="8" y1="6" x2="21" y2="6" />
                <line x1="8" y1="12" x2="21" y2="12" />
                <line x1="8" y1="18" x2="21" y2="18" />
                <line x1="3" y1="6" x2="3.01" y2="6" />
                <line x1="3" y1="12" x2="3.01" y2="12" />
                <line x1="3" y1="18" x2="3.01" y2="18" />
            </svg>
        </button>
        <button type="button" @click="cmd('toggleOrderedList')"
            :class="isActive('orderedList') && 'bg-violet-50 text-violet-700'" title="Ordered List"
            aria-pressed="false"
            class="flex items-center justify-center w-[30px] h-[30px] rounded text-gray-600 hover:bg-gray-100 transition-colors shrink-0">
            <svg class="w-[15px] h-[15px] pointer-events-none" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="10" y1="6" x2="21" y2="6" />
                <line x1="10" y1="12" x2="21" y2="12" />
                <line x1="10" y1="18" x2="21" y2="18" />
                <path d="M4 6h1v4" />
                <path d="M4 10h2" />
                <path d="M6 18H4c0-1 2-2 2-3s-1-1.5-2-1" />
            </svg>
        </button>
        <button type="button" @click="cmd('toggleTaskList')"
            :class="isActive('taskList') && 'bg-violet-50 text-violet-700'" title="Task List" aria-pressed="false"
            class="flex items-center justify-center w-[30px] h-[30px] rounded text-gray-600 hover:bg-gray-100 transition-colors shrink-0">
            <svg class="w-[15px] h-[15px] pointer-events-none" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m3 17 2 2 4-4" />
                <path d="m3 7 2 2 4-4" />
                <line x1="13" y1="8" x2="21" y2="8" />
                <line x1="13" y1="16" x2="21" y2="16" />
            </svg>
        </button>

        <div class="w-px h-[18px] bg-gray-300 mx-1 shrink-0"></div>

        {{-- Align --}}
        <button type="button" @click="setAlign('left')"
            :class="alignActive('left') && 'bg-violet-50 text-violet-700'" title="Align Left" aria-pressed="false"
            class="flex items-center justify-center w-[30px] h-[30px] rounded text-gray-600 hover:bg-gray-100 transition-colors shrink-0">
            <svg class="w-[15px] h-[15px] pointer-events-none" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="21" y1="6" x2="3" y2="6" />
                <line x1="15" y1="12" x2="3" y2="12" />
                <line x1="17" y1="18" x2="3" y2="18" />
            </svg>
        </button>
        <button type="button" @click="setAlign('center')"
            :class="alignActive('center') && 'bg-violet-50 text-violet-700'" title="Align Center"
            aria-pressed="false"
            class="flex items-center justify-center w-[30px] h-[30px] rounded text-gray-600 hover:bg-gray-100 transition-colors shrink-0">
            <svg class="w-[15px] h-[15px] pointer-events-none" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="21" y1="6" x2="3" y2="6" />
                <line x1="17" y1="12" x2="7" y2="12" />
                <line x1="19" y1="18" x2="5" y2="18" />
            </svg>
        </button>
        <button type="button" @click="setAlign('right')"
            :class="alignActive('right') && 'bg-violet-50 text-violet-700'" title="Align Right" aria-pressed="false"
            class="flex items-center justify-center w-[30px] h-[30px] rounded text-gray-600 hover:bg-gray-100 transition-colors shrink-0">
            <svg class="w-[15px] h-[15px] pointer-events-none" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="21" y1="6" x2="3" y2="6" />
                <line x1="21" y1="12" x2="9" y2="12" />
                <line x1="21" y1="18" x2="7" y2="18" />
            </svg>
        </button>
        <button type="button" @click="setAlign('justify')"
            :class="alignActive('justify') && 'bg-violet-50 text-violet-700'" title="Justify" aria-pressed="false"
            class="flex items-center justify-center w-[30px] h-[30px] rounded text-gray-600 hover:bg-gray-100 transition-colors shrink-0">
            <svg class="w-[15px] h-[15px] pointer-events-none" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="21" y1="6" x2="3" y2="6" />
                <line x1="21" y1="12" x2="3" y2="12" />
                <line x1="21" y1="18" x2="3" y2="18" />
            </svg>
        </button>

        <div class="w-px h-[18px] bg-gray-300 mx-1 shrink-0"></div>

        {{-- Indent / Outdent --}}
        <button type="button" @click="cmd('indent')" title="Indent"
            class="flex items-center justify-center w-[30px] h-[30px] rounded text-gray-600 hover:bg-gray-100 transition-colors shrink-0">
            <svg class="w-[15px] h-[15px] pointer-events-none" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="3 8 7 12 3 16" />
                <line x1="21" y1="12" x2="11" y2="12" />
                <line x1="21" y1="6" x2="3" y2="6" />
                <line x1="21" y1="18" x2="3" y2="18" />
            </svg>
        </button>
        <button type="button" @click="cmd('outdent')" title="Outdent"
            class="flex items-center justify-center w-[30px] h-[30px] rounded text-gray-600 hover:bg-gray-100 transition-colors shrink-0">
            <svg class="w-[15px] h-[15px] pointer-events-none" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="7 8 3 12 7 16" />
                <line x1="21" y1="12" x2="11" y2="12" />
                <line x1="21" y1="6" x2="3" y2="6" />
                <line x1="21" y1="18" x2="3" y2="18" />
            </svg>
        </button>

        {{-- Insert --}}
        <div class="relative ml-auto" x-data="{ open: false }" @click.outside="open = false">
            <button type="button" @click="open = !open" :aria-expanded="open"
                class="flex items-center gap-1.5 h-[30px] px-3 bg-violet-600 hover:bg-violet-700 text-white rounded text-xs font-semibold transition-colors">
                <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                    stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19" />
                    <line x1="5" y1="12" x2="19" y2="12" />
                </svg>
                Insert
            </button>
            <div x-show="open" x-cloak x-transition
                class="absolute top-full right-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-lg z-50 min-w-[156px] p-1">
                <button type="button" @click="openLinkBubble(); open=false"
                    class="w-full flex items-center gap-2 px-2.5 py-1.5 text-sm text-gray-700 hover:bg-gray-100 rounded transition-colors">
                    <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71" />
                        <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" />
                    </svg>
                    Link
                </button>
                <button type="button" @click="insertDropzone(); open=false"
                    class="w-full flex items-center gap-2 px-2.5 py-1.5 text-sm text-gray-700 hover:bg-gray-100 rounded transition-colors">
                    <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="18" height="18" rx="2" />
                        <circle cx="9" cy="9" r="2" />
                        <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21" />
                    </svg>
                    Image
                </button>
                <button type="button" @click="cmd('insertTable',{rows:3,cols:3,withHeaderRow:true}); open=false"
                    class="w-full flex items-center gap-2 px-2.5 py-1.5 text-sm text-gray-700 hover:bg-gray-100 rounded transition-colors">
                    <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                        <path d="M3 9h18" />
                        <path d="M9 21V9" />
                    </svg>
                    Table
                </button>
                <button type="button" @click="cmd('setHorizontalRule'); open=false"
                    class="w-full flex items-center gap-2 px-2.5 py-1.5 text-sm text-gray-700 hover:bg-gray-100 rounded transition-colors">
                    <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="3" y1="12" x2="21" y2="12" />
                        <polyline points="8 8 12 4 16 8" />
                        <polyline points="16 16 12 20 8 16" />
                    </svg>
                    Divider
                </button>
            </div>
        </div>
    </div>

    {{-- ── Table Toolbar ── --}}
    <div x-show="inTable" x-cloak x-transition
        class="flex flex-wrap justify-center items-center gap-1 px-3 py-2 border-b border-gray-200 bg-gray-50">
        <span class="text-xs text-gray-400 font-mono mr-2">Table:</span>
        <button type="button" @click="cmd('addColumnBefore')" title="Tambah Kolom Kiri"
            class="flex items-center justify-center w-[30px] h-[30px] rounded text-gray-600 hover:bg-gray-100 transition-colors">
            <svg class="w-[15px] h-[15px]" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="18" height="18" rx="2" />
                <path d="M9 3v18" />
                <line x1="6" y1="6" x2="6" y2="15" />
                <line x1="3" y1="9" x2="9" y2="9" />
            </svg>
        </button>
        <button type="button" @click="cmd('addColumnAfter')" title="Tambah Kolom Kanan"
            class="flex items-center justify-center w-[30px] h-[30px] rounded text-gray-600 hover:bg-gray-100 transition-colors">
            <svg class="w-[15px] h-[15px]" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="18" height="18" rx="2" />
                <path d="M15 3v18" />
                <line x1="18" y1="9" x2="18" y2="15" />
                <line x1="15" y1="12" x2="21" y2="12" />
            </svg>
        </button>
        <button type="button" @click="cmd('deleteColumn')" title="Hapus Kolom"
            class="flex items-center justify-center w-[30px] h-[30px] rounded text-gray-600 hover:bg-gray-100 transition-colors">
            <svg class="w-[15px] h-[15px]" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="18" height="18" rx="2" />
                <path d="M9 3v18" />
                <line x1="4" y1="9" x2="8" y2="13" />
                <line x1="8" y1="9" x2="4" y2="13" />
            </svg>
        </button>
        <div class="w-px h-[18px] bg-gray-300 mx-1 shrink-0"></div>
        <button type="button" @click="cmd('addRowBefore')" title="Tambah Baris Atas"
            class="flex items-center justify-center w-[30px] h-[30px] rounded text-gray-600 hover:bg-gray-100 transition-colors">
            <svg class="w-[15px] h-[15px]" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="18" height="18" rx="2" />
                <path d="M3 9h18" />
                <line x1="12" y1="3" x2="12" y2="9" />
                <line x1="9" y1="6" x2="15" y2="6" />
            </svg>
        </button>
        <button type="button" @click="cmd('addRowAfter')" title="Tambah Baris Bawah"
            class="flex items-center justify-center w-[30px] h-[30px] rounded text-gray-600 hover:bg-gray-100 transition-colors">
            <svg class="w-[15px] h-[15px]" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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
                                    onUpdate: (html) => {
                                        this.editorHtml = html;
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
                            // FIX: gunakan window._sanitizeUrl yang masih di-expose
                            const safeUrl = window._sanitizeUrl(this.linkBubble.url.trim());
                            if (!safeUrl) {
                                this.closeLinkBubble();
                                return;
                            }

                            const sel = this._savedSelection;
                            const title = this.linkBubble.title.trim();

                            if (sel && !sel.empty) {
                                if (title) {
                                    ed.chain()
                                        .setTextSelection({
                                            from: sel.from,
                                            to: sel.to
                                        })
                                        .insertContent(
                                            `<a href="${safeUrl}" target="_blank" rel="noopener noreferrer">${title}</a>`
                                            )
                                        .run();
                                } else {
                                    ed.chain()
                                        .setTextSelection({
                                            from: sel.from,
                                            to: sel.to
                                        })
                                        .setLink({
                                            href: safeUrl,
                                            target: '_blank',
                                            rel: 'noopener noreferrer'
                                        })
                                        .run();
                                }
                            } else {
                                ed.chain()
                                    .insertContent(
                                        `<a href="${safeUrl}" target="_blank" rel="noopener noreferrer">${title || safeUrl}</a>`
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
                        _syncToolbar() {
                            const ed = _ed();
                            if (!ed) return;

                            // Structure label
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

                            // Font size
                            this.fontSize = ed.getAttributes('textStyle').fontSize || '15px';

                            // Table context
                            this.inTable = ed.isActive('table');

                            // Word / char count
                            const txt = ed.getText();
                            const trimmed = txt.trim();
                            this.statWords = (trimmed ? trimmed.split(/\s+/).length : 0) + ' words';
                            this.statChars = txt.length + ' chars';

                            // Current node name
                            const node = ed.state.selection.$anchor.parent;
                            let nodeName = node.type.name;
                            if (nodeName === 'heading') nodeName = 'h' + node.attrs.level;
                            this.statNode = nodeName;
                        },
                    };
                });
            });
        </script>
    @endpush
@endonce

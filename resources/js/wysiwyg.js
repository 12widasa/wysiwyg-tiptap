import { Editor, Extension, Node } from "@tiptap/core";
import StarterKit from "@tiptap/starter-kit";
import Underline from "@tiptap/extension-underline";
import TextAlign from "@tiptap/extension-text-align";
import Link from "@tiptap/extension-link";
import Color from "@tiptap/extension-color";
import { TextStyle } from "@tiptap/extension-text-style";
import Highlight from "@tiptap/extension-highlight";
import { Table } from "@tiptap/extension-table";
import { TableRow } from "@tiptap/extension-table-row";
import { TableCell } from "@tiptap/extension-table-cell";
import { TableHeader } from "@tiptap/extension-table-header";
import Placeholder from "@tiptap/extension-placeholder";
import TaskList from "@tiptap/extension-task-list";
import TaskItem from "@tiptap/extension-task-item";
import Image from "@tiptap/extension-image";
import { TextSelection, AllSelection } from "prosemirror-state";

// ── Constants ──────────────────────────────────────────────────────────────
const NODE_NAMES = Object.freeze({
    IMAGE_FIGURE: "imageFigure",
    IMAGE_DROPZONE: "imageDropzone",
    PARAGRAPH: "paragraph",
    HEADING: "heading",
    BLOCKQUOTE: "blockquote",
    BULLET_LIST: "bulletList",
    ORDERED_LIST: "orderedList",
    TASK_LIST: "taskList",
    LIST_ITEM: "listItem",
    TASK_ITEM: "taskItem",
});

const ALIGN = Object.freeze({
    LEFT: "left",
    CENTER: "center",
    RIGHT: "right",
    JUSTIFY: "justify",
});

const FILE_MAX_BYTES = 5 * 1024 * 1024;   // 5 MB
const RESIZE_MIN_PX = 80;
const INDENT_STEP = 24;
const INDENT_MAX = 8;
const TOOLBAR_DEBOUNCE_MS = 60;            // debounce _syncToolbar

const INDENTABLE = Object.freeze([
    NODE_NAMES.BULLET_LIST,
    NODE_NAMES.ORDERED_LIST,
    NODE_NAMES.TASK_LIST,
    NODE_NAMES.PARAGRAPH,
    NODE_NAMES.HEADING,
    NODE_NAMES.BLOCKQUOTE,
]);
const SKIP_INSIDE = Object.freeze([NODE_NAMES.LIST_ITEM, NODE_NAMES.TASK_ITEM]);

// ── Utilities ──────────────────────────────────────────────────────────────

/** Validasi URL — hanya izinkan http/https/mailto, kembalikan null jika tidak valid */
function sanitizeUrl(raw) {
    const trimmed = (raw ?? "").trim();
    if (!trimmed) return null;
    try {
        const url = new URL(trimmed);
        if (!["https:", "http:", "mailto:"].includes(url.protocol)) return null;
        return url.href;
    } catch {
        return null;
    }
}

/** Escape karakter HTML berbahaya — cegah XSS di insertContent template string */
function escapeHtml(str) {
    return String(str ?? "")
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#39;");
}

/** Buat debounce wrapper sederhana */
function debounce(fn, ms) {
    let timer;
    return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => fn(...args), ms);
    };
}

/** Cari imageFigure di selection — kembalikan { node, pos } atau null */
function getFigureNode(state) {
    const { selection } = state;
    const { $from } = selection;
    for (let d = $from.depth; d >= 0; d--) {
        if ($from.node(d).type.name === NODE_NAMES.IMAGE_FIGURE)
            return { node: $from.node(d), pos: $from.before(d) };
    }
    if (selection.node?.type.name === NODE_NAMES.IMAGE_FIGURE)
        return { node: selection.node, pos: selection.from };
    return null;
}

/** Ambil posisi imageFigure yang sedang di-select, atau null */
function getFigurePos(state) {
    return getFigureNode(state)?.pos ?? null;
}

/** Ambil align dari imageFigure yang sedang di-select, atau null */
function getFigureAlign(state) {
    return getFigureNode(state)?.node.attrs.align ?? null;
}



// ── Upload gambar ke server ────────────────────────────────────────────────

/**
 * Upload file ke server.
 * @param {File}   file
 * @param {string} uploadUrl
 * @param {AbortSignal} signal  — batalkan request jika dropzone diganti
 * @returns {Promise<string>}   URL gambar yang diupload
 */
async function uploadImage(file, uploadUrl, signal) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    if (!csrfToken) throw new Error("CSRF token tidak ditemukan");

    const formData = new FormData();
    formData.append("image", file);

    const res = await fetch(uploadUrl, {
        method: "POST",
        headers: { "X-CSRF-TOKEN": csrfToken },
        body: formData,
        signal,                 // AbortSignal untuk cancel
    });

    if (!res.ok) {
        const contentType = res.headers.get('content-type') ?? '';
        const err = contentType.includes('application/json')
            ? await res.json().catch(() => ({}))
            : {};
        throw new Error(err.message || `Upload gagal (HTTP ${res.status})`);
    }

    const data = await res.json().catch(() => {
        throw new Error('Response upload tidak valid (bukan JSON)');
    });
    if (typeof data?.url !== "string" || !data.url.startsWith("http"))
        throw new Error("Response upload tidak valid");

    const returnedOrigin = new URL(data.url).origin;
    if (returnedOrigin !== window.location.origin)
        throw new Error("Response upload tidak valid: origin tidak dikenali");

    return data.url;
}

// ── ImageFigure: caption keydown handler ──────────────────────────────────
function handleCaptionKeydown(e, { caption, ed, getPos }) {
    e.stopPropagation();
    e.stopImmediatePropagation();

    const { state } = ed;
    const { tr } = state;

    if (e.key === "Backspace" || e.key === "Delete") {
        e.preventDefault();
        const start = caption.selectionStart;
        const end = caption.selectionEnd;
        const val = caption.value;

        if (e.key === "Backspace" && val.trim() === "") {
            caption.blur();
            const pos = getPos();
            if (typeof pos !== "number") return;
            try {
                const $pos = tr.doc.resolve(pos);
                tr.setSelection(TextSelection.near($pos));
                ed.view.dispatch(tr);
            } catch { /* best effort */ }
            ed.view.focus();
            return;
        }

        if (e.key === "Backspace") {
            caption.value = start !== end
                ? val.slice(0, start) + val.slice(end)
                : start > 0 ? val.slice(0, start - 1) + val.slice(start) : val;
            caption.selectionStart = caption.selectionEnd = start !== end ? start : Math.max(0, start - 1);
        } else {
            caption.value = start !== end
                ? val.slice(0, start) + val.slice(end)
                : start < val.length ? val.slice(0, start) + val.slice(start + 1) : val;
            caption.selectionStart = caption.selectionEnd = start;
        }
        caption.dispatchEvent(new Event("input"));
        return;
    }

    if (e.key !== "Enter") return;
    e.preventDefault();

    const pos = getPos();
    if (typeof pos !== "number") return;
    caption.blur();

    const figureNode = state.doc.nodeAt(pos);
    if (!figureNode) return;

    const afterPos = pos + figureNode.nodeSize;
    const nextNode = afterPos < state.doc.content.size ? state.doc.nodeAt(afterPos) : null;

    if (nextNode?.isBlock) {
        try {
            const $pos = tr.doc.resolve(afterPos);
            tr.setSelection(TextSelection.near($pos));
            ed.view.dispatch(tr);
        } catch { /* best effort */ }
    } else {
        tr.insert(afterPos, state.schema.nodes[NODE_NAMES.PARAGRAPH].create());
        try {
            const $pos = tr.doc.resolve(afterPos);
            tr.setSelection(TextSelection.near($pos));
        } catch { /* fallback */ }
        ed.view.dispatch(tr);
    }
    ed.view.focus();
}

// ── ImageFigure Node ───────────────────────────────────────────────────────
const ImageFigureNode = Node.create({
    name: NODE_NAMES.IMAGE_FIGURE,
    group: "block",
    atom: false,
    isolating: true,
    selectable: true,
    draggable: true,

    addAttributes() {
        return {
            src: { default: null },
            alt: { default: "" },
            width: { default: null },
            caption: { default: "" },
            align: { default: ALIGN.LEFT },
        };
    },

    parseHTML() {
        return [{ tag: `figure[data-type="${NODE_NAMES.IMAGE_FIGURE}"]` }];
    },

    renderHTML({ HTMLAttributes }) {
        const align = HTMLAttributes.align || ALIGN.LEFT;
        const alignClass = align === ALIGN.JUSTIFY
            ? "img-figure--left"
            : `img-figure--${align}`;

        const imgAttrs = {
            src: HTMLAttributes.src || "",
            alt: HTMLAttributes.alt || "",
            style: HTMLAttributes.width
                ? `width:${HTMLAttributes.width}px;max-width:100%`
                : "max-width:100%",
            class: "img-figure__img",
        };

        const children = [["img", imgAttrs]];
        if (HTMLAttributes.caption) {
            children.push(["figcaption", { class: "img-caption" }, HTMLAttributes.caption]);
        }

        return [
            "figure",
            {
                "data-type": NODE_NAMES.IMAGE_FIGURE,
                "data-src": HTMLAttributes.src,
                "data-alt": HTMLAttributes.alt,
                "data-width": HTMLAttributes.width,
                "data-caption": HTMLAttributes.caption,
                "data-align": HTMLAttributes.align,
                class: `img-figure ${alignClass}`,
            },
            ...children,
        ];
    },

    addNodeView() {
        return ({ node, editor: ed, getPos }) => {
            // ── DOM setup ──
            const wrap = document.createElement("div");
            wrap.className = "img-figure";
            wrap.contentEditable = "false";
            if (node.attrs.width) wrap.style.width = node.attrs.width + "px";

            const applyAlign = (align) => {
                wrap.classList.remove("img-figure--left", "img-figure--center", "img-figure--right");
                wrap.classList.add(`img-figure--${align === ALIGN.JUSTIFY ? ALIGN.LEFT : align}`);
            };
            applyAlign(node.attrs.align || ALIGN.LEFT);

            const img = document.createElement("img");
            img.className = "img-figure__img";
            img.alt = node.attrs.alt || "";
            // src set terakhir agar onload/onerror sudah terpasang
            img.onload = () => {
                if (!node.attrs.width) {
                    wrap.style.width = img.naturalWidth + "px";
                    wrap.style.maxWidth = "100%";
                }
                img.removeAttribute("data-error");
            };
            img.onerror = () => {
                img.setAttribute("data-error", "true");
                if (!img.alt) img.alt = "Image failed to load";
            };
            img.src = node.attrs.src || "";

            const handle = document.createElement("div");
            handle.className = "resize-handle";
            handle.title = "Drag to resize";
            handle.setAttribute("aria-hidden", "true");
            handle.innerHTML = `<svg viewBox="0 0 24 24" aria-hidden="true"><polyline points="15 3 21 3 21 9"/><polyline points="9 21 3 21 3 15"/><line x1="21" y1="3" x2="14" y2="10"/><line x1="3" y1="21" x2="10" y2="14"/></svg>`;

            const caption = document.createElement("textarea");
            caption.className = "img-caption";
            caption.placeholder = "Add a caption…";
            caption.rows = 1;
            caption.value = node.attrs.caption || "";
            caption.setAttribute("aria-label", "Image caption");

            wrap.appendChild(img);
            wrap.appendChild(handle);
            wrap.appendChild(caption);

            let currentAttrs = { ...node.attrs };

            // ── Resize (dengan max-width guard) ──
            let startX, startW;
            handle.addEventListener("mousedown", (e) => {
                e.preventDefault();
                e.stopPropagation();
                startX = e.clientX;
                startW = wrap.offsetWidth;
                wrap.classList.add("selected");

                const containerW = wrap.parentElement?.offsetWidth ?? Infinity;
                const maxW = containerW > 0 ? containerW : Infinity;

                const onMove = (e) => {
                    const newW = Math.min(maxW, Math.max(RESIZE_MIN_PX, startW + (e.clientX - startX)));
                    wrap.style.width = newW + "px";
                };
                const onUp = (e) => {
                    document.removeEventListener("mousemove", onMove);
                    document.removeEventListener("mouseup", onUp);
                    const newW = Math.round(Math.min(maxW, Math.max(RESIZE_MIN_PX, startW + (e.clientX - startX))));
                    const pos = getPos();
                    if (typeof pos !== "number") return;
                    currentAttrs = { ...currentAttrs, width: newW };
                    const { tr } = ed.state;
                    tr.setNodeMarkup(pos, undefined, { ...currentAttrs });
                    ed.view.dispatch(tr);
                };
                document.addEventListener("mousemove", onMove);
                document.addEventListener("mouseup", onUp);
            });

            // ── Caption events ──
            caption.addEventListener("mousedown", (e) => e.stopPropagation());
            caption.addEventListener("click", (e) => e.stopPropagation());
            caption.addEventListener("keyup", (e) => e.stopPropagation());
            caption.addEventListener("keypress", (e) => e.stopPropagation());

            caption.addEventListener("keydown", (e) => handleCaptionKeydown(e, { caption, ed, getPos }));

            caption.addEventListener("input", () => {
                caption.style.height = "auto";
                caption.style.height = caption.scrollHeight + "px";
            });

            caption.addEventListener("blur", () => {
                const val = caption.value.trim();
                caption.value = val;
                const pos = getPos();
                if (typeof pos !== "number" || val === currentAttrs.caption) return;
                currentAttrs = { ...currentAttrs, caption: val };
                const { tr, selection } = ed.state;
                tr.setNodeMarkup(pos, undefined, { ...currentAttrs });
                try { tr.setSelection(selection.map(tr.doc, tr.mapping)); } catch { /* stale */ }
                ed.view.dispatch(tr);
            });

            // ── Selection highlight ──
            const onWrapClick = (e) => {
                if (e.target === caption || e.target === handle) return;
                wrap.classList.add("selected");
            };
            const onDocClick = (e) => {
                if (!wrap.contains(e.target)) wrap.classList.remove("selected");
            };
            wrap.addEventListener("click", onWrapClick);
            document.addEventListener("click", onDocClick);

            // ── NodeView callbacks ──
            const update = (updatedNode) => {
                if (updatedNode.type.name !== NODE_NAMES.IMAGE_FIGURE) return false;
                currentAttrs = { ...updatedNode.attrs };
                if (updatedNode.attrs.src && updatedNode.attrs.src !== img.src)
                    img.src = updatedNode.attrs.src;
                const newCaption = updatedNode.attrs.caption || "";
                if (newCaption !== caption.value) caption.value = newCaption;
                if (updatedNode.attrs.width)
                    wrap.style.width = updatedNode.attrs.width + "px";
                applyAlign(updatedNode.attrs.align || ALIGN.LEFT);
                return true;
            };

            // FIX: destroy harus hapus SEMUA document listener agar tidak memory leak
            const destroy = () => {
                document.removeEventListener("click", onDocClick);
            };

            return { dom: wrap, update, destroy };
        };
    },
});

// ── ImageDropzone Node ─────────────────────────────────────────────────────
const ImageDropzoneNode = Node.create({
    name: NODE_NAMES.IMAGE_DROPZONE,
    group: "block",
    atom: true,
    selectable: true,
    draggable: false,

    parseHTML() {
        return [{ tag: `div[data-type="${NODE_NAMES.IMAGE_DROPZONE}"]` }];
    },
    renderHTML() {
        return ["div", { "data-type": NODE_NAMES.IMAGE_DROPZONE }];
    },

    addNodeView() {
        return ({ editor: ed, getPos }) => {
            const uploadUrl = ed.options.element.dataset.uploadUrl || "/content/upload-image";

            const dom = document.createElement("div");
            dom.className = "img-dropzone";
            dom.contentEditable = "false";
            dom.setAttribute("role", "button");
            dom.setAttribute("aria-label", "Upload image");
            dom.innerHTML = `
<input type="file" accept="image/jpeg,image/png,image/gif,image/webp" class="img-file-input" style="position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%" aria-hidden="true">
<div class="img-dropzone-icon" aria-hidden="true">
    <div class="file-body"></div>
    <div class="upload-circle">
        <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:11px;height:11px" aria-hidden="true">
            <line x1="12" y1="19" x2="12" y2="5"/><polyline points="5 12 12 5 19 12"/>
        </svg>
    </div>
</div>
<p class="img-dropzone-text"><strong>Click to upload</strong> or drag and drop</p>
<p class="img-dropzone-hint">PNG, JPG, GIF, WEBP — max 5MB</p>
<p class="img-dropzone-status" style="font-size:12px;color:#6b4fbb;display:none;" aria-live="polite">Uploading…</p>`;

            function insertFigure(url, alt) {
                const pos = getPos();
                if (typeof pos !== "number") return;
                const { state } = ed;
                const { tr } = state;
                const figureNode = state.schema.nodes[NODE_NAMES.IMAGE_FIGURE].create({
                    src: url, alt, width: null, caption: "", align: ALIGN.LEFT,
                });
                tr.replaceWith(pos, pos + 1, figureNode);
                const afterFigure = pos + figureNode.nodeSize;
                if (!tr.doc.nodeAt(afterFigure))
                    tr.insert(afterFigure, state.schema.nodes[NODE_NAMES.PARAGRAPH].create());
                try {
                    tr.setSelection(TextSelection.create(tr.doc, afterFigure + 1));
                } catch { /* best effort */ }
                ed.view.dispatch(tr);
                ed.view.focus();
            }

            // FIX: AbortController untuk cancel upload jika dropzone sudah diganti
            let currentAbort = null;

            async function handleFile(file) {
                if (!file.type.startsWith("image/")) return;
                if (file.size > FILE_MAX_BYTES) {
                    alert(`File terlalu besar. Maksimal 5 MB, ukuran: ${(file.size / 1024 / 1024).toFixed(1)} MB`);
                    return;
                }

                // Batalkan upload sebelumnya jika masih berjalan
                currentAbort?.abort();
                currentAbort = new AbortController();

                const status = dom.querySelector(".img-dropzone-status");
                const fileInput = dom.querySelector(".img-file-input");
                status.style.display = "block";
                if (fileInput) fileInput.disabled = true;

                try {
                    const url = await uploadImage(file, uploadUrl, currentAbort.signal);
                    insertFigure(url, file.name);
                } catch (err) {
                    if (err.name === "AbortError") return; // dibatalkan — tidak perlu alert
                    status.style.display = "none";
                    if (fileInput) fileInput.disabled = false;
                    alert("Upload gagal: " + err.message);
                } finally {
                    currentAbort = null;
                }
            }

            const fileInput = dom.querySelector(".img-file-input");
            fileInput.addEventListener("change", (e) => {
                const file = e.target.files?.[0];
                if (file) handleFile(file);
                // Reset value agar file yang sama bisa di-upload lagi
                e.target.value = "";
            });

            dom.addEventListener("dragover", (e) => { e.preventDefault(); dom.classList.add("drag-over"); });
            dom.addEventListener("dragleave", () => dom.classList.remove("drag-over"));
            dom.addEventListener("drop", (e) => {
                e.preventDefault();
                dom.classList.remove("drag-over");
                const file = e.dataTransfer?.files?.[0];
                if (file) handleFile(file);
            });

            // FIX: destroy batalkan upload yang sedang berjalan
            const destroy = () => { currentAbort?.abort(); };

            return { dom, destroy };
        };
    },
});

// ── Indent Extension ───────────────────────────────────────────────────────
function isInsideList($from) {
    for (let d = $from.depth - 1; d >= 0; d--) {
        if (SKIP_INSIDE.includes($from.node(d).type.name)) return true;
    }
    return false;
}

function indentNodes(state, delta) {
    const { selection, tr } = state;
    const { from, to } = selection;
    let changed = false;
    const handled = new Set();

    state.doc.nodesBetween(from, to, (node, pos) => {
        if (!INDENTABLE.includes(node.type.name)) return true;
        if (isInsideList(state.doc.resolve(pos))) return true;
        if (handled.has(pos)) return false;
        handled.add(pos);
        const cur = node.attrs.indent || 0;
        const next = cur + delta;
        if (next < 0 || next > INDENT_MAX) return false;
        tr.setNodeMarkup(pos, undefined, { ...node.attrs, indent: next });
        changed = true;
        return false;
    });

    return { tr, changed };
}

const IndentExtension = Extension.create({
    name: "indent",
    addGlobalAttributes() {
        return [{
            types: INDENTABLE,
            attributes: {
                indent: {
                    default: 0,
                    parseHTML: (el) =>
                        Math.round((parseFloat(el.style.marginLeft || el.style.paddingLeft) || 0) / INDENT_STEP) || 0,
                    renderHTML: (attrs) =>
                        attrs.indent ? { style: `margin-left: ${attrs.indent * INDENT_STEP}px` } : {},
                },
            },
        }];
    },
    addCommands() {
        return {
            indent: () => ({ state, dispatch }) => {
                const { tr, changed } = indentNodes(state, +1);
                if (changed && dispatch) dispatch(tr);
                return changed;
            },
            outdent: () => ({ state, dispatch }) => {
                const { tr, changed } = indentNodes(state, -1);
                if (changed && dispatch) dispatch(tr);
                return changed;
            },
        };
    },
});

// ── initWysiwyg — public API ───────────────────────────────────────────────
/**
 * Inisialisasi editor Tiptap pada elemen yang diberikan.
 *
 * @param {object} opts
 * @param {HTMLElement} opts.editorEl        - elemen container editor
 * @param {string}      opts.initialContent  - HTML awal (opsional)
 * @param {string}      opts.placeholder     - teks placeholder
 * @param {string}      opts.uploadUrl       - endpoint upload gambar
 * @param {Function}    opts.onUpdate        - callback(html) saat konten berubah
 * @param {Function}    opts.onSelectionUpdate - callback(editor) saat selection berubah
 * @returns {{ editor, getFigureAlign, getFigurePos, destroy }}
 */
window.initWysiwyg = function ({
    editorEl,
    initialContent = "",
    placeholder = "Mulai menulis di sini…",
    uploadUrl = "/content/upload-image",
    onUpdate,
    onSelectionUpdate,
}) {
    // Simpan uploadUrl di dataset agar bisa diakses ImageDropzoneNode
    editorEl.dataset.uploadUrl = uploadUrl;

    // FIX: debounce selection callback agar tidak trigger _syncToolbar tiap keystroke
    const debouncedSelectionUpdate = debounce((editor) => {
        onSelectionUpdate?.(editor);
    }, TOOLBAR_DEBOUNCE_MS);

    const editor = new Editor({
        element: editorEl,
        editorProps: {
            // ── Paste sanitizer (defense-in-depth, zero dependency) ──
            // transformPastedHTML dipanggil Tiptap sebelum HTML di-parse ke dokumen.
            // DOMParser strip script/style secara native — kita hanya hapus
            // event handler dan javascript: URI yang lolos.
            transformPastedHTML(html) {
                const doc = new DOMParser().parseFromString(html, "text/html");
                const DANGEROUS = ["script", "style", "iframe", "object", "embed", "noscript"];
                DANGEROUS.forEach(tag => doc.querySelectorAll(tag).forEach(el => el.remove()));
                doc.querySelectorAll("*").forEach(el => {
                    [...el.attributes].forEach(({ name, value }) => {
                        if (/^on/i.test(name)) { el.removeAttribute(name); return; }
                        if (/^\s*javascript:/i.test(value)) el.removeAttribute(name);
                    });
                });
                return doc.body.innerHTML;
            },

            handleKeyDown(view, event) {
                if (event.key !== "ArrowLeft" && event.key !== "ArrowRight") return false;
                if (event.shiftKey || event.metaKey || event.ctrlKey) return false;

                const { state } = view;
                const { selection, doc, tr } = state;

                // Hanya handle AllSelection — hasil Ctrl+A
                // ProseMirror tidak collapse AllSelection via arrow keys secara default
                if (!(selection instanceof AllSelection)) return false;

                const isLeft = event.key === "ArrowLeft";
                const pos = isLeft ? 0 : doc.content.size;
                const bias = isLeft ? 1 : -1;
                view.dispatch(tr.setSelection(TextSelection.near(doc.resolve(pos), bias)));
                return true;
            },
        },
        extensions: [
            StarterKit.configure({ link: false, underline: false }),
            Underline,
            TextAlign.configure({ types: [NODE_NAMES.HEADING, NODE_NAMES.PARAGRAPH] }),
            Link.configure({ openOnClick: false }),
            Image,
            TextStyle,
            Color,
            Highlight.configure({ multicolor: true }),
            Table.configure({ resizable: true }),
            TableRow,
            TableHeader,
            TableCell,
            Placeholder.configure({ placeholder }),
            TaskList,
            // FIX: TaskItem v3 default punya isolating:true (seperti table cell),
            // yang menyebabkan arrow kiri/kanan tidak bisa keluar dari task item.
            // Override isolating ke false agar navigasi normal seperti list biasa.
            TaskItem.configure({ nested: true }).extend({
                isolating: false,
            }),
            IndentExtension,
            ImageDropzoneNode,
            ImageFigureNode,
        ],
        content: initialContent || "",
        onUpdate({ editor }) {
            onUpdate?.(editor.getHTML(), editor.getText());
        },
        onSelectionUpdate({ editor }) {
            debouncedSelectionUpdate(editor);
        },
    });

    // Kembalikan public API — Alpine mengakses semua helper lewat instance ini,
    // tidak ada yang di-expose ke window kecuali initWysiwyg itu sendiri.
    return {
        editor,
        getFigureAlign: () => getFigureAlign(editor.state),
        getFigurePos: () => getFigurePos(editor.state),
        sanitizeUrl,
        escapeHtml,
        destroy() {
            editor.destroy();
        },
    };
};
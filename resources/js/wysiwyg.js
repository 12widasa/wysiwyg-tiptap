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
import { TextSelection } from "prosemirror-state";

// ── Constants ──
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

const FILE_MAX_BYTES = 5 * 1024 * 1024;

const INDENTABLE = Object.freeze([
    NODE_NAMES.BULLET_LIST,
    NODE_NAMES.ORDERED_LIST,
    NODE_NAMES.TASK_LIST,
    NODE_NAMES.PARAGRAPH,
    NODE_NAMES.HEADING,
    NODE_NAMES.BLOCKQUOTE,
]);
const SKIP_INSIDE = Object.freeze([NODE_NAMES.LIST_ITEM, NODE_NAMES.TASK_ITEM]);
const INDENT_STEP = 24;
const INDENT_MAX = 8;

// ── Utilities ──
function sanitizeUrl(raw) {
    const trimmed = raw.trim();
    if (!trimmed) return null;
    try {
        const url = new URL(trimmed);
        if (!["https:", "http:", "mailto:"].includes(url.protocol)) return null;
        return url.href;
    } catch {
        return null;
    }
}

function escapeHtml(str) {
    return str
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;");
}

// ── Expose helpers ke Alpine (dipanggil dari wysiwyg.blade.php) ──
window._sanitizeUrl = sanitizeUrl;

window._getFigureAlign = function (editor) {
    const { selection } = editor.state;
    const { $from } = selection;
    for (let d = $from.depth; d >= 0; d--) {
        if ($from.node(d).type.name === NODE_NAMES.IMAGE_FIGURE)
            return $from.node(d).attrs.align || ALIGN.LEFT;
    }
    if (selection.node?.type.name === NODE_NAMES.IMAGE_FIGURE)
        return selection.node.attrs.align || ALIGN.LEFT;
    return null;
};

window._getFigurePos = function (state) {
    const { selection } = state;
    const { $from } = selection;
    for (let d = $from.depth; d >= 0; d--) {
        if ($from.node(d).type.name === NODE_NAMES.IMAGE_FIGURE)
            return $from.before(d);
    }
    if (selection.node?.type.name === NODE_NAMES.IMAGE_FIGURE)
        return selection.from;
    return null;
};

// ── Upload gambar ke server ──
async function uploadImage(file, uploadUrl) {
    const csrfToken = document.querySelector(
        'meta[name="csrf-token"]',
    )?.content;
    if (!csrfToken) throw new Error("CSRF token tidak ditemukan");

    const formData = new FormData();
    formData.append("image", file);

    const res = await fetch(uploadUrl, {
        method: "POST",
        headers: { "X-CSRF-TOKEN": csrfToken },
        body: formData,
    });

    if (!res.ok) {
        const err = await res.json().catch(() => ({}));
        throw new Error(err.message || "Upload gagal");
    }

    const data = await res.json();
    return data.url;
}

// ── ImageFigure Node ──
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
        const alignClass =
            {
                left: "img-figure--left",
                center: "img-figure--center",
                right: "img-figure--right",
                justify: "img-figure--left",
            }[HTMLAttributes.align || "left"] || "img-figure--left";

        const nodes = [
            [
                "img",
                {
                    src: HTMLAttributes.src || "",
                    alt: HTMLAttributes.alt || "",
                    style: HTMLAttributes.width
                        ? `width:${HTMLAttributes.width}px;max-width:100%`
                        : "max-width:100%",
                    class: "img-figure__img",
                },
            ],
        ];

        if (HTMLAttributes.caption) {
            nodes.push([
                "figcaption",
                { class: "img-caption" },
                HTMLAttributes.caption,
            ]);
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
            ...nodes,
        ];
    },

    addNodeView() {
        return ({ node, editor: ed, getPos }) => {
            const wrap = document.createElement("div");
            wrap.className = "img-figure";
            wrap.contentEditable = "false";
            if (node.attrs.width) wrap.style.width = node.attrs.width + "px";

            function applyAlign(align) {
                wrap.classList.remove(
                    "img-figure--left",
                    "img-figure--center",
                    "img-figure--right",
                );
                wrap.classList.add(
                    `img-figure--${align === ALIGN.JUSTIFY ? ALIGN.LEFT : align}`,
                );
            }
            applyAlign(node.attrs.align || ALIGN.LEFT);

            const img = document.createElement("img");
            img.src = node.attrs.src || "";
            img.alt = node.attrs.alt || "";
            img.className = "img-figure__img";
            img.onload = () => {
                if (!node.attrs.width) {
                    wrap.style.width = img.naturalWidth + "px";
                    wrap.style.maxWidth = "100%";
                }
                img.removeAttribute("data-error");
            };
            img.onerror = () => {
                img.setAttribute("data-error", "true");
                img.alt = img.alt || "Image failed to load";
            };

            const handle = document.createElement("div");
            handle.className = "resize-handle";
            handle.title = "Drag to resize";
            handle.innerHTML = `<svg viewBox="0 0 24 24"><polyline points="15 3 21 3 21 9"/><polyline points="9 21 3 21 3 15"/><line x1="21" y1="3" x2="14" y2="10"/><line x1="3" y1="21" x2="10" y2="14"/></svg>`;

            const caption = document.createElement("textarea");
            caption.className = "img-caption";
            caption.placeholder = "Add a caption…";
            caption.rows = 1;
            caption.value = node.attrs.caption || "";

            wrap.appendChild(img);
            wrap.appendChild(handle);
            wrap.appendChild(caption);

            let currentAttrs = { ...node.attrs };

            // ── Resize ──
            let startX, startW;
            handle.addEventListener("mousedown", (e) => {
                e.preventDefault();
                e.stopPropagation();
                startX = e.clientX;
                startW = wrap.offsetWidth;
                wrap.classList.add("selected");

                const onMove = (e) => {
                    const newW = Math.max(80, startW + (e.clientX - startX));
                    wrap.style.width = newW + "px";
                };
                const onUp = (e) => {
                    const newW = Math.round(
                        Math.max(80, startW + (e.clientX - startX)),
                    );
                    document.removeEventListener("mousemove", onMove);
                    document.removeEventListener("mouseup", onUp);
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

            caption.addEventListener("keydown", (e) => {
                e.stopPropagation();
                e.stopImmediatePropagation();

                const { state } = ed;
                const { tr } = state;

                if (e.key === "Backspace" || e.key === "Delete") {
                    e.preventDefault();
                    e.stopImmediatePropagation();

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
                            ed.view.focus();
                        } catch {
                            ed.view.focus();
                        }
                        return;
                    }

                    if (e.key === "Backspace") {
                        if (start !== end) {
                            caption.value =
                                val.slice(0, start) + val.slice(end);
                            caption.selectionStart = caption.selectionEnd =
                                start;
                        } else if (start > 0) {
                            caption.value =
                                val.slice(0, start - 1) + val.slice(start);
                            caption.selectionStart = caption.selectionEnd =
                                start - 1;
                        }
                    }
                    if (e.key === "Delete") {
                        if (start !== end) {
                            caption.value =
                                val.slice(0, start) + val.slice(end);
                            caption.selectionStart = caption.selectionEnd =
                                start;
                        } else if (start < val.length) {
                            caption.value =
                                val.slice(0, start) + val.slice(start + 1);
                            caption.selectionStart = caption.selectionEnd =
                                start;
                        }
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
                const docSize = state.doc.content.size;
                let nextNode =
                    afterPos < docSize ? state.doc.nodeAt(afterPos) : null;

                if (nextNode && nextNode.isBlock) {
                    try {
                        const $pos = tr.doc.resolve(afterPos);
                        tr.setSelection(TextSelection.near($pos));
                        ed.view.dispatch(tr);
                        ed.view.focus();
                    } catch {
                        ed.view.focus();
                    }
                } else {
                    const paragraph = state.schema.nodes["paragraph"].create();
                    tr.insert(afterPos, paragraph);
                    try {
                        const $pos = tr.doc.resolve(afterPos);
                        tr.setSelection(TextSelection.near($pos));
                    } catch {
                        /* fallback */
                    }
                    ed.view.dispatch(tr);
                    ed.view.focus();
                }
            });

            caption.addEventListener("input", () => {
                caption.style.height = "auto";
                caption.style.height = caption.scrollHeight + "px";
            });

            caption.addEventListener("blur", () => {
                const val = caption.value.trim();
                caption.value = val;
                const pos = getPos();
                if (typeof pos !== "number" || val === currentAttrs.caption)
                    return;
                currentAttrs = { ...currentAttrs, caption: val };
                const { tr, selection } = ed.state;
                tr.setNodeMarkup(pos, undefined, { ...currentAttrs });
                try {
                    tr.setSelection(selection.map(tr.doc, tr.mapping));
                } catch {
                    /* stale */
                }
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

            const update = (updatedNode) => {
                if (updatedNode.type.name !== NODE_NAMES.IMAGE_FIGURE)
                    return false;
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

            const destroy = () =>
                document.removeEventListener("click", onDocClick);

            return { dom: wrap, update, destroy };
        };
    },
});

// ── ImageDropzone Node ──
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
            // uploadUrl diambil dari dataset editor element (di-set saat init)
            const uploadUrl =
                ed.options.element.dataset.uploadUrl || "/content/upload-image";

            const dom = document.createElement("div");
            dom.className = "img-dropzone";
            dom.contentEditable = "false";
            dom.innerHTML = `
<input type="file" accept="image/*" class="img-file-input" style="position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%">
<div class="img-dropzone-icon">
    <div class="file-body"></div>
    <div class="upload-circle">
        <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:11px;height:11px">
            <line x1="12" y1="19" x2="12" y2="5"/><polyline points="5 12 12 5 19 12"/>
        </svg>
    </div>
</div>
<p class="img-dropzone-text"><strong>Click to upload</strong> or drag and drop</p>
<p class="img-dropzone-hint">PNG, JPG, GIF, WEBP — max 5MB</p>
<p class="img-dropzone-status" style="font-size:12px;color:#6b4fbb;display:none;">Mengupload...</p>`;

            function insertFigure(url, alt) {
                const pos = getPos();
                if (typeof pos !== "number") return;
                const { state } = ed;
                const { tr } = state;
                const figureNode = state.schema.nodes[
                    NODE_NAMES.IMAGE_FIGURE
                ].create({
                    src: url,
                    alt,
                    width: null,
                    caption: "",
                    align: ALIGN.LEFT,
                });
                tr.replaceWith(pos, pos + 1, figureNode);
                const afterFigure = pos + figureNode.nodeSize;
                let hasBlockAfter = false;
                tr.doc.nodesBetween(
                    afterFigure,
                    tr.doc.content.size,
                    (n, p) => {
                        if (!hasBlockAfter && n.isBlock && p >= afterFigure)
                            hasBlockAfter = true;
                    },
                );
                if (!hasBlockAfter)
                    tr.insert(
                        afterFigure,
                        state.schema.nodes[NODE_NAMES.PARAGRAPH].create(),
                    );
                try {
                    tr.setSelection(
                        TextSelection.create(tr.doc, afterFigure + 1),
                    );
                } catch {
                    /* best effort */
                }
                ed.view.dispatch(tr);
                ed.view.focus();
            }

            async function handleFile(file) {
                if (!file.type.startsWith("image/")) return;
                if (file.size > FILE_MAX_BYTES) {
                    alert(
                        `File terlalu besar. Maksimal 5MB, ukuran: ${(file.size / 1024 / 1024).toFixed(1)}MB`,
                    );
                    return;
                }
                const status = dom.querySelector(".img-dropzone-status");
                status.style.display = "block";
                try {
                    const url = await uploadImage(file, uploadUrl);
                    insertFigure(url, file.name);
                } catch (err) {
                    alert("Upload gagal: " + err.message);
                    status.style.display = "none";
                }
            }

            const fileInput = dom.querySelector(".img-file-input");
            fileInput.addEventListener("change", (e) => {
                const file = e.target.files?.[0];
                if (file) handleFile(file);
            });
            dom.addEventListener("dragover", (e) => {
                e.preventDefault();
                dom.classList.add("drag-over");
            });
            dom.addEventListener("dragleave", () =>
                dom.classList.remove("drag-over"),
            );
            dom.addEventListener("drop", (e) => {
                e.preventDefault();
                dom.classList.remove("drag-over");
                const file = e.dataTransfer?.files?.[0];
                if (file) handleFile(file);
            });

            return { dom };
        };
    },
});

// ── Indent Extension ──
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
        return [
            {
                types: INDENTABLE,
                attributes: {
                    indent: {
                        default: 0,
                        parseHTML: (el) =>
                            Math.round(
                                (parseFloat(
                                    el.style.marginLeft || el.style.paddingLeft,
                                ) || 0) / INDENT_STEP,
                            ) || 0,
                        renderHTML: (attrs) =>
                            attrs.indent
                                ? {
                                      style: `margin-left: ${attrs.indent * INDENT_STEP}px`,
                                  }
                                : {},
                    },
                },
            },
        ];
    },
    addCommands() {
        return {
            indent:
                () =>
                ({ state, dispatch }) => {
                    const { tr, changed } = indentNodes(state, +1);
                    if (changed && dispatch) dispatch(tr);
                    return changed;
                },
            outdent:
                () =>
                ({ state, dispatch }) => {
                    const { tr, changed } = indentNodes(state, -1);
                    if (changed && dispatch) dispatch(tr);
                    return changed;
                },
        };
    },
});

// ── initWysiwyg — entry point dipanggil dari Alpine component ──
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

    const editor = new Editor({
        element: editorEl,
        extensions: [
            StarterKit.configure({ link: false, underline: false }),
            Underline,
            TextAlign.configure({
                types: [NODE_NAMES.HEADING, NODE_NAMES.PARAGRAPH],
            }),
            Link.configure({ openOnClick: false }),
            Image,
            TextStyle.extend({
                addAttributes() {
                    return {
                        ...this.parent?.(),
                        fontSize: {
                            default: null,
                            parseHTML: (el) => el.style.fontSize || null,
                            renderHTML: (attrs) =>
                                attrs.fontSize
                                    ? { style: `font-size: ${attrs.fontSize}` }
                                    : {},
                        },
                    };
                },
            }),
            Color,
            Highlight.configure({ multicolor: true }),
            Table.configure({ resizable: true }),
            TableRow,
            TableHeader,
            TableCell,
            Placeholder.configure({ placeholder }),
            TaskList,
            TaskItem.configure({ nested: true }),
            IndentExtension,
            ImageDropzoneNode,
            ImageFigureNode,
        ],
        content: initialContent || "",
        onUpdate({ editor }) {
            onUpdate?.(editor.getHTML());
        },
        onSelectionUpdate({ editor }) {
            onSelectionUpdate?.(editor);
        },
    });

    return editor;
};

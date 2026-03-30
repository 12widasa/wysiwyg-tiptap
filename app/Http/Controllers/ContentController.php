<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContentRequest;
use App\Models\Content;
use DOMDocument;
use DOMElement;
use HTMLPurifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ContentController extends Controller
{
    public function __construct(private readonly HTMLPurifier $purifier) {}

    public function create()
    {
        return view('content.create');
    }

    public function store(StoreContentRequest $request)
    {
        try {
            $clean   = $this->sanitize($request->input('description'));
            $content = Content::create(['description' => $clean]);

            return redirect()->route('content.show', $content->id);
        } catch (\Throwable $e) {
            Log::error('ContentController::store failed', ['error' => $e->getMessage()]);

            return back()
                ->withInput()
                ->withErrors(['description' => 'Gagal menyimpan konten. Silakan coba lagi.']);
        }
    }

    public function show(Content $content)
    {
        return view('content.show', compact('content'));
    }

    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => [
                'required',
                'image',
                'mimes:jpeg,png,jpg,gif,webp',
                'max:5120',
                'dimensions:max_width=4096,max_height=4096',
            ],
        ]);

        try {
            $ext      = strtolower($request->file('image')->getClientOriginalExtension());
            $filename = Str::uuid() . '.' . $ext;
            $path     = $request->file('image')->storeAs('content-images', $filename, 'public');

            if (! $path) {
                throw new \RuntimeException('File gagal disimpan ke storage.');
            }

            return response()->json(['url' => asset('storage/' . $path)]);
        } catch (\Throwable $e) {
            Log::error('ContentController::uploadImage failed', ['error' => $e->getMessage()]);

            return response()->json(
                ['message' => 'Upload gagal. Periksa kapasitas storage atau coba lagi.'],
                500
            );
        }
    }

    // ── Sanitasi ───────────────────────────────────────────────────────────

    /**
     * Sanitasi HTML dari editor Tiptap.
     *
     * HTMLPurifier HTML 4.01 tidak bisa menerima struktur task list Tiptap v3
     * (<label><input> + <div> di dalam <li>) karena content model terlalu ketat.
     *
     * Strategi:
     *   1. Ekstrak <ul data-type="taskList"> → placeholder
     *   2. HTMLPurifier untuk sisa HTML
     *   3. Sanitasi task list manual via DOMDocument
     *   4. Restore placeholder
     */
    private function sanitize(string $html): string
    {
        $placeholders = [];
        $counter      = 0;

        // Step 1: Ekstrak task list
        $html = preg_replace_callback(
            '/<ul[^>]*data-type=["\']taskList["\'][^>]*>.*?<\/ul>/si',
            function (array $m) use (&$placeholders, &$counter): string {
                $key                = '%%TASKLIST_' . $counter . '%%';
                $placeholders[$key] = $this->sanitizeTaskList($m[0]);
                $counter++;
                return $key;
            },
            $html
        );

        // Step 2: Purify sisa HTML
        $clean = $this->purifier->purify($html ?? '');

        // Step 3: Restore task list
        foreach ($placeholders as $key => $sanitized) {
            $clean = str_replace($key, $sanitized, $clean);
        }

        return $clean;
    }

    private function sanitizeTaskList(string $ulHtml): string
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $doc->loadHTML(
            '<?xml encoding="UTF-8"><div id="wr">' . $ulHtml . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();

        $root = $doc->getElementById('wr');
        if (! $root) {
            return '';
        }

        $result = '';
        foreach ($root->childNodes as $node) {
            if (! ($node instanceof DOMElement)) {
                continue;
            }
            if (strtolower($node->nodeName) !== 'ul') {
                continue;
            }
            $result .= $this->buildTaskListHtml($node);
        }

        return $result;
    }

    private function buildTaskListHtml(DOMElement $ul): string
    {
        $out = '<ul data-type="taskList">';

        foreach ($ul->childNodes as $node) {
            if (! ($node instanceof DOMElement)) {
                continue;
            }
            if (strtolower($node->nodeName) !== 'li') {
                continue;
            }

            $rawChecked = $node->getAttribute('data-checked');
            $checked    = ($rawChecked === 'true') ? 'true' : 'false';
            $isChecked  = ($checked === 'true');

            $text = $this->extractText($node);

            $out .= '<li data-type="taskItem" data-checked="' . $checked . '">';
            $out .= '<label><input type="checkbox"' . ($isChecked ? ' checked' : '') . ' disabled></label>';
            $out .= '<div><p>' . htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '</p></div>';
            $out .= '</li>';
        }

        $out .= '</ul>';
        return $out;
    }

    /**
     * Ambil teks dari <li> task item.
     * Tiptap v3: <li><label>…</label><div><p>teks</p></div></li>
     */
    private function extractText(DOMElement $li): string
    {
        // Cari <div> yang berisi teks konten
        foreach ($li->childNodes as $node) {
            if (($node instanceof DOMElement) && strtolower($node->nodeName) === 'div') {
                return trim($node->textContent);
            }
        }

        // Fallback: semua teks kecuali <label>
        $text = '';
        foreach ($li->childNodes as $node) {
            if (($node instanceof DOMElement) && strtolower($node->nodeName) === 'label') {
                continue;
            }
            $text .= $node->textContent;
        }
        return trim($text);
    }
}

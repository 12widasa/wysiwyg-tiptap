<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use HTMLPurifier;

class HtmlSanitizer
{
    public function __construct(private readonly HTMLPurifier $purifier) {}

    /**
     * Sanitasi HTML dari editor Tiptap.
     *
     * HTMLPurifier HTML 4.01 tidak bisa menerima struktur task list Tiptap v3
     * (<label><input> + <div> di dalam <li>) karena content model terlalu ketat.
     *
     * Strategi:
     *   1. Ekstrak & sanitasi <ul data-type="taskList"> → simpan sebagai placeholder
     *   2. HTMLPurifier untuk sisa HTML
     *   3. Restore placeholder
     */
    public function sanitize(string $html): string
    {
        // Step 1: Ekstrak & sanitasi task list via DOM — aman untuk nested <ul>
        // Regex tidak dipakai karena `.*?` non-greedy akan berhenti di </ul> pertama
        // yang ditemui, sehingga nested task list akan terpotong.
        $placeholders = [];
        $doc          = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8"><body>' . $html . '</body>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $xpath      = new \DOMXPath($doc);
        $taskLists  = $xpath->query('//ul[@data-type="taskList"]');
        $counter    = 0;

        // Iterasi dari belakang agar index tidak bergeser saat node diganti
        $nodes = iterator_to_array($taskLists);
        foreach (array_reverse($nodes) as $ul) {
            // Lewati jika ini adalah child dari taskList lain (sudah diproses oleh parent-nya)
            if ($xpath->query('ancestor::ul[@data-type="taskList"]', $ul)->length > 0) {
                continue;
            }
            $key          = '%%TASKLIST_' . $counter . '%%';
            $placeholder  = $doc->createTextNode($key);
            $ul->parentNode->replaceChild($placeholder, $ul);
            $placeholders[$key] = $this->sanitizeTaskList($doc->saveHTML($ul));
            $counter++;
        }

        $body = $doc->getElementsByTagName('body')->item(0);
        $html = '';
        foreach ($body->childNodes as $node) {
            $html .= $doc->saveHTML($node);
        }

        // Step 2: Purify sisa HTML
        $clean = $this->purifier->purify($html);

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

        $parts = [];
        foreach ($root->childNodes as $node) {
            if (($node instanceof DOMElement) && strtolower($node->nodeName) === 'ul') {
                $parts[] = $this->buildTaskListHtml($node);
            }
        }

        return implode('', $parts);
    }

    private function buildTaskListHtml(DOMElement $ul): string
    {
        $out = '<ul data-type="taskList">';

        foreach ($ul->childNodes as $node) {
            if (! ($node instanceof DOMElement) || strtolower($node->nodeName) !== 'li') {
                continue;
            }

            $checked = $node->getAttribute('data-checked') === 'true';
            $text    = $this->extractText($node);

            $out .= '<li data-type="taskItem" data-checked="' . ($checked ? 'true' : 'false') . '">';
            $out .= '<label><input type="checkbox"' . ($checked ? ' checked' : '') . ' disabled></label>';
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

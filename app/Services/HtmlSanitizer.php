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
     *   1. Ekstrak <ul data-type="taskList"> → placeholder
     *   2. HTMLPurifier untuk sisa HTML
     *   3. Sanitasi task list manual via DOMDocument
     *   4. Restore placeholder
     */
    public function sanitize(string $html): string
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
            if (($node instanceof DOMElement) && strtolower($node->nodeName) === 'ul') {
                $result .= $this->buildTaskListHtml($node);
            }
        }

        return $result;
    }

    private function buildTaskListHtml(DOMElement $ul): string
    {
        $out = '<ul data-type="taskList">';

        foreach ($ul->childNodes as $node) {
            if (! ($node instanceof DOMElement) || strtolower($node->nodeName) !== 'li') {
                continue;
            }

            $checked   = $node->getAttribute('data-checked') === 'true' ? 'true' : 'false';
            $isChecked = $checked === 'true';
            $text      = $this->extractText($node);

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

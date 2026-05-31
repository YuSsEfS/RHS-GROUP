<?php

namespace App\Services;

use App\Models\AdminEmployeeMessage;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use ZipArchive;

class OfficeAttachmentPreviewService
{
    public function render(AdminEmployeeMessage $message): ?string
    {
        if (!$message->attachment_path || !Storage::disk('local')->exists($message->attachment_path)) {
            return null;
        }

        $path = Storage::disk('local')->path($message->attachment_path);

        if ($message->isWordAttachment()) {
            return $this->renderDocx($path, $message->attachment_original_name ?: 'Document Word');
        }

        if ($message->isSpreadsheetAttachment()) {
            return $this->renderXlsx($path, $message->attachment_original_name ?: 'Classeur Excel');
        }

        return null;
    }

    private function renderDocx(string $path, string $title): ?string
    {
        $phpWordHtml = $this->renderDocxWithPhpWord($path, $title);

        if ($phpWordHtml) {
            return $phpWordHtml;
        }

        $zip = new ZipArchive();

        if ($zip->open($path) !== true) {
            return null;
        }

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if (!$xml) {
            return null;
        }

        $document = simplexml_load_string($xml, null, LIBXML_NONET);

        if (!$document) {
            return null;
        }

        $document->registerXPathNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $body = $document->xpath('//w:body/*') ?: [];
        $html = '<article class="rhs-office-preview rhs-office-docx"><h1>' . e($title) . '</h1>';

        foreach ($body as $node) {
            $name = $node->getName();

            if ($name === 'p') {
                $text = $this->nodeText($node);

                if (trim($text) !== '') {
                    $html .= '<p>' . nl2br(e($text)) . '</p>';
                }
            }

            if ($name === 'tbl') {
                $html .= $this->renderDocxTable($node);
            }
        }

        $content = substr($html, strpos($html, '</h1>') + 5);

        return $this->hasVisibleContent($content) ? $html . '</article>' : null;
    }

    public function fallback(AdminEmployeeMessage $message): string
    {
        $title = $message->attachment_original_name ?: 'Piece jointe';
        $type = $message->isSpreadsheetAttachment() ? 'Excel' : 'Word';

        return '<article class="rhs-office-preview rhs-office-fallback">'
            . '<h1>' . e($title) . '</h1>'
            . '<div class="rhs-office-empty">'
            . '<strong>Apercu ' . e($type) . ' indisponible</strong>'
            . '<p>Le fichier est bien attache, mais son contenu ne peut pas etre converti en apercu dans le navigateur. Utilisez le bouton de telechargement pour l ouvrir dans Office.</p>'
            . '</div>'
            . '</article>';
    }

    private function renderDocxWithPhpWord(string $path, string $title): ?string
    {
        try {
            $phpWord = WordIOFactory::load($path);
            $html = '<article class="rhs-office-preview rhs-office-docx"><h1>' . e($title) . '</h1>';
            $content = '';

            foreach ($phpWord->getSections() as $section) {
                foreach ($section->getElements() as $element) {
                    $content .= $this->renderPhpWordElement($element);
                }
            }

            return $this->hasVisibleContent($content) ? $html . $content . '</article>' : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function renderPhpWordElement($element): string
    {
        if (method_exists($element, 'getRows')) {
            $html = '<table><tbody>';

            foreach ($element->getRows() as $row) {
                $html .= '<tr>';
                foreach ($row->getCells() as $cell) {
                    $cellHtml = '';
                    foreach ($cell->getElements() as $child) {
                        $cellHtml .= $this->renderPhpWordElement($child);
                    }
                    $html .= '<td>' . ($cellHtml ?: '&nbsp;') . '</td>';
                }
                $html .= '</tr>';
            }

            return $html . '</tbody></table>';
        }

        if (method_exists($element, 'getElements')) {
            $html = '';

            foreach ($element->getElements() as $child) {
                $html .= $this->renderPhpWordElement($child);
            }

            return $html;
        }

        if (method_exists($element, 'getText')) {
            $text = $element->getText();

            if (is_string($text) && trim($text) !== '') {
                return '<p>' . nl2br(e($text)) . '</p>';
            }
        }

        return '';
    }

    private function renderDocxTable($table): string
    {
        $rows = $table->xpath('.//*[local-name()="tr"]') ?: [];
        $html = '<table><tbody>';

        foreach ($rows as $row) {
            $html .= '<tr>';
            foreach (($row->xpath('.//*[local-name()="tc"]') ?: []) as $cell) {
                $html .= '<td>' . nl2br(e($this->nodeText($cell))) . '</td>';
            }
            $html .= '</tr>';
        }

        return $html . '</tbody></table>';
    }

    private function renderXlsx(string $path, string $title): ?string
    {
        $zip = new ZipArchive();

        if ($zip->open($path) !== true) {
            return null;
        }

        $sharedStrings = $this->xlsxSharedStrings($zip);
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if (!$sheetXml) {
            return null;
        }

        $sheet = simplexml_load_string($sheetXml, null, LIBXML_NONET);

        if (!$sheet) {
            return null;
        }

        $rows = array_slice($sheet->xpath('//*[local-name()="sheetData"]/*[local-name()="row"]') ?: [], 0, 120);
        $html = '<article class="rhs-office-preview rhs-office-xlsx"><h1>' . e($title) . '</h1><div><table><tbody>';

        foreach ($rows as $row) {
            $html .= '<tr>';
            foreach (($row->xpath('./*[local-name()="c"]') ?: []) as $cell) {
                $html .= '<td>' . e($this->xlsxCellValue($cell, $sharedStrings)) . '</td>';
            }
            $html .= '</tr>';
        }

        $tableHtml = substr($html, strpos($html, '<tbody>') + 7);

        return $this->hasVisibleContent($tableHtml) ? $html . '</tbody></table></div></article>' : null;
    }

    private function xlsxSharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');

        if (!$xml) {
            return [];
        }

        $strings = simplexml_load_string($xml, null, LIBXML_NONET);

        if (!$strings) {
            return [];
        }

        return array_map(fn ($item) => $this->nodeText($item), $strings->xpath('//*[local-name()="si"]') ?: []);
    }

    private function xlsxCellValue($cell, array $sharedStrings): string
    {
        $attributes = $cell->attributes();
        $type = (string) ($attributes['t'] ?? '');

        if ($type === 'inlineStr') {
            return $this->nodeText($cell);
        }

        $value = (string) (($cell->xpath('./*[local-name()="v"]') ?: [])[0] ?? '');

        if ($type === 's') {
            return $sharedStrings[(int) $value] ?? '';
        }

        return $value;
    }

    private function nodeText($node): string
    {
        $pieces = [];

        foreach (($node->xpath('.//*[local-name()="t"]') ?: []) as $textNode) {
            $pieces[] = (string) $textNode;
        }

        return trim(implode('', $pieces));
    }

    private function hasVisibleContent(string $html): bool
    {
        if (str_contains($html, '<img')) {
            return true;
        }

        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace("\xc2\xa0", ' ', $text);

        return trim($text) !== '';
    }
}

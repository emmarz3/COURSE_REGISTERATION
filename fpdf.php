<?php
class FPDF
{
    private array $pages = [];
    private array $currentLines = [];
    private string $fontStyle = '';
    private int $fontSize = 12;
    private float $leftMargin = 40;
    private float $lineHeight = 16;
    private string $title = '';

    public function __construct($orientation = 'P', $unit = 'mm', $size = 'A4')
    {
    }

    public function SetTitle($title)
    {
        $this->title = (string) $title;
    }

    public function AddPage()
    {
        if (!empty($this->currentLines)) {
            $this->pages[] = $this->currentLines;
        }
        $this->currentLines = [];
    }

    public function SetFont($family, $style = '', $size = 12)
    {
        $this->fontStyle = (string) $style;
        $this->fontSize = (int) $size;
    }

    public function SetTextColor($r, $g = null, $b = null)
    {
    }

    public function SetFillColor($r, $g = null, $b = null)
    {
    }

    public function SetDrawColor($r, $g = null, $b = null)
    {
    }

    public function SetLineWidth($width)
    {
    }

    public function Cell($w, $h = 0, $txt = '', $border = 0, $ln = 0, $align = '', $fill = false, $link = '')
    {
        $this->currentLines[] = [
            'text' => $this->cleanText((string) $txt),
            'size' => $this->fontSize,
            'style' => $this->fontStyle,
            'align' => (string) $align,
        ];

        if ($ln > 0) {
            $this->Ln();
        }
    }

    public function MultiCell($w, $h, $txt, $border = 0, $align = 'J', $fill = false)
    {
        $parts = preg_split('/\r\n|\r|\n/', (string) $txt);
        foreach ($parts as $part) {
            $this->Cell($w, $h, $part, $border, 1, $align, $fill);
        }
    }

    public function Ln($h = null)
    {
        $this->currentLines[] = [
            'text' => '',
            'size' => $this->fontSize,
            'style' => $this->fontStyle,
            'align' => '',
        ];
    }

    public function Output($dest = '', $name = '', $isUTF8 = false)
    {
        if (!empty($this->currentLines)) {
            $this->pages[] = $this->currentLines;
            $this->currentLines = [];
        }

        if (empty($this->pages)) {
            $this->pages[] = [];
        }

        $pdf = $this->buildPdf();

        if ($dest === 'D') {
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . ($name ?: 'document.pdf') . '"');
            header('Content-Length: ' . strlen($pdf));
            echo $pdf;
            exit();
        }

        header('Content-Type: application/pdf');
        echo $pdf;
        exit();
    }

    private function buildPdf(): string
    {
        $objects = [];
        $kids = [];
        $pageCount = count($this->pages);
        $catalogId = 1;
        $pagesId = 2;
        $fontId = 3;
        $nextId = 4;

        foreach ($this->pages as $pageLines) {
            $contentId = $nextId++;
            $pageId = $nextId++;
            $kids[] = $pageId . ' 0 R';
            $objects[$contentId] = "<< /Length " . strlen($this->pageStream($pageLines)) . " >>\nstream\n" . $this->pageStream($pageLines) . "\nendstream";
            $objects[$pageId] = "<< /Type /Page /Parent {$pagesId} 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 {$fontId} 0 R >> >> /Contents {$contentId} 0 R >>";
        }

        $objects[$catalogId] = "<< /Type /Catalog /Pages {$pagesId} 0 R >>";
        $objects[$pagesId] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . "] /Count {$pageCount} >>";
        $objects[$fontId] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
        ksort($objects);

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $id => $body) {
            $offsets[$id] = strlen($pdf);
            $pdf .= "{$id} 0 obj\n{$body}\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";

        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }

        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root {$catalogId} 0 R >>\n";
        $pdf .= "startxref\n{$xref}\n%%EOF";

        return $pdf;
    }

    private function pageStream(array $lines): string
    {
        $stream = "BT\n/F1 12 Tf\n";
        $y = 790;

        foreach ($lines as $line) {
            $size = max(8, (int) $line['size']);
            $text = $this->escapePdfText($line['text']);
            $x = $this->leftMargin;

            if (($line['align'] ?? '') === 'C') {
                $x = 297 - (strlen($line['text']) * $size * 0.18);
            } elseif (($line['align'] ?? '') === 'R') {
                $x = 555 - (strlen($line['text']) * $size * 0.35);
            }

            if ($text !== '') {
                $stream .= "/F1 {$size} Tf\n1 0 0 1 {$x} {$y} Tm\n({$text}) Tj\n";
            }

            $y -= $this->lineHeight;
            if ($y < 50) {
                break;
            }
        }

        $stream .= "ET";
        return $stream;
    }

    private function cleanText(string $text): string
    {
        $text = str_replace(["\r", "\n", "\t"], ' ', $text);
        return preg_replace('/[^\x20-\x7E]/', '', $text);
    }

    private function escapePdfText(string $text): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }
}
?>

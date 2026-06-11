<?php

/**
 * Rapports caisse — préparation des données et génération PDF (tableau).
 */

require_once __DIR__ . '/dashboard_helpers.php';
require_once __DIR__ . '/money.php';

/**
 * @return array{
 *   type: string,
 *   titre: string,
 *   filename: string,
 *   totaux: array{ca:float,ca_especes:float,ca_mobile:float,nb:int},
 *   lignes: list<array<string,mixed>>
 * }
 */
function dashboard_report_resolve(PDO $pdo, array $query): array
{
    $type = ($query['type'] ?? 'mensuel') === 'journalier' ? 'journalier' : 'mensuel';
    $period = dashboard_report_parse_period(
        isset($query['annee']) ? (int) $query['annee'] : null,
        isset($query['mois']) ? (int) $query['mois'] : null
    );
    $moisKey = $period['mois_key'];
    $jourNum = isset($query['jour']) ? (int) $query['jour'] : (int) date('j');
    $daysInMonth = (int) date('t', strtotime($moisKey . '-01'));
    $jourNum = max(1, min($daysInMonth, $jourNum));
    $dayYmd = $moisKey . '-' . str_pad((string) $jourNum, 2, '0', STR_PAD_LEFT);

    if ($type === 'journalier') {
        $lignes = dashboard_fetch_factures_lignes($pdo, $moisKey, $dayYmd);
        $totaux = dashboard_sales_totals($pdo, 'day', $dayYmd);
        $titre = 'Rapport journalier — ' . date('d/m/Y', strtotime($dayYmd));
        $filename = 'rapport-journalier-' . $dayYmd . '.pdf';
    } else {
        $lignes = dashboard_fetch_factures_lignes($pdo, $moisKey);
        $totaux = dashboard_sales_totals($pdo, 'month', $moisKey);
        $titre = 'Rapport mensuel — ' . dashboard_report_month_label($period['annee'], $period['mois']);
        $filename = 'rapport-mensuel-' . $moisKey . '.pdf';
    }

    return [
        'type' => $type,
        'titre' => $titre,
        'filename' => $filename,
        'totaux' => $totaux,
        'lignes' => $lignes,
    ];
}

function dashboard_report_pdf_latin1(string $text): string
{
    $text = trim($text);
    if ($text === '') {
        return '';
    }
    $converted = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $text);

    return $converted !== false ? $converted : $text;
}

function dashboard_report_pdf_escape(string $text): string
{
    $text = dashboard_report_pdf_latin1($text);

    return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
}

/**
 * Générateur PDF minimal (tableau, sans dépendance externe).
 */
final class DashboardReportPdf
{
    private const PAGE_W = 595.28;
    private const PAGE_H = 841.89;
    private const MARGIN = 36.0;
    private const ROW_H = 18.0;
    private const HEADER_H = 20.0;
    private const FONT_BODY = 9.0;
    private const FONT_HEADER = 9.0;
    private const FONT_TITLE = 16.0;

    /** @var list<string> */
    private array $pages = [];

    private int $pageIndex = -1;

    private float $y = 0.0;

    /** @var list<float> */
    private array $colWidths;

    /** @var list<string> */
    private array $headers;

    public function __construct(
        private readonly string $title,
        private readonly array $totaux,
        private readonly array $lignes,
        private readonly string $contextLabel = 'Caisse'
    ) {
        $usable = self::PAGE_W - (2 * self::MARGIN);
        $this->colWidths = [98.0, 48.0, 52.0, 108.0, 38.0, 72.0, $usable - (98.0 + 48.0 + 52.0 + 108.0 + 38.0 + 72.0)];
        $this->headers = ['Date et heure', 'Facture', 'Commande', 'Client', 'Table', 'Mode', 'Montant'];
        $this->addPage();
        $this->drawTitleBlock();
        $this->drawTableHeader();
        if ($this->lignes === []) {
            $this->drawEmptyRow();
        } else {
            foreach ($this->lignes as $ligne) {
                $this->drawDataRow($ligne);
            }
        }
    }

    public function outputString(): string
    {
        /** @var list<string> $objects index 0 unused; 1-based ids */
        $objects = [''];
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';

        $pagesId = 3;
        $objects[] = '<< /Type /Pages /Kids [] /Count 0 >>';

        $pageIds = [];
        foreach ($this->pages as $stream) {
            $contentId = count($objects);
            $objects[] = '<< /Length ' . strlen($stream) . " >>\nstream\n" . $stream . "\nendstream";
            $pageId = count($objects);
            $objects[] = '<< /Type /Page /Parent ' . $pagesId . ' 0 R /MediaBox [0 0 '
                . self::PAGE_W . ' ' . self::PAGE_H . '] /Contents ' . $contentId . ' 0 R '
                . '/Resources << /Font << /F1 1 0 R /F2 2 0 R >> >> >>';
            $pageIds[] = $pageId;
        }

        $kids = implode(' ', array_map(static fn (int $id): string => $id . ' 0 R', $pageIds));
        $objects[$pagesId] = '<< /Type /Pages /Kids [' . $kids . '] /Count ' . count($pageIds) . ' >>';

        $catalogId = count($objects);
        $objects[] = '<< /Type /Catalog /Pages ' . $pagesId . ' 0 R >>';

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        for ($i = 1, $n = count($objects); $i < $n; $i++) {
            $offsets[] = strlen($pdf);
            $pdf .= $i . " 0 obj\n" . $objects[$i] . "\nendobj\n";
        }

        $xrefPos = strlen($pdf);
        $size = count($objects);
        $pdf .= "xref\n0 {$size}\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i < $size; $i++) {
            $pdf .= sprintf('%010d 00000 n %s', $offsets[$i], "\n");
        }
        $pdf .= "trailer\n<< /Size {$size} /Root {$catalogId} 0 R >>\n";
        $pdf .= "startxref\n{$xrefPos}\n%%EOF";

        return $pdf;
    }

    private function addPage(): void
    {
        $this->pages[] = '';
        $this->pageIndex++;
        $this->y = self::PAGE_H - self::MARGIN;
    }

    private function ensureSpace(float $height): void
    {
        if ($this->y - $height < self::MARGIN) {
            $this->addPage();
            $this->drawTableHeader();
        }
    }

    private function append(string $content): void
    {
        $this->pages[$this->pageIndex] .= $content;
    }

    private function text(float $x, float $y, float $size, string $text, bool $bold = false): void
    {
        $font = $bold ? '/F2' : '/F1';
        $this->append("BT\n{$font} {$size} Tf\n{$x} {$y} Td\n(" . dashboard_report_pdf_escape($text) . ") Tj\nET\n");
    }

    private function rect(float $x, float $y, float $w, float $h, bool $fill): void
    {
        $this->append(sprintf("%.2F %.2F %.2F %.2F re\n%s\n", $x, $y, $w, $h, $fill ? 'f' : 'S'));
    }

    private function line(float $x1, float $y1, float $x2, float $y2): void
    {
        $this->append("0.75 w\n");
        $this->append(sprintf("%.2F %.2F m %.2F %.2F l S\n", $x1, $y1, $x2, $y2));
    }

    private function drawTitleBlock(): void
    {
        $x = self::MARGIN;
        $this->text($x, $this->y, self::FONT_TITLE, $this->title, true);
        $this->y -= 24;
        $this->text($x, $this->y, self::FONT_BODY, 'DynamoMenu — ' . $this->contextLabel . ' — genere le ' . date('d/m/Y H:i'));
        $this->y -= 20;

        $boxW = (self::PAGE_W - 2 * self::MARGIN - 18) / 4;
        $labels = [
            'CA total' => format_money((float) ($this->totaux['ca'] ?? 0)),
            'Cash' => format_money((float) ($this->totaux['ca_especes'] ?? 0)),
            'Mobile money' => format_money((float) ($this->totaux['ca_mobile'] ?? 0)),
            'Paiements' => (string) (int) ($this->totaux['nb'] ?? 0),
        ];
        $i = 0;
        foreach ($labels as $label => $value) {
            $bx = $x + $i * ($boxW + 6);
            $this->append("0.92 g\n");
            $this->rect($bx, $this->y - 28, $boxW, 32, true);
            $this->append("0 g\n");
            $this->rect($bx, $this->y - 28, $boxW, 32, false);
            $this->text($bx + 6, $this->y - 12, self::FONT_BODY, $label, true);
            $this->text($bx + 6, $this->y - 24, self::FONT_BODY + 1, $value);
            $i++;
        }
        $this->y -= 48;
    }

    private function tableLeft(): float
    {
        return self::MARGIN;
    }

    private function tableWidth(): float
    {
        return array_sum($this->colWidths);
    }

    private function drawTableHeader(): void
    {
        $this->ensureSpace(self::HEADER_H + 4);
        $x = $this->tableLeft();
        $top = $this->y;
        $this->append("0.88 g\n");
        $this->rect($x, $top - self::HEADER_H, $this->tableWidth(), self::HEADER_H, true);
        $this->append("0 g\n");
        $this->rect($x, $top - self::HEADER_H, $this->tableWidth(), self::HEADER_H, false);

        $cx = $x;
        foreach ($this->headers as $idx => $header) {
            $this->text($cx + 4, $top - 13, self::FONT_HEADER, $header, true);
            $cx += $this->colWidths[$idx];
        }
        $this->y = $top - self::HEADER_H;
    }

    private function drawEmptyRow(): void
    {
        $this->ensureSpace(self::ROW_H);
        $x = $this->tableLeft();
        $this->rect($x, $this->y - self::ROW_H, $this->tableWidth(), self::ROW_H, false);
        $this->text($x + 4, $this->y - 12, self::FONT_BODY, 'Aucun paiement');
        $this->y -= self::ROW_H;
    }

    /** @param array<string, mixed> $ligne */
    private function drawDataRow(array $ligne): void
    {
        $this->ensureSpace(self::ROW_H);
        $cells = [
            date('d/m/Y H:i', strtotime((string) $ligne['date_facture'])),
            '#' . (int) $ligne['num_facture'],
            '#' . str_pad((string) $ligne['num_commande'], 5, '0', STR_PAD_LEFT),
            trim(($ligne['prenom_client'] ?? '') . ' ' . ($ligne['nom_client'] ?? '')),
            (string) ($ligne['num_table'] ?? ''),
            dashboard_mode_paiement_label((string) $ligne['mode_paiement']),
            format_money((float) $ligne['total_paye']),
        ];

        $x = $this->tableLeft();
        $top = $this->y;
        $this->rect($x, $top - self::ROW_H, $this->tableWidth(), self::ROW_H, false);

        $cx = $x;
        foreach ($cells as $idx => $cell) {
            $txt = $cell;
            $max = (int) floor($this->colWidths[$idx] / 5.5);
            if (strlen($txt) > $max && $max > 3) {
                $txt = substr($txt, 0, $max - 3) . '...';
            }
            $this->text($cx + 3, $top - 12, self::FONT_BODY, $txt);
            if ($idx > 0) {
                $this->line($cx, $top, $cx, $top - self::ROW_H);
            }
            $cx += $this->colWidths[$idx];
        }
        $this->line($x + $this->tableWidth(), $top, $x + $this->tableWidth(), $top - self::ROW_H);
        $this->y = $top - self::ROW_H;
    }
}

function dashboard_report_pdf_bytes(string $titre, array $totaux, array $lignes, string $contextLabel = 'Caisse'): string
{
    $gen = new DashboardReportPdf($titre, $totaux, $lignes, $contextLabel);

    return $gen->outputString();
}

function dashboard_report_send_pdf(string $pdf, string $filename, bool $inline): void
{
    header('Content-Type: application/pdf');
    header('Content-Length: ' . strlen($pdf));
    $disp = $inline ? 'inline' : 'attachment';
    header('Content-Disposition: ' . $disp . '; filename="' . $filename . '"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    echo $pdf;
    exit;
}

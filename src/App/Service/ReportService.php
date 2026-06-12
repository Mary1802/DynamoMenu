<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\Application;
use App\Repository\AdminStatsRepository;
use App\Repository\FactureRepository;
use DateTime;
use PDO;

final class ReportService
{
    /** @var array<int, string> */
    public const MONTH_NAMES = [
        1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
        5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
        9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
    ];

    public function __construct(
        private readonly PDO $pdo,
        private readonly AdminStatsRepository $stats,
        private readonly FactureRepository $factures,
    ) {
    }

    public static function fromApp(?Application $app = null): self
    {
        $app ??= Application::getInstance();

        return new self($app->db(), $app->adminStatsRepository(), $app->factureRepository());
    }

    /** @return array{annee:int,mois:int,mois_key:string} */
    public function parsePeriod(?int $annee, ?int $mois): array
    {
        $annee = $annee ?? (int) date('Y');
        $mois = $mois ?? (int) date('n');
        $annee = max(2020, min(2100, $annee));
        $mois = max(1, min(12, $mois));

        return [
            'annee' => $annee,
            'mois' => $mois,
            'mois_key' => $this->monthKey($annee, $mois),
        ];
    }

    public function monthKey(int $annee, int $mois): string
    {
        return sprintf('%04d-%02d', $annee, max(1, min(12, $mois)));
    }

    public function monthLabel(int $annee, int $mois): string
    {
        $noms = [
            1 => 'janvier', 2 => 'février', 3 => 'mars', 4 => 'avril',
            5 => 'mai', 6 => 'juin', 7 => 'juillet', 8 => 'août',
            9 => 'septembre', 10 => 'octobre', 11 => 'novembre', 12 => 'décembre',
        ];

        return ($noms[$mois] ?? '') . ' ' . $annee;
    }

    /**
     * @param array<string, mixed> $get
     * @return array{
     *   annee:int,
     *   moisNum:int,
     *   moisKey:string,
     *   moisLabel:string,
     *   daysInMonth:int,
     *   jourExport:int,
     *   exportBase:string,
     *   annees:list<int>
     * }
     */
    public function parseFilter(array $get): array
    {
        if (!empty($get['mois']) && preg_match('/^\d{4}-\d{2}$/', (string) $get['mois'])) {
            $dateMois = DateTime::createFromFormat('Y-m', (string) $get['mois']);
            $annee = $dateMois ? (int) $dateMois->format('Y') : (int) date('Y');
            $moisNum = $dateMois ? (int) $dateMois->format('n') : (int) date('n');
        } else {
            $period = $this->parsePeriod(
                isset($get['annee']) ? (int) $get['annee'] : null,
                isset($get['mois']) ? (int) $get['mois'] : null
            );
            $annee = $period['annee'];
            $moisNum = $period['mois'];
        }

        $moisKey = $this->monthKey($annee, $moisNum);
        $daysInMonth = (int) date('t', strtotime($moisKey . '-01'));
        $jourExport = isset($get['jour']) ? (int) $get['jour'] : min((int) date('j'), $daysInMonth);
        if ($moisKey !== date('Y-m')) {
            $jourExport = min($jourExport, $daysInMonth);
        }
        $jourExport = max(1, min($daysInMonth, $jourExport));

        return [
            'annee' => $annee,
            'moisNum' => $moisNum,
            'moisKey' => $moisKey,
            'moisLabel' => $this->monthLabel($annee, $moisNum),
            'daysInMonth' => $daysInMonth,
            'jourExport' => $jourExport,
            'exportBase' => 'annee=' . $annee . '&mois=' . $moisNum,
            'annees' => range((int) date('Y') - 2, (int) date('Y') + 1),
        ];
    }

    /**
     * @param array<string, mixed> $get
     * @return array<string, mixed>
     */
    public function buildIndexData(array $get, bool $includeDailySummary = false): array
    {
        $filter = $this->parseFilter($get);
        $rapportMois = $this->stats->salesTotals('month', $filter['moisKey']);
        $lignesMois = $this->factures->fetchReportLines($filter['moisKey']);

        $data = array_merge($filter, [
            'rapport_mois' => $rapportMois,
            'lignes_mois' => $lignesMois,
        ]);

        if ($includeDailySummary) {
            $data['rapport_jour'] = $this->stats->salesTotals('day', date('Y-m-d'));
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $query
     * @return array{type:string,titre:string,filename:string,totaux:array<string,mixed>,lignes:list<array<string,mixed>>}
     */
    public function resolveReport(array $query): array
    {
        $type = ($query['type'] ?? 'mensuel') === 'journalier' ? 'journalier' : 'mensuel';
        $period = $this->parsePeriod(
            isset($query['annee']) ? (int) $query['annee'] : null,
            isset($query['mois']) ? (int) $query['mois'] : null
        );
        $moisKey = $period['mois_key'];
        $jourNum = isset($query['jour']) ? (int) $query['jour'] : (int) date('j');
        $daysInMonth = (int) date('t', strtotime($moisKey . '-01'));
        $jourNum = max(1, min($daysInMonth, $jourNum));
        $dayYmd = $moisKey . '-' . str_pad((string) $jourNum, 2, '0', STR_PAD_LEFT);

        if ($type === 'journalier') {
            $lignes = $this->factures->fetchReportLines($moisKey, $dayYmd);
            $totaux = $this->stats->salesTotals('day', $dayYmd);
            $titre = 'Rapport journalier — ' . date('d/m/Y', strtotime($dayYmd));
            $filename = 'rapport-journalier-' . $dayYmd . '.pdf';
        } else {
            $lignes = $this->factures->fetchReportLines($moisKey);
            $totaux = $this->stats->salesTotals('month', $moisKey);
            $titre = 'Rapport mensuel — ' . $this->monthLabel($period['annee'], $period['mois']);
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

    /** @param array<string, mixed> $get */
    public function sendPdf(array $get, string $contextLabel, bool $inline): never
    {
        $report = $this->resolveReport($get);
        $pdf = ReportPdfGenerator::generate(
            $report['titre'],
            $report['totaux'],
            $report['lignes'],
            $contextLabel
        );

        header('Content-Type: application/pdf');
        header('Content-Length: ' . strlen($pdf));
        $disp = $inline ? 'inline' : 'attachment';
        header('Content-Disposition: ' . $disp . '; filename="' . $report['filename'] . '"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        echo $pdf;
        exit;
    }
}

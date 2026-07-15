<?php

declare(strict_types=1);

use App\Http\Dashboard;
use App\Support\Money;

$contacts = Dashboard::contacts();
$restoNom = trim((string) ($contacts['nom'] ?? 'DynamoMenu')) ?: 'DynamoMenu';
$restoAdresse = trim((string) ($contacts['adresse'] ?? ''));
$restoTel = trim((string) ($contacts['telephone'] ?? ''));
$restoEmail = trim((string) ($contacts['email'] ?? ''));
$clientNom = trim(($facture['prenom_client'] ?? '') . ' ' . ($facture['nom_client'] ?? ''));
$modeLabel = Dashboard::modePaiementLabel((string) ($facture['mode_paiement'] ?? 'especes'));
$numFacturePad = str_pad((string) $num_facture, 4, '0', STR_PAD_LEFT);
$numCmdPad = str_pad((string) $facture['num_commande'], 5, '0', STR_PAD_LEFT);
$dateFacture = date('d/m/Y H:i', strtotime((string) $facture['date_facture']));
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ticket F-<?php echo $numFacturePad; ?> - DynamoMenu</title>
    <style>
        @page {
            /* Format ticket thermique 80 mm — la hauteur est recalculée avant impression */
            size: 80mm 200mm;
            margin: 0;
        }

        * { box-sizing: border-box; }

        html {
            /* l’écran reste plein ; l’impression force 80 mm via @page / JS */
        }

        body {
            margin: 0;
            padding: 1rem;
            background: #1a1a1a;
            color: #111;
            font-family: "Courier New", Courier, monospace;
            display: flex;
            justify-content: center;
        }

        .screen-only {
            position: fixed;
            z-index: 1000;
            border: none;
            padding: 0.55rem 1rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 700;
            font-family: system-ui, sans-serif;
            font-size: 0.85rem;
        }

        .print-btn {
            top: 16px;
            right: 16px;
            background: #ff6f1f;
            color: #fff;
        }

        .back-btn {
            top: 16px;
            left: 16px;
            background: #6c757d;
            color: #fff;
        }

        .ticket-stage {
            display: block;
            width: 80mm;
            max-width: 80mm;
            padding: 3.5rem 0 2rem;
            margin: 0;
        }

        /* Ticket thermique type supermarché : étroit et long */
        .ticket {
            width: 80mm;
            max-width: 80mm;
            background: #fff;
            color: #111;
            padding: 10px 8px 16px;
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.45);
        }

        .ticket-center { text-align: center; }
        .ticket-left { text-align: left; }
        .ticket-right { text-align: right; }

        .ticket-brand {
            font-size: 1.15rem;
            font-weight: 900;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            margin: 0 0 4px;
            line-height: 1.2;
        }

        .ticket-sub {
            font-size: 0.72rem;
            line-height: 1.35;
            margin: 0;
            word-break: break-word;
        }

        .ticket-title {
            font-size: 0.95rem;
            font-weight: 900;
            letter-spacing: 0.12em;
            margin: 8px 0 2px;
        }

        .divider {
            border: none;
            border-top: 1px dashed #222;
            margin: 8px 0;
        }

        .divider-double {
            border: none;
            border-top: 2px solid #111;
            margin: 8px 0;
        }

        .meta {
            font-size: 0.72rem;
            line-height: 1.4;
        }

        .meta-row {
            display: flex;
            justify-content: space-between;
            gap: 0.5rem;
        }

        .lines {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.72rem;
        }

        .lines td {
            padding: 3px 0;
            vertical-align: top;
            border: none;
        }

        .line-name {
            word-break: break-word;
            padding-right: 6px;
        }

        .line-name small {
            display: block;
            font-size: 0.65rem;
            opacity: 0.75;
        }

        .line-qty {
            white-space: nowrap;
            padding-right: 4px;
            width: 1%;
        }

        .line-price {
            white-space: nowrap;
            text-align: right;
            width: 1%;
            font-weight: 700;
        }

        .totals {
            font-size: 0.75rem;
        }

        .totals-row {
            display: flex;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 2px 0;
        }

        .totals-row.grand {
            font-size: 0.92rem;
            font-weight: 900;
            margin-top: 4px;
            padding-top: 6px;
            border-top: 1px solid #111;
        }

        .totals-row.payable {
            font-size: 1.02rem;
            font-weight: 900;
            margin-top: 6px;
            padding-top: 6px;
            border-top: 2px solid #111;
        }

        .thanks {
            font-size: 0.78rem;
            font-weight: 700;
            margin: 10px 0 4px;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .barcode-fake {
            margin: 10px auto 4px;
            height: 36px;
            width: 78%;
            background: repeating-linear-gradient(
                90deg,
                #111 0 2px,
                transparent 2px 4px,
                #111 4px 5px,
                transparent 5px 8px,
                #111 8px 11px,
                transparent 11px 13px
            );
        }

        .barcode-num {
            font-size: 0.68rem;
            letter-spacing: 0.15em;
        }

        @media print {
            .screen-only { display: none !important; }

            html, body {
                width: 80mm !important;
                max-width: 80mm !important;
                min-width: 80mm !important;
                margin: 0 !important;
                padding: 0 !important;
                background: #fff !important;
                overflow: visible !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .ticket-stage {
                width: 80mm !important;
                max-width: 80mm !important;
                margin: 0 !important;
                padding: 0 !important;
                display: block !important;
            }

            .ticket {
                width: 80mm !important;
                max-width: 80mm !important;
                margin: 0 !important;
                padding: 3mm 2.5mm 5mm !important;
                box-shadow: none !important;
                page-break-after: avoid;
                page-break-inside: avoid;
            }
        }

        @media screen and (max-width: 420px) {
            html, body, .ticket-stage, .ticket {
                width: 100%;
                max-width: 100%;
            }
            body { padding: 0.5rem; }
            .ticket-stage { padding-top: 4rem; }
        }
    </style>
</head>
<body>
    <button type="button" class="screen-only back-btn" onclick="window.location.replace('paiement.php')">← Retour</button>
    <button type="button" class="screen-only print-btn" onclick="printTicket()">Imprimer le ticket</button>

    <div class="ticket-stage">
        <div class="ticket">
            <div class="ticket-center">
                <h1 class="ticket-brand"><?php echo htmlspecialchars($restoNom); ?></h1>
                <?php if ($restoAdresse !== ''): ?>
                <p class="ticket-sub"><?php echo htmlspecialchars($restoAdresse); ?></p>
                <?php endif; ?>
                <?php if ($restoTel !== ''): ?>
                <p class="ticket-sub">Tél. <?php echo htmlspecialchars($restoTel); ?></p>
                <?php endif; ?>
                <?php if ($restoEmail !== ''): ?>
                <p class="ticket-sub"><?php echo htmlspecialchars($restoEmail); ?></p>
                <?php endif; ?>
                <p class="ticket-title">TICKET DE CAISSE</p>
            </div>

            <hr class="divider">

            <div class="meta">
                <div class="meta-row"><span>Ticket</span><span>F-<?php echo htmlspecialchars($numFacturePad); ?></span></div>
                <div class="meta-row"><span>Commande</span><span>#<?php echo htmlspecialchars($numCmdPad); ?></span></div>
                <div class="meta-row"><span>Date</span><span><?php echo htmlspecialchars($dateFacture); ?></span></div>
                <div class="meta-row"><span>Table</span><span><?php echo htmlspecialchars((string) ($facture['num_table'] ?? '—')); ?></span></div>
                <?php if ($clientNom !== ''): ?>
                <div class="meta-row"><span>Client</span><span><?php echo htmlspecialchars($clientNom); ?></span></div>
                <?php endif; ?>
                <?php if (!empty($facture['telephone_client'])): ?>
                <div class="meta-row"><span>Tél.</span><span><?php echo htmlspecialchars((string) $facture['telephone_client']); ?></span></div>
                <?php endif; ?>
            </div>

            <hr class="divider-double">

            <table class="lines">
                <tbody>
                <?php foreach ($articles as $article):
                    $name = '';
                    $extra = '';
                    if (!empty($article['nom_plat'])) {
                        $name = (string) $article['nom_plat'];
                        if (!empty($article['sauces'])) {
                            $extra = 'Sauces: ' . (string) $article['sauces'];
                        }
                    } elseif (!empty($article['nom_boisson'])) {
                        $name = (string) $article['nom_boisson'];
                        if (!empty($article['personnalisation_boisson'])) {
                            $extra = (string) $article['personnalisation_boisson'];
                        }
                    } else {
                        $name = 'Article';
                    }
                    $qty = (int) ($article['quantite'] ?? 0);
                    ?>
                    <tr>
                        <td class="line-qty"><?php echo $qty; ?>x</td>
                        <td class="line-name">
                            <?php echo htmlspecialchars($name); ?>
                            <?php if ($extra !== ''): ?>
                            <small><?php echo htmlspecialchars($extra); ?></small>
                            <?php endif; ?>
                        </td>
                        <td class="line-price"><?php echo Money::format((float) $article['sous_total']); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <hr class="divider">

            <?php
            $totalTtcAffiche = isset($total_ttc) ? (float) $total_ttc : (float) ($facture['montant_total'] ?? $facture['total_paye']);
            $totalAPayerAffiche = isset($total_a_payer) ? (float) $total_a_payer : Money::roundPayable($totalTtcAffiche);
            ?>
            <div class="totals">
                <div class="totals-row">
                    <span>TOTAL HT</span>
                    <span><?php echo Money::format((float) $ht); ?></span>
                </div>
                <div class="totals-row">
                    <span>TVA 16 %</span>
                    <span><?php echo Money::format((float) $tva); ?></span>
                </div>
                <div class="totals-row grand">
                    <span>TOTAL TTC</span>
                    <span><?php echo Money::format($totalTtcAffiche); ?></span>
                </div>
                <div class="totals-row payable">
                    <span>TOTAL À PAYER</span>
                    <span><?php echo Money::format($totalAPayerAffiche); ?></span>
                </div>
            </div>

            <hr class="divider">

            <div class="meta">
                <div class="meta-row">
                    <span>Paiement</span>
                    <span><?php echo htmlspecialchars($modeLabel); ?></span>
                </div>
                <div class="meta-row">
                    <span>Articles</span>
                    <span><?php echo count($articles); ?></span>
                </div>
            </div>

            <hr class="divider-double">

            <div class="ticket-center">
                <p class="thanks">Merci de votre visite</p>
                <p class="ticket-sub">Conservez ce ticket</p>
                <div class="barcode-fake" aria-hidden="true"></div>
                <p class="barcode-num">F<?php echo htmlspecialchars($numFacturePad); ?><?php echo htmlspecialchars($numCmdPad); ?></p>
                <p class="ticket-sub" style="margin-top:8px;">Bon appétit !</p>
            </div>
        </div>
    </div>

    <script>
        function applyTicketPageSize() {
            var ticket = document.querySelector('.ticket');
            if (!ticket) {
                return;
            }

            // Mesure hors écran sombre : largeur forcée 80 mm
            var prev = ticket.style.width;
            ticket.style.width = '80mm';
            var heightPx = Math.ceil(ticket.scrollHeight || ticket.getBoundingClientRect().height);
            ticket.style.width = prev;

            // px → mm (96 dpi CSS)
            var heightMm = Math.max(80, Math.ceil(heightPx * 25.4 / 96) + 6);

            var style = document.getElementById('print-page-size');
            if (!style) {
                style = document.createElement('style');
                style.id = 'print-page-size';
                document.head.appendChild(style);
            }
            style.textContent =
                '@page { size: 80mm ' + heightMm + 'mm !important; margin: 0 !important; }' +
                '@media print { html, body, .ticket-stage, .ticket { width: 80mm !important; max-width: 80mm !important; } }';
        }

        function printTicket() {
            applyTicketPageSize();
            window.print();
        }

        window.addEventListener('beforeprint', applyTicketPageSize);

        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('print')) {
            applyTicketPageSize();
            setTimeout(function () { window.print(); }, 150);
        }

        (function () {
            var facture = urlParams.get('facture');
            if (!facture) {
                return;
            }

            var cleanUrl = 'generer_facture.php?facture=' + encodeURIComponent(facture);
            if (urlParams.has('encaisse') && window.history.replaceState) {
                history.replaceState({ caisseEncaisse: true }, document.title, cleanUrl);
            }

            window.addEventListener('pageshow', function (event) {
                if (event.persisted) {
                    window.location.replace('paiement.php');
                }
            });
        })();
    </script>
</body>
</html>

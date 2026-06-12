<?php

require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/../includes/staff_auth.php';
require_once __DIR__ . '/../services/qr_service.php';

use App\Controller\Admin\TableController;

staff_require(['admin']);

$result = (new TableController())->printStickers($_GET);
if ($result === null) {
    header('Location: tables.php');
    exit;
}

$stickers = $result['stickers'];
$printAll = $result['printAll'];
$pageTitle = $printAll
    ? 'QR codes — toutes les tables'
    : 'QR code — ' . ($stickers[0]['label'] ?? 'table');
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($pageTitle); ?> — DynamoMenu</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 20px;
            font-family: Arial, Helvetica, sans-serif;
            color: #1a1a2e;
            background: #eef1f5;
        }

        .screen-only {
            position: fixed;
            z-index: 1000;
            border: none;
            padding: 10px 18px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.95rem;
        }

        .print-btn {
            top: 20px;
            right: 20px;
            background: #ff6f1f;
            color: #fff;
        }

        .print-btn:hover {
            background: #ff8a3d;
        }

        .back-btn {
            top: 20px;
            left: 20px;
            background: #6c757d;
            color: #fff;
        }

        .back-btn:hover {
            background: #5a6268;
        }

        .print-intro {
            max-width: 720px;
            margin: 70px auto 24px;
            padding: 16px 20px;
            background: #fff;
            border-radius: 10px;
            border: 1px solid #dde3ea;
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .qr-print-sheet {
            max-width: 720px;
            margin: 0 auto;
        }

        .qr-sticker {
            background: #fff;
            border: 2px dashed #cbd5e1;
            border-radius: 12px;
            padding: 14mm 10mm;
            margin: 0 auto 16px;
            text-align: center;
            page-break-inside: avoid;
        }

        .qr-sticker-brand {
            font-size: 1.35rem;
            font-weight: 700;
            color: #ff6f1f;
            letter-spacing: 0.02em;
            margin: 0 0 4mm;
        }

        .qr-sticker-brand span {
            color: #1a1a2e;
        }

        .qr-sticker-table {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0 0 2mm;
            color: #1a1a2e;
        }

        .qr-sticker-meta {
            font-size: 0.85rem;
            color: #64748b;
            margin: 0 0 6mm;
        }

        .qr-sticker-img-wrap {
            display: inline-block;
            padding: 4mm;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            margin-bottom: 5mm;
        }

        .qr-sticker-img {
            display: block;
            width: 70mm;
            height: 70mm;
            max-width: 100%;
        }

        .qr-sticker-hint {
            font-size: 1rem;
            font-weight: 600;
            color: #334155;
            margin: 0 0 2mm;
        }

        .qr-sticker-sub {
            font-size: 0.78rem;
            color: #94a3b8;
            margin: 0;
            word-break: break-all;
        }

        .qr-sticker-inactive {
            display: inline-block;
            margin-top: 3mm;
            padding: 2px 8px;
            border-radius: 999px;
            background: #fee2e2;
            color: #b91c1c;
            font-size: 0.75rem;
            font-weight: 600;
        }

        @media print {
            .screen-only,
            .print-intro {
                display: none !important;
            }

            body {
                padding: 0;
                background: #fff;
            }

            .qr-print-sheet {
                max-width: none;
                margin: 0;
            }

            .qr-sticker {
                border: 1px solid #cbd5e1;
                border-radius: 0;
                margin: 0;
                min-height: calc(100vh - 24mm);
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                page-break-after: always;
            }

            .qr-sticker:last-child {
                page-break-after: auto;
            }

            .qr-sticker-img {
                width: 75mm;
                height: 75mm;
            }
        }
    </style>
</head>
<body>
    <button type="button" class="screen-only back-btn" onclick="window.location.href='tables.php'">← Retour aux tables</button>
    <button type="button" class="screen-only print-btn" onclick="window.print()">🖨️ Imprimer</button>

    <div class="print-intro screen-only">
        <strong>Impression des QR codes</strong> — Chaque table est sur une page séparée, prête à découper et coller.
        Vérifiez l'aperçu avant d'imprimer. Format conseillé : A4, marges par défaut.
    </div>

    <div class="qr-print-sheet">
        <?php foreach ($stickers as $sticker): ?>
        <article class="qr-sticker">
            <p class="qr-sticker-brand">Dynamo<span>Menu</span></p>
            <h1 class="qr-sticker-table"><?php echo htmlspecialchars($sticker['label']); ?></h1>
            <p class="qr-sticker-meta">
                Table n°<?php echo (int) $sticker['num_table']; ?>
                · <?php echo (int) $sticker['places']; ?> place(s)
            </p>
            <div class="qr-sticker-img-wrap">
                <img
                    src="<?php echo htmlspecialchars($sticker['qr_img']); ?>"
                    alt="QR code <?php echo htmlspecialchars($sticker['label']); ?>"
                    class="qr-sticker-img"
                    width="480"
                    height="480"
                >
            </div>
            <p class="qr-sticker-hint">Scannez pour commander</p>
            <p class="qr-sticker-sub"><?php echo htmlspecialchars($sticker['code']); ?></p>
            <?php if (!$sticker['actif']): ?>
            <span class="qr-sticker-inactive">Table inactive</span>
            <?php endif; ?>
        </article>
        <?php endforeach; ?>
    </div>

    <script>
        const params = new URLSearchParams(window.location.search);
        if (params.has('print')) {
            window.addEventListener('load', () => window.print());
        }
    </script>
</body>
</html>

<?php

declare(strict_types=1);

use App\Http\ClientPage;
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bienvenue - DynamoMenu</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <?php ClientPage::csrfMetaTag(); ?>
    <style>
        body {
            background: radial-gradient(circle at top left, rgba(255,111,31,0.14), transparent 22%),
                        linear-gradient(180deg, #071119 0%, #0f172a 50%, #111827 100%);
            color: #f8fafc;
            min-height: 100vh;
        }
        .identite-container {
            max-width: 640px;
            margin: 0 auto;
            padding: 2.5rem 1rem 4rem;
        }
        .identite-card {
            background: rgba(15, 23, 42, 0.94);
            border-radius: 24px;
            border: 1px solid rgba(255,111,31,0.15);
            box-shadow: 0 24px 80px rgba(0,0,0,0.35);
            padding: 2rem;
        }
        .form-label { font-weight: 600; color: #e2e8f0; margin-bottom: 0.5rem; }
        .form-label.required::after { content: ' *'; color: #f87171; }
        .form-control {
            padding: 0.85rem 1rem;
            border: 1px solid rgba(148,163,184,0.24);
            border-radius: 14px;
            background: rgba(15, 23, 42, 0.8);
            color: #e2e8f0;
        }
        .form-control:focus {
            border-color: #ff6f1f;
            box-shadow: 0 0 0 4px rgba(255,111,31,0.12);
            background: rgba(15, 23, 42, 0.8);
            color: #e2e8f0;
        }
        .btn-continue {
            background: linear-gradient(135deg, #ff6f1f, #ff8a3d);
            border: none;
            border-radius: 14px;
            color: white;
            padding: 1rem 2rem;
            font-weight: 700;
            width: 100%;
        }
        .table-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 999px;
            background: rgba(255,111,31,0.12);
            border: 1px solid rgba(255,111,31,0.25);
            color: #f8fafc;
            margin-bottom: 1.5rem;
        }
        .error-message {
            background: rgba(248,215,218,0.18);
            color: #f8d7da;
            padding: 1rem;
            border-radius: 14px;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(248,215,218,0.35);
        }
        .fidele-box {
            background: rgba(255,111,31,0.08);
            border: 1px solid rgba(255,111,31,0.22);
            border-radius: 16px;
            padding: 1rem 1.15rem;
        }
        .fidele-box .form-check-label {
            color: #f8fafc;
            font-weight: 600;
        }
        .fidele-fields.d-none { display: none !important; }
    </style>
    <link rel="stylesheet" href="../assets/css/client-luxury.css?v=16">
    <link rel="stylesheet" href="../assets/css/client-pages-theme.css?v=1">
</head>
<body class="client-site client-luxury">
    <div class="identite-container">
        <div class="text-center mb-4">
            <a href="<?php echo htmlspecialchars($indexUrl); ?>" class="text-decoration-none">
                <span class="client-brand-accent">Dynamo</span><span class="client-brand-name client-brand-name--dark">Menu</span>
            </a>
        </div>

        <div class="identite-card">
            <div class="table-badge">
                <i class="bi bi-table" aria-hidden="true"></i>
                <?php echo htmlspecialchars($tableCtx['label']); ?>
            </div>

            <h1 class="h3 mb-2">Vos informations</h1>
            <p class="text-secondary mb-4">
                <?php if (!empty($isCheckout)): ?>
                Avant de valider votre commande, indiquez au moins votre nom et prénom.
                <?php else: ?>
                Indiquez au moins votre nom et prénom pour démarrer votre commande.
                <?php endif; ?>
            </p>

            <?php if ($error !== null): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" id="identite-form">
                <?php ClientPage::csrfField(); ?>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label required" for="nom">Nom</label>
                        <input type="text" id="nom" name="nom" class="form-control" required
                               value="<?php echo htmlspecialchars($nom); ?>" autocomplete="family-name">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required" for="prenom">Prénom</label>
                        <input type="text" id="prenom" name="prenom" class="form-control" required
                               value="<?php echo htmlspecialchars($prenom); ?>" autocomplete="given-name">
                    </div>

                    <div class="col-12">
                        <div class="fidele-box">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="1" id="client_fidele" name="client_fidele"
                                    <?php echo !empty($fidele) ? ' checked' : ''; ?>>
                                <label class="form-check-label" for="client_fidele">
                                    Voulez-vous être un client fidèle ?
                                </label>
                            </div>
                            <p class="text-secondary small mb-0 mt-2">
                                Oui → nous enregistrons votre téléphone (obligatoire) pour vous reconnaître lors de vos prochaines visites. L’e-mail reste facultatif.
                            </p>
                        </div>
                    </div>
                </div>

                <div id="fidele-fields" class="row g-3 mt-1 fidele-fields<?php echo !empty($fidele) ? '' : ' d-none'; ?>">
                    <div class="col-md-6">
                        <label class="form-label required" for="telephone">Téléphone</label>
                        <input type="tel" id="telephone" name="telephone" class="form-control"
                               value="<?php echo htmlspecialchars($telephone); ?>" autocomplete="tel"
                               inputmode="tel" minlength="10" maxlength="13"
                               pattern="(\+[0-9]{9,12}|0[0-9]{9}|[1-9][0-9]{9,12})"
                               title="10 à 13 caractères : local (0812345678) ou avec indicatif (+243812345678)"
                               <?php echo !empty($fidele) ? ' required' : ''; ?>>
                        <div class="form-text text-secondary small mt-1">
                            10 à 13 caractères — local (ex. 0812345678) ou international (ex. +243812345678)
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="email">E-mail <span class="text-secondary fw-normal">(facultatif)</span></label>
                        <input type="email" id="email" name="email" class="form-control"
                               value="<?php echo htmlspecialchars($email); ?>" autocomplete="email">
                    </div>
                </div>

                <button type="submit" name="enregistrer_identite" class="btn-continue mt-4">
                    <?php echo !empty($isCheckout) ? 'Continuer vers la validation' : 'Continuer'; ?>
                </button>
            </form>
        </div>
    </div>
    <script src="../assets/js/csrf.js?v=1"></script>
    <script>
    (function () {
        var fideleCheck = document.getElementById('client_fidele');
        var fideleFields = document.getElementById('fidele-fields');
        var phone = document.getElementById('telephone');
        var email = document.getElementById('email');

        function syncFideleFields() {
            var on = !!(fideleCheck && fideleCheck.checked);
            if (fideleFields) {
                fideleFields.classList.toggle('d-none', !on);
            }
            if (phone) {
                phone.required = on;
                if (!on) {
                    phone.value = '';
                }
            }
            if (email && !on) {
                email.value = '';
            }
        }

        if (fideleCheck) {
            fideleCheck.addEventListener('change', syncFideleFields);
            syncFideleFields();
        }

        if (phone) {
            function limitPhone() {
                var raw = this.value.replace(/[^\d+]/g, '');
                var hasLeadingPlus = raw.charAt(0) === '+';
                var digits = raw.replace(/\+/g, '');
                if (hasLeadingPlus) {
                    this.value = '+' + digits.slice(0, 12);
                    this.maxLength = 13;
                } else if (digits.charAt(0) === '0') {
                    this.value = digits.slice(0, 10);
                    this.maxLength = 10;
                } else {
                    this.value = digits.slice(0, 13);
                    this.maxLength = 13;
                }
            }
            phone.addEventListener('input', limitPhone);
            limitPhone.call(phone);
        }

        window.addEventListener('pageshow', function (e) {
            if (e.persisted) {
                window.location.replace('index.php');
            }
        });
    })();
    </script>
</body>
</html>

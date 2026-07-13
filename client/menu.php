<?php
require_once __DIR__ . '/../bootstrap/app.php';

use App\Http\ClientPage;
use App\Http\Kernel;
use App\Support\Money;

$result = Kernel::forFile(__FILE__);
if ($result !== null) {
    extract($result, EXTR_SKIP);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DynamoMenu - Menu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/style.css?v=11">
    <link rel="stylesheet" href="../assets/css/client-luxury.css?v=18">
    <?php ClientPage::csrfMetaTag(); ?>
    <style>
        a.position-fixed .floating-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #c5a059;
            color: #0a0a0a;
            border-radius: 999px;
            padding: 0.18rem 0.42rem;
            font-size: 0.72rem;
            font-weight: 700;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.25);
        }
        .add-cart.in-cart { opacity: 0.45; pointer-events: none; }
        .soda-unit-row { margin-bottom: 0.5rem; }
    </style>
</head>
<body class="client-site client-luxury">
    <?php ClientPage::nav('menu'); ?>

    <main class="container-fluid px-3 px-md-4 client-main-menu">
        <section class="lux-menu-hero">
            <h1 class="lux-menu-hero__title">MENU</h1>
            <div id="categories" class="d-flex justify-content-center flex-wrap gap-2"></div>
        </section>

        <section id="menuList" class="menu-grid mb-5" aria-live="polite"></section>

        <!-- Toast container -->
        <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1080">
            <div id="cartToast" class="toast align-items-center text-bg-dark border-0" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body" id="cartToastBody">Article ajouté au panier</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        </div>

        <!-- Retour en haut -->
        <button type="button" id="scrollTopBtn" class="client-scroll-top" aria-label="Retour en haut de la page" title="Retour en haut">
            <i class="bi bi-arrow-up" aria-hidden="true"></i>
        </button>

        <!-- Floating quick cart -->
                <a href="<?php echo htmlspecialchars(ClientPage::tableLink('panier.php')); ?>" class="client-fab-cart position-fixed d-flex align-items-center justify-content-center rounded-circle" style="width:56px;height:56px;right:20px;bottom:20px;z-index:1070;">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-cart" viewBox="0 0 16 16">
              <path d="M0 1.5A.5.5 0 0 1 .5 1h1a.5.5 0 0 1 .485.379L2.89 5H14.5a.5.5 0 0 1 .49.598l-1.5 6A.5.5 0 0 1 13 12H4a.5.5 0 0 1-.491-.408L1.01 1.607 1 1.5H.5zM5 12a1 1 0 1 0 0 2 1 1 0 0 0 0-2zm6 0a1 1 0 1 0 0 2 1 1 0 0 0 0-2z"/>
            </svg>
                        <span id="floatingCartCount" class="floating-badge">0</span>
        </a>

        <div class="text-center pb-4">
            <a class="btn btn-primary btn-lg px-5" href="<?php echo htmlspecialchars(ClientPage::tableLink('panier.php')); ?>">Voir mon panier</a>
        </div>
    </main>

    <?php ClientPage::footer(); ?>

    <!-- Modal boisson fruit -->
    <div class="modal fade drink-modal" id="fruitDrinkModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title" id="fruitDrinkTitle">Personnaliser</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-secondary small mb-3">Choisissez la saveur (fruit) :</p>
                    <div class="flavor-pick mb-3" id="fruitFlavorList"></div>
                    <label class="form-label">Quantité</label>
                    <input type="number" class="form-control" id="fruitQty" min="1" max="20" value="1">
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-primary" id="fruitDrinkConfirm">Ajouter au panier</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal sodas -->
    <div class="modal fade drink-modal" id="sodaDrinkModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title">Coca-Cola, Fanta ou Sprite</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">Quantité</label>
                    <input type="number" class="form-control mb-3" id="sodaQty" min="1" max="12" value="1">
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="sodaSameFlavor" checked>
                        <label class="form-check-label" for="sodaSameFlavor">Même saveur pour toutes les unités</label>
                    </div>
                    <div id="sodaSameBlock">
                        <label class="form-label">Saveur</label>
                        <select class="form-select" id="sodaSingleFlavor">
                            <option value="Coca-Cola">Coca-Cola</option>
                            <option value="Fanta">Fanta</option>
                            <option value="Sprite">Sprite</option>
                        </select>
                    </div>
                    <div id="sodaMultiBlock" style="display:none;">
                        <label class="form-label">Saveur par unité</label>
                        <div id="sodaUnitsContainer"></div>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-primary" id="sodaDrinkConfirm">Ajouter au panier</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function () {
            const nav = document.querySelector('.client-navbar');
            if (!nav) return;
            const onScroll = () => nav.classList.toggle('is-scrolled', window.scrollY > 24);
            window.addEventListener('scroll', onScroll, { passive: true });
            onScroll();
        })();
    </script>
    <script>
        const MONEY = <?php echo json_encode(Money::jsConfig(), JSON_UNESCAPED_UNICODE); ?>;
        function menuUnitToCdf(unit) {
            return Math.round(Number(unit) * MONEY.multiplier);
        }
        function fmtMoney(cdf) {
            return new Intl.NumberFormat('fr-CD', { maximumFractionDigits: MONEY.decimals }).format(cdf) + ' ' + MONEY.symbol;
        }

        let categories = ['All'];
        const imageIndex = <?php echo json_encode($menuImageIndex, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
        const IMAGE_PLACEHOLDER = <?php echo json_encode($menuImagePlaceholder, JSON_UNESCAPED_SLASHES); ?>;

        function encodeImagePath(path) {
            return path.split('/').map(seg => (seg === '..' || seg === '.' || seg === '') ? seg : encodeURIComponent(seg)).join('/');
        }

        function localImg(filename) {
            if (!filename) return IMAGE_PLACEHOLDER;
            let file = String(filename).trim();
            if (file === '') return IMAGE_PLACEHOLDER;
            file = file.replace(/assets\/images\/kombo\//gi, 'assets/images/combo/');
            if (/^https?:\/\//i.test(file) || file.startsWith('data:')) {
                return file;
            }
            if (file.startsWith('../assets/') || file.startsWith('./assets/') || file.startsWith('/assets/')) {
                return encodeImagePath(file);
            }
            if (file.startsWith('assets/')) {
                return encodeImagePath('../' + file);
            }
            const basename = file.split('/').pop().toLowerCase();
            const resolved = imageIndex[basename];
            return resolved ? encodeImagePath(resolved) : IMAGE_PLACEHOLDER;
        }

        const FRUITS = ['Orange', 'Banane', 'Pomme', 'Ananas', 'Mangue', 'Fraise'];
        const SODA_BRANDS = ['Coca-Cola', 'Fanta', 'Sprite'];
        const DRINK_FRUIT_NAMES = ['jus de fruit', 'milkshake', 'cocktail de fruits'];
        const DRINK_SODA_NAME = 'coca-cola, fanta, sprite';

        let cartKeys = new Set();
        let pendingDrinkItem = null;
        const fruitModal = new bootstrap.Modal(document.getElementById('fruitDrinkModal'));
        const sodaModal = new bootstrap.Modal(document.getElementById('sodaDrinkModal'));

        const items = <?php echo json_encode($menuItems ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

        function drinkKind(name) {
            const n = name.toLowerCase().trim();
            if (DRINK_FRUIT_NAMES.some(d => n === d)) return 'fruit';
            if (n === DRINK_SODA_NAME) return 'soda';
            return null;
        }

        async function fetchCartKey(item, perso = '') {
            const params = new URLSearchParams({
                type: 'menu_item',
                name: item.name,
                category: item.category,
                personnalisation: perso
            });
            const r = await fetch('cart_key.php?' + params.toString());
            const data = await r.json();
            return data.key || '';
        }

        async function loadCartKeys() {
            const data = await fetch('get_cart_count.php').then(r => r.json());
            cartKeys = new Set(data.keys || []);
            updateCartCountFromData(data);
        }

        function updateCartCountFromData(data) {
            const count = data.count || 0;
            document.querySelectorAll('[data-cart-count]').forEach(function (el) {
                el.textContent = count;
                el.style.display = count === 0 ? 'none' : 'inline-block';
            });
            const floating = document.getElementById('floatingCartCount');
            if (floating) {
                floating.textContent = count;
                floating.style.display = count === 0 ? 'none' : 'block';
            }
        }

        // The menu is rendered from the database rows seeded by schema_upgrade().
        items.forEach(i => {
            if (i.category && !categories.includes(i.category)) {
                categories.push(i.category);
            }
        });

        // render categories buttons
        const categoriesContainer = document.getElementById('categories');
        categories.forEach(cat=>{
            const btn=document.createElement('button');
            btn.className='btn category-pill'+(cat==='All'? ' active':'');
            btn.textContent=cat; btn.addEventListener('click', ()=>{ document.querySelectorAll('.category-pill').forEach(b=>b.classList.remove('active')); btn.classList.add('active'); render(cat); });
            categoriesContainer.appendChild(btn);
        });

        const menuList = document.getElementById('menuList');
        let currentList = [];

        const MENU_DESC_FALLBACK = {
            'Combo': 'Formule complète, idéale pour un repas sur place.',
            'Apéritifs': 'Pour commencer en douceur.',
            'Entrées': 'Une entrée fraîche pour ouvrir l\'appétit.',
            'Plats principaux': 'Préparé à la commande par notre cuisine.',
            'Accompagnements': 'Le complément parfait de votre plat.',
            'Desserts': 'Une touche sucrée pour finir en beauté.',
            'Boissons': 'Servi frais avec votre commande.',
        };

        function menuItemDescription(item) {
            const custom = (item.desc || '').trim();
            if (custom) {
                return custom;
            }
            return MENU_DESC_FALLBACK[item.category] || 'Disponible à la commande — servi à votre table.';
        }

        function render(filter){
            menuList.innerHTML='';
            const list = filter==='All' ? items : items.filter(i=>i.category===filter);
            currentList = list;
            list.forEach((i, idx)=>{
                const card=document.createElement('article');
                card.className='card menu-card border-0 overflow-hidden';
                const descText = menuItemDescription(i);
                const typeTag = i.type ? `<span class="menu-card-tag menu-card-tag--type">${i.type}</span>` : '';
                const prepTag = i.prep_min ? `<span class="menu-card-tag menu-card-tag--prep"><i class="bi bi-clock"></i> ${i.prep_min} min</span>` : '';
                card.innerHTML = `
                    <div class="menu-card-inner">
                        <div class="menu-img-wrap">
                            <img src="${localImg(i.img)}" alt="${i.name}" loading="lazy" decoding="async"
                                 onerror="this.onerror=null;this.src=IMAGE_PLACEHOLDER;">
                        </div>
                        <div class="menu-card-body">
                            <h3 class="h5 mb-0 d-flex justify-content-between align-items-start gap-2">
                                <span class="flex-grow-1">${i.name}</span>
                                <button type="button" class="btn btn-sm btn-outline-light flex-shrink-0 add-cart" data-idx="${idx}" title="Ajouter au panier">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-cart-plus" viewBox="0 0 16 16" aria-hidden="true">
                                      <path d="M8 7a.5.5 0 0 1 .5.5V9H10a.5.5 0 0 1 0 1H8.5V11.5a.5.5 0 0 1-1 0V10H6a.5.5 0 0 1 0-1h1.5V7.5A.5.5 0 0 1 8 7z"/>
                                      <path d="M0 1.5A.5.5 0 0 1 .5 1h1a.5.5 0 0 1 .485.379L2.89 5H14.5a.5.5 0 0 1 .49.598l-1.5 6A.5.5 0 0 1 13 12H4a.5.5 0 0 1-.491-.408L1.01 1.607 1 1.5H.5z"/>
                                    </svg>
                                </button>
                            </h3>
                            <div class="menu-card-tags">
                                <span class="menu-card-tag">${i.category}</span>
                                ${typeTag}
                                ${prepTag}
                            </div>
                            <p class="text-muted menu-desc">${descText}</p>
                            <div class="menu-card-footer">
                                <span class="price">${fmtMoney(menuUnitToCdf(i.price))}</span>
                            </div>
                        </div>
                    </div>`;
                menuList.appendChild(card);
            });

            document.querySelectorAll('.add-cart').forEach(btn => {
                btn.addEventListener('click', () => {
                    addToCart(parseInt(btn.getAttribute('data-idx'), 10));
                });
            });
            syncCartButtons();
        }

        async function syncCartButtons() {
            await loadCartKeys();
            const buttons = document.querySelectorAll('.add-cart');
            for (const btn of buttons) {
                const idx = parseInt(btn.getAttribute('data-idx'), 10);
                const item = currentList[idx];
                if (!item) continue;
                const key = await fetchCartKey(item, '');
                if (cartKeys.has(key)) {
                    btn.classList.add('in-cart');
                    btn.title = 'Déjà au panier — modifiez la quantité dans le panier';
                } else {
                    btn.classList.remove('in-cart');
                    btn.title = 'Ajouter au panier';
                }
            }
        }

        function submitToCart(item, quantite, personnalisation) {
            const formData = new FormData();
            formData.append('type', 'menu_item');
            formData.append('name', item.name);
            formData.append('price', menuUnitToCdf(item.price));
            formData.append('quantite', quantite);
            formData.append('img', item.img);
            formData.append('category', item.category);
            formData.append('personnalisation', personnalisation);
            if (item.id_plat) formData.append('id_plat', item.id_plat);
            if (item.id_boisson) formData.append('id_boisson', item.id_boisson);
            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            if (csrfMeta) {
                formData.append('_csrf', csrfMeta.getAttribute('content'));
            }

            return fetch('panier.php?action=add', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        if (data.cart_key) cartKeys.add(data.cart_key);
                        if (data.keys) cartKeys = new Set(data.keys);
                        updateCartCountFromData(data);
                        showToast(item.name + ' ajouté au panier');
                        syncCartButtons();
                    } else if (data.duplicate) {
                        showToast(data.message || 'Déjà dans le panier');
                        syncCartButtons();
                    } else {
                        showToast('Erreur: ' + (data.message || 'inconnue'));
                    }
                    return data;
                });
        }

        async function addToCart(idx) {
            const item = currentList[idx];
            if (!item) return;

            const key = await fetchCartKey(item, '');
            if (cartKeys.has(key)) {
                showToast('Cet article est déjà dans votre panier. Ajustez la quantité depuis le panier.');
                return;
            }

            const kind = drinkKind(item.name);
            if (kind === 'fruit') {
                openFruitModal(item);
                return;
            }
            if (kind === 'soda') {
                openSodaModal(item);
                return;
            }

            submitToCart(item, 1, '');
        }

        function openFruitModal(item) {
            pendingDrinkItem = item;
            document.getElementById('fruitDrinkTitle').textContent = item.name;
            const list = document.getElementById('fruitFlavorList');
            list.innerHTML = FRUITS.map((f, i) =>
                `<label><input type="radio" name="fruitFlavor" value="${f}" ${i === 0 ? 'checked' : ''}><span>${f}</span></label>`
            ).join('');
            document.getElementById('fruitQty').value = 1;
            fruitModal.show();
        }

        function openSodaModal(item) {
            pendingDrinkItem = item;
            document.getElementById('sodaQty').value = 1;
            document.getElementById('sodaSameFlavor').checked = true;
            document.getElementById('sodaSameBlock').style.display = 'block';
            document.getElementById('sodaMultiBlock').style.display = 'none';
            buildSodaUnitRows(1);
            sodaModal.show();
        }

        function buildSodaUnitRows(qty) {
            const container = document.getElementById('sodaUnitsContainer');
            container.innerHTML = '';
            for (let i = 1; i <= qty; i++) {
                const row = document.createElement('div');
                row.className = 'soda-unit-row';
                row.innerHTML = `<label class="form-label small mb-1">Unité ${i}</label>
                    <select class="form-select form-select-sm soda-unit-flavor">
                        ${SODA_BRANDS.map(b => `<option value="${b}">${b}</option>`).join('')}
                    </select>`;
                container.appendChild(row);
            }
        }

        document.getElementById('sodaQty').addEventListener('change', function() {
            const q = Math.min(12, Math.max(1, parseInt(this.value, 10) || 1));
            this.value = q;
            if (!document.getElementById('sodaSameFlavor').checked) {
                buildSodaUnitRows(q);
            }
        });

        document.getElementById('sodaSameFlavor').addEventListener('change', function() {
            document.getElementById('sodaSameBlock').style.display = this.checked ? 'block' : 'none';
            document.getElementById('sodaMultiBlock').style.display = this.checked ? 'none' : 'block';
            if (!this.checked) {
                buildSodaUnitRows(Math.min(12, Math.max(1, parseInt(document.getElementById('sodaQty').value, 10) || 1)));
            }
        });

        document.getElementById('fruitDrinkConfirm').addEventListener('click', function() {
            const item = pendingDrinkItem;
            const flavor = document.querySelector('input[name="fruitFlavor"]:checked');
            const qty = Math.max(1, parseInt(document.getElementById('fruitQty').value, 10) || 1);
            if (!item || !flavor) return;
            const perso = 'Saveur : ' + flavor.value;
            fruitModal.hide();
            submitToCart(item, qty, perso);
            pendingDrinkItem = null;
        });

        document.getElementById('sodaDrinkConfirm').addEventListener('click', function() {
            const item = pendingDrinkItem;
            const qty = Math.min(12, Math.max(1, parseInt(document.getElementById('sodaQty').value, 10) || 1));
            if (!item) return;
            let perso = '';
            if (document.getElementById('sodaSameFlavor').checked) {
                const brand = document.getElementById('sodaSingleFlavor').value;
                perso = brand + (qty > 1 ? ' ×' + qty : '');
            } else {
                const picks = Array.from(document.querySelectorAll('.soda-unit-flavor')).map(s => s.value);
                const counts = {};
                picks.forEach(b => { counts[b] = (counts[b] || 0) + 1; });
                perso = Object.entries(counts).map(([b, n]) => b + ' ×' + n).join(', ');
            }
            sodaModal.hide();
            submitToCart(item, qty, perso);
            pendingDrinkItem = null;
        });

        function showToast(message) {
            const toastEl = document.getElementById('cartToast');
            document.getElementById('cartToastBody').textContent = message;
            bootstrap.Toast.getOrCreateInstance(toastEl).show();
        }

        loadCartKeys();
        render('All');
        setInterval(loadCartKeys, 5000);

        const scrollTopBtn = document.getElementById('scrollTopBtn');
        if (scrollTopBtn) {
            const toggleScrollTop = () => {
                scrollTopBtn.classList.toggle('is-visible', window.scrollY > 320);
            };
            window.addEventListener('scroll', toggleScrollTop, { passive: true });
            toggleScrollTop();
            scrollTopBtn.addEventListener('click', () => {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        }
    </script>
</body>
</html>
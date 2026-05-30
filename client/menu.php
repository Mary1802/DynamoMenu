<?php
session_start();
$db_config = require __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/table_context.php';
require_once __DIR__ . '/../includes/menu_image_index.php';

try {
    $pdo = new PDO(
        'mysql:host=' . $db_config['host'] . ';dbname=' . $db_config['dbname'],
        $db_config['user'],
        $db_config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    bootstrap_table_context($pdo);
} catch (PDOException $e) {
    die('Erreur de connexion');
}
$tableCtx = table_session();

$menuImageIndex = build_menu_image_index(__DIR__ . '/../assets/images');
$menuImagePlaceholder = 'https://images.unsplash.com/photo-1525755662778-989d0524087e?auto=format&fit=crop&w=800&q=80';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DynamoMenu - Menu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        :root {
            --accent-color: #ff6f1f;
            --menu-img-width: 200px;
            --menu-img-min-height: 200px;
        }
        .item-category { font-size: 0.85rem; color: rgba(255, 255, 255, 0.8); }
        .price { color: var(--accent-color); font-weight: 700; }
        .add-cart svg, #cartBtn svg, a.position-fixed svg { color: var(--accent-color); }

        #menuList.menu-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.25rem;
            align-items: stretch;
        }
        @media (min-width: 768px) {
            #menuList.menu-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }

        .menu-card {
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        /* Image à gauche (200 px) ; hauteur = toute la carte */
        .menu-card-inner {
            display: flex;
            flex-direction: row;
            align-items: stretch;
            height: 100%;
            min-height: var(--menu-img-min-height);
        }

        .menu-card .menu-img-wrap {
            flex: 0 0 var(--menu-img-width);
            width: var(--menu-img-width);
            min-width: var(--menu-img-width);
            max-width: var(--menu-img-width);
            align-self: stretch;
            min-height: 100%;
            position: relative;
            overflow: hidden;
            background: rgba(255, 255, 255, 0.05);
            border-right: 1px solid rgba(255, 255, 255, 0.06);
        }

        .menu-card .menu-img-wrap img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            display: block;
        }

        .menu-card-body {
            flex: 1 1 auto;
            min-width: 0;
            padding: 1.15rem 1.25rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .menu-card-body .menu-desc {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .menu-card-body h3 {
            font-size: 1.05rem;
            line-height: 1.3;
        }

        @media (max-width: 575.98px) {
            .menu-card-inner {
                flex-direction: column;
                min-height: 0;
            }
            .menu-card .menu-img-wrap {
                flex: 0 0 auto;
                width: 100%;
                min-width: 100%;
                max-width: 100%;
                min-height: var(--menu-img-min-height);
                height: var(--menu-img-min-height);
                align-self: auto;
                border-right: none;
                border-bottom: 1px solid rgba(255, 255, 255, 0.06);
            }
        }

        a.position-fixed .floating-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: var(--accent-color);
            color: #fff;
            border-radius: 999px;
            padding: 0.18rem 0.42rem;
            font-size: 0.72rem;
            font-weight: 700;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.25);
        }
    </style>
</head>
<body>
    <header class="navbar navbar-expand-lg navbar-dark px-4 py-3">
        <a class="navbar-brand fw-bold text-white" href="index.php">DynamoMenu</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu" aria-controls="navMenu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav ms-auto align-items-lg-center">
                <li class="nav-item"><a class="nav-link text-white" href="index.php">Accueil</a></li>
                <li class="nav-item"><a class="nav-link text-white active" aria-current="page" href="menu.php">Menu</a></li>
                <li class="nav-item">
                    <a class="nav-link text-white position-relative" href="panier.php">
                        Panier
                        <span id="cartCount" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem;">0</span>
                    </a>
                </li>
                <li class="nav-item"><a class="nav-link text-white" href="#contact.php">Contact</a></li>
                <li class="nav-item"><a class="nav-link text-white" href="../login.php">Employé</a></li>
            </ul>
            <a class="btn btn-primary ms-lg-4" href="#contact.php">Contact Now</a>
        </div>
    </header>

    <main class="container-fluid px-4 py-5">
        <?php if ($tableCtx): ?>
        <p class="text-center text-warning mb-3"><small>Table : <strong><?php echo htmlspecialchars($tableCtx['label']); ?></strong></small></p>
        <?php endif; ?>
        <section class="text-center mb-4">
            <p class="text-uppercase text-warning mb-2">Notre Carte</p>
            <h1 class="display-5 fw-bold menu-title">Notre <span class="text-warning">Menu</span></h1>
            <div id="categories" class="d-flex justify-content-center flex-wrap gap-2 mt-4"></div>
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

        <!-- Floating quick cart -->
                <a href="panier.php" class="position-fixed d-flex align-items-center justify-content-center bg-warning text-dark rounded-circle" style="width:56px;height:56px;right:20px;bottom:20px;z-index:1070;box-shadow:0 6px 18px rgba(0,0,0,0.2);">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-cart" viewBox="0 0 16 16">
              <path d="M0 1.5A.5.5 0 0 1 .5 1h1a.5.5 0 0 1 .485.379L2.89 5H14.5a.5.5 0 0 1 .49.598l-1.5 6A.5.5 0 0 1 13 12H4a.5.5 0 0 1-.491-.408L1.01 1.607 1 1.5H.5zM5 12a1 1 0 1 0 0 2 1 1 0 0 0 0-2zm6 0a1 1 0 1 0 0 2 1 1 0 0 0 0-2z"/>
            </svg>
                        <span id="floatingCartCount" class="floating-badge">0</span>
        </a>

        <div class="text-center">
            <a class="btn btn-primary btn-lg px-5" href="panier.php">Voir mon panier</a>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const categories = ['All','Plats principaux','Apéritifs','Entrées','Kombo','Boissons','Desserts','Accompagnements'];
        const imageIndex = <?php echo json_encode($menuImageIndex, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
        const IMAGE_PLACEHOLDER = <?php echo json_encode($menuImagePlaceholder, JSON_UNESCAPED_SLASHES); ?>;

        function encodeImagePath(path) {
            return path.split('/').map(seg => (seg === '..' || seg === '.' || seg === '') ? seg : encodeURIComponent(seg)).join('/');
        }

        function localImg(filename) {
            if (!filename) return IMAGE_PLACEHOLDER;
            const key = String(filename).toLowerCase();
            const resolved = imageIndex[key];
            return resolved ? encodeImagePath(resolved) : IMAGE_PLACEHOLDER;
        }

        const items = [];
        function add(cat,name,desc,price,img,rating=4.3,reviews=50){
            items.push({category:cat,name,desc,price,img:localImg(img),rating,reviews});
        }

        // --- Plats principaux (20)
        add('Plats principaux','Pizza Margherita','Tomate, mozzarella, basilic',24, 'Pizza.jpg',4.6,210);
        add('Plats principaux','Tacos Maison','Tacos garnis de viande épicée',27, 'Tacos.jpg',4.3,115);
        add('Plats principaux','Poulet Mayo','Poulet rôti, sauce mayo',22, 'Poulet mayo.jpg',4.5,89);
        add('Plats principaux','Spaghetti Bolognaise','Pâtes à la sauce bolognaise',19, 'spaghetti bolognaise.jpg',4.4,152);
        add('Plats principaux','Fried Rice','Riz sauté aux légumes',18, 'Fried rice.jpg',4.2,76);
        add('Plats principaux','Crevettes Sautées','Crevettes à l’ail et beurre',34, 'Crevetes.jpg',4.7,98);
        add('Plats principaux','Poisson Grillé','Poisson ambassade grillé',36, 'poisson ambassade.jpg',4.5,125);
        add('Plats principaux','Poisson Fumé','Filet de poisson fumé',28, 'Poisson fumé.jpg',4.2,64);
        add('Plats principaux','Ntaba','Spécialité locale Ntaba',30, 'Ntaba.jpg',4.6,44);
        add('Plats principaux','Poisson Salé','Poisson salé braisé',26, 'Poisson salé.jpg',4.1,54);
        add('Plats principaux','Poulet Rôti','Poulet entier rôti',23, 'poulet.jpg',4.3,210);
        add('Plats principaux','Macaroni Saucisse','Macaroni avec saucisse',20, 'pates aux saucisses.png',4.0,89);
        add('Plats principaux','Saucisses Grillées','Saucisses maison',17, 'Saucisses.jpg',4.1,65);
        add('Plats principaux','Combo Burger Poulet','Burger maison + frites',29, 'combo burger frites poulet.jpg',4.4,310);
        add('Plats principaux','Fufu et Sauce','Fufu traditionnel avec sauce',16, 'Fufu.jpg',4.2,33);
        add('Plats principaux','Burger Maison','Burger gourmet',21, 'KFC.jpg',4.3,140);
        add('Plats principaux','Saucisses & Frites','Plateau familial',26, 'Saucisses frites.jpg',4.2,112);
        add('Plats principaux','Makoso','Plat traditionnel',25, 'makoso.jpg',4.5,67);

        // --- Apéritifs (5)
        add('Apéritifs','Samoussa','Triangles croustillants farcis',6, 'Samoussa.jpg',4.4,125);
        add('Apéritifs','Croquettes au fromage','Croquettes au fromage',5, 'croque monsieur.png',4.2,410);
        add('Apéritifs','Croquettes aux pommes de terre','Croquettes aux pommes de terre',5, 'croquettes aux pommes de terre.png',4.1,84);
        add('Apéritifs','4 Petits pains ','Petits pains',6, 'petits pains.png',4.0,30);
        add('Apéritifs','3 Croissants au beurre','Croissants au beurre',6, 'pancakes.png',4.1,27);

        // --- Entrées (5)
        add('Entrées','Salade Verte','Salade fraîche',8, 'salade aux légumes.png',4.1,54);
        add('Entrées','Soupe du Jour','Soupe aux légumes',9, 'soupes aux légumes.png',4.0,31);
        add('Entrées','Salade Avocat','Salade avocat',8, 'salade avocat.png', 4.0,31);
        add('Entrées','Carpaccio de Boeuf','Fines tranches de boeuf',12, 'bouillon à la viande de boeuf.png',4.5,22);

        // --- Kombo (5)
        add('Kombo','Combo 2 Burger + frites + coca','Assortiment pour 2 personnes',55, 'combo 2burgers frites et coca.png',4.6,48);
        add('Kombo','Combo Burger','Burger + frites + boisson',28, 'combo burger frites poulet.jpg',4.4,142);
        add('Kombo','Combo Sandwich','Sandwich + frites + salade',30, 'combo sandwich frites.png',4.3,36);
        add('Kombo','Combo Croque monsieur','3 Croques monsieur + frites + Cocktail',32, 'combo 3croques monsieur frites et mojito.png',4.4,29);

        // --- Boissons (20)
        add('Boissons','Jus de Fruit','Jus frais maison',4, 'Jus de fruit.jpg',4.2,120);
        add('Boissons','Milkshake','Milkshake onctueux',5, 'Milkshakes.jpg',4.3,88);
        add('Boissons','Cocktail de Fruits','Mix vitaminé',5, 'Coktail de fruit.jpg',4.1,75);
        add('Boissons','Smoothie Banane','Smoothie à la banane',5, 'glace a la banane.jpg',4.0,55);
        add('Boissons','Coca-Cola, Fanta, Sprite','Boisson gazeuse',3, 'boissons coca cola.png',4.0,310);
        add('Boissons','Eau Minérale','Bouteille 50cl',2, null,4.0,500);
        add('Boissons','Pinacolada','Cocktail',3, 'pinnacolada.png',4.2,80);
        add('Boissons','Mojito','Cocktail',3, 'mojito.png',4.2,80);
        add('Boissons','Jack Daniels','Whisky',4, 'whisky jack daniel.jpg',4.3,44);
        add('Boissons','Red Label','Whisky',5, 'whisky red label.jpg',4.2,67);
        add('Boissons','Heinekein','Bierre',5, 'bierre heinekein.jpg',4.2,67);

        // --- Desserts (10)
        add('Desserts','Gâteau au Chocolat','Moelleux au chocolat',7, 'Gateau au chocolat.jpg',4.7,129);
        add('Desserts','Glace à la Banane','Crème glacée banane',6, 'glace a la banane.jpg',4.4,98);
        add('Desserts','Churros','Dessert italien',6, 'spring au chocolat.png',4.4,98);
        add('Desserts','Salade de fruit','Salade de fruit',7, 'salade de fruit.png',4.2,34);
        add('Desserts','Crepes au chocolat','Crepe au chocolat',7, 'crepes au chocolat.jpg',4.2,34);
        add('Desserts','Tarte aux pommes','Tarte maison',7, 'tarte aux pommes.jpg',4.6,41);

        // --- Accompagnements (5)
        add('Accompagnements','Frites','Pommes frites',4, 'Frites.jpg',4.3,410);
        add('Accompagnements','Fufu','Farine de mais',4, 'Fufu.jpg',4.3,410);
        add('Accompagnements','Riz Blanc','Riz vapeur',3, 'Riz blanc.jpg',4.0,210);
        add('Accompagnements','Pommes de Terre','Pommes de terre rissolées',4, 'Pomme de terre.jpg',4.1,64);
        add('Accompagnements','Chikwangue','Manioc',4, 'Chikwangue.jpg',4.0,88);
        add('Accompagnements','Bananes Plantain','Accompagnement local',5, 'Bananes.jpg',4.2,27);

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
        function render(filter){
            menuList.innerHTML='';
            const list = filter==='All' ? items : items.filter(i=>i.category===filter);
            currentList = list;
            list.forEach((i, idx)=>{
                const card=document.createElement('article');
                card.className='card menu-card border-0 overflow-hidden h-100';
                card.innerHTML = `
                    <div class="menu-card-inner">
                        <div class="menu-img-wrap">
                            <img src="${i.img}" alt="${i.name}" loading="lazy" decoding="async"
                                 onerror="this.onerror=null;this.src=IMAGE_PLACEHOLDER;">
                        </div>
                        <div class="menu-card-body">
                            <h3 class="h5 mb-1 d-flex justify-content-between align-items-start gap-2">
                                <span class="flex-grow-1">${i.name}</span>
                                <button type="button" class="btn btn-sm btn-outline-light flex-shrink-0 add-cart" data-idx="${idx}" title="Ajouter au panier">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-cart-plus" viewBox="0 0 16 16" aria-hidden="true">
                                      <path d="M8 7a.5.5 0 0 1 .5.5V9H10a.5.5 0 0 1 0 1H8.5V11.5a.5.5 0 0 1-1 0V10H6a.5.5 0 0 1 0-1h1.5V7.5A.5.5 0 0 1 8 7z"/>
                                      <path d="M0 1.5A.5.5 0 0 1 .5 1h1a.5.5 0 0 1 .485.379L2.89 5H14.5a.5.5 0 0 1 .49.598l-1.5 6A.5.5 0 0 1 13 12H4a.5.5 0 0 1-.491-.408L1.01 1.607 1 1.5H.5z"/>
                                    </svg>
                                </button>
                            </h3>
                            <div class="item-category mb-2">${i.category}</div>
                            <p class="text-muted mb-3 menu-desc">${i.desc}</p>
                            <div class="d-flex align-items-center justify-content-between text-warning fw-bold mt-auto">
                                <span class="small">★ ${i.rating} (${i.reviews} avis)</span>
                                <span class="price">${i.price} €</span>
                            </div>
                        </div>
                    </div>`;
                menuList.appendChild(card);
            });

            // attach add-to-cart listeners
            document.querySelectorAll('.add-cart').forEach(btn=>{
                btn.addEventListener('click', ()=>{
                    const idx = parseInt(btn.getAttribute('data-idx'),10);
                    addToCart(idx);
                });
            });
        }

        // Cart functions using AJAX to PHP session
        function updateCartCount(){ 
            fetch('get_cart_count.php')
                .then(response => response.json())
                .then(data => {
                    const cartBadge = document.getElementById('cartCount');
                    const floatingBadge = document.getElementById('floatingCartCount');
                    
                    if (cartBadge) {
                        cartBadge.textContent = data.count;
                        // Cacher le badge si 0
                        if (data.count === 0) {
                            cartBadge.style.display = 'none';
                        } else {
                            cartBadge.style.display = 'block';
                        }
                    }
                    
                    if (floatingBadge) {
                        floatingBadge.textContent = data.count;
                        // Cacher le badge flottant si 0
                        if (data.count === 0) {
                            floatingBadge.style.display = 'none';
                        } else {
                            floatingBadge.style.display = 'block';
                        }
                    }
                });
        }
        
        function addToCart(idx){ 
            const item = currentList[idx]; 
            if(!item) return; 
            
            // Envoyer l'item au serveur via AJAX
            const formData = new FormData();
            formData.append('type', 'menu_item');
            formData.append('name', item.name);
            formData.append('price', item.price);
            formData.append('quantite', 1);
            formData.append('img', item.img);
            formData.append('category', item.category);
            
            fetch('panier.php?action=add', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    updateCartCount();
                    showToast(item.name + ' ajouté au panier');
                } else {
                    showToast('Erreur: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Erreur lors de l\'ajout au panier');
            });
        }

        function showToast(message){ const toastEl = document.getElementById('cartToast'); document.getElementById('cartToastBody').textContent = message; const toast = new bootstrap.Toast(toastEl); toast.show(); }

        // initial
        updateCartCount(); 
        render('All');
        
        // Vérifier le panier toutes les 5 secondes
        setInterval(updateCartCount, 5000);
    </script>
</body>
</html>
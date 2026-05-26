<?php
// Page menu client réorganisée
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
        :root { --accent-color: #ff6f1f; }
        .item-category { font-size:0.85rem; color: rgba(255,255,255,0.8); }
        .price { color: var(--accent-color); font-weight:700; }
        /* Make cart icons use the same accent color as the price */
        .add-cart svg, #cartBtn svg, a.position-fixed svg { color: var(--accent-color); }
        .menu-grid { display: grid; grid-template-columns: 1fr; gap: 1rem; }
        @media(min-width:768px){ .menu-grid{ grid-template-columns: 1fr 1fr; } }
        .menu-card img{ height:160px; object-fit:cover; }
        /* floating cart small badge */
        a.position-fixed .floating-badge { position: absolute; top: -8px; right: -8px; background: var(--accent-color); color: #fff; border-radius: 999px; padding: 0.18rem 0.42rem; font-size: 0.72rem; font-weight:700; box-shadow: 0 2px 6px rgba(0,0,0,0.25); }
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
        <section class="text-center mb-4">
            <p class="text-uppercase text-warning mb-2">Notre Carte</p>
            <h1 class="display-5 fw-bold menu-title">Notre <span class="text-warning">Menu</span></h1>
            <div id="categories" class="d-flex justify-content-center flex-wrap gap-2 mt-4"></div>
        </section>

        <section id="menuList" class="menu-grid mb-5 row"></section>

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
        function localImg(name){ return '../assets/images/' + encodeURIComponent(name); }

        const items = [];
        function add(cat,name,desc,price,img,rating=4.3,reviews=50){ items.push({category:cat,name,desc,price,img:img||'https://images.unsplash.com/photo-1525755662778-989d0524087e?auto=format&fit=crop&w=800&q=80',rating,reviews}); }

        // --- Plats principaux (20)
        add('Plats principaux','Pizza Margherita','Tomate, mozzarella, basilic',24, localImg('Pizza.jpg'),4.6,210);
        add('Plats principaux','Tacos Maison','Tacos garnis de viande épicée',27, localImg('Tacos.jpg'),4.3,115);
        add('Plats principaux','Poulet Mayo','Poulet rôti, sauce mayo',22, localImg('Poulet mayo.jpg'),4.5,89);
        add('Plats principaux','Spaghetti Bolognaise','Pâtes à la sauce bolognaise',19, localImg('spaghetti bolognaise.jpg'),4.4,152);
        add('Plats principaux','Fried Rice','Riz sauté aux légumes',18, localImg('Fried rice.jpg'),4.2,76);
        add('Plats principaux','Crevettes Sautées','Crevettes à l’ail et beurre',34, localImg('Crevetes.jpg'),4.7,98);
        add('Plats principaux','Poisson Grillé','Poisson ambassade grillé',36, localImg('poisson ambassade.jpg'),4.5,125);
        add('Plats principaux','Poisson Fumé','Filet de poisson fumé',28, localImg('Poisson fumé.jpg'),4.2,64);
        add('Plats principaux','Ntaba','Spécialité locale Ntaba',30, localImg('Ntaba.jpg'),4.6,44);
        add('Plats principaux','Poisson Salé','Poisson salé braisé',26, localImg('Poisson salé.jpg'),4.1,54);
        add('Plats principaux','Poulet Rôti','Poulet entier rôti',23, localImg('poulet.jpg'),4.3,210);
        add('Plats principaux','Macaroni Saucisse','Macaroni avec saucisse',20, localImg('Macaroni saucisse.jpg'),4.0,89);
        add('Plats principaux','Saucisses Grillées','Saucisses maison',17, localImg('Saucisses.jpg'),4.1,65);
        add('Plats principaux','Combo Burger Poulet','Burger maison + frites',29, localImg('combo burger frites poulet.jpg'),4.4,310);
        add('Plats principaux','Riz Blanc et Légumes','Accompagnement de saison',15, localImg('Riz blanc.jpg'),4.0,42);
        add('Plats principaux','Fufu et Sauce','Fufu traditionnel avec sauce',16, localImg('Fufu.jpg'),4.2,33);
        add('Plats principaux','Burger Maison','Burger gourmet',21, localImg('KFC.jpg'),4.3,140);
        add('Plats principaux','Saucisses & Frites','Plateau familial',26, localImg('Saucisses frites.jpg'),4.2,112);

        // --- Apéritifs (5)
        add('Apéritifs','Samoussa','Triangles croustillants farcis',6, localImg('Samoussa.jpg'),4.4,125);
        add('Apéritifs','Croquettes au fromage','Croquettes au fromage',5, localImg('croquettes au fromage.png'),4.2,410);
        add('Apéritifs','Croquettes aux pommes de terre','Croquettes aux pommes de terre',5, localImg('croquettes aux pommes de terre.png'),4.1,84);
        add('Apéritifs','4 Petits pains ','Petits pains',6, localImg('petits pains.png'),4.0,30);
        add('Apéritifs','3 Croissants au beurre','Croissants au beurre',6, localImg('croissants au beurre.png'),4.1,27);

        // --- Entrées (5)
        add('Entrées','Salade Verte','Salade fraîche',8, localImg('salade aux légumes.png'),4.1,54);
        add('Entrées','Soupe du Jour','Soupe aux légumes',9, localImg('soupes aux légumes.png'),4.0,31);
        add('Entrées','Salade Verte','Salade avocat',8, localImg('salade avocat.png'), 4.0,31);
        add('Entrées','Carpaccio de Boeuf','Fines tranches de boeuf',12, localImg('bouillon à la viande de boeuf.png'),4.5,22);
        

        // --- Kombo (5)
        add('Kombo','Combo 2 Burger + frites + coca','Assortiment pour 2 personnes',55, localImg('combo 2burgers frites coca.png'),4.6,48);
        add('Kombo','Combo Burger','Burger + frites + boisson',28, localImg('combo burger frites poulet.jpg'),4.4,142);
        add('Kombo','Combo Sandwich','Sandwich + frites + salade',30, localImg('combo sandwich frites.png'),4.3,36);
        add('Kombo','Kombo Croque monsieur','3 Croques monsieur + frites + Cocktail',32, localImg('3 croques monsieur frites mojito.png'),4.4,29);

        // --- Boissons (20)
        add('Boissons','Jus de Fruit','Jus frais maison',4, localImg('Jus de fruit.jpg'),4.2,120);
        add('Boissons','Milkshake','Milkshake onctueux',5, localImg('Milkshakes.jpg'),4.3,88);
        add('Boissons','Cocktail de Fruits','Mix vitaminé',5, localImg('Coktail de fruit.jpg'),4.1,75);
        add('Boissons','Smoothie Banane','Smoothie à la banane',5, localImg('glace a la banane.jpg'),4.0,55);
        add('Boissons','Coca-Cola, Fanta, Sprite','Boisson gazeuse',3, localImg('boissons coca cola.png'),4.0,310);
        add('Boissons','Eau Minérale','Bouteille 50cl',2, null,4.0,500);
        add('Boissons','Pinacolada','Cocktail',3, localImg('pinnacolada.png'),4.2,80);
        add('Boissons','Mojito','Cocktail',3, localImg('mojito.png'),4.2,80);
        add('Boissons','Jack Daniels','Whisky',4, localImg('whisky jack daniel.jpg'),4.3,44);
        add('Boissons','Red Label','Whisky',5, localImg('whisky red label.jpg'),4.2,67);
        add('Boissons','Heinekein','Bierre',5, localImg('bierre heinekein.jpg'),4.2,67);

        // --- Desserts (10)
        add('Desserts','Gâteau au Chocolat','Moelleux au chocolat',7, localImg('Gateau au chocolat.jpg'),4.7,129);
        add('Desserts','Glace à la Banane','Crème glacée banane',6, localImg('glace a la banane.jpg'),4.4,98);
        add('Desserts','Churros','Dessert italien',6, localImg('spring au chocolat.png'),4.4,98);
        add('Desserts','Salade de fruit','Salade de fruit',7, localImg('salade de fruit.png'),4.2,34);
        add('Desserts','Crepes au chocolat','Crepe au chocolat',7, localImg('crepes au chocolat.jpg'),4.2,34);

        // --- Accompagnements (5)
        add('Accompagnements','Frites','Pommes frites',4, localImg('Frites.jpg'),4.3,410);
        add('Accompagnements','Fufu','Farine de mais',4, localImg('Fufu.jpg'),4.3,410);
        add('Accompagnements','Riz Blanc','Riz vapeur',3, localImg('Riz blanc.jpg'),4.0,210);
        add('Accompagnements','Pommes de Terre','Pommes de terre rissolées',4, localImg('Pomme de terre.jpg'),4.1,64);
        add('Accompagnements','Chikwangue','Manioc',4, localImg('Chikwangue.jpg'),4.0,88);
        add('Accompagnements','Bananes Plantain','Accompagnement local',5, localImg('Bananes.jpg'),4.2,27);

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
                const col=document.createElement('div'); col.className='col mb-3';
                const card=document.createElement('article'); card.className='card menu-card border-0 overflow-hidden';
                card.innerHTML = `
                    <div class="row g-0 align-items-center">
                        <div class="col-5">
                            <img src="${i.img}" class="img-fluid rounded-start" alt="${i.name}">
                        </div>
                        <div class="col-7 p-4">
                            <h3 class="h5 mb-1 d-flex justify-content-between align-items-center">
                                <span>${i.name}</span>
                                <button class="btn btn-sm btn-outline-light ms-3 add-cart" data-idx="${idx}" title="Ajouter au panier">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-cart-plus" viewBox="0 0 16 16">
                                      <path d="M8 7a.5.5 0 0 1 .5.5V9H10a.5.5 0 0 1 0 1H8.5V11.5a.5.5 0 0 1-1 0V10H6a.5.5 0 0 1 0-1h1.5V7.5A.5.5 0 0 1 8 7z"/>
                                      <path d="M0 1.5A.5.5 0 0 1 .5 1h1a.5.5 0 0 1 .485.379L2.89 5H14.5a.5.5 0 0 1 .49.598l-1.5 6A.5.5 0 0 1 13 12H4a.5.5 0 0 1-.491-.408L1.01 1.607 1 1.5H.5z"/>
                                    </svg>
                                </button>
                            </h3>
                            <div class="item-category mb-2">${i.category}</div>
                            <p class="text-muted mb-3">${i.desc}</p>
                            <div class="d-flex align-items-center justify-content-between text-warning fw-bold">
                                <span>★ ${i.rating} (${i.reviews} avis)</span>
                                <span class="price">$${i.price}</span>
                            </div>
                        </div>
                    </div>`;
                col.appendChild(card); menuList.appendChild(col);
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
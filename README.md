# DynamoMenu

Application web **PHP + MySQL (WAMP)** pour un restaurant : les clients consultent le menu via un QR code, passent commande depuis leur table, la cuisine suit la préparation, puis le caissier encaisse et génère la facture.

## Stack

- **PHP** (>= 8.0)
- **MySQL**
- **HTML/CSS** + **Bootstrap 5** + **Bootstrap Icons**
- **JavaScript** vanilla (pas de framework front)
- **Composer** uniquement pour l’autoload PSR-4 (pas de framework PHP)

## Démarrage rapide (WAMP)

1. Mettre le projet dans le dossier web, par ex. `C:\wamp64\www\DynamoMenu`.
2. Vérifier la config BDD dans `config/db.php`.
3. (Première installation) Initialiser la base :
   - Dans le navigateur : `http://localhost/DynamoMenu/init_db.php`
   - Ou en CLI : `php init_db.php`
4. Se connecter : `http://localhost/DynamoMenu/login.php`

## Configuration

- **Base de données** : `config/db.php`
- **Paramètres appli** : `config/app.php`
  - `base_url` (ex. `http://localhost/DynamoMenu`)
  - devise (CDF/FC), sessions, et accès scripts setup
  - `allow_web_setup` : autorise `init_db.php` / `run_update.php` via navigateur (accès admin requis)

## Parcours fonctionnel

### Côté client

1. Le client scanne un **QR code de table**
2. Il consulte le **menu** et gère son **panier**
3. Il **confirme** la commande (infos client + table + mode de paiement prévu)
4. Il suit l’avancement dans **Suivi commande** (statuts et récapitulatif)

### Côté staff

- **Cuisine** : voit les commandes et fait avancer les statuts (préparation → prête → livrée)
- **Caissier** : encaisse les commandes livrées, crée la facture et empêche le double encaissement
- **Admin** : gère tables/QR, menu (plats), employés, commandes, rapports, etc.

## Comptes de démonstration

Après `init_db.php`, des comptes de test sont créés (affichés sur la page de succès) :

- **Admin** : `admin@dynamomenu.fr` / `admin123`
- **Cuisinier** : `pierre@dynamomenu.fr` / `chef123`
- **Caissier** : `jean@dynamomenu.fr` / `caisse123`

## Architecture (MVC maison)

Le projet est organisé autour d’un MVC léger, sans framework.

### Points d’entrée publics

Dossiers accessibles via l’URL :

- `client/` (pages clients)
- `cuisine/` (dashboard cuisine)
- `caissier/` (dashboard caisse)
- `admin/` (dashboard admin)
- `api/` (endpoints JSON)
- `login.php`, `logout.php`

Chaque page publique charge le bootstrap puis délègue au **dispatcher** :

- `bootstrap/app.php` : boot + autoload
- `App\Http\Kernel` : dispatch via `config/routes.php`

### Code applicatif

Namespace `App\` (autoload PSR-4) → `src/App/` :

- `src/App/Core/` : `Application` (conteneur), `Config`, `Database`
- `src/App/Http/` : `Kernel` + helpers pages (Admin/Staff/Client)
- `src/App/Controller/` : contrôleurs (Admin, Client, Cuisine, Caissier, Api, Staff…)
- `src/App/Service/` : logique métier (panier, commandes, paiement, schéma…)
- `src/App/Repository/` : accès BDD (requêtes PDO)
- `src/App/View/` : vues/layouts + templates
- `src/App/Security/` : CSRF, accès commande par token, etc.
- `src/App/Setup/` : installation/migrations (utilisées par `init_db.php` / `run_update.php`)

## Scripts base de données

- `init_db.php` : **crée** la base/tables et des données de démonstration (à exécuter une fois)
- `run_update.php` : applique des **mises à jour** idempotentes du schéma (si besoin)

> La connexion quotidienne de l’app ne dépend pas de ces scripts : elle passe par `bootstrap/app.php` + `config/db.php`.

## Sécurité (résumé)

- **Sessions staff** dédiées (auth par rôle : admin / cuisinier / caissier)
- **CSRF** sur les formulaires POST
- **Accès suivi commande** protégé (token / session) pour éviter l’accès non autorisé

## API (aperçu)

Endpoints JSON sous `api/` (statut commande, notifications, menu, paiement, stats…).  
Le mapping exact est dans `config/routes.php`.

## Dépannage

- Erreur BDD : vérifier `config/db.php` et que MySQL est démarré (WAMP vert)
- Problème de schéma : lancer `run_update.php`
- Après modifications CSS : forcer le rechargement (Ctrl+F5)


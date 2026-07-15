# DynamoMenu

Application web **PHP + MySQL (WAMP)** pour digitaliser le service en salle d’un restaurant (contexte Kinshasa / RDC, devise **CDF / FC**, TVA **16 %**).

À chaque table, une **tablette** est déjà ouverte sur l’application client. Le client compose sa commande, suit la préparation, puis le personnel assure le flux **cuisine → manager (livraison) → caisse (encaissement / ticket)**.

---

## Stack

| Couche | Technologie |
|--------|-------------|
| Backend | **PHP** ≥ 8.0 (`strict_types`), OOP, PDO |
| Base de données | **MySQL** |
| Autoload | **Composer** PSR-4 uniquement (pas de framework PHP) |
| Front | HTML/CSS, **Bootstrap 5**, **Bootstrap Icons**, **JavaScript vanilla** |
| PDF / tickets | Générateur PDF maison + ticket thermique **80 mm** (impression navigateur) |
| Serveur local | **WAMP** (Apache + MySQL + PHP) |

---

## Parcours fonctionnel

### Côté client

1. Accès depuis la **tablette de table** (application déjà ouverte, table associée en session)  
2. **Identité** (nom, prénom ; option client fidèle → téléphone obligatoire, e-mail facultatif)  
3. Consultation du **menu** (plats + boissons, personnalisations)  
4. Gestion du **panier** (quantités, validation stock boissons)  
5. **Confirmation** de commande (+ mode de paiement souhaité : espèces ou mobile money)  
6. **Suivi** en temps quasi réel (polling statut + countdown de préparation)  
7. Historique (`mes_commandes`), nouvelle commande, annulation avant validation (vide panier / session / profil)

### Chaîne opérationnelle (staff)

```
en_attente → en_preparation → prete → livree
                 ↑ cuisine        ↑ manager    → encaissement (caissier)
                                    (ou annulee)
```

| Rôle | Rôle métier |
|------|-------------|
| **Cuisine** | File d’attente : démarrer la préparation (chrono figé) → marquer « prête » |
| **Manager** | Livraison en salle des commandes prêtes → statut `livree` |
| **Caissier** | Encaissement, facture, ticket 80 mm ; anti double-paiement |
| **Admin** | Gestion complète + tableaux de bord + rapports PDF |

---

## Fonctionnalités principales

### Client
- Accès client via **tablette** déjà configurée pour la table
- Menu plats & boissons (rupture gérée pour les **boissons** uniquement)
- Panier session + compteur
- Identité : nom / prénom suffisent ; option **client fidèle** (téléphone obligatoire, e-mail facultatif) ; profil verrouillable
- Modes paiement **souhaités** : `especes` | `mobile_money` (préférence ; confirmation à la caisse)
- Suivi avec countdown : **Σ (temps_préparation × quantité)** sur les plats ; figé au démarrage cuisine
- Message « Bon appétit ! » lorsque le statut est `livree`
- Annulation côté confirmation : nettoyage panier / session / client

### Admin
- Dashboard : CA jour/mois, commandes récentes, **meilleurs plats** (vendus plus de 5 fois)
- CRUD **plats** / **boissons** (images, stock boissons, temps de préparation)
- CRUD **tables** (libellé, places, code interne, actif) — pour lier chaque tablette à une table
- CRUD **employés** (admin, cuisinier, caissier, manager)
- CRUD **clients** (édition, suppression, tri alphabétique)
- Consultation des **commandes** (lecture seule — les statuts sont gérés par cuisine / manager / caisse), logs d’activité, paramètres (contacts, horaires)
- Rapports PDF (journalier / mensuel) — export & impression

### Cuisine
- Dashboard file d’attente
- Passage `en_attente` → `en_preparation` → `prete`
- Liste des commandes, paramètres staff

### Manager
- Dashboard des commandes **prêtes**
- Marquage **livrée**
- Liste des commandes, paramètres staff

### Caissier
- Encaissement des commandes (modes facture : `especes` | `mobile` | `carte`)
- Protection contre le **double encaissement**
- **Ticket de caisse thermique 80 mm** + impression navigateur
- Rapports PDF caisse

### API JSON
Endpoints sous `api/` (détail dans `config/routes.php`) :
- Statut commande client (`api/client/commande_statut.php`)
- Menu, commande, paiement, employés, stats

---

## Règles métier importantes

1. **Stock** : suivi et décrémentation pour les **boissons** uniquement ; restauration en cas d’annulation. Les plats n’ont pas de stock quantitatif.
2. **Identité** : nom + prénom obligatoires. Si client fidèle → téléphone unique obligatoire, e-mail facultatif (unicité si renseigné).
3. **Téléphone** (client fidèle) : 10–13 caractères ; si commence par `0` → max 10 ; si par `+` → max 13.
4. **TVA 16 %** : montants en TTC ; HT/TVA dérivés à la facture.
5. **Devise** : CDF (FC) ; multiplicateur legacy € → CDF configurable (`eur_to_cdf`).
6. **Paiement à deux niveaux** : préférence client ≠ mode réel d’encaissement caisse (pas encore de passerelle Mobile Money / CinetPay).
7. **Countdown** : somme des temps de préparation des plats × quantités ; boissons n’allongent pas l’estimé métier.
8. Modules retirés / simplifiés : notifications applicatives dédiées, fidélité / points, stock plats.

---

## Architecture (MVC maison)

Pas de framework PHP lourd. Flux :

```
page publique (client|admin|…)
  → bootstrap/app.php
  → FrontController / Kernel
  → config/routes.php (auth, setup, controller, template)
  → Service / Repository (PDO)
  → View::render (templates PHP)
```

### Points d’entrée

| Dossier / fichier | Contenu |
|-------------------|---------|
| `client/` | Parcours client |
| `admin/` | Administration |
| `cuisine/` | Cuisine |
| `manager/` | Service / livraison |
| `caissier/` | Encaissement & factures |
| `api/` | Endpoints JSON |
| `login.php` / `logout.php` | Authentification staff |

### Code applicatif (`src/App/`)

| Dossier | Rôle |
|---------|------|
| `Core/` | Conteneur léger, config, BDD |
| `Http/` | Kernel, FrontController, helpers pages |
| `Controller/` | Admin, Client, Cuisine, Manager, Caissier, Api, Staff |
| `Service/` | Logique métier (panier, commande, stock, paiement, rapports…) |
| `Repository/` | Requêtes PDO |
| `View/` | Layouts + templates |
| `Model/` | Statuts, lignes de commande, etc. |
| `Auth/` | Sessions client & staff |
| `Security/` | CSRF, tokens commande, hash mots de passe |
| `Setup/` | Init BDD & upgrades de schéma |

---

## Démarrage rapide (WAMP)

1. Placer le projet dans le dossier web, ex. `C:\wamp64\www\DynamoMenu`.
2. Vérifier `config/db.php` (hôte, base `dynamomenu`, utilisateur MySQL).
3. Installer les dépendances Composer (autoload) :
   ```bash
   composer install
   ```
4. Initialiser la base (une fois) :
   - Navigateur : `http://localhost/DynamoMenu/init_db.php`
   - Ou CLI : `php init_db.php`
5. (Si besoin) appliquer les upgrades de schéma : `run_update.php`
6. Connexion staff : `http://localhost/DynamoMenu/login.php`
7. Accès client : ouvrir l’URL client sur la tablette de table (association table gérée côté admin → Tables)

---

## Configuration

- **`config/db.php`** — connexion MySQL  
- **`config/app.php`** — `base_url`, devise CDF, TVA, sessions, contacts restaurant, `session_secret`, `allow_web_setup`  
- **`config/routes.php`** — mapping routes → controllers / templates / auth  

Scripts BDD :
- `init_db.php` — création schéma + données de démonstration  
- `run_update.php` — migrations / upgrades idempotentes  

> L’app au quotidien utilise `bootstrap/app.php` + `config/db.php` ; les scripts setup ne sont pas nécessaires à chaque requête.

---

## Comptes de démonstration

Après `init_db.php`, un **admin** est créé. Les autres rôles peuvent être ajoutés via **Admin → Employés** (ou selon l’affichage de la page de succès d’init).

| Rôle | Email (indicatif) | Mot de passe (indicatif) |
|------|-------------------|--------------------------|
| Admin | `admin@dynamomenu.fr` | `admin123` |
| Cuisinier | `pierre@dynamomenu.fr` | `chef123` |
| Caissier | `jean@dynamomenu.fr` | `caisse123` |

Le rôle **manager** se crée depuis l’admin (rôle `manager`).

> Changez ces mots de passe en environnement réel.

---

## Sécurité

- Sessions **staff** et **client** séparées (durées configurables)
- Auth par rôle (`admin`, `cuisinier`, `caissier`, `manager`)
- **CSRF** sur les formulaires POST sensibles
- Mots de passe hashés
- Accès suivi commande protégé (token / session)
- Scripts `init_db` / `run_update` via navigateur contrôlés par `allow_web_setup` + admin

---

## Perspectives

- Intégration **Mobile Money** réelle (ex. **CinetPay**) via API + webhook → confirmation automatique du paiement
- Déploiement production (HTTPS, `session_secret` fort, désactivation du setup web)
- Amélioration des analytics / rapports

---

## Dépannage

| Problème | Piste |
|----------|--------|
| Erreur BDD | Vérifier `config/db.php` et que MySQL (WAMP) est démarré |
| Colonnes / schéma manquants | Lancer `run_update.php` |
| CSS / JS obsolète | Rechargement forcé (Ctrl+F5) |
| Boisson en « rupture » indûment | Stock `quantite_boisson` ; les plats sans stock doivent rester commandables |
| Mauvaise URL client | Vérifier `base_url` et l’association de la tablette à la bonne table (admin → Tables) |

---

## Licence / projet

Projet pédagogique / applicatif restaurant — `dynamomenu/app` (Composer).

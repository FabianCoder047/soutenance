# e-Media Support — Cahier des charges technique

> **Stack :** PHP 8.2+ (MVC maison ou framework léger) + HTML/CSS/JS vanilla ou Bootstrap  
> **Version :** 1.0.0  
> **Date :** Mai 2025

---

## Sommaire

1. [Vue d'ensemble](#1-vue-densemble)
2. [Architecture & Structure](#2-architecture--structure)
3. [Fonctionnalités détaillées](#3-fonctionnalités-détaillées)
4. [Dashboards & Pages](#4-dashboards--pages)
5. [Routes & Contrôleurs](#5-routes--contrôleurs)
6. [Traçabilité (Audit Log)](#6-traçabilité-audit-log)
7. [Design & Responsive](#7-design--responsive)
8. [Règles métier & Sécurité](#8-règles-métier--sécurité)
9. [Livrables attendus](#9-livrables-attendus)

---

## 1. Vue d'ensemble

**e-Media Support** est une application web de gestion du support client permettant à e-Media de :
- Suivre les projets clients de façon structurée
- Mieux comprendre les besoins des clients
- Éviter les échanges informels sans traçabilité
- Faciliter les mises à jour collaboratives sur chaque projet

### 1.1 Stack technique

| Couche | Technologie | Justification |
|--------|------------|---------------|
| Backend | PHP 8.2+ | Largement supporté, robuste |
| Base de données | MySQL 8+ via `mysqli` | Relationnel, natif PHP |
| Frontend | HTML5 + CSS3 + JS (Bootstrap 5) | Responsive, standard |
| Auth | Sessions PHP natives | Simple, sécurisé |
| Emails | PHPMailer ou `mail()` | Invitations, notifications |
| Validation | PHP natif (filter_var, regex) | Validation des entrées |
| Déploiement | Apache / Nginx + PHP-FPM | Standard hébergement mutualisé ou VPS |

---

## 2. Architecture & Structure

### 2.1 Structure des dossiers

```
/
├── public/                          # Seul dossier exposé au web (document root)
│   ├── index.php                    # Point d'entrée unique (front controller)
│   ├── assets/
│   │   ├── css/
│   │   ├── js/
│   │   └── img/
│   └── .htaccess                    # Réécriture d'URL (Apache)
│
├── app/
│   ├── controllers/
│   │   ├── AuthController.php       # Login, logout, reset MDP
│   │   ├── InvitationController.php # Invitation & complétion de profil
│   │   ├── AdminController.php      # Dashboard, users, projets (admin)
│   │   ├── DeveloperController.php  # Dashboard dev
│   │   ├── ClientController.php     # Dashboard client
│   │   ├── ProjectController.php    # CRUD projets
│   │   ├── TasklistController.php   # CRUD tasklists
│   │   ├── WishlistController.php   # CRUD wishlists
│   │   └── AuditController.php      # Lecture journal d'audit
│   │
│   ├── models/
│   │   ├── User.php
│   │   ├── Project.php
│   │   ├── Tasklist.php
│   │   ├── Wishlist.php
│   │   └── AuditLog.php
│   │
│   ├── views/
│   │   ├── layouts/
│   │   │   ├── admin.php            # Layout sidebar Admin
│   │   │   ├── developer.php        # Layout sidebar Développeur
│   │   │   └── client.php           # Layout sidebar Client
│   │   ├── auth/
│   │   │   ├── login.php
│   │   │   ├── invitation.php       # Complétion de profil
│   │   │   └── reset-password.php
│   │   ├── admin/
│   │   │   ├── dashboard.php
│   │   │   ├── users.php
│   │   │   ├── projects.php
│   │   │   ├── tasklists.php
│   │   │   ├── wishlists.php
│   │   │   ├── audit.php
│   │   │   └── profile.php
│   │   ├── developer/
│   │   │   ├── dashboard.php
│   │   │   ├── my-projects.php
│   │   │   ├── my-tasklists.php
│   │   │   ├── projects.php
│   │   │   └── profile.php
│   │   └── client/
│   │       ├── dashboard.php
│   │       ├── projects.php
│   │       ├── tasklists.php
│   │       ├── wishlists.php
│   │       ├── wishlist-new.php
│   │       └── profile.php
│   │
│   └── core/
│       ├── Router.php               # Routeur simple (GET/POST → Controller@method)
│       ├── Database.php             # Singleton connexion mysqli
│       ├── Session.php              # Helpers session PHP
│       ├── Auth.php                 # Vérification session & rôle
│       ├── Email.php                # Service envoi email
│       ├── Audit.php                # Service logAction()
│       └── Validator.php            # Validation des entrées
│
├── config/
│   ├── database.php                 # Constantes DB (host, user, pass, name)
│   ├── app.php                      # Constantes globales (APP_URL, etc.)
│   └── mail.php                     # Config SMTP
│
├── database/
│   ├── schema.sql                   # Schéma complet (CREATE TABLE)
│   └── migrations/                  # Scripts SQL numérotés
│
└── .env                             # Variables sensibles (non versionné)
```

### 2.2 Connexion à la base de données (mysqli)

```php
// app/core/Database.php
class Database {
    private static ?mysqli $instance = null;

    public static function getInstance(): mysqli {
        if (self::$instance === null) {
            self::$instance = new mysqli(
                $_ENV['DB_HOST'],
                $_ENV['DB_USER'],
                $_ENV['DB_PASS'],
                $_ENV['DB_NAME']
            );
            if (self::$instance->connect_error) {
                die('Erreur de connexion : ' . self::$instance->connect_error);
            }
            self::$instance->set_charset('utf8mb4');
        }
        return self::$instance;
    }
}
```

> **Important :** Toutes les requêtes utilisent des **requêtes préparées** (`prepare` / `bind_param` / `execute`) pour prévenir les injections SQL.

```php
// Exemple d'utilisation systématique
$db = Database::getInstance();
$stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND status = ?");
$stmt->bind_param("ss", $email, $status);
$stmt->execute();
$result = $stmt->get_result();
```

---

### 2.3 Schéma de base de données (MySQL)

```sql
-- database/schema.sql

SET FOREIGN_KEY_CHECKS = 0;

-- Utilisateurs
CREATE TABLE users (
    id           VARCHAR(36)  NOT NULL PRIMARY KEY,  -- UUID v4
    email        VARCHAR(255) NOT NULL UNIQUE,
    role         ENUM('ADMIN','DEV','CLIENT') NOT NULL,
    first_name   VARCHAR(100),                        -- Admin / Dev
    last_name    VARCHAR(100),                        -- Admin / Dev
    company_name VARCHAR(255),                        -- Client
    address      TEXT,                               -- Client
    password_hash VARCHAR(255),
    status       ENUM('PENDING','ACTIVE','SUSPENDED') NOT NULL DEFAULT 'PENDING',
    invite_token VARCHAR(255) UNIQUE,
    invite_expiry DATETIME,
    profile_complete TINYINT(1) NOT NULL DEFAULT 0,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Projets
CREATE TABLE projects (
    id           VARCHAR(36)  NOT NULL PRIMARY KEY,
    name         VARCHAR(255) NOT NULL,
    description  TEXT,
    status       ENUM('ACTIVE','ARCHIVED') NOT NULL DEFAULT 'ACTIVE',
    client_id    VARCHAR(36)  NOT NULL,
    created_by_id VARCHAR(36) NOT NULL,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id)     REFERENCES users(id),
    FOREIGN KEY (created_by_id) REFERENCES users(id)
);

-- Tasklists
CREATE TABLE tasklists (
    id               VARCHAR(36)  NOT NULL PRIMARY KEY,
    title            VARCHAR(255) NOT NULL,
    description      TEXT,
    status           ENUM('PENDING','IN_PROGRESS','DONE','CLIENT_FILLED') NOT NULL DEFAULT 'PENDING',
    project_id       VARCHAR(36)  NOT NULL,
    created_by_id    VARCHAR(36)  NOT NULL,
    assigned_to_id   VARCHAR(36),
    client_filled    TINYINT(1)   NOT NULL DEFAULT 0,
    client_response  TEXT,
    treated_at       DATETIME,
    created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id)     REFERENCES projects(id),
    FOREIGN KEY (created_by_id)  REFERENCES users(id),
    FOREIGN KEY (assigned_to_id) REFERENCES users(id)
);

-- Wishlists
CREATE TABLE wishlists (
    id         VARCHAR(36) NOT NULL PRIMARY KEY,
    content    TEXT        NOT NULL,
    status     ENUM('PENDING','ACKNOWLEDGED','DONE') NOT NULL DEFAULT 'PENDING',
    client_id  VARCHAR(36) NOT NULL,
    project_id VARCHAR(36),
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id)  REFERENCES users(id),
    FOREIGN KEY (project_id) REFERENCES projects(id)
);

-- Journal d'audit
CREATE TABLE audit_logs (
    id          VARCHAR(36)  NOT NULL PRIMARY KEY,
    action      VARCHAR(100) NOT NULL,   -- Ex: PROJECT_CREATED
    entity_type VARCHAR(100) NOT NULL,   -- Ex: Project, Tasklist
    entity_id   VARCHAR(36)  NOT NULL,
    user_id     VARCHAR(36)  NOT NULL,
    metadata    JSON,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

SET FOREIGN_KEY_CHECKS = 1;
```

> **Note :** Les IDs sont des UUID v4 générés en PHP avec `bin2hex(random_bytes(16))` formaté, ou via `ramsey/uuid` si Composer est utilisé.

---

## 3. Fonctionnalités détaillées

### 3.1 Gestion des comptes

#### 3.1.1 Invitation d'un utilisateur
**Rôle autorisé :** Administrateur uniquement

| Étape | Description |
|-------|-------------|
| 1 | L'admin saisit un email et sélectionne le rôle (ADMIN, DEV, CLIENT) |
| 2 | PHP génère un token aléatoire (`bin2hex(random_bytes(32))`) avec expiration à 72h |
| 3 | Un email est envoyé avec le lien : `/invitation?token=xxxx` |
| 4 | L'utilisateur est créé en BDD avec `status='PENDING'` et `profile_complete=0` |

#### 3.1.2 Complétion de profil

| Rôle | Champs obligatoires | Validation |
|------|-------------------|------------|
| Admin / Dev | Prénom, Nom, Mot de passe, Confirmation MDP | MDP ≥ 8 chars, 1 maj + 1 chiffre, confirmation identique |
| Client | Nom entreprise, Adresse, Mot de passe, Confirmation MDP | Idem |

- Après soumission : `profile_complete=1`, `status='ACTIVE'`, token mis à NULL
- Si le token est expiré ou invalide : afficher un message d'erreur et inviter à contacter l'admin

```php
// Vérification du token en PHP
$stmt = $db->prepare(
    "SELECT * FROM users WHERE invite_token = ? AND invite_expiry > NOW() AND status = 'PENDING'"
);
$stmt->bind_param("s", $token);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
if (!$user) {
    // Token invalide ou expiré
}
```

#### 3.1.3 Authentification & Redirection

- Page de login : `/login` — email + mot de passe
- Session PHP native (`session_start()`) — durée configurable via `session.gc_maxlifetime`
- Middleware PHP protège toutes les pages par rôle (`Auth::require('ADMIN')`)
- Redirection post-login selon le rôle :
  - `ADMIN` → `/admin/dashboard`
  - `DEV` → `/developer/dashboard`
  - `CLIENT` → `/client/dashboard`
- Compte `SUSPENDED` → erreur 403, accès bloqué

```php
// app/core/Auth.php
class Auth {
    public static function check(): bool {
        return isset($_SESSION['user_id']);
    }

    public static function user(): ?array {
        return $_SESSION['user'] ?? null;
    }

    public static function require(string ...$roles): void {
        if (!self::check()) {
            header('Location: /login');
            exit;
        }
        $user = self::user();
        if ($user['status'] === 'SUSPENDED') {
            http_response_code(403);
            include 'views/errors/403.php';
            exit;
        }
        if (!empty($roles) && !in_array($user['role'], $roles)) {
            http_response_code(403);
            include 'views/errors/403.php';
            exit;
        }
    }
}
```

---

### 3.2 Actions par rôle

#### 3.2.1 Administrateur

| Action | Détail |
|--------|--------|
| Inviter un utilisateur | Formulaire : email + rôle. Génère token, envoie email |
| Gérer les utilisateurs | Liste tous les comptes. Actions : Suspendre / Réactiver |
| Attribuer une tasklist | Select pour choisir un dev et le réassigner |
| Créer un projet | Formulaire : nom, description, sélection du client |
| Créer une tasklist | Formulaire : titre, description, sélection du projet |
| Marquer une tasklist traitée | Bouton → `status='DONE'`, `treated_at=NOW()` |

#### 3.2.2 Développeur

| Action | Détail |
|--------|--------|
| Créer un projet | Même formulaire que l'admin |
| Créer une tasklist | Assignée à lui-même par défaut (`assigned_to_id = $_SESSION['user_id']`) |
| Voir ses tasklists | Dashboard affiche uniquement `assigned_to_id = userId` |
| Marquer une tasklist traitée | Uniquement pour les tasklists qui lui sont assignées |

#### 3.2.3 Client

| Action | Détail |
|--------|--------|
| Voir ses projets | Liste des projets dont il est le `client_id` |
| Voir ses tasklists | Tasklists liées à ses projets |
| Compléter une tasklist | Zone de texte libre → `client_response` + `client_filled=1` |
| Envoyer une liste d'attentes | Formulaire : description + projet optionnel → crée une `Wishlist` |

#### 3.2.4 Tous les utilisateurs

- **Édition de profil :** formulaire prérempli, email affiché en lecture seule (non modifiable)
- **Réinitialisation MDP :** lien "Mot de passe oublié" → email avec lien sécurisé → formulaire nouveau MDP

---

## 4. Dashboards & Pages

### 4.1 Dashboard Administrateur (`/admin/`)

| Page | Contenu & Fonctionnalités |
|------|--------------------------|
| `dashboard` | KPIs : nb projets actifs, nb tasklists en cours, nb clients, nb demandes en attente |
| `users` | Tableau paginé avec filtres (rôle, statut). Boutons Suspendre / Réactiver. Badge de statut coloré |
| `projects` | Liste des projets avec statut, client associé, date. Actions : Voir, Modifier, Archiver |
| `tasklists` | Tableau avec filtre par projet/statut. Colonne "Assigné à" avec select pour réassigner |
| `wishlists` | Tableau des Wishlists avec statut. Actions : Acquitter, Marquer comme fait |
| `audit` | Timeline des actions : qui, quoi, quand. Filtres par type d'action, utilisateur, date |
| `profile` | Formulaire d'édition (prénom, nom, MDP). Email en lecture seule |

### 4.2 Dashboard Développeur (`/developer/`)

| Page | Contenu & Fonctionnalités |
|------|--------------------------|
| `dashboard` | KPIs : nb projets créés, nb tasklists assignées, nb tasklists traitées |
| `my-projects` | Projets créés par ce dev. Actions : Voir détail, Créer tasklist |
| `my-tasklists` | Tasklists assignées. Filtres : statut. Action : Marquer traitée |
| `projects` | Vue lecture de tous les projets actifs |
| `profile` | Formulaire d'édition. Email en lecture seule |

### 4.3 Dashboard Client (`/client/`)

| Page | Contenu & Fonctionnalités |
|------|--------------------------|
| `dashboard` | KPIs : nb projets, nb tasklists à compléter, nb demandes envoyées |
| `projects` | Liste des projets avec statut et dernière activité |
| `tasklists` | Tasklists reçues. Badge "À compléter" si `client_filled=0`. Bouton "Compléter" |
| `wishlists` | Liste des demandes envoyées avec statut |
| `wishlists/new` | Formulaire : description libre + projet optionnel |
| `profile` | Formulaire d'édition. Email en lecture seule |

---

## 5. Routes & Contrôleurs

Le routeur frontal (`public/index.php`) parse `$_GET['url']` ou utilise le PATH_INFO pour dispatcher vers le bon contrôleur.

### 5.1 Auth & Invitations

| URL | Méthode | Contrôleur@méthode | Auth |
|-----|---------|--------------------|------|
| `/login` | GET / POST | `AuthController@login` | Non |
| `/logout` | GET | `AuthController@logout` | Oui |
| `/invitation` | GET / POST | `InvitationController@complete` | Non |
| `/reset-password` | GET / POST | `AuthController@resetRequest` | Non |
| `/reset-password/confirm` | GET / POST | `AuthController@resetConfirm` | Non |

### 5.2 Utilisateurs

| URL | Méthode | Contrôleur@méthode | Auth |
|-----|---------|--------------------|------|
| `/admin/users` | GET | `AdminController@users` | ADMIN |
| `/admin/users/invite` | POST | `AdminController@inviteUser` | ADMIN |
| `/admin/users/status` | POST | `AdminController@updateStatus` | ADMIN |
| `/admin/profile` | GET / POST | `AdminController@profile` | ADMIN |
| `/developer/profile` | GET / POST | `DeveloperController@profile` | DEV |
| `/client/profile` | GET / POST | `ClientController@profile` | CLIENT |

### 5.3 Projets

| URL | Méthode | Contrôleur@méthode | Auth |
|-----|---------|--------------------|------|
| `/admin/projects` | GET | `ProjectController@indexAdmin` | ADMIN |
| `/admin/projects/create` | GET / POST | `ProjectController@create` | ADMIN, DEV |
| `/admin/projects/edit` | GET / POST | `ProjectController@edit` | ADMIN, DEV |
| `/admin/projects/archive` | POST | `ProjectController@archive` | ADMIN |
| `/developer/my-projects` | GET | `ProjectController@indexDev` | DEV |
| `/client/projects` | GET | `ProjectController@indexClient` | CLIENT |

### 5.4 Tasklists

| URL | Méthode | Contrôleur@méthode | Auth |
|-----|---------|--------------------|------|
| `/admin/tasklists` | GET | `TasklistController@indexAdmin` | ADMIN |
| `/admin/tasklists/create` | GET / POST | `TasklistController@create` | ADMIN, DEV |
| `/admin/tasklists/assign` | POST | `TasklistController@assign` | ADMIN |
| `/admin/tasklists/treat` | POST | `TasklistController@treat` | ADMIN, DEV assigné |
| `/developer/my-tasklists` | GET | `TasklistController@indexDev` | DEV |
| `/client/tasklists` | GET | `TasklistController@indexClient` | CLIENT |
| `/client/tasklists/fill` | POST | `TasklistController@clientFill` | CLIENT |

### 5.5 Wishlists & Audit

| URL | Méthode | Contrôleur@méthode | Auth |
|-----|---------|--------------------|------|
| `/admin/wishlists` | GET | `WishlistController@indexAdmin` | ADMIN |
| `/admin/wishlists/status` | POST | `WishlistController@updateStatus` | ADMIN |
| `/client/wishlists` | GET | `WishlistController@indexClient` | CLIENT |
| `/client/wishlists/new` | GET / POST | `WishlistController@create` | CLIENT |
| `/admin/audit` | GET | `AuditController@index` | ADMIN |

---

## 6. Traçabilité (Audit Log)

Chaque action importante est enregistrée via `app/core/Audit.php` → méthode `logAction()`.

```php
// app/core/Audit.php
class Audit {
    public static function logAction(
        string $action,
        string $entityType,
        string $entityId,
        string $userId,
        array  $metadata = []
    ): void {
        $db = Database::getInstance();
        $id = bin2hex(random_bytes(16)); // UUID simplifié
        $metaJson = !empty($metadata) ? json_encode($metadata) : null;

        $stmt = $db->prepare(
            "INSERT INTO audit_logs (id, action, entity_type, entity_id, user_id, metadata)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("ssssss", $id, $action, $entityType, $entityId, $userId, $metaJson);
        $stmt->execute();
    }
}
```

### Actions à logger

| Action | Déclencheur | Metadata |
|--------|-------------|----------|
| `PROJECT_CREATED` | ProjectController@create | `{ project_name, client_id, client_name }` |
| `PROJECT_ARCHIVED` | ProjectController@archive | `{ project_name }` |
| `TASKLIST_CREATED` | TasklistController@create | `{ title, project_id, assigned_to_id }` |
| `TASKLIST_ASSIGNED` | TasklistController@assign | `{ previous_assignee, new_assignee }` |
| `TASKLIST_TREATED` | TasklistController@treat | `{ treated_at }` |
| `TASKLIST_CLIENT_FILLED` | TasklistController@clientFill | `{ client_id }` |
| `USER_INVITED` | AdminController@inviteUser | `{ email, role }` |
| `USER_SUSPENDED` | AdminController@updateStatus | `{ reason? }` |
| `USER_REACTIVATED` | AdminController@updateStatus | `{}` |
| `WISHLIST_CREATED` | WishlistController@create | `{ content_preview }` |

---

## 7. Design & Responsive

### 7.1 Palette de couleurs par rôle

| Rôle | Couleur primaire | Usage |
|------|-----------------|-------|
| Admin | `#1E3A5F` (bleu foncé) | Sidebar, boutons principaux |
| Développeur | `#2E6DA4` (bleu moyen) | Sidebar, boutons principaux |
| Client | `#0D7A5F` (vert/teal) | Sidebar, boutons principaux |

### 7.2 Bibliothèques Frontend recommandées

- **CSS / Composants :** Bootstrap 5 (cards, tables, badges, modals, forms)
- **Icônes :** Bootstrap Icons ou Font Awesome 6
- **Notifications :** Toastr.js ou alertes Bootstrap
- **Formulaires :** Validation HTML5 native + contrôles PHP côté serveur

### 7.3 Composants à créer (partials PHP)

| Fichier | Description |
|---------|-------------|
| `views/partials/status_badge.php` | Badge coloré selon statut (PENDING=jaune, IN_PROGRESS=bleu, DONE=vert, SUSPENDED=rouge) |
| `views/partials/data_table.php` | Tableau avec pagination PHP et filtres GET |
| `views/partials/confirm_modal.php` | Modal Bootstrap de confirmation pour actions destructives |
| `views/partials/user_avatar.php` | Initiales dans un cercle coloré selon le rôle |
| `views/partials/assign_select.php` | Select avec liste des devs disponibles |

### 7.4 Responsive

| Breakpoint | Layout | Navigation |
|------------|--------|------------|
| Mobile (< 768px) | Colonne unique | Burger menu (offcanvas Bootstrap) |
| Tablet (768–1024px) | Grille 2 colonnes sur certaines vues | Sidebar rétractable |
| Desktop (> 1024px) | Sidebar fixe + contenu principal | Sidebar étendue avec labels |

---

## 8. Règles métier & Sécurité

### 8.1 Règles métier

- Un projet est créé pour **un seul client** (relation 1:N Client → Projects)
- Une tasklist est toujours liée à un projet, donc indirectement à un client
- Par défaut, une tasklist est assignée au **créateur** (Admin ou Dev)
- Seul l'**Admin** peut réassigner une tasklist à un autre développeur
- Un Dev ne voit dans son dashboard que **ses tasklists assignées**
- Un Client ne voit que **ses propres** projets et tasklists
- Un compte **SUSPENDED** ne peut pas se connecter (retourner 403)
- L'**email est immutable** après création du compte
- Le token d'invitation expire après **72h** — au-delà, afficher une erreur

### 8.2 Sécurité

- **Toutes les requêtes SQL** utilisent des requêtes préparées mysqli (jamais de concaténation directe)
- **Vérification du rôle côté serveur** en début de chaque page/contrôleur via `Auth::require()`
- **Hash des mots de passe** avec `password_hash($password, PASSWORD_BCRYPT, ['cost' => 12])`
- **Vérification** avec `password_verify($input, $hash)`
- **Tokens** d'invitation et de réinitialisation : `bin2hex(random_bytes(32))`
- **Protection CSRF** : token CSRF en session inclus dans chaque formulaire POST
- **Échappement des sorties** : `htmlspecialchars($var, ENT_QUOTES, 'UTF-8')` sur toutes les variables affichées en HTML
- **Rate limiting** rudimentaire sur les routes d'auth (compteur en session ou en BDD)
- **En-têtes de sécurité HTTP** : `X-Frame-Options`, `X-Content-Type-Options`, `Content-Security-Policy`

```php
// Exemple protection CSRF
// Génération (dans le formulaire)
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
echo '<input type="hidden" name="csrf_token" value="' . $_SESSION['csrf_token'] . '">';

// Vérification (à la réception du POST)
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    http_response_code(403);
    exit('CSRF token invalide');
}
```

### 8.3 Variables d'environnement

```env
# .env (non versionné — ajouter au .gitignore)
DB_HOST=localhost
DB_NAME=emedia_support
DB_USER=root
DB_PASS=fabio

APP_URL=https://votre-domaine.com
APP_ENV=production          # development | production

MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=paulingoudo@gmail.com
MAIL_PASSWORD=wxbi vvkj zxxb qzij
MAIL_FROM=noreply@emedia.com
MAIL_FROM_NAME=e-Media Support
```

> Chargement des variables avec une librairie légère (`vlucas/phpdotenv`) ou manuellement via `parse_ini_file('.env')`.

---

## 9. Livrables attendus

### 9.1 Code source

- Repository Git structuré selon l'arborescence de la section 2.1
- Code PHP avec vérification des types (`declare(strict_types=1)`)
- `README.md` avec instructions d'installation et de configuration
- `.env.example` avec toutes les variables requises
- `database/schema.sql` complet + scripts de migration numérotés
-`Crée un compte admin par défaut à l'installation fabiodab83@gmail.com et apk_APK_4774 pour mdp`

### 9.2 Checklist de validation

| Critère | OK |
|---------|-----|
| Invitation email fonctionnelle, lien valide 72h | ☐ |
| Profil Admin/Dev complétable via lien d'invitation | ☐ |
| Profil Client complétable via lien d'invitation | ☐ |
| Login redirige vers le bon dashboard selon le rôle | ☐ |
| Admin peut suspendre / réactiver un compte | ☐ |
| Compte suspendu bloqué à la connexion | ☐ |
| Admin peut créer un projet pour un client | ☐ |
| Dev peut créer une tasklist, assignée à lui par défaut | ☐ |
| Admin peut réassigner une tasklist à un autre dev | ☐ |
| Dev voit uniquement ses tasklists dans son dashboard | ☐ |
| Client voit uniquement ses projets et tasklists | ☐ |
| Client peut compléter une tasklist | ☐ |
| Client peut envoyer une liste d'attentes | ☐ |
| Journal d'audit enregistre toutes les actions clés | ☐ |
| Édition de profil possible, email non modifiable | ☐ |
| Réinitialisation de mot de passe fonctionnelle | ☐ |
| Toutes les requêtes SQL utilisent des requêtes préparées | ☐ |
| Protection CSRF sur tous les formulaires POST | ☐ |
| Sorties HTML échappées (htmlspecialchars) | ☐ |
| Responsive sur mobile (< 768px) | ☐ |
| Responsive sur desktop (> 1024px) | ☐ |

<?php
declare(strict_types=1);

class Helper {
    public static function uuid(): string {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    public static function escape(string $value): string {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    public static function redirect(string $url): void {
        header('Location: ' . $url);
        exit;
    }

    /**
     * URL absolue de l'application (emails, liens d'invitation).
     * En développement, utilise l'hôte/port de la requête courante si disponible.
     */
    public static function appUrl(string $path = ''): string {
        $appConfig = include dirname(__DIR__, 2) . '/config/app.php';
        $base = rtrim((string)($appConfig['url'] ?? ''), '/');
        $env = $appConfig['env'] ?? 'development';

        if ($env === 'development' && !empty($_SERVER['HTTP_HOST'])) {
            $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
            $base = ($https ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
        }

        if ($base === '') {
            $base = 'http://localhost:8000';
        }

        $basePath = rtrim((string)($_ENV['APP_BASE_PATH'] ?? ''), '/');

        if ($path !== '' && !str_starts_with($path, '/')) {
            $path = '/' . $path;
        }

        return $base . $basePath . $path;
    }

    public static function formatDateTime(string $datetime): string {
        return date('d/m/Y H:i', strtotime($datetime));
    }

    public static function formatDate(string $datetime): string {
        return date('d/m/Y', strtotime($datetime));
    }

    public static function formatTime(string $datetime): string {
        return date('H:i', strtotime($datetime));
    }

    public static function datetimeIso(string $datetime): string {
        $ts = strtotime($datetime);
        return $ts !== false ? date('c', $ts) : '';
    }

    /**
     * Calcule les paramètres de pagination à partir de la requête.
     * Renvoie : page, per_page, total_pages, offset, per_page_options.
     */
    public static function paginationFromRequest(array $get, int $total, array $perPageOptions = [10, 25, 50, 100], int $defaultPerPage = 25): array {
        $perPage = (int)($get['per_page'] ?? $defaultPerPage);
        if (!in_array($perPage, $perPageOptions, true)) {
            $perPage = $defaultPerPage;
        }

        $page = max(1, (int)($get['page'] ?? 1));
        $totalPages = max(1, (int)ceil($total / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }

        return [
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => $totalPages,
            'offset' => ($page - 1) * $perPage,
            'per_page_options' => $perPageOptions,
        ];
    }

    /**
     * Tableau des actions d'audit [code => libellé français].
     */
    public static function auditActions(): array {
        return [
            'PROJECT_CREATED' => 'Projet créé',
            'PROJECT_ARCHIVED' => 'Projet archivé',
            'TASKLIST_CREATED' => 'Liste créée',
            'TASKLIST_UPDATED' => 'Liste modifiée',
            'TASKLIST_PUBLISHED' => 'Liste publiée',
            'TASKLIST_ASSIGNED' => 'Liste assignée',
            'TASKLIST_TREATED' => 'Liste traitée',
            'TASKLIST_DELETED' => 'Liste supprimée',
            'TASKLIST_CLIENT_FILLED' => 'Réponse client enregistrée',
            'USER_INVITED' => 'Utilisateur invité',
            'USER_LOGIN' => 'Connexion',
            'USER_LOGOUT' => 'Déconnexion',
            'USER_SUSPENDED' => 'Compte suspendu',
            'USER_REACTIVATED' => 'Compte réactivé',
            'PASSWORD_RESET_REQUESTED' => 'Réinitialisation du mot de passe demandée',
            'PASSWORD_RESET_COMPLETED' => 'Mot de passe réinitialisé',
            'OTP_SENT' => 'Code de vérification envoyé',
            'OTP_VERIFIED' => 'Code de vérification validé',
            'OTP_FAILED' => 'Code de vérification rejeté',
            'WISHLIST_CREATED' => 'Demande client créée',
        ];
    }

    /**
     * Libellé français d'une action d'audit (raccourci depuis un code).
     */
    public static function actionLabel(string $action): string {
        return self::auditActions()[$action] ?? $action;
    }

    /**
     * Tableau des types d'entité d'audit [clé => libellé français].
     */
    public static function auditEntities(): array {
        return [
            'Project' => 'Projet',
            'Tasklist' => 'Liste de tâches',
            'User' => 'Utilisateur',
            'Wishlist' => 'Demande client',
        ];
    }

    /**
     * Libellé français d'un type d'entité.
     */
    public static function entityLabel(string $entity): string {
        return self::auditEntities()[$entity] ?? $entity;
    }

    /**
     * Affiche les métadonnées d'audit sous forme de paires clé/valeur lisibles,
     * en ne révélant aucun identifiant ni nom de colonne interne.
     */
    public static function auditMetadata(?string $metadataJson): array {
        if (empty($metadataJson)) {
            return [];
        }
        $data = json_decode($metadataJson, true);
        if (!is_array($data)) {
            return [];
        }

        $labels = [
            'name' => 'Intitulé',
            'title' => 'Intitulé',
            'project_name' => 'Projet concerné',
            'description' => 'Description',
            'reason' => 'Motif',
            'email' => 'Adresse email',
            'role' => 'Profil',
            'email_sent' => 'Notification envoyée',
            'status' => 'Statut',
            'questions_count' => 'Nombre de questions',
            'questions_answered' => 'Réponses fournies',
            'content_preview' => 'Contenu',
            'expires_in' => 'Durée de validité (sec)',
        ];

        // Champs techniques toujours masqués (identifiants internes, timestamps...)
        $hidden = [
            'id', 'user_id', 'client_id', 'project_id', 'created_by_id',
            'assigned_to_id', 'previous_assignee', 'new_assignee',
            'created_at', 'updated_at', 'treated_at', 'entity_id',
        ];

        $out = [];
        foreach ($data as $key => $value) {
            if (in_array($key, $hidden, true)) {
                continue;
            }
            $label = $labels[$key] ?? self::humanize($key);
            if (is_bool($value)) {
                $value = $value ? 'Oui' : 'Non';
            }
            $out[$label] = is_scalar($value) || $value === null ? (string)$value : json_encode($value);
        }
        return $out;
    }

    private static function humanize(string $key): string {
        $key = str_replace(['_', '-'], ' ', $key);
        return ucfirst(trim($key));
    }
}

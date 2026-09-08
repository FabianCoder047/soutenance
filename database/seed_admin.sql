-- Réinsère uniquement le compte administrateur par défaut
-- Email : codingfabio20@gmail.com | Mot de passe : azerty123

INSERT INTO users (id, email, role, first_name, last_name, password_hash, status, profile_complete)
VALUES (
    'f0000000-0000-0000-0000-000000000001',
    'codingfabio20@gmail.com',
    'ADMIN',
    'Fabio',
    'DAB',
    '$2y$12$3dnJqbSkuakcZ8C/RBwnserncKKjGFN45UChdBmoByFtBlIafr/Ee',
    'ACTIVE',
    1
)
ON DUPLICATE KEY UPDATE
    email = VALUES(email),
    role = VALUES(role),
    first_name = VALUES(first_name),
    last_name = VALUES(last_name),
    password_hash = VALUES(password_hash),
    status = VALUES(status),
    profile_complete = VALUES(profile_complete),
    invite_token = NULL,
    invite_expiry = NULL;

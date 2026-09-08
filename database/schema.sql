SET FOREIGN_KEY_CHECKS = 0;

-- Drop tables if they exist (for clean setup/reset)
DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS wishlists;
DROP TABLE IF EXISTS tasklist_answer_files;
DROP TABLE IF EXISTS tasklist_answer_options;
DROP TABLE IF EXISTS tasklist_answers;
DROP TABLE IF EXISTS tasklist_question_options;
DROP TABLE IF EXISTS tasklist_questions;
DROP TABLE IF EXISTS tasklists;
DROP TABLE IF EXISTS projects;
DROP TABLE IF EXISTS users;

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
    otp_code      VARCHAR(255),           -- Code de vérification à 6 chiffres (hashé)
    otp_expiry    DATETIME,               -- Expiration du code (300 s)
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
    status           ENUM('DRAFT','PENDING','IN_PROGRESS','DONE','CLIENT_FILLED') NOT NULL DEFAULT 'DRAFT',
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

-- Questions d'une tasklist
CREATE TABLE tasklist_questions (
    id           VARCHAR(36)  NOT NULL PRIMARY KEY,
    tasklist_id  VARCHAR(36)  NOT NULL,
    label        TEXT         NOT NULL,
    type         ENUM('SINGLE_CHOICE','MULTIPLE_CHOICE','LONG_TEXT','FILE_UPLOAD') NOT NULL,
    required     TINYINT(1)   NOT NULL DEFAULT 1,
    sort_order   INT          NOT NULL DEFAULT 0,
    created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tasklist_id) REFERENCES tasklists(id) ON DELETE CASCADE
);

-- Options pour choix unique / multiple
CREATE TABLE tasklist_question_options (
    id           VARCHAR(36)  NOT NULL PRIMARY KEY,
    question_id  VARCHAR(36)  NOT NULL,
    label        VARCHAR(255) NOT NULL,
    sort_order   INT          NOT NULL DEFAULT 0,
    FOREIGN KEY (question_id) REFERENCES tasklist_questions(id) ON DELETE CASCADE
);

-- Réponses client par question
CREATE TABLE tasklist_answers (
    id           VARCHAR(36)  NOT NULL PRIMARY KEY,
    question_id  VARCHAR(36)  NOT NULL,
    tasklist_id  VARCHAR(36)  NOT NULL,
    text_value   TEXT,
    created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (question_id) REFERENCES tasklist_questions(id) ON DELETE CASCADE,
    FOREIGN KEY (tasklist_id) REFERENCES tasklists(id) ON DELETE CASCADE,
    UNIQUE KEY uq_answer_question (question_id, tasklist_id)
);

-- Options sélectionnées (choix unique / multiple)
CREATE TABLE tasklist_answer_options (
    answer_id    VARCHAR(36) NOT NULL,
    option_id    VARCHAR(36) NOT NULL,
    PRIMARY KEY (answer_id, option_id),
    FOREIGN KEY (answer_id) REFERENCES tasklist_answers(id) ON DELETE CASCADE,
    FOREIGN KEY (option_id) REFERENCES tasklist_question_options(id) ON DELETE CASCADE
);

-- Fichiers joints (images, documents)
CREATE TABLE tasklist_answer_files (
    id            VARCHAR(36)  NOT NULL PRIMARY KEY,
    answer_id     VARCHAR(36)  NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    stored_path   VARCHAR(500) NOT NULL,
    mime_type     VARCHAR(100),
    file_size     INT UNSIGNED NOT NULL DEFAULT 0,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (answer_id) REFERENCES tasklist_answers(id) ON DELETE CASCADE
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

-- Create default admin account
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
);

SET FOREIGN_KEY_CHECKS = 1;

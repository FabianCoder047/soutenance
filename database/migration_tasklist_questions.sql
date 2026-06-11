-- Migration : ajout du système de questions typées aux tasklists
-- Exécuter sur une base existante : mysql -u user -p database < migration_tasklist_questions.sql

SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS tasklist_questions (
    id           VARCHAR(36)  NOT NULL PRIMARY KEY,
    tasklist_id  VARCHAR(36)  NOT NULL,
    label        TEXT         NOT NULL,
    type         ENUM('SINGLE_CHOICE','MULTIPLE_CHOICE','LONG_TEXT','FILE_UPLOAD') NOT NULL,
    required     TINYINT(1)   NOT NULL DEFAULT 1,
    sort_order   INT          NOT NULL DEFAULT 0,
    created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tasklist_id) REFERENCES tasklists(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS tasklist_question_options (
    id           VARCHAR(36)  NOT NULL PRIMARY KEY,
    question_id  VARCHAR(36)  NOT NULL,
    label        VARCHAR(255) NOT NULL,
    sort_order   INT          NOT NULL DEFAULT 0,
    FOREIGN KEY (question_id) REFERENCES tasklist_questions(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS tasklist_answers (
    id           VARCHAR(36)  NOT NULL PRIMARY KEY,
    question_id  VARCHAR(36)  NOT NULL,
    tasklist_id  VARCHAR(36)  NOT NULL,
    text_value   TEXT,
    created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (question_id) REFERENCES tasklist_questions(id) ON DELETE CASCADE,
    FOREIGN KEY (tasklist_id) REFERENCES tasklists(id) ON DELETE CASCADE,
    UNIQUE KEY uq_answer_question (question_id, tasklist_id)
);

CREATE TABLE IF NOT EXISTS tasklist_answer_options (
    answer_id    VARCHAR(36) NOT NULL,
    option_id    VARCHAR(36) NOT NULL,
    PRIMARY KEY (answer_id, option_id),
    FOREIGN KEY (answer_id) REFERENCES tasklist_answers(id) ON DELETE CASCADE,
    FOREIGN KEY (option_id) REFERENCES tasklist_question_options(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS tasklist_answer_files (
    id            VARCHAR(36)  NOT NULL PRIMARY KEY,
    answer_id     VARCHAR(36)  NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    stored_path   VARCHAR(500) NOT NULL,
    mime_type     VARCHAR(100),
    file_size     INT UNSIGNED NOT NULL DEFAULT 0,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (answer_id) REFERENCES tasklist_answers(id) ON DELETE CASCADE
);

SET FOREIGN_KEY_CHECKS = 1;

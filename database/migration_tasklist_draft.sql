-- Ajout du statut DRAFT (brouillon) aux tasklists
ALTER TABLE tasklists
    MODIFY COLUMN status ENUM('DRAFT','PENDING','IN_PROGRESS','DONE','CLIENT_FILLED') NOT NULL DEFAULT 'DRAFT';

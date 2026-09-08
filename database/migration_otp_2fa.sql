-- Migration : ajout de la double authentification (code à 6 chiffres)
-- Exécuter sur une base existante : mysql -u user -p database < migration_otp_2fa.sql

ALTER TABLE users
    ADD COLUMN otp_code VARCHAR(255) NULL AFTER profile_complete,
    ADD COLUMN otp_expiry DATETIME NULL AFTER otp_code;
ALTER TABLE verification_codes
    ADD COLUMN code_hash VARCHAR(255) NULL AFTER code,
    ADD COLUMN expires_at DATETIME NULL AFTER created_at;

DELETE FROM verification_codes;

ALTER TABLE verification_codes
    MODIFY code_hash VARCHAR(255) NOT NULL,
    MODIFY expires_at DATETIME NOT NULL,
    DROP COLUMN code;
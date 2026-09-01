/*
  db_mentor_trigger.sql - adds a trigger to enforce corporate email domain for mentors
*/
DELIMITER $$
CREATE TRIGGER before_mentor_insert
BEFORE INSERT ON users
FOR EACH ROW
BEGIN
    IF NEW.role = 'mentor' THEN
        DECLARE domain VARCHAR(255);
        SET domain = SUBSTRING_INDEX(NEW.email, '@', -1);
        IF domain IN ('gmail.com','yahoo.com','hotmail.com','outlook.com','live.com') THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Mentor email must be a corporate or institutional address.';
        END IF;
    END IF;
END$$
DELIMITER ;

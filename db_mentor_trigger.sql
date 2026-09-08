/*
  db_mentor_trigger.sql - adds a trigger to enforce corporate email domain for mentors
*/
DELIMITER $$

DROP TRIGGER IF EXISTS before_mentor_insert$$
CREATE TRIGGER before_mentor_insert
BEFORE INSERT ON users
FOR EACH ROW
BEGIN
    IF NEW.role = 'mentor' THEN
        IF SUBSTRING_INDEX(NEW.email, '@', -1) IN ('gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com', 'live.com', 'aol.com') THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Mentor email must be a corporate or institutional address.';
        END IF;
    END IF;
END$$

DELIMITER ;

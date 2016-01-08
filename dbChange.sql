---- 2016/1/18
ALTER TABLE `photos`
	CHANGE COLUMN `user_id` `user_id` INT(11) NOT NULL AFTER `id`,
	ADD COLUMN `name` VARCHAR(255) NOT NULL AFTER `user_id`,
	CHANGE COLUMN `description` `description` TINYTEXT NULL AFTER `thumbnail`;

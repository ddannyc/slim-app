-- 2016/1/9
ALTER TABLE `photos`
	ADD COLUMN `is_public` TINYINT NOT NULL DEFAULT '0' AFTER `edited`;

-- 2016/1/8
ALTER TABLE `photos`
	CHANGE COLUMN `user_id` `user_id` INT(11) NOT NULL AFTER `id`,
	ADD COLUMN `name` VARCHAR(255) NOT NULL AFTER `user_id`,
	CHANGE COLUMN `description` `description` TINYTEXT NULL AFTER `thumbnail`;

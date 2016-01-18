-- 2016/1/18
ALTER TABLE `photos`
	ADD COLUMN `album_id` INT(11) NOT NULL DEFAULT '0' AFTER `id`,
	ADD COLUMN `tags_id` INT(11) NOT NULL DEFAULT '0' AFTER `album_id`;

CREATE TABLE `albums` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`user_id` INT(11) NOT NULL,
	`name` VARCHAR(255) NULL DEFAULT NULL,
	`description` TINYTEXT NULL DEFAULT NULL,
	`created` TIMESTAMP NULL DEFAULT NULL,
	`cover` INT(11) NULL DEFAULT NULL,
	`weight` INT NOT NULL DEFAULT '0',
	`is_public` TINYINT NOT NULL DEFAULT '0'
	PRIMARY KEY (`id`)
)
COLLATE='utf8_general_ci'
ENGINE=InnoDB
;

CREATE TABLE `tags` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`name` VARCHAR(255) NOT NULL,
	`created` TIMESTAMP NOT NULL,
	`weight` INT NOT NULL DEFAULT '0',
	PRIMARY KEY (`id`)
)
COLLATE='utf8_general_ci'
ENGINE=InnoDB
;

CREATE TABLE `tags_use` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`type` TINYINT NOT NULL DEFAULT '0' COMMENT '1 as photo,  >1 as others',
	`target_id` INT NOT NULL COMMENT 'target maybe photo or other objects id',
	`tag_id` INT NOT NULL,
	PRIMARY KEY (`id`)
)
COLLATE='utf8_general_ci'
ENGINE=InnoDB
;

-- 2016/1/9
ALTER TABLE `photos`
	ADD COLUMN `is_public` TINYINT NOT NULL DEFAULT '0' AFTER `edited`;

-- 2016/1/8
ALTER TABLE `photos`
	CHANGE COLUMN `user_id` `user_id` INT(11) NOT NULL AFTER `id`,
	ADD COLUMN `name` VARCHAR(255) NOT NULL AFTER `user_id`,
	CHANGE COLUMN `description` `description` TINYTEXT NULL AFTER `thumbnail`;

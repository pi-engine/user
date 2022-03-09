CREATE TABLE `account`
(
    `id`             INT(10) UNSIGNED    NOT NULL AUTO_INCREMENT,
    `name`           VARCHAR(255)                 DEFAULT NULL,
    `identity`       VARCHAR(128)                 DEFAULT NULL,
    `email`          VARCHAR(128)                 DEFAULT NULL,
    `credential`     VARCHAR(255)        NOT NULL DEFAULT '',
    `status`         TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
    `time_created`   INT(10) UNSIGNED    NOT NULL DEFAULT '0',
    `time_activated` INT(10) UNSIGNED    NOT NULL DEFAULT '0',
    `time_disabled`  INT(10) UNSIGNED    NOT NULL DEFAULT '0',
    `time_deleted`   INT(10) UNSIGNED    NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`),
    UNIQUE KEY `identity` (`identity`),
    UNIQUE KEY `email` (`email`),
    KEY `name` (`name`),
    KEY `status` (`status`)
);

CREATE TABLE `profile`
(
    `id`             INT(10) UNSIGNED    NOT NULL AUTO_INCREMENT,

    PRIMARY KEY (`id`)
);

CREATE TABLE `role`
(
    `id`      INT(10) UNSIGNED        NOT NULL AUTO_INCREMENT,
    `name`    VARCHAR(255)                     DEFAULT NULL,
    `title`   VARCHAR(128)                     DEFAULT NULL,
    `section` ENUM ('front', 'admin') NOT NULL DEFAULT 'front',
    `status`  TINYINT(1) UNSIGNED     NOT NULL DEFAULT '1',
    PRIMARY KEY (`id`),
    UNIQUE KEY `name` (`name`),
    KEY `status` (`status`)
);

CREATE TABLE `account_role`
(
    `id`      INT(10) UNSIGNED        NOT NULL AUTO_INCREMENT,
    `user_id` INT(10) UNSIGNED        NOT NULL,
    `role`    VARCHAR(64)             NOT NULL,
    `section` ENUM ('front', 'admin') NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `section_user` (`section`, `user_id`, `role`)
);
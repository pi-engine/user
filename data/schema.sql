CREATE TABLE IF NOT EXISTS `user_account`
(
    `id`             INT(10) UNSIGNED    NOT NULL AUTO_INCREMENT,
    `name`           VARCHAR(255)                 DEFAULT NULL,
    `identity`       VARCHAR(128)                 DEFAULT NULL,
    `email`          VARCHAR(128)                 DEFAULT NULL,
    `mobile`         VARCHAR(128)                 DEFAULT NULL,
    `credential`     VARCHAR(255)                 DEFAULT NULL,
    `otp`            VARCHAR(255)                 DEFAULT NULL,
    `status`         TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
    `time_created`   INT(10) UNSIGNED    NOT NULL DEFAULT '0',
    `time_activated` INT(10) UNSIGNED    NOT NULL DEFAULT '0',
    `time_disabled`  INT(10) UNSIGNED    NOT NULL DEFAULT '0',
    `time_deleted`   INT(10) UNSIGNED    NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`),
    UNIQUE KEY `identity` (`identity`),
    UNIQUE KEY `email` (`email`),
    UNIQUE KEY `mobile` (`mobile`),
    KEY `name` (`name`),
    KEY `status` (`status`)
);

CREATE TABLE IF NOT EXISTS `user_profile`
(
    `id`              INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`         INT(10) UNSIGNED NOT NULL          DEFAULT 0,
    `first_name`      VARCHAR(64)                        DEFAULT NULL,
    `last_name`       VARCHAR(64)                        DEFAULT NULL,
    `birthdate`       VARCHAR(16)                        DEFAULT NULL,
    `gender`          ENUM ('male', 'female', 'unknown') DEFAULT NULL,
    `avatar`          VARCHAR(128)                       DEFAULT NULL,
    `ip_register`     VARCHAR(128)                       DEFAULT NULL,
    `register_source` VARCHAR(16)                        DEFAULT NULL,
    `id_number`       VARCHAR(16)                        DEFAULT NULL,
    `homepage`        VARCHAR(128)                       DEFAULT NULL,
    `phone`           VARCHAR(16)                        DEFAULT NULL,
    `address_1`       VARCHAR(255)                       DEFAULT NULL,
    `item_id`       VARCHAR(255)                       DEFAULT NULL,
    `country`         VARCHAR(32)                        DEFAULT NULL,
    `state`           VARCHAR(32)                        DEFAULT NULL,
    `city`            VARCHAR(32)                        DEFAULT NULL,
    `zip_code`        VARCHAR(16)                        DEFAULT NULL,
    `bank_name`       VARCHAR(32)                        DEFAULT NULL,
    `bank_card`       VARCHAR(32)                        DEFAULT NULL,
    `bank_account`    VARCHAR(32)                        DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `user_id` (`user_id`)
);

CREATE TABLE IF NOT EXISTS `role_resource`
(
    `id`      INT(10) UNSIGNED      NOT NULL AUTO_INCREMENT,
    `name`    VARCHAR(255)                   DEFAULT NULL,
    `title`   VARCHAR(128)                   DEFAULT NULL,
    `section` ENUM ('api', 'admin') NOT NULL DEFAULT 'api',
    `status`  TINYINT(1) UNSIGNED   NOT NULL DEFAULT '1',
    PRIMARY KEY (`id`),
    UNIQUE KEY `name` (`name`),
    KEY `status` (`status`)
);

CREATE TABLE IF NOT EXISTS `role_account`
(
    `id`      INT(10) UNSIGNED      NOT NULL AUTO_INCREMENT,
    `user_id` INT(10) UNSIGNED      NOT NULL DEFAULT 0,
    `role`    VARCHAR(64)           NOT NULL DEFAULT '',
    `section` ENUM ('api', 'admin') NOT NULL DEFAULT 'api',
    PRIMARY KEY (`id`),
    UNIQUE KEY `section_user` (`section`, `user_id`, `role`)
);

CREATE TABLE IF NOT EXISTS `permission_resource`
(
    `id`      INT(10) UNSIGNED          NOT NULL AUTO_INCREMENT,
    `title`   VARCHAR(255)              NOT NULL DEFAULT '',
    `section` ENUM ('api', 'admin')     NOT NULL DEFAULT 'api',
    `module`  VARCHAR(64)               NOT NULL DEFAULT '',
    `name`    VARCHAR(64)               NULL     DEFAULT NULL,
    `type`    ENUM ('system', 'custom') NOT NULL DEFAULT 'system',
    PRIMARY KEY (`id`),
    UNIQUE KEY `name` (`name`),
    UNIQUE KEY `key` (`section`, `module`, `name`, `type`)
);

CREATE TABLE IF NOT EXISTS `permission_role`
(
    `id`       INT(10) UNSIGNED      NOT NULL AUTO_INCREMENT,
    `resource` VARCHAR(64)           NOT NULL DEFAULT '',
    `section`  ENUM ('api', 'admin') NOT NULL DEFAULT 'api',
    `module`   VARCHAR(64)           NOT NULL DEFAULT '',
    `role`     VARCHAR(64)           NOT NULL DEFAULT '',
    `name`     VARCHAR(64)           NULL     DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `name` (`name`),
    UNIQUE KEY `key` (`resource`, `section`, `module`, `role`, `name`)
);

CREATE TABLE IF NOT EXISTS `permission_page`
(
    `id`          INT(8) UNSIGNED         NOT NULL AUTO_INCREMENT,
    `title`       VARCHAR(64)             NOT NULL DEFAULT '',
    `section`     ENUM ('api', 'admin')   NOT NULL DEFAULT 'api',
    `module`      VARCHAR(64)             NOT NULL DEFAULT '',
    `package`     VARCHAR(64)             NOT NULL DEFAULT '',
    `handler`     VARCHAR(64)             NOT NULL DEFAULT '',
    `resource`    VARCHAR(64)             NOT NULL DEFAULT '',
    `name`        VARCHAR(64)             NULL     DEFAULT NULL,
    `cache_type`  ENUM ('page', 'action') NOT NULL DEFAULT 'page',
    `cache_ttl`   INT(10)                 NOT NULL DEFAULT '0', # positive: for cache TTL; negative: for inheritance
    `cache_level` VARCHAR(64)             NOT NULL DEFAULT '',
    PRIMARY KEY (`id`),
    UNIQUE KEY `name` (`name`),
    UNIQUE KEY `key` (`section`, `module`, `package`, `handler`, `resource`, `name`)
);

INSERT INTO `role_resource` (`id`, `name`, `title`, `status`, `section`)
VALUES (NULL, 'member', 'Member', '1', 'api'),
       (NULL, 'admin', 'Admin', '1', 'admin');
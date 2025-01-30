CREATE TABLE IF NOT EXISTS `user_account`
(
    `id`                  INT(10) UNSIGNED    NOT NULL AUTO_INCREMENT,
    `name`                VARCHAR(255)                 DEFAULT NULL,
    `identity`            VARCHAR(128)                 DEFAULT NULL,
    `email`               VARCHAR(128)                 DEFAULT NULL,
    `mobile`              VARCHAR(128)                 DEFAULT NULL,
    `credential`          VARCHAR(255)                 DEFAULT NULL,
    `otp`                 VARCHAR(255)                 DEFAULT NULL,
    `status`              TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
    `time_created`        INT(10) UNSIGNED    NOT NULL DEFAULT '0',
    `time_activated`      INT(10) UNSIGNED    NOT NULL DEFAULT '0',
    `time_disabled`       INT(10) UNSIGNED    NOT NULL DEFAULT '0',
    `time_deleted`        INT(10) UNSIGNED    NOT NULL DEFAULT '0',
    `multi_factor_status` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
    `multi_factor_secret` VARCHAR(255)                 DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `identity` (`identity`),
    UNIQUE KEY `email` (`email`),
    UNIQUE KEY `mobile` (`mobile`),
    KEY `name` (`name`),
    KEY `status` (`status`)
);

CREATE TABLE IF NOT EXISTS `user_profile`
(
    `id`          INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`     INT(10) UNSIGNED NOT NULL          DEFAULT 0,
    `first_name`  VARCHAR(64)                        DEFAULT NULL,
    `last_name`   VARCHAR(64)                        DEFAULT NULL,
    `birthdate`   VARCHAR(16)                        DEFAULT NULL,
    `gender`      ENUM ('male', 'female', 'unknown') DEFAULT NULL,
    `avatar`      VARCHAR(255)                      DEFAULT NULL,
    `information` JSON                               DEFAULT NULL,
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
    `title`   VARCHAR(255)             NOT NULL DEFAULT '',
    `key`     VARCHAR(255)             NULL     DEFAULT NULL,
    `section` ENUM ('api', 'admin')     NOT NULL DEFAULT 'api',
    `module`  VARCHAR(64)               NOT NULL DEFAULT '',
    `type`    ENUM ('system', 'custom') NOT NULL DEFAULT 'system',
    PRIMARY KEY (`id`),
    UNIQUE KEY `key` (`key`),
    UNIQUE KEY `keys` (`key`, `section`, `module`, `type`)
);

CREATE TABLE IF NOT EXISTS `permission_page`
(
    `id`          INT(8) UNSIGNED         NOT NULL AUTO_INCREMENT,
    `title`       VARCHAR(255)           NOT NULL DEFAULT '',
    `key`         VARCHAR(255)           NULL     DEFAULT NULL,
    `resource`    VARCHAR(255)           NOT NULL DEFAULT '',
    `section`     ENUM ('api', 'admin')   NOT NULL DEFAULT 'api',
    `module`      VARCHAR(64)             NOT NULL DEFAULT '',
    `package`     VARCHAR(64)             NOT NULL DEFAULT '',
    `handler`     VARCHAR(64)             NOT NULL DEFAULT '',
    `cache_type`  ENUM ('page', 'action') NOT NULL DEFAULT 'page',
    `cache_ttl`   INT(10)                 NOT NULL DEFAULT '0', # positive: for cache TTL; negative: for inheritance
    `cache_level` VARCHAR(64)             NOT NULL DEFAULT '',
    PRIMARY KEY (`id`),
    UNIQUE KEY `key` (`key`),
    UNIQUE KEY `keys` (`key`, `resource`, `section`, `module`, `package`, `handler`)
);

CREATE TABLE IF NOT EXISTS `permission_role`
(
    `id`       INT(10) UNSIGNED      NOT NULL AUTO_INCREMENT,
    `key`      VARCHAR(255)         NULL     DEFAULT NULL,
    `resource` VARCHAR(255)         NOT NULL DEFAULT '',
    `section`  ENUM ('api', 'admin') NOT NULL DEFAULT 'api',
    `module`   VARCHAR(64)           NOT NULL DEFAULT '',
    `role`     VARCHAR(64)           NOT NULL DEFAULT '',
    PRIMARY KEY (`id`),
    UNIQUE KEY `key` (`key`),
    UNIQUE KEY `keys` (`key`, `resource`, `section`, `module`, `role`)
);

INSERT INTO `role_resource` (`id`, `name`, `title`, `status`, `section`)
VALUES (NULL, 'member', 'Member', '1', 'api'),
       (NULL, 'admin', 'Full Admin', '1', 'admin');
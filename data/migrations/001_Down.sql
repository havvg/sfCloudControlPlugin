ALTER TABLE `crontab` DROP `parameters`;
ALTER TABLE `crontab` CHANGE `command` `command_line` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
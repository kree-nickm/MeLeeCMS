SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

CREATE TABLE `changelog` (
  `index` bigint(20) UNSIGNED NOT NULL,
  `table` varchar(255) NOT NULL,
  `timestamp` bigint(20) UNSIGNED NOT NULL,
  `data` longblob NOT NULL,
  `previous` longblob NOT NULL,
  `blame` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

CREATE TABLE `pages` (
  `index` int(10) UNSIGNED NOT NULL,
  `title` varchar(127) NOT NULL,
  `url` varchar(127) NOT NULL,
  `subtheme` varchar(63) NOT NULL,
  `file` varchar(50) NOT NULL,
  `css` longtext NOT NULL,
  `js` longtext NOT NULL,
  `xsl` longtext NOT NULL,
  `permission` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `content` longblob NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

CREATE TABLE `pages_drafts` (
  `user` int(10) UNSIGNED NOT NULL,
  `page` int(10) UNSIGNED NOT NULL,
  `timestamp` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(127) NOT NULL,
  `url` varchar(127) NOT NULL,
  `subtheme` varchar(63) NOT NULL,
  `css` longtext NOT NULL,
  `js` longtext NOT NULL,
  `xsl` longtext NOT NULL,
  `permission` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `content` longblob NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

CREATE TABLE `page_components` (
  `index` int(10) UNSIGNED NOT NULL,
  `title` varchar(127) NOT NULL,
  `css` longtext NOT NULL,
  `js` longtext NOT NULL,
  `xsl` longtext NOT NULL,
  `content` longblob NOT NULL,
  `token` varchar(50) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

CREATE TABLE `settings` (
  `setting` varchar(30) NOT NULL,
  `value` varchar(127) NOT NULL,
  `type` enum('string','number','boolean','page','theme','user_system') NOT NULL,
  `description` text NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

INSERT INTO `settings` (`setting`, `value`, `type`, `description`) VALUES
('index_page', '', 'page', 'Page that will load if someone visits your website but doesn\'t specify another page. For example, if they visit the URL <tt>www.yourdomain.com/</tt>, with nothing after the slash.');

CREATE TABLE `users` (
  `index` int(10) UNSIGNED NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `jointime` bigint(20) UNSIGNED NOT NULL,
  `timezone` varchar(255) NOT NULL,
  `permissions` json NOT NULL,
  `custom_data` longblob NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

CREATE TABLE `sessions` (
  `session_id` varchar(255) NOT NULL,
  `session_data` longblob NOT NULL,
  `session_indefinite` tinyint(1) NOT NULL,
  `user` int(10) UNSIGNED NOT NULL,
  `time` bigint(20) UNSIGNED NOT NULL,
  `ip` varchar(15) NOT NULL,
  `user_agent` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

CREATE TABLE `error_log` (
  `index` bigint(20) UNSIGNED NOT NULL,
  `time` bigint(20) UNSIGNED NOT NULL,
  `user` int(10) UNSIGNED NOT NULL,
  `level` int(10) UNSIGNED NOT NULL,
  `type` varchar(20) NOT NULL,
  `message` text NOT NULL,
  `file` varchar(255) NOT NULL,
  `line` int(11) NOT NULL,
  `stack` json NOT NULL,
  `read_by` text NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


ALTER TABLE `changelog`
  ADD PRIMARY KEY (`index`);

ALTER TABLE `pages`
  ADD PRIMARY KEY (`index`);

ALTER TABLE `pages_drafts`
  ADD UNIQUE KEY `unique` (`user`,`page`);

ALTER TABLE `page_components`
  ADD PRIMARY KEY (`index`);

ALTER TABLE `settings`
  ADD UNIQUE KEY `setting` (`setting`);

ALTER TABLE `users`
  ADD PRIMARY KEY (`index`),
  ADD UNIQUE KEY `username` (`username`(250));

ALTER TABLE `sessions`
  ADD UNIQUE KEY `session_id` (`session_id`(250));

ALTER TABLE `error_log`
  ADD PRIMARY KEY (`index`);


ALTER TABLE `changelog`
  MODIFY `index` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `pages`
  MODIFY `index` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `page_components`
  MODIFY `index` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `users`
  MODIFY `index` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `error_log`
  MODIFY `index` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
COMMIT;

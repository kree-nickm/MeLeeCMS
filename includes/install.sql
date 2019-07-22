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

CREATE TABLE `pages_special` (
  `index` int(10) UNSIGNED NOT NULL,
  `title` varchar(127) NOT NULL,
  `subtheme` varchar(63) NOT NULL,
  `css` longtext NOT NULL,
  `js` longtext NOT NULL,
  `xsl` longtext NOT NULL,
  `content` longblob NOT NULL,
  `token` varchar(50) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

INSERT INTO `pages_special` (`index`, `title`, `subtheme`, `css`, `js`, `xsl`, `content`, `token`) VALUES
(1, 'Permission Denied', 'default', '[]', '[]', '[]', 0x613a313a7b733a373a22636f6e74656e74223b4f3a393a22436f6e7461696e6572223a333a7b733a353a227469746c65223b733a31373a225065726d697373696f6e2044656e696564223b733a353a226174747273223b613a303a7b7d733a373a22636f6e74656e74223b613a313a7b733a343a2274657874223b4f3a343a2254657874223a323a7b733a343a2274657874223b733a35393a22596f7520646f206e6f74206861766520746865207265717569726564207065726d697373696f6e7320746f2076696577207468697320706167652e223b733a353a226174747273223b613a303a7b7d7d7d7d7d, ''),
(2, 'Page Not Found', 'default', '[]', '[]', '[]', 0x613a313a7b733a373a22636f6e74656e74223b4f3a393a22436f6e7461696e6572223a333a7b733a353a227469746c65223b733a31343a2250616765204e6f7420466f756e64223b733a353a226174747273223b613a303a7b7d733a373a22636f6e74656e74223b613a313a7b733a343a2274657874223b4f3a343a2254657874223a323a7b733a343a2274657874223b733a34353a22546865207061676520796f7520617265206c6f6f6b696e6720666f722063616e6e6f7420626520666f756e642e223b733a353a226174747273223b613a303a7b7d7d7d7d7d, ''),
(3, 'Database Error', 'default', '[]', '[]', '[]', 0x613a313a7b733a333a226d7367223b4f3a343a2254657874223a323a7b733a343a2274657874223b733a33333a224572726f7220636f6e6e656374696e6720746f207468652064617461626173652e223b733a353a226174747273223b613a303a7b7d7d7d, '');

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
('site_title', 'MeLeeCMS', 'string', 'Name used on browser tabs for this website. Will appear after the specific page name, ie. <tt>Page Name - Site Title</tt>'),
('default_theme', 'default', 'theme', 'The theme of the website, from which all pages will load their CSS, JS, and XSL. Changing this could break the website if the new theme is not setup to support all of the pages of the site. Does not affect the control panel.'),
('index_page', '1', 'page', 'Page that will load if someone visits your website but doesn\'t specify another page. For example, if they visit the URL <tt>www.yourdomain.com/</tt>, with nothing after the slash.');

CREATE TABLE `themes` (
  `index` int(10) UNSIGNED NOT NULL,
  `name` varchar(63) NOT NULL,
  `doctype` text NOT NULL,
  `xsl` longtext NOT NULL,
  `css` longtext NOT NULL,
  `token` varchar(50) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

CREATE TABLE `users` (
  `index` int(10) UNSIGNED NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `jointime` bigint(20) UNSIGNED NOT NULL,
  `permission` bigint(20) UNSIGNED NOT NULL,
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
  `line` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;


ALTER TABLE `changelog`
  ADD PRIMARY KEY (`index`);

ALTER TABLE `pages`
  ADD PRIMARY KEY (`index`);

ALTER TABLE `pages_drafts`
  ADD UNIQUE KEY `unique` (`user`,`page`);

ALTER TABLE `pages_special`
  ADD PRIMARY KEY (`index`);

ALTER TABLE `page_components`
  ADD PRIMARY KEY (`index`);

ALTER TABLE `settings`
  ADD UNIQUE KEY `setting` (`setting`);

ALTER TABLE `themes`
  ADD PRIMARY KEY (`index`);

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

ALTER TABLE `pages_special`
  MODIFY `index` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `page_components`
  MODIFY `index` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `themes`
  MODIFY `index` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `users`
  MODIFY `index` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `error_log`
  MODIFY `index` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
COMMIT;

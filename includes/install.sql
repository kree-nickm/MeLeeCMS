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
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE `pages` (
  `index` int(10) UNSIGNED NOT NULL,
  `title` varchar(127) NOT NULL,
  `url` varchar(127) NOT NULL,
  `subtheme` varchar(63) NOT NULL,
  `css` longtext NOT NULL,
  `js` longtext NOT NULL,
  `xsl` longtext NOT NULL,
  `permission` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `content` longblob NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE `pages_drafts` (
  `user` int(10) UNSIGNED NOT NULL,
  `index` int(10) UNSIGNED NOT NULL,
  `timestamp` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(127) NOT NULL,
  `url` varchar(127) NOT NULL,
  `subtheme` varchar(63) NOT NULL,
  `css` longtext NOT NULL,
  `js` longtext NOT NULL,
  `xsl` longtext NOT NULL,
  `permission` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `content` longblob NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE `pages_special` (
  `index` int(10) UNSIGNED NOT NULL,
  `title` varchar(127) NOT NULL,
  `subtheme` varchar(63) NOT NULL,
  `css` longtext NOT NULL,
  `js` longtext NOT NULL,
  `xsl` longtext NOT NULL,
  `content` longblob NOT NULL,
  `token` varchar(50) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE `page_components` (
  `index` int(10) UNSIGNED NOT NULL,
  `title` varchar(127) NOT NULL,
  `css` longtext NOT NULL,
  `js` longtext NOT NULL,
  `xsl` longtext NOT NULL,
  `content` longblob NOT NULL,
  `token` varchar(50) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE `settings` (
  `setting` varchar(30) NOT NULL,
  `value` varchar(127) NOT NULL,
  `type` enum('string','number','boolean','page','theme','user_system') NOT NULL,
  `description` text NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE `themes` (
  `index` int(10) UNSIGNED NOT NULL,
  `name` varchar(63) NOT NULL,
  `doctype` text NOT NULL,
  `xsl` longtext NOT NULL,
  `css` longtext NOT NULL,
  `token` varchar(50) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

CREATE TABLE `users` (
  `index` int(10) UNSIGNED NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `jointime` bigint(20) UNSIGNED NOT NULL,
  `permission` bigint(20) UNSIGNED NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


ALTER TABLE `changelog`
  ADD PRIMARY KEY (`index`);

ALTER TABLE `pages`
  ADD PRIMARY KEY (`index`);

ALTER TABLE `pages_drafts`
  ADD UNIQUE KEY `unique` (`user`,`index`);

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
  ADD UNIQUE KEY `username` (`username`);


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
COMMIT;

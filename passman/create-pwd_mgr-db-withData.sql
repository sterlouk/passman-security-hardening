-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               10.4.22-MariaDB - mariadb.org binary distribution
-- Server OS:                    Win64
-- HeidiSQL Version:             12.7.0.6850
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

CREATE DATABASE IF NOT EXISTS `pwd_mgr` /*!40100 DEFAULT CHARACTER SET latin1 */;
USE `pwd_mgr`;

CREATE TABLE IF NOT EXISTS `dummy` (
  `id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `login_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(20) NOT NULL,
  `password` varchar(256) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `user` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;

INSERT INTO `login_users` (`id`, `username`, `password`) VALUES
	(1, 'u1', 'p1');

CREATE TABLE IF NOT EXISTS `notes` (
  `notesid` int(11) NOT NULL AUTO_INCREMENT,
  `login_user_id` int(11) DEFAULT NULL,
  `note` varchar(300) NOT NULL,
  PRIMARY KEY (`notesid`) USING BTREE,
  KEY `FK_notes-login_users` (`login_user_id`) USING BTREE,
  CONSTRAINT `FK_notes-login_users` FOREIGN KEY (`login_user_id`) REFERENCES `login_users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=latin1;

INSERT INTO `notes` (`notesid`, `login_user_id`, `note`) VALUES
	(1, 1, 'test1');

CREATE TABLE IF NOT EXISTS `websites` (
  `webid` int(11) NOT NULL AUTO_INCREMENT,
  `login_user_id` int(11) DEFAULT NULL,
  `web_url` varchar(250) NOT NULL,
  `web_username` varchar(20) NOT NULL DEFAULT '',
  `web_password` varchar(300) NOT NULL DEFAULT '',
  PRIMARY KEY (`webid`) USING BTREE,
  KEY `FK_websites-login_users` (`login_user_id`),
  CONSTRAINT `FK_websites-login_users` FOREIGN KEY (`login_user_id`) REFERENCES `login_users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=latin1;

INSERT INTO `websites` (`webid`, `login_user_id`, `web_url`, `web_username`, `web_password`) VALUES
	(1, 1, 'www.test.com', 'tom', 'tompass');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;


DROP DATABASE IF EXISTS `mapping-scientific`;
CREATE DATABASE IF NOT EXISTS `mapping-scientific` /*!40100 DEFAULT CHARACTER SET utf8 */;
USE `mapping-scientific`;

CREATE TABLE `document` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(255) DEFAULT NULL,
  `title_slug` varchar(255) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `abstract` text,
  `authors` varchar(255) DEFAULT NULL,
  `year` varchar(255) DEFAULT NULL,
  `volume` varchar(255) DEFAULT NULL,
  `issue` varchar(255) DEFAULT NULL,
  `issn` varchar(255) DEFAULT NULL,
  `isbns` varchar(255) DEFAULT NULL,
  `doi` varchar(255) DEFAULT NULL,
  `pdf_link` varchar(255) DEFAULT NULL,
  `keywords` text,
  `published_in` varchar(255) DEFAULT NULL,
  `numpages` varchar(255) DEFAULT NULL,
  `pages` varchar(255) DEFAULT NULL,
  `publisher` varchar(255) DEFAULT NULL,
  `source` varchar(255) DEFAULT NULL COMMENT 'Bases',
  `search_string` varchar(255) DEFAULT NULL,
  `duplicate` smallint(6) DEFAULT '0',
  `duplicate_id` int(11) DEFAULT NULL,
  `cited` int(11) DEFAULT NULL,
  `full_text` text,
  PRIMARY KEY (`id`),
  KEY `title` (`title`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


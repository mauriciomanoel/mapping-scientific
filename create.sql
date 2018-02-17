-- --------------------------------------------------------
-- Servidor:                     127.0.0.1
-- Versão do servidor:           10.1.30-MariaDB - mariadb.org binary distribution
-- OS do Servidor:               Win32
-- HeidiSQL Versão:              9.5.0.5196
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;


-- Copiando estrutura do banco de dados para mapping-scientific
DROP DATABASE IF EXISTS `mapping-scientific`;
CREATE DATABASE IF NOT EXISTS `mapping-scientific` /*!40100 DEFAULT CHARACTER SET utf8 */;
USE `mapping-scientific`;

-- Copiando estrutura para tabela mapping-scientific.document
DROP TABLE IF EXISTS `document`;
CREATE TABLE IF NOT EXISTS `document` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(255) DEFAULT NULL,
  `bibtex_citation` varchar(50) DEFAULT NULL,
  `title_slug` varchar(255) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `abstract` text,
  `authors` text,
  `keywords` text,
  `year` varchar(255) DEFAULT NULL,
  `volume` varchar(255) DEFAULT NULL,
  `issue` varchar(255) DEFAULT NULL,
  `issn` varchar(255) DEFAULT NULL,
  `isbns` varchar(255) DEFAULT NULL,
  `doi` varchar(255) DEFAULT NULL,
  `document_url` varchar(255) DEFAULT NULL,
  `pdf_link` varchar(255) DEFAULT NULL,
  `pdf_path_local` varchar(255) DEFAULT NULL,
  `published_in` varchar(255) DEFAULT NULL,
  `numpages` varchar(255) DEFAULT NULL,
  `pages` varchar(255) DEFAULT NULL,
  `publisher` varchar(255) DEFAULT NULL,
  `source` varchar(255) DEFAULT NULL COMMENT 'Bases',
  `search_string` varchar(255) DEFAULT NULL,
  `duplicate` tinyint(4) DEFAULT '0',
  `duplicate_id` int(11) DEFAULT NULL,
  `cited` int(11) DEFAULT NULL,
  `text_views` int(11) DEFAULT NULL,
  `downloads` int(11) DEFAULT NULL,
  `full_text` text,
  `bibtex` text,
  PRIMARY KEY (`id`),
  KEY `title` (`title`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8;

/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;

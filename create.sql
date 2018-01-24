-- Copiando estrutura do banco de dados para doutorado
CREATE DATABASE IF NOT EXISTS `scientific_documents` /*!40100 DEFAULT CHARACTER SET utf8 */;
USE `scientific_documents`;

-- Copiando estrutura para tabela doutorado.document
CREATE TABLE IF NOT EXISTS `document` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(255) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `abstract` text,
  `authors` varchar(255) DEFAULT NULL,
  `year` int(11) DEFAULT NULL,
  `volume` int(11) DEFAULT NULL,
  `issue` int(11) DEFAULT NULL,
  `issn` varchar(255) DEFAULT NULL,
  `isbns` varchar(255) DEFAULT NULL,
  `doi` varchar(255) DEFAULT NULL,
  `pdf link` varchar(255) DEFAULT NULL,
  `keywords` varchar(255) DEFAULT NULL,
  `published_in` varchar(255) DEFAULT NULL,
  `numpages` varchar(255) DEFAULT NULL,
  `pages` varchar(255) DEFAULT NULL,
  `publisher` varchar(255) DEFAULT NULL COMMENT 'Bases',
  PRIMARY KEY (`id`),
  KEY `title` (`title`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


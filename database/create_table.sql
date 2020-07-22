-- Dumping database structure for mysm
CREATE DATABASE IF NOT EXISTS `mysm` /*!40100 DEFAULT CHARACTER SET utf8 */;
USE `mysm`;

-- Dumping structure for table mysm.document
CREATE TABLE IF NOT EXISTS `document` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(255) DEFAULT NULL,
  `bibtex_citation` varchar(50) DEFAULT NULL,
  `source` varchar(255) DEFAULT NULL COMMENT 'Bases',
  `source_id` varchar(255) DEFAULT NULL,
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
  `pages` varchar(255) DEFAULT NULL,
  `search_string` varchar(255) DEFAULT NULL,
  `duplicate` tinyint(4) DEFAULT '0',
  `duplicate_id` int(11) DEFAULT NULL,
  `citation_count` int(11) DEFAULT NULL,
  `download_count` int(11) DEFAULT NULL,
  `metrics` text,
  `full_text` text,
  `file_name` varchar(255) DEFAULT NULL,
  `bibtex` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_title_file_name_source` (`title_slug`,`file_name`,`source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `administratoret` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `emri` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `roli` varchar(30) NOT NULL DEFAULT 'admin',
  `aktiv` tinyint(1) NOT NULL DEFAULT 1,
  `krijuar_me` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `aktivitetet` (
  `aktivitet_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `veprimi` varchar(100) NOT NULL,
  `entiteti` varchar(100) NOT NULL,
  `entitet_id` bigint(20) unsigned DEFAULT NULL,
  `detaje` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`detaje`)),
  `krijuar_me` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`aktivitet_id`),
  KEY `idx_aktivitet_data` (`krijuar_me`),
  KEY `idx_aktivitet_user` (`user_id`),
  CONSTRAINT `fk_aktivitetet_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `klient_shenime` (
  `shenim_id` int(11) NOT NULL AUTO_INCREMENT,
  `klient_id` int(11) NOT NULL,
  `shenime` text DEFAULT NULL,
  `krijuar_me` timestamp NOT NULL DEFAULT current_timestamp(),
  `perditesuar_me` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`shenim_id`),
  UNIQUE KEY `klient_id` (`klient_id`),
  CONSTRAINT `fk_shenime_klient` FOREIGN KEY (`klient_id`) REFERENCES `klientet` (`klient_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `klientet` (
  `klient_id` int(11) NOT NULL AUTO_INCREMENT,
  `emri` varchar(100) NOT NULL,
  `mbiemri` varchar(100) NOT NULL,
  `telefoni` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `adresa` varchar(255) DEFAULT NULL,
  `data_regjistrimit` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`klient_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `konfigurimet_studios` (
  `id` tinyint(3) unsigned NOT NULL,
  `emri_studios` varchar(150) NOT NULL DEFAULT 'LUMIÈRE Beauty Studio',
  `telefoni` varchar(30) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `adresa` varchar(255) DEFAULT NULL,
  `orari_hapjes` time NOT NULL DEFAULT '09:00:00',
  `orari_mbylljes` time NOT NULL DEFAULT '18:00:00',
  `kohezgjatja_terminit` smallint(5) unsigned NOT NULL DEFAULT 60,
  `pragu_stokut` int(10) unsigned NOT NULL DEFAULT 5,
  `shenime` text DEFAULT NULL,
  `perditesuar_me` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `kuponet` (
  `kupon_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `kodi` varchar(50) NOT NULL,
  `zbritja_perqindje` decimal(5,2) NOT NULL,
  `data_fillimit` date NOT NULL,
  `data_mbarimit` date NOT NULL,
  `perdorime_max` int(11) DEFAULT NULL,
  `perdorime` int(11) NOT NULL DEFAULT 0,
  `aktiv` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`kupon_id`),
  UNIQUE KEY `kodi` (`kodi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `levizjet_stokut` (
  `levizja_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `produkt_id` int(10) unsigned NOT NULL,
  `lloji` enum('hyrje','dalje','korrigjim') NOT NULL,
  `sasia` int(11) NOT NULL,
  `arsye` varchar(255) DEFAULT NULL,
  `data_levizjes` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`levizja_id`),
  KEY `produkt_id` (`produkt_id`),
  CONSTRAINT `levizjet_stokut_ibfk_1` FOREIGN KEY (`produkt_id`) REFERENCES `produktet` (`produkt_id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mungesat_stafit` (
  `mungesa_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `staf_id` int(11) NOT NULL,
  `data_fillimit` datetime NOT NULL,
  `data_mbarimit` datetime NOT NULL,
  `arsye` varchar(255) DEFAULT NULL,
  `statusi` enum('ne_pritje','miratuar','refuzuar') NOT NULL DEFAULT 'ne_pritje',
  PRIMARY KEY (`mungesa_id`),
  KEY `fk_mungesa_staf` (`staf_id`),
  CONSTRAINT `fk_mungesa_staf` FOREIGN KEY (`staf_id`) REFERENCES `stafi` (`staf_id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `njoftimet` (
  `njoftim_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `titulli` varchar(150) NOT NULL,
  `mesazhi` text NOT NULL,
  `lloji` varchar(50) NOT NULL DEFAULT 'info',
  `lexuar` tinyint(1) NOT NULL DEFAULT 0,
  `krijuar_me` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`njoftim_id`),
  KEY `idx_njoftim_user_lexuar` (`user_id`,`lexuar`),
  CONSTRAINT `fk_njoftimet_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `orari_punes` (
  `orari_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `staf_id` int(11) NOT NULL,
  `dita_javes` tinyint(3) unsigned NOT NULL,
  `ora_fillimit` time NOT NULL,
  `ora_mbarimit` time NOT NULL,
  `aktiv` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`orari_id`),
  UNIQUE KEY `uq_staf_dite` (`staf_id`,`dita_javes`),
  CONSTRAINT `fk_orari_staf` FOREIGN KEY (`staf_id`) REFERENCES `stafi` (`staf_id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pagesa` (
  `pagesa_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `termin_id` int(11) NOT NULL,
  `shuma` decimal(10,2) NOT NULL,
  `menyra_pageses` varchar(30) NOT NULL DEFAULT 'Cash',
  `statusi` enum('Ne pritje','Paguar','Rimbursuar') NOT NULL DEFAULT 'Ne pritje',
  `data_pageses` datetime DEFAULT NULL,
  `shenime` varchar(500) DEFAULT NULL,
  `krijuar_me` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`pagesa_id`),
  KEY `idx_pagesa_termin` (`termin_id`),
  KEY `idx_pagesa_status` (`statusi`),
  KEY `idx_pagesa_data` (`data_pageses`),
  CONSTRAINT `fk_pagesa_termin` FOREIGN KEY (`termin_id`) REFERENCES `terminet` (`termin_id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `produktet` (
  `produkt_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `emri` varchar(150) NOT NULL,
  `pershkrimi` text DEFAULT NULL,
  `kategoria` varchar(100) DEFAULT NULL,
  `sasia` int(11) NOT NULL DEFAULT 0,
  `stoku_minimal` int(11) NOT NULL DEFAULT 5,
  `cmimi_blerjes` decimal(10,2) NOT NULL DEFAULT 0.00,
  `cmimi_shitjes` decimal(10,2) DEFAULT NULL,
  `aktiv` tinyint(1) NOT NULL DEFAULT 1,
  `krijuar_me` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`produkt_id`),
  KEY `idx_produkt_emri` (`emri`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `promocionet` (
  `promocion_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `emri` varchar(150) NOT NULL,
  `pershkrimi` text DEFAULT NULL,
  `zbritja_perqindje` decimal(5,2) NOT NULL,
  `data_fillimit` date NOT NULL,
  `data_mbarimit` date NOT NULL,
  `aktiv` tinyint(1) NOT NULL DEFAULT 1,
  `krijuar_me` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`promocion_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sherbimet` (
  `sherbim_id` int(11) NOT NULL AUTO_INCREMENT,
  `emri` varchar(100) NOT NULL,
  `pershkrimi` text DEFAULT NULL,
  `cmimi` decimal(10,2) NOT NULL,
  `kategoria` varchar(100) DEFAULT NULL,
  `kohezgjatja_min` int(11) DEFAULT NULL,
  PRIMARY KEY (`sherbim_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `shpenzimet` (
  `shpenzim_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pershkrimi` varchar(255) NOT NULL,
  `kategoria` varchar(100) NOT NULL,
  `shuma` decimal(10,2) NOT NULL,
  `data_shpenzimit` date NOT NULL,
  `menyra_pageses` varchar(30) DEFAULT NULL,
  `shenime` text DEFAULT NULL,
  `krijuar_me` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`shpenzim_id`),
  KEY `idx_shpenzime_data` (`data_shpenzimit`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stafi` (
  `staf_id` int(11) NOT NULL AUTO_INCREMENT,
  `emri` varchar(100) NOT NULL,
  `mbiemri` varchar(100) NOT NULL,
  `telefoni` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `pozita` varchar(100) DEFAULT NULL,
  `data_punesimit` date DEFAULT NULL,
  `statusi` enum('Aktive','Joaktive') NOT NULL DEFAULT 'Aktive',
  PRIMARY KEY (`staf_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `terminet` (
  `termin_id` int(11) NOT NULL AUTO_INCREMENT,
  `klient_id` int(11) NOT NULL,
  `staf_id` int(11) NOT NULL,
  `sherbim_id` int(11) NOT NULL,
  `data` date NOT NULL,
  `ora` time NOT NULL,
  `statusi` varchar(30) DEFAULT 'Ne pritje',
  PRIMARY KEY (`termin_id`),
  KEY `fk_termin_klient` (`klient_id`),
  KEY `fk_termin_staf` (`staf_id`),
  KEY `fk_termin_sherbim` (`sherbim_id`),
  CONSTRAINT `fk_termin_klient` FOREIGN KEY (`klient_id`) REFERENCES `klientet` (`klient_id`),
  CONSTRAINT `fk_termin_sherbim` FOREIGN KEY (`sherbim_id`) REFERENCES `sherbimet` (`sherbim_id`),
  CONSTRAINT `fk_termin_staf` FOREIGN KEY (`staf_id`) REFERENCES `stafi` (`staf_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `user_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `staf_id` int(11) DEFAULT NULL,
  `emri` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `roli` enum('admin','recepsionist','punonjes') NOT NULL DEFAULT 'punonjes',
  `statusi` enum('aktiv','joaktiv') NOT NULL DEFAULT 'aktiv',
  `krijuar_me` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`),
  KEY `fk_users_staf` (`staf_id`),
  CONSTRAINT `fk_users_staf` FOREIGN KEY (`staf_id`) REFERENCES `stafi` (`staf_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vleresimet` (
  `vleresim_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `klient_id` int(11) NOT NULL,
  `termin_id` int(11) NOT NULL,
  `nota` tinyint(3) unsigned NOT NULL,
  `komenti` text DEFAULT NULL,
  `statusi` enum('ne_shqyrtim','publikuar','fshehur') NOT NULL DEFAULT 'ne_shqyrtim',
  `krijuar_me` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`vleresim_id`),
  KEY `idx_vleresim_termin` (`termin_id`),
  KEY `idx_vleresim_klient` (`klient_id`),
  CONSTRAINT `fk_vleresimet_klient` FOREIGN KEY (`klient_id`) REFERENCES `klientet` (`klient_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_vleresimet_termin` FOREIGN KEY (`termin_id`) REFERENCES `terminet` (`termin_id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;


DROP TABLE IF EXISTS `request`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `request` (
  `id` int(12) NOT NULL AUTO_INCREMENT,
  `donation_id` int(12) DEFAULT NULL,
  `timestamp` datetime NOT NULL,
  `anrede` varchar(16) DEFAULT NULL,
  `firma` varchar(100) DEFAULT NULL,
  `titel` varchar(16) DEFAULT NULL,
  `name` varchar(250) NOT NULL DEFAULT '',
  `vorname` varchar(50) NOT NULL DEFAULT '',
  `nachname` varchar(50) NOT NULL DEFAULT '',
  `strasse` varchar(100) DEFAULT NULL,
  `plz` varchar(8) DEFAULT NULL,
  `ort` varchar(100) DEFAULT NULL,
  `email` varchar(250) NOT NULL DEFAULT '',
  `phone` varchar(30) NOT NULL DEFAULT '',
  `dob` date DEFAULT NULL,
  `wikimedium_shipping` varchar(255) NOT NULL DEFAULT '',
  `membership_type` varchar(255) NOT NULL DEFAULT 'sustaining',
  `membership_fee` int(11) NOT NULL DEFAULT 0,
  `membership_fee_interval` smallint(6) DEFAULT 12,
  `account_number` varchar(16) NOT NULL DEFAULT '',
  `bank_name` varchar(100) NOT NULL DEFAULT '',
  `bank_code` varchar(16) NOT NULL DEFAULT '',
  `iban` varchar(32) DEFAULT '',
  `bic` varchar(32) DEFAULT '',
  `account_holder` varchar(50) NOT NULL DEFAULT '',
  `comment` text NOT NULL,
  `export` datetime DEFAULT NULL,
  `backup` datetime DEFAULT NULL,
  `wikilogin` tinyint(4) NOT NULL DEFAULT 0,
  `tracking` varchar(50) DEFAULT NULL,
  `status` smallint(6) DEFAULT 0,
  `country` varchar(8) DEFAULT '',
  `data` text DEFAULT NULL,
  `payment_type` varchar(255) NOT NULL DEFAULT 'BEZ',
  `donation_receipt` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  FULLTEXT KEY `m_email` (`email`),
  FULLTEXT KEY `m_name` (`name`),
  FULLTEXT KEY `m_ort` (`ort`)
) ENGINE=InnoDB AUTO_INCREMENT=51934 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;



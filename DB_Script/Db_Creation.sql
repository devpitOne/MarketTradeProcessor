CREATE DATABASE `mtp` /*!40100 DEFAULT CHARACTER SET utf8 */;

CREATE TABLE `currency` (
  `currencyCode` varchar(3) NOT NULL,
  `currencyName` varchar(45) NOT NULL,
  PRIMARY KEY (`currencyCode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `user` (
  `userId` int(10) unsigned NOT NULL,
  `userName` varchar(45) NOT NULL,
  `lastRequest` datetime DEFAULT NULL,
  `throttleLimit` int(11) DEFAULT NULL,
  PRIMARY KEY (`userId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `currencyrate` (
  `currencyFrom` varchar(3) NOT NULL,
  `currencyTo` varchar(3) NOT NULL,
  `rate` float DEFAULT NULL,
  PRIMARY KEY (`currencyFrom`,`currencyTo`),
  KEY `currencyTo_idx` (`currencyTo`),
  CONSTRAINT `currencyFrom` FOREIGN KEY (`currencyFrom`) REFERENCES `currency` (`currencyCode`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `currencyTo` FOREIGN KEY (`currencyTo`) REFERENCES `currency` (`currencyCode`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `transaction` (
  `transactionId` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userId` int(10) unsigned NOT NULL,
  `currencyFrom` varchar(3) NOT NULL,
  `currencyTo` varchar(3) NOT NULL,
  `amountSell` float NOT NULL,
  `amountBuy` float NOT NULL,
  `rate` float DEFAULT NULL,
  `timePlaced` datetime DEFAULT NULL,
  `originatingCountry` varchar(5) NOT NULL,
  PRIMARY KEY (`transactionId`),
  KEY `currencyFrom_idx` (`currencyFrom`)
) ENGINE=InnoDB AUTO_INCREMENT=89 DEFAULT CHARSET=utf8;

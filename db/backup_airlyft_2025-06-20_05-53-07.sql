-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: airlyft
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

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

--
-- Table structure for table `addresses`
--

DROP TABLE IF EXISTS `addresses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `addresses` (
  `address_id` int(11) NOT NULL AUTO_INCREMENT,
  `street` varchar(255) NOT NULL,
  `barangay` varchar(255) NOT NULL,
  `city` varchar(255) NOT NULL,
  `province` varchar(255) NOT NULL,
  PRIMARY KEY (`address_id`)
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `addresses`
--

LOCK TABLES `addresses` WRITE;
/*!40000 ALTER TABLE `addresses` DISABLE KEYS */;
INSERT INTO `addresses` VALUES (26,'Zone 5','Pantay Matanda','Tanauan City','Batangas'),(27,'Zone 1','Pantay Bata','Tanauan City','Batangas'),(28,'Zone 1','Pantay Bata','Tanauan City','Batangas'),(29,'Zone 1','Pantay Bata','Tanauan City','Batangas'),(30,'Zone 1','Pantay Bata','Tanauan City','Batangas'),(31,'Zone 1','Pantay Bata','Tanauan City','Batangas'),(32,'Zone 1','Pantay Bata','Tanauan City','Batangas'),(33,'Zone 1','Pantay Bata','Tanauan City','Batangas');
/*!40000 ALTER TABLE `addresses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `booking`
--

DROP TABLE IF EXISTS `booking`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `booking` (
  `Booking_Id` int(11) NOT NULL AUTO_INCREMENT,
  `User_Id` int(11) NOT NULL,
  `Aircraft_Id` int(11) NOT NULL,
  `Selected_Date_of_Flight` date NOT NULL,
  `Sched_Id` int(11) DEFAULT NULL,
  `Total_Cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `Booking_Date` date NOT NULL,
  PRIMARY KEY (`Booking_Id`)
) ENGINE=InnoDB AUTO_INCREMENT=57 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `booking`
--

LOCK TABLES `booking` WRITE;
/*!40000 ALTER TABLE `booking` DISABLE KEYS */;
INSERT INTO `booking` VALUES (49,58,3,'2025-06-29',53,300000.00,'2025-06-20'),(50,58,2,'2025-06-22',54,120000.00,'2025-06-20'),(51,58,2,'2025-06-22',55,120000.00,'2025-06-20'),(52,58,2,'2025-06-22',56,120000.00,'2025-06-20'),(53,58,2,'2025-06-22',57,120000.00,'2025-06-20'),(54,58,2,'2025-06-22',58,120000.00,'2025-06-20'),(55,58,2,'2025-06-22',59,120000.00,'2025-06-20'),(56,58,2,'2025-06-22',60,120000.00,'2025-06-20');
/*!40000 ALTER TABLE `booking` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lift`
--

DROP TABLE IF EXISTS `lift`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lift` (
  `Aircraft_Id` int(11) NOT NULL AUTO_INCREMENT,
  `Aircraft_Name` varchar(255) NOT NULL,
  `Capacity` varchar(50) DEFAULT NULL,
  `Description` text DEFAULT NULL,
  `Rate` decimal(10,2) NOT NULL,
  PRIMARY KEY (`Aircraft_Id`),
  UNIQUE KEY `Aircraft_Name` (`Aircraft_Name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lift`
--

LOCK TABLES `lift` WRITE;
/*!40000 ALTER TABLE `lift` DISABLE KEYS */;
INSERT INTO `lift` VALUES (1,'Cessna Turbo Stationair HD (T206H)','Up to 5 passengers','A high-performance single-engine piston aircraft, ideal for short to medium-range flights and scenic tours. Known for its reliability and versatility.',50000.00),(2,'Cessna Grand Caravan EX (Deluxe Config)','Up to 12 passengers','A powerful and reliable turboprop aircraft, perfect for larger groups or cargo. The deluxe configuration offers enhanced comfort and amenities.',120000.00),(3,'Airbus H160','Up to 10 passengers','A next-generation medium twin-engine helicopter, offering superior performance, comfort, and safety. Ideal for executive transport and aerial tours.',300000.00),(4,'Sikorsky S-76D','Up to 12 passengers','A highly sophisticated and reliable medium-sized commercial helicopter, renowned for its executive transport capabilities and long-range flights.',450000.00);
/*!40000 ALTER TABLE `lift` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `passengers`
--

DROP TABLE IF EXISTS `passengers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `passengers` (
  `Passenger_Id` int(11) NOT NULL AUTO_INCREMENT,
  `Booking_Id` int(11) NOT NULL,
  `FName` varchar(100) NOT NULL,
  `LName` varchar(100) NOT NULL,
  `Age` int(11) NOT NULL,
  `Address_Id` int(11) DEFAULT NULL,
  `Has_Insurance` tinyint(1) NOT NULL,
  `Insurance_Details` text DEFAULT NULL,
  PRIMARY KEY (`Passenger_Id`),
  KEY `Booking_Id` (`Booking_Id`),
  CONSTRAINT `passengers_ibfk_1` FOREIGN KEY (`Booking_Id`) REFERENCES `booking` (`Booking_Id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=52 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `passengers`
--

LOCK TABLES `passengers` WRITE;
/*!40000 ALTER TABLE `passengers` DISABLE KEYS */;
INSERT INTO `passengers` VALUES (44,49,'Christian Leo','Manimtim',20,26,1,'12345678'),(45,50,'Christian Leo 2','Manimtim',32,27,1,'123454321'),(46,51,'Christian Leo 2','Manimtim',32,28,1,'123454321'),(47,52,'Christian Leo 2','Manimtim',32,29,1,'123454321'),(48,53,'Christian Leo 2','Manimtim',32,30,1,'123454321'),(49,54,'Christian Leo 2','Manimtim',32,31,1,'123454321'),(50,55,'Christian Leo 2','Manimtim',32,32,1,'123454321');
/*!40000 ALTER TABLE `passengers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL AUTO_INCREMENT,
  `Booking_id` int(11) NOT NULL,
  `amount_paid` decimal(10,2) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_mode` varchar(255) NOT NULL,
  `ref_number` int(11) NOT NULL,
  `proof_file` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`payment_id`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
INSERT INTO `payments` VALUES (22,49,300000.00,'2025-06-20','Gcash',123456789,'0'),(23,50,120000.00,'2025-06-20','Gcash',123456789,'0'),(24,51,120000.00,'2025-06-20','Gcash',123456789,'0'),(25,52,120000.00,'2025-06-20','Gcash',123456789,'0'),(26,53,120000.00,'2025-06-20','Gcash',123456789,'0'),(27,54,120000.00,'2025-06-20','Gcash',123456789,'0'),(28,55,120000.00,'2025-06-20','Gcash',123456789,'0'),(29,56,120000.00,'2025-06-20','Gcash',123456789,'0');
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `places`
--

DROP TABLE IF EXISTS `places`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `places` (
  `Place_Id` int(11) NOT NULL,
  `Place_Name` varchar(255) NOT NULL,
  PRIMARY KEY (`Place_Id`),
  UNIQUE KEY `name` (`Place_Name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `places`
--

LOCK TABLES `places` WRITE;
/*!40000 ALTER TABLE `places` DISABLE KEYS */;
INSERT INTO `places` VALUES (8,'Alphaland Baguio'),(1,'Amanpulo'),(3,'Amorita Resort'),(11,'Aureo La Union'),(2,'Balesin Island'),(6,'Banwa'),(5,'El Nido Resorts'),(10,'Farm San Benito Lipa'),(4,'Huma Island Resort'),(7,'Nay Palad'),(9,'Shangri-La Boracay');
/*!40000 ALTER TABLE `places` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `schedule`
--

DROP TABLE IF EXISTS `schedule`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `schedule` (
  `Sched_Id` int(11) NOT NULL AUTO_INCREMENT,
  `Aircraft_Id` int(11) NOT NULL,
  `Departure_Coordinates` varchar(255) DEFAULT NULL,
  `Arrival_Coordinates` varchar(255) DEFAULT NULL,
  `Arr_Date_Time` datetime DEFAULT NULL,
  `Status` varchar(50) DEFAULT NULL,
  `Booked_Capacity` int(11) DEFAULT 0,
  `Dep_Date_Time` datetime DEFAULT NULL,
  PRIMARY KEY (`Sched_Id`)
) ENGINE=InnoDB AUTO_INCREMENT=61 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `schedule`
--

LOCK TABLES `schedule` WRITE;
/*!40000 ALTER TABLE `schedule` DISABLE KEYS */;
INSERT INTO `schedule` VALUES (53,3,'MNL','Huma Island Resort','2025-06-29 01:50:00','Confirmed',1,'0000-00-00 00:00:00'),(54,2,'MNL','Amanpulo','2025-06-22 01:10:00','accepted',1,'0000-00-00 00:00:00'),(55,2,'MNL','Amanpulo','2025-06-22 01:10:00','accepted',1,'0000-00-00 00:00:00'),(56,2,'MNL','Amanpulo','2025-06-22 01:10:00','Confirmed',1,'0000-00-00 00:00:00'),(57,2,'MNL','Amanpulo','2025-06-22 01:10:00','Confirmed',1,'0000-00-00 00:00:00'),(58,2,'MNL','Amanpulo','2025-06-22 01:10:00','Confirmed',1,'0000-00-00 00:00:00'),(59,2,'MNL','Amanpulo','2025-06-22 01:10:00','Confirmed',1,'0000-00-00 00:00:00'),(60,2,'MNL','Amanpulo','2025-06-22 01:10:00','Confirmed',1,'0000-00-00 00:00:00');
/*!40000 ALTER TABLE `schedule` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(50) NOT NULL,
  `phonenumber` varchar(11) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'user',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`,`email`)
) ENGINE=MyISAM AUTO_INCREMENT=67 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (58,'kalbow','kalbow@gmail.com','09519678889','$2y$10$YnuUt1MFqIFt75.3YLD2Oevmi7C2JQdjfRnkRGGBbrajyWtm5hg.C','user'),(59,'admin','admin@gmail.com','','admin123','admin'),(66,'admin2','admin2@gmail.com','09859115623','admin2123','admin');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-06-20  5:53:08

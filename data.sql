CREATE DATABASE  IF NOT EXISTS `sopa` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `sopa`;
-- MySQL dump 10.13  Distrib 8.0.46, for Win64 (x86_64)
--
-- Host: localhost    Database: sopa
-- ------------------------------------------------------
-- Server version	8.0.46

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `cardapios`
--

DROP TABLE IF EXISTS `cardapios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cardapios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario_id` int NOT NULL,
  `nome_restaurante` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `categoria` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cor_primaria` varchar(7) COLLATE utf8mb4_general_ci DEFAULT '#2f6b4f',
  `cor_texto` varchar(7) COLLATE utf8mb4_general_ci DEFAULT '#1c1c1c',
  `logo` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `data_criacao` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `cardapios_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cardapios`
--

LOCK TABLES `cardapios` WRITE;
/*!40000 ALTER TABLE `cardapios` DISABLE KEYS */;
INSERT INTO `cardapios` VALUES (1,2,'Costelaria Sá','Churrasco','#2f6b4f','#1c1c1c',NULL,'2026-07-22 21:22:55'),(5,3,'Bia Bolos','Bolos','#ff7ae2','#99e6ff','logo_5_26888ec378d2.jpg','2026-08-23 22:20:22');
/*!40000 ALTER TABLE `cardapios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `estabelecimentos`
--

DROP TABLE IF EXISTS `estabelecimentos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `estabelecimentos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario_id` int NOT NULL,
  `nome_estabelecimento` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `cep` varchar(9) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `logradouro` varchar(120) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `numero` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bairro` varchar(80) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cidade` varchar(80) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `estado` char(2) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `pix` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_usuario_id` (`usuario_id`),
  CONSTRAINT `fk_estab_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `estabelecimentos`
--

LOCK TABLES `estabelecimentos` WRITE;
/*!40000 ALTER TABLE `estabelecimentos` DISABLE KEYS */;
INSERT INTO `estabelecimentos` VALUES (2,2,'Costelaria Sá',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-22 21:06:54'),(3,3,'Teste',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-08-03 22:14:42'),(4,4,'code',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-08-07 20:04:11');
/*!40000 ALTER TABLE `estabelecimentos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `itens_cardapio`
--

DROP TABLE IF EXISTS `itens_cardapio`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `itens_cardapio` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cardapio_id` int NOT NULL,
  `nome` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `descricao` text COLLATE utf8mb4_general_ci,
  `preco` decimal(10,2) NOT NULL,
  `disponivel` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `cardapio_id` (`cardapio_id`),
  CONSTRAINT `itens_cardapio_ibfk_1` FOREIGN KEY (`cardapio_id`) REFERENCES `cardapios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `itens_cardapio`
--

LOCK TABLES `itens_cardapio` WRITE;
/*!40000 ALTER TABLE `itens_cardapio` DISABLE KEYS */;
INSERT INTO `itens_cardapio` VALUES (1,1,'Costela',NULL,50.00,1),(2,1,'Picanha',NULL,60.00,1),(3,1,'Contra filé',NULL,35.00,1),(12,5,'bolo cenoura',NULL,45.00,1),(13,5,'bolo chocolate',NULL,35.00,1);
/*!40000 ALTER TABLE `itens_cardapio` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tentativas_login`
--

DROP TABLE IF EXISTS `tentativas_login`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tentativas_login` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `bem_sucedido` tinyint(1) NOT NULL DEFAULT '0',
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ip_email_data` (`ip`,`email`,`criado_em`)
) ENGINE=InnoDB AUTO_INCREMENT=57 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tentativas_login`
--

LOCK TABLES `tentativas_login` WRITE;
/*!40000 ALTER TABLE `tentativas_login` DISABLE KEYS */;
/*!40000 ALTER TABLE `tentativas_login` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tokens_recuperacao`
--

DROP TABLE IF EXISTS `tokens_recuperacao`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tokens_recuperacao` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token_hash` varchar(255) NOT NULL,
  `metodo` enum('email') NOT NULL DEFAULT 'email',
  `usado` tinyint(1) NOT NULL DEFAULT 0,
  `expira_em` datetime NOT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ip_solicitante` varchar(45) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `idx_email_ativo` (`email`,`usado`,`expira_em`),
  KEY `idx_token_hash` (`token_hash`(64)),
  KEY `fk_tokens_usuario` (`usuario_id`),
  CONSTRAINT `fk_tokens_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tokens_recuperacao`
--

LOCK TABLES `tokens_recuperacao` WRITE;
/*!40000 ALTER TABLE `tokens_recuperacao` DISABLE KEYS */;
INSERT INTO `tokens_recuperacao` VALUES (22,2,'renatomirandalimadesa@gmail.com','6c2c5120c3ebb0d59fbac34b5bd83062589533e51b211b41ccf78635c0e4a716','email',0,'2026-08-04 00:42:49','2026-08-03 19:12:49','::1');
/*!40000 ALTER TABLE `tokens_recuperacao` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(120) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `telefone` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `senha` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `tipo_usuario` enum('admin','empresa') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `ultimo_login` datetime DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (2,'RENATO MIRANDA LIMA DE SA','renatomirandalimadesa@gmail.com','12996283105','$2y$10$STD38y7f0T.LWiNe19ZJe.JOZgCpCm2680x89rM8X6bXpR7ggtBym',NULL,1,1,NULL,'2026-07-22 21:06:54','2026-07-22 21:06:54'),(3,'Teste','teste@gmail.com','12996283105','$2y$10$cvUf34NYDOmcc94yQb0.f.4aSodfxzWOXerBg7sssz65tjzShqZgO',NULL,1,1,NULL,'2026-08-03 22:14:42','2026-08-03 22:14:42'),(4,'qrcode','qrcode@gmail.com','12234425345','$2y$10$2SZbLC7WoD53CVxkIm1Da.f1X9EWhrtqhpbYsKpxerdj.GpkkiSyG',NULL,1,1,NULL,'2026-08-07 20:04:11','2026-08-07 20:04:11');
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-08-23 19:23:16

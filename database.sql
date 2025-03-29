-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: mysql_container
-- Creato il: Mar 29, 2025 alle 05:52
-- Versione del server: 9.2.0
-- Versione PHP: 8.2.27

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `CarbonQuestDB`
--

-- --------------------------------------------------------

--
-- Struttura della tabella `Punteggio`
--

CREATE TABLE `Punteggio` (
  `username` varchar(20) NOT NULL,
  `data` date NOT NULL,
  `megabyte` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `Token`
--

CREATE TABLE `Token` (
  `token` varchar(32) NOT NULL,
  `username` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `Utente`
--

CREATE TABLE `Utente` (
  `username` varchar(20) NOT NULL,
  `password` varchar(60) NOT NULL,
  `email` varchar(50) NOT NULL,
  `consiglio` text,
  `ultimo_consiglio` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Indici per le tabelle scaricate
--

--
-- Indici per le tabelle `Punteggio`
--
ALTER TABLE `Punteggio`
  ADD PRIMARY KEY (`username`,`data`);

--
-- Indici per le tabelle `Token`
--
ALTER TABLE `Token`
  ADD PRIMARY KEY (`token`),
  ADD KEY `username` (`username`);

--
-- Indici per le tabelle `Utente`
--
ALTER TABLE `Utente`
  ADD PRIMARY KEY (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Limiti per le tabelle scaricate
--

--
-- Limiti per la tabella `Punteggio`
--
ALTER TABLE `Punteggio`
  ADD CONSTRAINT `Punteggio_ibfk_1` FOREIGN KEY (`username`) REFERENCES `Utente` (`username`);

--
-- Limiti per la tabella `Token`
--
ALTER TABLE `Token`
  ADD CONSTRAINT `Token_ibfk_1` FOREIGN KEY (`username`) REFERENCES `Utente` (`username`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

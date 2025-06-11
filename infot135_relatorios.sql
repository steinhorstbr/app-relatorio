-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Tempo de geração: 11/06/2025 às 18:15
-- Versão do servidor: 5.7.23-23
-- Versão do PHP: 8.1.32

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `infot135_relatorios`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `relatorios`
--

CREATE TABLE `relatorios` (
  `id` int(11) NOT NULL,
  `periodo` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Ex: Maio de 2025',
  `supervisor` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nome do supervisor de campo',
  `regiao` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Região de cobertura',
  `data_relatorio` date NOT NULL COMMENT 'Data de criação do relatório',
  `total_servicos` int(11) DEFAULT '0' COMMENT 'Contador automático de serviços',
  `servicos_concluidos` int(11) DEFAULT '0' COMMENT 'Contador de serviços concluídos',
  `servicos_andamento` int(11) DEFAULT '0' COMMENT 'Contador de serviços em andamento',
  `observacoes_gerais` text COLLATE utf8mb4_unicode_ci COMMENT 'Observações gerais do período',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Data de criação',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Data de atualização'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabela principal dos relatórios mensais';

--
-- Despejando dados para a tabela `relatorios`
--

INSERT INTO `relatorios` (`id`, `periodo`, `supervisor`, `regiao`, `data_relatorio`, `total_servicos`, `servicos_concluidos`, `servicos_andamento`, `observacoes_gerais`, `created_at`, `updated_at`) VALUES
(7, 'Janeiro 2025', 'Rodrigo', 'Costa Rica - MS', '2025-06-11', 3, 3, 0, 'Manutenções de janeiro de 2025 \r\ndia 05 - 30', '2025-06-11 18:56:45', '2025-06-11 19:17:49');

-- --------------------------------------------------------

--
-- Estrutura para tabela `servicos`
--

CREATE TABLE `servicos` (
  `id` int(11) NOT NULL,
  `relatorio_id` int(11) NOT NULL COMMENT 'Referência ao relatório',
  `titulo` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Título/tipo do serviço executado',
  `status` enum('concluido','andamento','pendente') COLLATE utf8mb4_unicode_ci DEFAULT 'pendente' COMMENT 'Status do serviço',
  `tipo_reparo` tinyint(1) DEFAULT '0' COMMENT 'Serviço de reparo marcado',
  `tipo_construcao` tinyint(1) DEFAULT '0' COMMENT 'Construção de rede marcado',
  `tipo_ceo` tinyint(1) DEFAULT '0' COMMENT 'Instalação de CEO marcado',
  `tipo_cto` tinyint(1) DEFAULT '0' COMMENT 'Instalação de CTO marcado',
  `equipe` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Código/nome da equipe',
  `tecnicos` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Nomes dos técnicos responsáveis',
  `data_execucao` date DEFAULT NULL COMMENT 'Data de execução do serviço',
  `local_servico` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Endereço/local do serviço',
  `foto_1` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Primeira foto do serviço',
  `foto_2` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Segunda foto do serviço',
  `foto_3` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Terceira foto do serviço',
  `foto_4` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Quarta foto do serviço',
  `descricao_foto_1` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Descrição da primeira foto',
  `descricao_foto_2` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Descrição da segunda foto',
  `descricao_foto_3` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Descrição da terceira foto',
  `descricao_foto_4` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Descrição da quarta foto',
  `comentarios` text COLLATE utf8mb4_unicode_ci COMMENT 'Observações e comentários técnicos',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Data de criação',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Data de atualização'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabela dos serviços executados com novos campos';

--
-- Despejando dados para a tabela `servicos`
--

INSERT INTO `servicos` (`id`, `relatorio_id`, `titulo`, `status`, `tipo_reparo`, `tipo_construcao`, `tipo_ceo`, `tipo_cto`, `equipe`, `tecnicos`, `data_execucao`, `local_servico`, `foto_1`, `foto_2`, `foto_3`, `foto_4`, `descricao_foto_1`, `descricao_foto_2`, `descricao_foto_3`, `descricao_foto_4`, `comentarios`, `created_at`, `updated_at`) VALUES
(6, 7, 'Instalação de CEO', 'concluido', 0, 0, 1, 0, 'Infraestrutura', 'Carlos Henrique', '2025-06-11', 'Av. Kendi nakai', '6849d1b34492d_1749668275.jpg', '6849d1b361542_1749668275.jpg', '6849d1b387562_1749668275.jpg', '6849d1b39f643_1749668275.jpg', '', '', '', '', 'Foi instalado uma ceo com 3 bandeijas', '2025-06-11 18:57:55', '2025-06-11 18:57:55'),
(7, 7, 'Reparo de CTO', 'concluido', 1, 0, 0, 0, 'Infraestrutura', 'Carlos Henrique', '2025-06-11', 'Centro', '6849d22da8501_1749668397.jpg', '6849d22dcb5cf_1749668397.jpg', '', '', 'Como estava antes da manutenção', 'como ficou após a manutenção', '', '', 'Cabo com curvatura acima do normal', '2025-06-11 18:59:57', '2025-06-11 18:59:57'),
(8, 7, 'Emenda Cabo Flor do campo', 'concluido', 1, 1, 1, 0, 'Infraestrutura', 'Carlos Henrique', '2025-06-11', 'Flor do campo', '6849d65cda19a_1749669468.jpg', '6849d65d08e81_1749669469.jpg', '', '', '', '', '', '', 'foi feita uma emenda no cabo', '2025-06-11 19:17:49', '2025-06-11 19:17:49');

--
-- Acionadores `servicos`
--
DELIMITER $$
CREATE TRIGGER `tr_servicos_delete_v2` AFTER DELETE ON `servicos` FOR EACH ROW BEGIN
    UPDATE relatorios SET 
        total_servicos = (SELECT COUNT(*) FROM servicos WHERE relatorio_id = OLD.relatorio_id),
        servicos_concluidos = (SELECT COUNT(*) FROM servicos WHERE relatorio_id = OLD.relatorio_id AND status = 'concluido'),
        servicos_andamento = (SELECT COUNT(*) FROM servicos WHERE relatorio_id = OLD.relatorio_id AND status = 'andamento')
    WHERE id = OLD.relatorio_id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tr_servicos_insert_v2` AFTER INSERT ON `servicos` FOR EACH ROW BEGIN
    UPDATE relatorios SET 
        total_servicos = (SELECT COUNT(*) FROM servicos WHERE relatorio_id = NEW.relatorio_id),
        servicos_concluidos = (SELECT COUNT(*) FROM servicos WHERE relatorio_id = NEW.relatorio_id AND status = 'concluido'),
        servicos_andamento = (SELECT COUNT(*) FROM servicos WHERE relatorio_id = NEW.relatorio_id AND status = 'andamento')
    WHERE id = NEW.relatorio_id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tr_servicos_update_v2` AFTER UPDATE ON `servicos` FOR EACH ROW BEGIN
    UPDATE relatorios SET 
        total_servicos = (SELECT COUNT(*) FROM servicos WHERE relatorio_id = NEW.relatorio_id),
        servicos_concluidos = (SELECT COUNT(*) FROM servicos WHERE relatorio_id = NEW.relatorio_id AND status = 'concluido'),
        servicos_andamento = (SELECT COUNT(*) FROM servicos WHERE relatorio_id = NEW.relatorio_id AND status = 'andamento')
    WHERE id = NEW.relatorio_id;
END
$$
DELIMITER ;

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `relatorios`
--
ALTER TABLE `relatorios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_periodo` (`periodo`),
  ADD KEY `idx_supervisor` (`supervisor`),
  ADD KEY `idx_data_relatorio` (`data_relatorio`);

--
-- Índices de tabela `servicos`
--
ALTER TABLE `servicos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_relatorio_id` (`relatorio_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_data_execucao` (`data_execucao`),
  ADD KEY `idx_equipe` (`equipe`),
  ADD KEY `idx_tipos` (`tipo_reparo`,`tipo_construcao`,`tipo_ceo`,`tipo_cto`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `relatorios`
--
ALTER TABLE `relatorios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `servicos`
--
ALTER TABLE `servicos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `servicos`
--
ALTER TABLE `servicos`
  ADD CONSTRAINT `fk_servicos_relatorio` FOREIGN KEY (`relatorio_id`) REFERENCES `relatorios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

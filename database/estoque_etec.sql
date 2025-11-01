-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 17/10/2025 às 04:01
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `estoque_etec`
--
CREATE DATABASE IF NOT EXISTS `estoque_etec` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `estoque_etec`;

-- --------------------------------------------------------

--
-- Estrutura para tabela `categorias`
--

CREATE TABLE `categorias` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `cor` varchar(7) DEFAULT '#8B0000',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `categorias`
--

INSERT INTO `categorias` (`id`, `nome`, `cor`, `created_at`) VALUES
(1, 'Grãos e Cereais', '#C41E3A', '2025-10-17 00:34:20'),
(2, 'Carnes', '#8B0000', '2025-10-17 00:34:20'),
(3, 'Frutas e Legumes', '#A52A2A', '2025-10-17 00:34:20'),
(4, 'Laticínios', '#DC143C', '2025-10-17 00:34:20'),
(5, 'Condimentos e Temperos', '#B22222', '2025-10-17 00:34:20'),
(6, 'Biscoitos e Doces', '#CD5C5C', '2025-10-17 00:34:20'),
(7, 'Óleos e Gorduras', '#800000', '2025-10-17 00:34:20');

-- --------------------------------------------------------

--
-- Estrutura para tabela `entradas`
--

CREATE TABLE `entradas` (
  `id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL,
  `remessa` varchar(50) NOT NULL,
  `lote` varchar(50) NOT NULL,
  `quantidade_caixas` int(11) NOT NULL,
  `peso_por_caixa` decimal(10,3) NOT NULL COMMENT 'em kg',
  `peso_total` decimal(10,3) NOT NULL COMMENT 'em kg',
  `quantidade_pacotes` int(11) NOT NULL COMMENT 'Calculado automaticamente',
  `data_recebimento` date NOT NULL,
  `recebido_por` varchar(200) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `entradas`
--

INSERT INTO `entradas` (`id`, `produto_id`, `remessa`, `lote`, `quantidade_caixas`, `peso_por_caixa`, `peso_total`, `quantidade_pacotes`, `data_recebimento`, `recebido_por`, `observacoes`, `created_at`) VALUES
(1, 3, '111111', '2222222', 2, 10.000, 20.000, 4, '2025-10-16', 'Giulia Acsa', 'Amo', '2025-10-17 00:51:28');

--
-- Acionadores `entradas`
--
DELIMITER $$
CREATE TRIGGER `atualizar_estoque_entrada` AFTER INSERT ON `entradas` FOR EACH ROW BEGIN
    UPDATE produtos 
    SET estoque_atual = estoque_atual + NEW.quantidade_pacotes
    WHERE id = NEW.produto_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estrutura para tabela `produtos`
--

CREATE TABLE `produtos` (
  `id` int(11) NOT NULL,
  `nome` varchar(200) NOT NULL,
  `categoria_id` int(11) NOT NULL,
  `peso_pacote` decimal(10,3) NOT NULL COMMENT 'Peso em kg ou ml',
  `unidade_medida` enum('kg','g','ml','l','unidade') DEFAULT 'kg',
  `estoque_atual` int(11) DEFAULT 0 COMMENT 'Quantidade em pacotes',
  `estoque_minimo` int(11) DEFAULT 10,
  `ativo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `produtos`
--

INSERT INTO `produtos` (`id`, `nome`, `categoria_id`, `peso_pacote`, `unidade_medida`, `estoque_atual`, `estoque_minimo`, `ativo`, `created_at`, `updated_at`) VALUES
(1, 'Feijão Carioca', 1, 1.000, 'kg', 18, 20, 1, '2025-10-17 00:34:20', '2025-10-17 00:34:20'),
(2, 'Feijão Preto', 1, 1.000, 'kg', 94, 20, 1, '2025-10-17 00:34:20', '2025-10-17 00:34:20'),
(3, 'Arroz Parboilizado', 1, 5.000, 'kg', 45, 15, 1, '2025-10-17 00:34:20', '2025-10-17 00:53:58'),
(4, 'Sal Refinado', 1, 1.000, 'kg', 11, 10, 1, '2025-10-17 00:34:20', '2025-10-17 00:34:20'),
(5, 'Farinha de Trigo com Fermento', 1, 1.000, 'kg', 35, 15, 1, '2025-10-17 00:34:20', '2025-10-17 00:34:20'),
(6, 'Polvilho Doce', 1, 1.000, 'kg', 21, 10, 1, '2025-10-17 00:34:20', '2025-10-17 00:34:20'),
(7, 'Açúcar Refinado', 1, 1.000, 'kg', 31, 20, 1, '2025-10-17 00:34:20', '2025-10-17 00:34:20'),
(8, 'Macarrão', 1, 1.000, 'kg', 92, 30, 1, '2025-10-17 00:34:20', '2025-10-17 00:34:20'),
(9, 'Trigo para Kibe', 1, 0.500, 'kg', 60, 10, 1, '2025-10-17 00:34:20', '2025-10-17 00:34:20'),
(10, 'Grão de Bico', 1, 1.000, 'kg', 7, 5, 1, '2025-10-17 00:34:20', '2025-10-17 00:34:20'),
(11, 'Flocos de Milho', 1, 2.000, 'kg', 0, 10, 1, '2025-10-17 00:34:20', '2025-10-17 00:34:20'),
(12, 'Carne Bovina', 2, 2.000, 'kg', 46, 15, 1, '2025-10-17 00:34:20', '2025-10-17 00:34:20'),
(13, 'Carne Suína', 2, 3.000, 'kg', 2, 10, 1, '2025-10-17 00:34:20', '2025-10-17 00:34:20'),
(14, 'Ave', 2, 3.000, 'kg', 13, 10, 1, '2025-10-17 00:34:20', '2025-10-17 00:34:20'),
(15, 'Leite em Pó', 4, 1.000, 'kg', 32, 15, 1, '2025-10-17 00:34:20', '2025-10-17 00:34:20'),
(16, 'Extrato de Tomate', 5, 1.000, 'kg', 68, 20, 1, '2025-10-17 00:34:20', '2025-10-17 00:34:20'),
(17, 'Alho Branco', 5, 1.000, 'kg', 0, 5, 1, '2025-10-17 00:34:20', '2025-10-17 00:34:20'),
(18, 'Cebola Amarela', 5, 1.000, 'kg', 0, 10, 1, '2025-10-17 00:34:20', '2025-10-17 00:34:20'),
(19, 'Torrada Integral', 6, 0.140, 'kg', 79, 20, 1, '2025-10-17 00:34:20', '2025-10-17 00:34:20'),
(20, 'Geleia de Morango', 6, 3.000, 'kg', 2, 3, 1, '2025-10-17 00:34:20', '2025-10-17 00:34:20'),
(21, 'Biscoito Água e Sal', 6, 0.360, 'kg', 20, 15, 1, '2025-10-17 00:34:20', '2025-10-17 00:34:20'),
(22, 'Cacau em Pó', 6, 0.500, 'kg', 72, 10, 1, '2025-10-17 00:34:20', '2025-10-17 00:34:20'),
(23, 'Biscoito Doce Maisena', 6, 0.400, 'kg', 13, 10, 1, '2025-10-17 00:34:20', '2025-10-17 00:34:20'),
(24, 'Biscoito Doce Chocolate', 6, 0.400, 'kg', 400, 10, 1, '2025-10-17 00:34:20', '2025-10-17 00:34:20'),
(25, 'Biscoito Água e Sal La Petit', 6, 0.030, 'kg', 600, 50, 1, '2025-10-17 00:34:20', '2025-10-17 00:34:20'),
(26, 'Óleo de Soja', 7, 0.900, 'l', 29, 15, 1, '2025-10-17 00:34:20', '2025-10-17 00:34:20'),
(27, 'Banana Nanica', 3, 1.000, 'kg', 0, 50, 1, '2025-10-17 00:34:20', '2025-10-17 00:34:20'),
(28, 'Limão', 3, 1.000, 'kg', 0, 20, 1, '2025-10-17 00:34:20', '2025-10-17 00:34:20'),
(29, 'Tangerina', 3, 1.000, 'kg', 0, 20, 1, '2025-10-17 00:34:20', '2025-10-17 00:34:20'),
(30, 'Alface', 3, 1.000, 'unidade', 0, 10, 1, '2025-10-17 00:34:20', '2025-10-17 00:34:20'),
(31, 'Beterraba', 3, 1.000, 'kg', 0, 10, 1, '2025-10-17 00:34:20', '2025-10-17 00:34:20'),
(32, 'Batata', 3, 1.000, 'kg', 0, 30, 1, '2025-10-17 00:34:20', '2025-10-17 00:34:20'),
(33, 'Cebolinha', 3, 1.000, 'unidade', 0, 5, 1, '2025-10-17 00:34:20', '2025-10-17 00:34:20'),
(34, 'Chuchu', 3, 1.000, 'kg', 0, 15, 1, '2025-10-17 00:34:20', '2025-10-17 00:34:20'),
(35, 'Mandioca Branca', 3, 1.000, 'kg', 0, 10, 1, '2025-10-17 00:34:20', '2025-10-17 00:34:20'),
(36, 'Pepino', 3, 1.000, 'kg', 0, 5, 1, '2025-10-17 00:34:20', '2025-10-17 00:34:20'),
(37, 'Salsinha', 3, 1.000, 'unidade', 0, 5, 1, '2025-10-17 00:34:20', '2025-10-17 00:34:20'),
(38, 'Repolho Verde', 3, 1.000, 'kg', 0, 10, 1, '2025-10-17 00:34:20', '2025-10-17 00:34:20'),
(39, 'Tomate', 3, 1.000, 'kg', 0, 20, 1, '2025-10-17 00:34:20', '2025-10-17 00:34:20'),
(40, 'Mamão', 3, 1.000, 'kg', 0, 10, 1, '2025-10-17 00:34:20', '2025-10-17 00:34:20'),
(41, 'Cenoura', 3, 1.000, 'kg', 0, 15, 1, '2025-10-17 00:34:20', '2025-10-17 00:34:20'),
(42, 'Couve', 3, 1.000, 'unidade', 0, 10, 1, '2025-10-17 00:34:20', '2025-10-17 00:34:20'),
(43, 'Abacaxi', 3, 1.000, 'unidade', 0, 10, 1, '2025-10-17 00:34:20', '2025-10-17 00:34:20'),
(44, 'Abóbora', 3, 1.000, 'kg', 0, 10, 1, '2025-10-17 00:34:20', '2025-10-17 00:34:20'),
(45, 'Batata Doce', 3, 1.000, 'kg', 0, 15, 1, '2025-10-17 00:34:20', '2025-10-17 00:34:20'),
(46, 'Acelga', 3, 1.000, 'kg', 0, 5, 1, '2025-10-17 00:34:20', '2025-10-17 00:34:20'),
(47, 'Pimentão Verde', 3, 1.000, 'kg', 0, 5, 1, '2025-10-17 00:34:20', '2025-10-17 00:34:20'),
(48, 'Melão', 3, 1.000, 'kg', 0, 10, 1, '2025-10-17 00:34:20', '2025-10-17 00:34:20'),
(49, 'Melancia', 3, 1.000, 'kg', 0, 10, 1, '2025-10-17 00:34:20', '2025-10-17 00:34:20'),
(50, 'Manga', 3, 1.000, 'kg', 0, 10, 1, '2025-10-17 00:34:20', '2025-10-17 00:34:20'),
(51, 'Brócolis Ninja', 3, 1.000, 'kg', 0, 5, 1, '2025-10-17 00:34:20', '2025-10-17 00:34:20'),
(52, 'Ovo de Galinha Branco', 3, 1.000, 'unidade', 0, 20, 1, '2025-10-17 00:34:20', '2025-10-17 00:34:20'),
(53, 'Suco de Uva 1,5L', 1, 1.500, 'l', 0, 10, 1, '2025-10-17 00:34:20', '2025-10-17 00:34:20');

-- --------------------------------------------------------

--
-- Estrutura para tabela `saidas`
--

CREATE TABLE `saidas` (
  `id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL,
  `lote` varchar(50) DEFAULT NULL,
  `quantidade_pacotes` int(11) NOT NULL,
  `peso_utilizado` decimal(10,3) DEFAULT NULL COMMENT 'Calculado automaticamente em kg',
  `data_utilizacao` date NOT NULL,
  `responsavel` varchar(200) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `saidas`
--

INSERT INTO `saidas` (`id`, `produto_id`, `lote`, `quantidade_pacotes`, `peso_utilizado`, `data_utilizacao`, `responsavel`, `observacoes`, `created_at`) VALUES
(1, 3, '2222222', 2, 10.000, '2025-10-16', 'Vanessa', '', '2025-10-17 00:52:16'),
(2, 3, '2222222', 2, 10.000, '2025-10-16', 'Vanessa', '', '2025-10-17 00:53:58');

--
-- Acionadores `saidas`
--
DELIMITER $$
CREATE TRIGGER `atualizar_estoque_saida` AFTER INSERT ON `saidas` FOR EACH ROW BEGIN
    UPDATE produtos 
    SET estoque_atual = estoque_atual - NEW.quantidade_pacotes
    WHERE id = NEW.produto_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `v_estoque_completo`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `v_estoque_completo` (
`id` int(11)
,`produto` varchar(200)
,`categoria` varchar(100)
,`peso_pacote` decimal(10,3)
,`unidade_medida` enum('kg','g','ml','l','unidade')
,`estoque_atual` int(11)
,`estoque_minimo` int(11)
,`peso_total_estoque` decimal(20,2)
,`status_estoque` varchar(7)
,`cor_categoria` varchar(7)
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `v_historico_movimentacoes`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `v_historico_movimentacoes` (
`tipo` varchar(7)
,`id` int(11)
,`produto` varchar(200)
,`categoria` varchar(100)
,`quantidade` int(11)
,`lote` varchar(50)
,`data_movimentacao` date
,`responsavel` varchar(200)
,`created_at` timestamp
);

-- --------------------------------------------------------

--
-- Estrutura para view `v_estoque_completo`
--
DROP TABLE IF EXISTS `v_estoque_completo`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_estoque_completo`  AS SELECT `p`.`id` AS `id`, `p`.`nome` AS `produto`, `c`.`nome` AS `categoria`, `p`.`peso_pacote` AS `peso_pacote`, `p`.`unidade_medida` AS `unidade_medida`, `p`.`estoque_atual` AS `estoque_atual`, `p`.`estoque_minimo` AS `estoque_minimo`, round(`p`.`estoque_atual` * `p`.`peso_pacote`,2) AS `peso_total_estoque`, CASE WHEN `p`.`estoque_atual` <= `p`.`estoque_minimo` THEN 'Crítico' WHEN `p`.`estoque_atual` <= `p`.`estoque_minimo` * 1.5 THEN 'Baixo' ELSE 'Normal' END AS `status_estoque`, `c`.`cor` AS `cor_categoria` FROM (`produtos` `p` join `categorias` `c` on(`p`.`categoria_id` = `c`.`id`)) WHERE `p`.`ativo` = 1 ORDER BY `c`.`nome` ASC, `p`.`nome` ASC ;

-- --------------------------------------------------------

--
-- Estrutura para view `v_historico_movimentacoes`
--
DROP TABLE IF EXISTS `v_historico_movimentacoes`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_historico_movimentacoes`  AS SELECT 'ENTRADA' AS `tipo`, `e`.`id` AS `id`, `p`.`nome` AS `produto`, `c`.`nome` AS `categoria`, `e`.`quantidade_pacotes` AS `quantidade`, `e`.`lote` AS `lote`, `e`.`data_recebimento` AS `data_movimentacao`, `e`.`recebido_por` AS `responsavel`, `e`.`created_at` AS `created_at` FROM ((`entradas` `e` join `produtos` `p` on(`e`.`produto_id` = `p`.`id`)) join `categorias` `c` on(`p`.`categoria_id` = `c`.`id`))union all select 'SAÍDA' AS `tipo`,`s`.`id` AS `id`,`p`.`nome` AS `produto`,`c`.`nome` AS `categoria`,`s`.`quantidade_pacotes` AS `quantidade`,`s`.`lote` AS `lote`,`s`.`data_utilizacao` AS `data_movimentacao`,`s`.`responsavel` AS `responsavel`,`s`.`created_at` AS `created_at` from ((`saidas` `s` join `produtos` `p` on(`s`.`produto_id` = `p`.`id`)) join `categorias` `c` on(`p`.`categoria_id` = `c`.`id`)) order by `data_movimentacao` desc,`created_at` desc  ;

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `entradas`
--
ALTER TABLE `entradas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_produto` (`produto_id`),
  ADD KEY `idx_data` (`data_recebimento`),
  ADD KEY `idx_lote` (`lote`);

--
-- Índices de tabela `produtos`
--
ALTER TABLE `produtos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_categoria` (`categoria_id`),
  ADD KEY `idx_nome` (`nome`);

--
-- Índices de tabela `saidas`
--
ALTER TABLE `saidas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_produto` (`produto_id`),
  ADD KEY `idx_data` (`data_utilizacao`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `entradas`
--
ALTER TABLE `entradas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `produtos`
--
ALTER TABLE `produtos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT de tabela `saidas`
--
ALTER TABLE `saidas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `entradas`
--
ALTER TABLE `entradas`
  ADD CONSTRAINT `entradas_ibfk_1` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`);

--
-- Restrições para tabelas `produtos`
--
ALTER TABLE `produtos`
  ADD CONSTRAINT `produtos_ibfk_1` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`);

--
-- Restrições para tabelas `saidas`
--
ALTER TABLE `saidas`
  ADD CONSTRAINT `saidas_ibfk_1` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

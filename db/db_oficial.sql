CREATE DATABASE IF NOT EXISTS decklog_db;
USE decklog_db;

-- TABELA BASE
CREATE TABLE `lojas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cod_estabelecimento` varchar(50) DEFAULT NULL,
  `cnpj` varchar(20) DEFAULT NULL,
  `razao_social` varchar(255) DEFAULT NULL,
  `nome` varchar(255) DEFAULT NULL,
  `inscricao_estadual` varchar(50) DEFAULT NULL,
  `endereco` varchar(255) DEFAULT NULL,
  `numero` varchar(20) DEFAULT NULL,
  `complemento` varchar(100) DEFAULT NULL,
  `bairro` varchar(100) DEFAULT NULL,
  `municipio` varchar(100) DEFAULT NULL,
  `uf` char(2) DEFAULT NULL,
  `cep` varchar(15) DEFAULT NULL,
  `pais` varchar(50) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `senha_hash` varchar(255) DEFAULT NULL,
  `cnae` varchar(20) DEFAULT NULL,
  `nir` varchar(50) DEFAULT NULL,
  `data_nirc` date DEFAULT NULL,
  `regime_estadual` varchar(50) DEFAULT NULL,
  `regime_federal` varchar(50) DEFAULT NULL,
  `centralizacao_escrituracao` varchar(3) DEFAULT NULL,
  `area_construida_m2` decimal(10,2) DEFAULT NULL,
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cnpj` (`cnpj`)
);

-- USUÁRIOS
CREATE TABLE `usuarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `loja_id` int DEFAULT NULL,
  `nome` varchar(255) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `senha_hash` varchar(255) DEFAULT NULL,
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `loja_id` (`loja_id`),
  CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`loja_id`) REFERENCES `lojas` (`id`)
);

-- PRODUTOS
CREATE TABLE `produtos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `loja_id` int DEFAULT NULL,
  `nome` varchar(255) DEFAULT NULL,
  `descricao` text,
  `lote` varchar(50) DEFAULT NULL,
  `quantidade_estoque` int DEFAULT NULL,
  `preco_unitario` decimal(10,2) DEFAULT NULL,
  `data_reabastecimento` date DEFAULT NULL,
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `custo_unitario` decimal(10,2) DEFAULT NULL,
  `usuario_id` int DEFAULT NULL,
  `quantidade_inicial` int NOT NULL DEFAULT '0',
  `deletado_em` datetime DEFAULT NULL,
  `usuario_exclusao_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `loja_id` (`loja_id`),
  CONSTRAINT `produtos_ibfk_1` FOREIGN KEY (`loja_id`) REFERENCES `lojas` (`id`)
);

-- VENDAS
CREATE TABLE `vendas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `loja_id` int DEFAULT NULL,
  `data_venda` date DEFAULT NULL,
  `valor_total` decimal(12,2) DEFAULT NULL,
  `custo_total` decimal(12,2) DEFAULT NULL,
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `usuario_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `loja_id` (`loja_id`),
  CONSTRAINT `vendas_ibfk_1` FOREIGN KEY (`loja_id`) REFERENCES `lojas` (`id`)
);

-- ITENS DA VENDA
CREATE TABLE `itens_venda` (
  `id` int NOT NULL AUTO_INCREMENT,
  `venda_id` int DEFAULT NULL,
  `produto_id` int DEFAULT NULL,
  `quantidade` int DEFAULT NULL,
  `preco_unitario` decimal(10,2) DEFAULT NULL,
  `custo_unitario` decimal(10,2) DEFAULT NULL,
  `data_venda` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `venda_id` (`venda_id`),
  KEY `produto_id` (`produto_id`),
  CONSTRAINT `itens_venda_ibfk_1` FOREIGN KEY (`venda_id`) REFERENCES `vendas` (`id`),
  CONSTRAINT `itens_venda_ibfk_2` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`)
);

-- MOVIMENTAÇÕES DE ESTOQUE
CREATE TABLE `movimentacoes_estoque` (
  `id` int NOT NULL AUTO_INCREMENT,
  `produto_id` int DEFAULT NULL,
  `quantidade` int DEFAULT NULL,
  `tipo` enum('entrada','saida') DEFAULT NULL,
  `motivo` varchar(255) DEFAULT NULL,
  `data_movimentacao` date DEFAULT NULL,
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `usuario_id` int DEFAULT NULL,
  `custo_unitario` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `produto_id` (`produto_id`),
  CONSTRAINT `movimentacoes_estoque_ibfk_1` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`)
);

-- TAGS
CREATE TABLE `tags` (
  `id` int NOT NULL AUTO_INCREMENT,
  `loja_id` int DEFAULT NULL,
  `nome` varchar(100) DEFAULT NULL,
  `nome_criado` varchar(255) DEFAULT NULL,
  `cor` varchar(20) DEFAULT NULL,
  `icone` varchar(100) DEFAULT NULL,
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `usuario_id` int DEFAULT NULL,
  `atualizado_em` datetime DEFAULT NULL,
  `usuario_atualizacao_id` int DEFAULT NULL,
  `nome_antigo` varchar(255) DEFAULT NULL,
  `deletado_em` datetime DEFAULT NULL,
  `usuario_exclusao_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `loja_id` (`loja_id`),
  CONSTRAINT `tags_ibfk_1` FOREIGN KEY (`loja_id`) REFERENCES `lojas` (`id`)
);

-- RELAÇÃO PRODUTO-TAG
CREATE TABLE `produto_tag` (
  `id` int NOT NULL AUTO_INCREMENT,
  `produto_id` int DEFAULT NULL,
  `tag_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `produto_id` (`produto_id`),
  KEY `tag_id` (`tag_id`),
  CONSTRAINT `produto_tag_ibfk_1` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`),
  CONSTRAINT `produto_tag_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`)
);

-- TRANSACOES FINANCEIRAS
CREATE TABLE `transacoes_financeiras` (
  `id` int NOT NULL AUTO_INCREMENT,
  `loja_id` int DEFAULT NULL,
  `categoria` varchar(100) DEFAULT NULL,
  `descricao` varchar(255) DEFAULT NULL,
  `tipo` enum('entrada','saida') DEFAULT NULL,
  `valor` decimal(12,2) DEFAULT NULL,
  `data_transacao` date DEFAULT NULL,
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `loja_id` (`loja_id`),
  CONSTRAINT `transacoes_financeiras_ibfk_1` FOREIGN KEY (`loja_id`) REFERENCES `lojas` (`id`)
);

-- RECOMENDAÇÕES
CREATE TABLE `recomendacoes_reabastecimento` (
  `id` int NOT NULL AUTO_INCREMENT,
  `produto_id` int NOT NULL,
  `recomendacao` int NOT NULL,
  `dias_projecao` int NOT NULL,
  `demanda_prevista` int NOT NULL,
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `produto_id` (`produto_id`),
  CONSTRAINT `recomendacoes_reabastecimento_ibfk_1` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`)
);

-- HISTÓRICO DE PRODUTOS
CREATE TABLE `historico_produtos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `produto_id` int DEFAULT NULL,
  `nome` varchar(255) DEFAULT NULL,
  `quantidade` int DEFAULT NULL,
  `lote` varchar(255) DEFAULT NULL,
  `usuario_id` int DEFAULT NULL,
  `acao` varchar(50) DEFAULT NULL,
  `criado_em` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
);

-- ANOMALIAS
CREATE TABLE `anomalias` (
  `id` int NOT NULL AUTO_INCREMENT,
  `loja_id` int NOT NULL,
  `produto_id` int DEFAULT NULL,
  `tipo_anomalia` varchar(100) NOT NULL,
  `data_ocorrencia` date NOT NULL,
  `detalhe` text,
  `score` decimal(12,4) DEFAULT NULL,
  `metodo` varchar(50) DEFAULT NULL,
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `loja_id` (`loja_id`),
  KEY `produto_id` (`produto_id`),
  CONSTRAINT `anomalias_ibfk_1` FOREIGN KEY (`loja_id`) REFERENCES `lojas` (`id`),
  CONSTRAINT `anomalias_ibfk_2` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`)
);

CREATE SCHEMA `decklog_db`;
USE decklog_db;

-- ========================
-- Tabela lojas
-- ========================
CREATE TABLE lojas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cod_estabelecimento VARCHAR(50),
    cnpj VARCHAR(20) UNIQUE,
    razao_social VARCHAR(255),
    nome VARCHAR(255),
    inscricao_estadual VARCHAR(50),
    endereco VARCHAR(255),
    numero VARCHAR(20),
    complemento VARCHAR(100),
    bairro VARCHAR(100),
    municipio VARCHAR(100),
    uf CHAR(2),
    cep VARCHAR(15),
    pais VARCHAR(50),
    telefone VARCHAR(20),
    email VARCHAR(100),
    senha_hash VARCHAR(255),
    cnae VARCHAR(20),
    nir VARCHAR(50),
    data_nirc DATE,
    regime_estadual VARCHAR(50),
    regime_federal VARCHAR(50),
    centralizacao_escrituracao VARCHAR(3),
    area_construida_m2 DECIMAL(10,2),
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ========================
-- Tabela usuarios
-- ========================
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    loja_id INT,
    nome VARCHAR(255),
    email VARCHAR(100) UNIQUE,
    senha_hash VARCHAR(255),
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (loja_id) REFERENCES lojas(id)
);

-- ========================
-- Tabela produtos
-- ========================
CREATE TABLE produtos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    loja_id INT,
    nome VARCHAR(255),
    descricao TEXT,
    lote VARCHAR(50),
    quantidade_estoque INT,
    quantidade_inicial INT NOT NULL DEFAULT 0,
    preco_unitario DECIMAL(10,2),
    custo_unitario DECIMAL(10,2),
    data_reabastecimento DATE,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usuario_id INT,
    deletado_em DATETIME DEFAULT NULL,
    usuario_exclusao_id INT DEFAULT NULL,
    FOREIGN KEY (loja_id) REFERENCES lojas(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (usuario_exclusao_id) REFERENCES usuarios(id)
);

-- ========================
-- Tabela tags
-- ========================
CREATE TABLE tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    loja_id INT,
    nome VARCHAR(100),
    nome_criado VARCHAR(255),
    nome_antigo VARCHAR(255) NULL,
    cor VARCHAR(20),
    icone VARCHAR(100),
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NULL,
    usuario_id INT,
    usuario_atualizacao_id INT NULL,
    FOREIGN KEY (loja_id) REFERENCES lojas(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (usuario_atualizacao_id) REFERENCES usuarios(id)
);

-- ========================
-- Relacionamento produto_tag (N:N)
-- ========================
CREATE TABLE produto_tag (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produto_id INT,
    tag_id INT,
    FOREIGN KEY (produto_id) REFERENCES produtos(id),
    FOREIGN KEY (tag_id) REFERENCES tags(id)
);

-- ========================
-- Tabela vendas
-- ========================
CREATE TABLE vendas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    loja_id INT,
    data_venda DATE,
    valor_total DECIMAL(12,2),
    custo_total DECIMAL(12,2),
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usuario_id INT,
    FOREIGN KEY (loja_id) REFERENCES lojas(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- ========================
-- Itens da venda
-- ========================
CREATE TABLE itens_venda (
    id INT AUTO_INCREMENT PRIMARY KEY,
    venda_id INT,
    produto_id INT,
    quantidade INT,
    preco_unitario DECIMAL(10,2),
    custo_unitario DECIMAL(10,2),
    data_venda DATE,
    FOREIGN KEY (venda_id) REFERENCES vendas(id),
    FOREIGN KEY (produto_id) REFERENCES produtos(id)
);

-- ========================
-- Movimentações de estoque
-- ========================
CREATE TABLE movimentacoes_estoque (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produto_id INT,
    quantidade INT,
    tipo ENUM('entrada','saida'),
    motivo VARCHAR(255),
    data_movimentacao DATE,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usuario_id INT,
    FOREIGN KEY (produto_id) REFERENCES produtos(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- ========================
-- Transações financeiras
-- ========================
CREATE TABLE transacoes_financeiras (
    id INT AUTO_INCREMENT PRIMARY KEY,
    loja_id INT,
    categoria VARCHAR(100),
    descricao VARCHAR(255),
    tipo ENUM('entrada','saida'),
    valor DECIMAL(12,2),
    data_transacao DATE,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (loja_id) REFERENCES lojas(id)
);

-- ========================
-- Histórico de produtos
-- ========================
CREATE TABLE historico_produtos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produto_id INT,
    nome VARCHAR(255),
    quantidade INT,
    lote VARCHAR(255),
    usuario_id INT,
    acao VARCHAR(50),
    criado_em DATETIME,
    FOREIGN KEY (produto_id) REFERENCES produtos(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

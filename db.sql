CREATE TABLE lojas (
  id INT PRIMARY KEY AUTO_INCREMENT,
  nome VARCHAR(100), -- nome fantasia
  razao_social VARCHAR(100),
  email VARCHAR(100) UNIQUE,
  telefone VARCHAR(20),
  senha_hash VARCHAR(255),

  cep VARCHAR(20),
  endereco VARCHAR(120),
  numero INT,
  complemento VARCHAR(50),
  bairro VARCHAR(60),
  municipio VARCHAR(60),
  uf CHAR(2),
  pais VARCHAR(50),

  regime_federal VARCHAR(50),
  cnpj VARCHAR(32),
  cnae VARCHAR(20),

  regime_estadual VARCHAR(50),
  nir VARCHAR(20),
  centralizacao_escrituracao VARCHAR(5), -- 'Sim' ou 'Não'
  inscricao_estadual VARCHAR(30),

  data_nirc DATE,
  area_construida_m2 INT,
  cod_estabelecimento VARCHAR(50),

  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE usuarios (
  id INT PRIMARY KEY AUTO_INCREMENT,
  loja_id INT,
  nome VARCHAR(100),
  email VARCHAR(100) UNIQUE,
  senha_hash VARCHAR(255),
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (loja_id) REFERENCES lojas(id)
);

CREATE TABLE produtos (
  id INT PRIMARY KEY AUTO_INCREMENT,
  loja_id INT,
  nome VARCHAR(100),
  descricao TEXT,
  preco_unitario DECIMAL(10,2),
  quantidade_estoque INT DEFAULT 0,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (loja_id) REFERENCES lojas(id)
);

CREATE TABLE tags (
  id INT PRIMARY KEY AUTO_INCREMENT,
  loja_id INT,
  nome VARCHAR(50),
  cor VARCHAR(10),
  icone VARCHAR(50),
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (loja_id) REFERENCES lojas(id)
);

CREATE TABLE produto_tag (
  id INT PRIMARY KEY AUTO_INCREMENT,
  produto_id INT,
  tag_id INT,
  FOREIGN KEY (produto_id) REFERENCES produtos(id),
  FOREIGN KEY (tag_id) REFERENCES tags(id)
);

CREATE TABLE transacoes_financeiras (
  id INT PRIMARY KEY AUTO_INCREMENT,
  loja_id INT,
  tipo VARCHAR(10),
  categoria VARCHAR(100),
  descricao TEXT,
  valor DECIMAL(10,2),
  data_transacao DATE,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (loja_id) REFERENCES lojas(id)
);

CREATE TABLE movimentacoes_estoque (
  id INT PRIMARY KEY AUTO_INCREMENT,
  produto_id INT,
  tipo VARCHAR(10),
  quantidade INT,
  motivo TEXT,
  data_movimentacao DATE,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (produto_id) REFERENCES produtos(id)
);

CREATE TABLE vendas (
  id INT PRIMARY KEY AUTO_INCREMENT,
  loja_id INT,
  data_venda DATE,
  valor_total DECIMAL(10,2),
  custo_total DECIMAL(10,2),
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (loja_id) REFERENCES lojas(id)
);

CREATE TABLE itens_venda (
  id INT PRIMARY KEY AUTO_INCREMENT,
  produto_id INT,
  venda_id INT,
  quantidade INT,
  preco_unitario DECIMAL(10,2),
  custo_unitario DECIMAL(10,2),
  FOREIGN KEY (produto_id) REFERENCES produtos(id),
  FOREIGN KEY (venda_id) REFERENCES vendas(id)
); 
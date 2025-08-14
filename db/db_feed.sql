-- LOJAS
INSERT INTO lojas (
  nome, razao_social, email, telefone, senha_hash,
  cep, endereco, numero, complemento, bairro, municipio, uf, pais,
  regime_federal, cnpj, cnae,
  regime_estadual, nir, centralizacao_escrituracao, inscricao_estadual,
  data_nirc, area_construida_m2, cod_estabelecimento
) VALUES
('TechStore', 'Tech Store Comércio e Tecnologia LTDA', 'contato@techstore.com', '(11) 99999-0001', 'hash123',
'01000-000', 'Av. Paulista', 1000, 'Sala 101', 'Bela Vista', 'São Paulo', 'SP', 'Brasil',
'Simples Nacional', '12.345.678/0001-99', '47.51-2-00',
'Normal', '12345678', 'Sim', '987654321',
'2020-05-20', 300, 'TS-001'),

('EcoMarket', 'Eco Market Produtos Naturais LTDA', 'suporte@ecomarket.com', '(21) 88888-0002', 'hash456',
'20000-000', 'Rua das Árvores', 200, '', 'Centro', 'Rio de Janeiro', 'RJ', 'Brasil',
'Lucro Presumido', '98.765.432/0001-11', '47.29-6-01',
'Isento', '98765432', 'Não', '123456789',
'2019-10-10', 150, 'EM-001');

-- USUÁRIOS
INSERT INTO usuarios (loja_id, nome, email, senha_hash) VALUES
(1, 'Rafael Oliveira', 'rafael@techstore.com', 'hash_rafael'),
(1, 'Fernanda Lima', 'fernanda@techstore.com', 'hash_fernanda'),
(2, 'Carlos Silva', 'carlos@ecomarket.com', 'hash_carlos'),
(2, 'Juliana Rocha', 'juliana@ecomarket.com', 'hash_juliana');

-- PRODUTOS
INSERT INTO produtos (loja_id, nome, descricao, preco_unitario, quantidade_estoque) VALUES
(1, 'Mouse Gamer', 'Mouse com iluminação RGB', 120.00, 50),
(1, 'Teclado Mecânico', 'Teclado com switches azuis', 250.00, 30),
(1, 'Monitor 24"', 'Full HD IPS HDMI', 900.00, 20),
(2, 'Granola Orgânica', 'Granola sem açúcar 1kg', 25.00, 100),
(2, 'Chá Verde', 'Chá verde importado 250g', 18.00, 80),
(2, 'Sabonete Natural', 'Feito com óleos essenciais', 9.50, 200);

-- TAGS
INSERT INTO tags (loja_id, nome, cor, icone) VALUES
(1, 'Promoção', '#FF0000', '🔥'),
(1, 'Novo', '#00FF00', '🆕'),
(2, 'Orgânico', '#006400', '🌱'),
(2, 'Importado', '#0000FF', '✈️');

-- PRODUTO_TAG (Relacionando produtos às tags)
INSERT INTO produto_tag (produto_id, tag_id) VALUES
(1, 1), -- Mouse Gamer → Promoção
(2, 2), -- Teclado Mecânico → Novo
(4, 3), -- Granola → Orgânico
(5, 4); -- Chá Verde → Importado

-- MOVIMENTAÇÕES DE ESTOQUE INICIAIS
INSERT INTO movimentacoes_estoque (produto_id, tipo, quantidade, motivo, data_movimentacao) VALUES
(1, 'entrada', 50, 'Estoque inicial', CURDATE()),
(2, 'entrada', 30, 'Estoque inicial', CURDATE()),
(3, 'entrada', 20, 'Estoque inicial', CURDATE()),
(4, 'entrada', 100, 'Estoque inicial', CURDATE()),
(5, 'entrada', 80, 'Estoque inicial', CURDATE()),
(6, 'entrada', 200, 'Estoque inicial', CURDATE());

-- VENDAS
INSERT INTO vendas (loja_id, data_venda, valor_total, custo_total) VALUES
(1, CURDATE(), 370.00, 200.00),  -- Venda TechStore
(2, CURDATE(), 61.00, 30.00);   -- Venda EcoMarket

-- ITENS_VENDA
INSERT INTO itens_venda (produto_id, venda_id, quantidade, preco_unitario, custo_unitario) VALUES
(1, 1, 2, 120.00, 50.00),   -- Mouse
(2, 1, 1, 250.00, 100.00),  -- Teclado
(4, 2, 2, 25.00, 10.00),    -- Granola
(6, 2, 1, 11.00, 10.00);    -- Sabonete

-- MOVIMENTAÇÃO DE ESTOQUE AUTOMÁTICA PÓS-VENDA
INSERT INTO movimentacoes_estoque (produto_id, tipo, quantidade, motivo, data_movimentacao) VALUES
(1, 'saida', 2, 'Venda #1', CURDATE()),
(2, 'saida', 1, 'Venda #1', CURDATE()),
(4, 'saida', 2, 'Venda #2', CURDATE()),
(6, 'saida', 1, 'Venda #2', CURDATE());

-- TRANSAÇÕES FINANCEIRAS
INSERT INTO transacoes_financeiras (loja_id, tipo, categoria, descricao, valor, data_transacao) VALUES
(1, 'entrada', 'Venda de produtos', 'Venda realizada - Pedido 1', 370.00, CURDATE()),
(1, 'saida', 'Compra de estoque', 'Reabastecimento de produtos', 500.00, CURDATE()),
(2, 'entrada', 'Venda de produtos', 'Venda realizada - Pedido 2', 61.00, CURDATE()),
(2, 'saida', 'Marketing', 'Campanha redes sociais', 150.00, CURDATE());

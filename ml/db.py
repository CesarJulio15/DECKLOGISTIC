import sqlalchemy
import pandas as pd
from sqlalchemy import text

DB_URI = "mysql+pymysql://root:Home%40spSENAI2025!@localhost:3306/decklog_db"
engine = sqlalchemy.create_engine(DB_URI)

def carregar_vendas():
    query = """
    SELECT 
        p.id AS produto_id,
        p.nome,
        iv.data_venda,
        iv.quantidade,
        p.quantidade_estoque
    FROM itens_venda iv
    JOIN produtos p ON p.id = iv.produto_id
    ORDER BY p.id, iv.data_venda;
    """
    return pd.read_sql(query, engine)

def carregar_estoque(produto_id):
    query = f"SELECT id, nome, quantidade_estoque FROM produtos WHERE id = {produto_id};"
    return pd.read_sql(query, engine).iloc[0]

def salvar_recomendacao(produto_id, recomendacao, dias_projecao, demanda_prevista):
    query = text("""
        INSERT INTO recomendacoes_reabastecimento 
        (produto_id, recomendacao, dias_projecao, demanda_prevista)
        VALUES (:produto_id, :recomendacao, :dias_projecao, :demanda_prevista)
    """)
    params = {
        "produto_id": produto_id,
        "recomendacao": recomendacao,
        "dias_projecao": dias_projecao,
        "demanda_prevista": demanda_prevista
    }
    with engine.begin() as conn:
        conn.execute(query, params)
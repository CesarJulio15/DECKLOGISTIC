from db import carregar_vendas, carregar_estoque, salvar_recomendacao
from preprocess import preparar_dados
from forecast import prever_vendas
from reabastecimento import calcular_reabastecimento
import sys
def main():
    sys.stdout.reconfigure(encoding='utf-8')
    print("Rodando previsao de reabastecimento...")
    
    df = carregar_vendas()
    produtos = df['produto_id'].unique()

    for produto_id in produtos:
        dados = preparar_dados(df, produto_id)
        previsao = prever_vendas(dados, dias=30)

        if previsao is None:
            print(f"Produto {produto_id}: dados insuficientes para previsao.")
            continue

        estoque = carregar_estoque(produto_id)
        sugestao, demanda = calcular_reabastecimento(estoque['quantidade_estoque'], previsao)

        salvar_recomendacao(produto_id, sugestao, 30, int(demanda))

        print(f"Produto {produto_id} ({estoque['nome']}): Reabasteca {sugestao} unidades (demanda prevista: {int(demanda)})")

if __name__ == "__main__":
    main()

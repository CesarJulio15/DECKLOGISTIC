import pandas as pd

def preparar_dados(df, produto_id):
    # Filtra apenas o produto
    dados = df[df['produto_id'] == produto_id] \
        .groupby('data_venda')['quantidade'].sum().reset_index()

    # Converte a coluna para datetime
    dados['data_venda'] = pd.to_datetime(dados['data_venda'])

    # Cria intervalo completo de datas
    datas_completas = pd.DataFrame({
        'data_venda': pd.date_range(dados['data_venda'].min(), dados['data_venda'].max())
    })

    # Converte para datetime também
    datas_completas['data_venda'] = pd.to_datetime(datas_completas['data_venda'])

    # Merge preenchendo zeros para datas sem venda
    dados = datas_completas.merge(dados, on='data_venda', how='left').fillna(0)

    # Renomeia para Prophet
    dados.rename(columns={'data_venda': 'ds', 'quantidade': 'y'}, inplace=True)
    return dados


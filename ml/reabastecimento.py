def calcular_reabastecimento(estoque_atual, previsao):
    demanda = previsao['yhat'].sum()
    sugestao = max(0, int(demanda - estoque_atual))
    return sugestao, demanda

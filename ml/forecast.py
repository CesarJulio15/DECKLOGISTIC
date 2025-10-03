from prophet import Prophet

def prever_vendas(dados, dias=30):
    if len(dados) < 5:  # poucos dados históricos
        return None
    
    model = Prophet()
    model.fit(dados)

    future = model.make_future_dataframe(periods=dias)
    forecast = model.predict(future)

    return forecast[['ds', 'yhat']].tail(dias)

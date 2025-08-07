
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cadastro da Empresa</title>
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      font-family: sans-serif;
    }
    body {
      background-color: #fff;
      padding: 30px;
    }
    h1 {
      text-align: center;
      margin-bottom: 30px;
    }
    form {
      max-width: 1100px;
      margin: auto;
    }
    .form-group {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin-bottom: 15px;
    }
    .form-group input, .form-group select {
      flex: 1;
      min-width: 180px;
      padding: 10px;
      border: 1px solid #ccc;
      border-radius: 20px;
    }
    .form-group input[type="checkbox"] {
      flex: 0;
      margin-right: 5px;
    }
    button {
      background-color: #000;
      color: #fff;
      border: none;
      border-radius: 20px;
      padding: 12px 30px;
      font-size: 16px;
      cursor: pointer;
      margin-top: 20px;
    }
    small {
      font-size: 0.8em;
      margin-left: 5px;
    }
  </style>
  <script>
    function criptografar(valor) {
      return btoa(valor);
    }
    function criptografarCamposSigilosos() {
      let cnpj = document.getElementById("cnpj");
      let ie = document.getElementById("inscricao_estadual");
      let nir = document.getElementById("nir");

      cnpj.value = criptografar(cnpj.value);
      ie.value = criptografar(ie.value);
      nir.value = criptografar(nir.value);
    }
  </script>
</head>
<body>
  <h1>Finalize o cadastro da sua empresa</h1>
  <form onsubmit="criptografarCamposSigilosos()">
    <div class="form-group">
      <input type="text" placeholder="Razão" maxlength="100" required pattern="[A-Za-zÀ-ú\\s]+">
      <input type="text" placeholder="Fantasia" maxlength="100" required pattern="[A-Za-zÀ-ú\\s]+">
    </div>
    <div class="form-group">
      <input type="text" placeholder="CEP" pattern="\\d{5}-?\\d{3}" required>
      <input type="text" placeholder="Endereço" maxlength="120" required>
      <input type="number" placeholder="Número" required min="1" max="99999">
      <input type="text" placeholder="Complemento" maxlength="50">
    </div>
    <div class="form-group">
      <input type="text" placeholder="Bairro" maxlength="60" required>
      <select required>
        <option value="">UF</option>
        <option>SP</option><option>RJ</option><option>MG</option><option>RS</option>
        <option>BA</option><option>PE</option><option>PR</option><option>SC</option>
        <option>GO</option><option>DF</option>
      </select>
      <input type="text" placeholder="Município" maxlength="60" required>
      <select required>
        <option value="">País</option>
        <option>Brasil</option>
        <option>Argentina</option>
        <option>Paraguai</option>
      </select>
    </div>
    <div class="form-group">
      <input type="email" placeholder="Email" maxlength="100" required>
      <input type="tel" placeholder="Fone" maxlength="15" pattern="\\d{10,15}" required>
    </div>
    <hr><br>
    <div class="form-group">
      <select required>
        <option value="">Regime Federal</option>
        <option>Lucro Real</option><option>Lucro Presumido</option><option>Simples Nacional</option>
      </select>
      <input type="text" placeholder="CNPJ" id="cnpj" pattern="\\d{14}" required>
      <input type="text" placeholder="CNAE-F" maxlength="20">
    </div>
    <div class="form-group">
      <select required>
        <option value="">Regime Estadual</option>
        <option>Normal</option><option>Substituição</option><option>Isento</option>
      </select>
      <input type="text" placeholder="NIRC" id="nir" maxlength="20">
      <select required>
        <option value="">Escrituração Centralizada</option>
        <option>Sim</option><option>Não</option>
      </select>
      <input type="text" placeholder="Inscrição Estadual" id="inscricao_estadual" pattern="\\d{9,12}">
    </div>
    <div class="form-group">
      <input type="date" placeholder="Data NIRC">
      <input type="number" placeholder="Área construída m²" min="0" max="100000">
      <input type="text" placeholder="Cod. Estabelecimento" maxlength="20">
    </div>
    <div class="form-group">
      <input type="checkbox" required><small>Eu li e concordo com os termos de uso</small>
    </div>
    <button type="submit">Prosseguir</button>
  </form>
</body>
</html>





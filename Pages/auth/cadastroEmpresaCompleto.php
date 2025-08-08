<!-- cadastroEmpresaCompleto.php -->
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../assets/cadastroEmpresa.css">
  <title>Cadastro da Empresa</title>
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
  <div class="left-side"></div>
  <div class="container">
    <h1>Finalize o cadastro da sua empresa</h1>

    <form onsubmit="criptografarCamposSigilosos()" action="finalizarCadastro.php" method="POST">
      <div class="form-group">
        <input type="text" name="razao" placeholder="Razão" maxlength="100" required pattern="[A-Za-zÀ-ú\s]+">
        <input type="text" name="fantasia" placeholder="Fantasia" maxlength="100" required pattern="[A-Za-zÀ-ú\s]+">
      </div>
      <div class="form-group">
        <input type="text" name="cep" placeholder="CEP" required>
        <input type="text" name="endereco" placeholder="Endereço" maxlength="120" required>
        <input type="number" name="numero" placeholder="Número" required min="1" max="99999">
        <input type="text" name="complemento" placeholder="Complemento" maxlength="50">
      </div>
      <div class="form-group">
        <input type="text" name="bairro" placeholder="Bairro" maxlength="60" required>
        <select name="uf" required>
          <option value="">UF</option>
          <option>SP</option><option>RJ</option><option>MG</option><option>RS</option>
          <option>BA</option><option>PE</option><option>PR</option><option>SC</option>
          <option>GO</option><option>DF</option>
        </select>
        <input type="text" name="municipio" placeholder="Município" maxlength="60" required>
        <select name="pais" required>
          <option value="">País</option>
          <option>Brasil</option>
          <option>Argentina</option>
          <option>Paraguai</option>
        </select>
      </div>
      <div class="form-group">
        <input type="tel" name="fone" placeholder="Fone" required>
      </div>
      <hr><br>
      <div class="form-group">
        <select name="regime_federal" required>
          <option value="">Regime Federal</option>
          <option>Lucro Real</option><option>Lucro Presumido</option><option>Simples Nacional</option>
        </select>
        <input type="text" name="cnpj" placeholder="CNPJ" id="cnpj" required>
        <input type="text" name="cnae_f" placeholder="CNAE-F" maxlength="20">
      </div>
      <div class="form-group">
        <select name="regime_estadual" required>
          <option value="">Regime Estadual</option>
          <option>Normal</option><option>Substituição</option><option>Isento</option>
        </select>
        <input type="text" name="nir" placeholder="NIRC" id="nir" maxlength="20">
        <select name="escrituracao_centralizada" required>
          <option value="">Escrituração Centralizada</option>
          <option>Sim</option><option>Não</option>
        </select>
        <input type="text" name="inscricao_estadual" placeholder="Inscrição Estadual" id="inscricao_estadual">
      </div>
      <div class="form-group">
        <input type="date" name="data_nir" placeholder="Data NIRC">
        <input type="number" name="area_construida" placeholder="Área construída m²">
        <input type="text" name="cod_estabelecimento" placeholder="Cod. Estabelecimento">
      </div>

      <label style="display: inline-flex; align-items: center;">
        <input type="checkbox" required style="margin-right: 10px;"> Eu li e concordo com os termos de uso
      </label>
      <div>Termos de uso</div>

      <div class="form-group" style="display: flex; gap: 10px; margin-top: 30px;">
        <button type="button" onclick="location.href='cadastro.php'">Voltar</button>
        <button type="submit" style="width: 200px;">Prosseguir</button>
      </div>

      <input type="hidden" name="id" value="<?= htmlspecialchars($_GET['id'] ?? '') ?>">

    </form>
  </div>
</body>
</html>

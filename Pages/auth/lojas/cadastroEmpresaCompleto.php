<!-- cadastroEmpresaCompleto.php -->
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../../../assets/cadastroEmpresa.css">
  <title>Cadastro da Empresa</title>
  <script>
    // Criptografia
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

  // Função para aplicar máscara de CNPJ
  function formatarCNPJ(cnpj) {
    return cnpj
      .replace(/\D/g, "") // remove tudo que não é número
      .replace(/^(\d{2})(\d)/, "$1.$2") // 2 primeiros -> 99.
      .replace(/^(\d{2})\.(\d{3})(\d)/, "$1.$2.$3") // depois -> 99.999.
      .replace(/\.(\d{3})(\d)/, ".$1/$2") // depois -> 99.999.999/9
      .replace(/(\d{4})(\d)/, "$1-$2") // depois -> 99.999.999/9999-99
      .substring(0, 18); // limita ao tamanho máximo
  }

  // Aplicar máscara dinamicamente
  document.addEventListener("DOMContentLoaded", () => {
    const cnpjInput = document.getElementById("cnpj");
    cnpjInput.addEventListener("input", (e) => {
      e.target.value = formatarCNPJ(e.target.value);
    });

    const cepInput = document.getElementById("cep");
    cepInput.addEventListener("input", (e) => {
      e.target.value = e.target.value.replace(/\D/g, "").replace(/^(\d{5})(\d)/, "$1-$2").substring(0, 9);
    });

    const telefoneInput = document.querySelector("input[name='fone']");
    telefoneInput.addEventListener("input", (e) => {
      let v = e.target.value.replace(/\D/g, "");
      if (v.length <= 10) {
        e.target.value = v.replace(/(\d{2})(\d{4})(\d)/, "($1) $2-$3");
      } else {
        e.target.value = v.replace(/(\d{2})(\d{5})(\d)/, "($1) $2-$3");
      }
    });
  });


    // Consulta CEP
    function consultarCEP() {
      const cep = document.getElementById("cep").value.replace(/\D/g, '');
      if (cep.length === 8) {
        fetch(`https://viacep.com.br/ws/${cep}/json/`)
          .then(response => response.json())
          .then(data => {
            if (!data.erro) {
              document.getElementById("endereco").value = data.logradouro;
              document.getElementById("bairro").value = data.bairro;
              document.getElementById("municipio").value = data.localidade;
              document.getElementById("uf").value = data.uf;
            } else {
              alert("❌ CEP não encontrado.");
            }
          })
          .catch(() => alert("⚠️ Erro ao buscar o CEP."));
      }
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
        <!-- CEP com evento para buscar dados -->
        <input type="text" name="cep" id="cep" placeholder="CEP" required onblur="consultarCEP()">
        <input type="text" name="endereco" id="endereco" placeholder="Endereço" maxlength="120" required>
        <input type="number" name="numero" placeholder="Número" required min="1" max="99999">
        <input type="text" name="complemento" placeholder="Complemento" maxlength="50">
      </div>
      <div class="form-group">
        <input type="text" name="bairro" id="bairro" placeholder="Bairro" maxlength="60" required>
        <select name="uf" id="uf" required>
          <option value="">UF</option>
          <option>SP</option><option>RJ</option><option>MG</option><option>RS</option>
          <option>BA</option><option>PE</option><option>PR</option><option>SC</option>
          <option>GO</option><option>DF</option>
        </select>
        <input type="text" name="municipio" id="municipio" placeholder="Município" maxlength="60" required>
        <select name="pais" required>
          <option value="">País</option>
          <option selected>Brasil</option>
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
        <input type="checkbox" required style="margin-right: 10px;">
        Eu li e concordo com os 
        <a href="../../../termosUso.php" target="_blank" style="color: #00a3e0; text-decoration: underline;">
          Termos de Uso
        </a>
      </label>

      <div class="form-group" style="display: flex; gap: 10px; margin-top: 30px;">
        <button type="button" onclick="location.href='cadastro.php'">Voltar</button>
        <button type="submit" style="width: 200px;">Prosseguir</button>
      </div>

      <input type="hidden" name="id" value="<?php echo isset($_GET['id']) ? (int) $_GET['id'] : 0; ?>">


    </form>
  </div>
</body>
</html>

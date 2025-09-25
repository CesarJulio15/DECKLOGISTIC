<!-- cadastroEmpresaCompleto.php -->
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../../../assets/cadastroEmpresa.css">
  <title>Cadastro da Empresa</title>
  <script>
    // ===============================
    // Funções de Criptografia
    // ===============================
    function criptografar(valor) {
      return btoa(valor); // Base64
    }

    // Remove máscara e criptografa campos sensíveis
    function criptografarCamposSigilosos() {
      let cnpj = document.getElementById("cnpj");
      let ie   = document.getElementById("inscricao_estadual");
      let nir  = document.getElementById("nir");

      cnpj.value = criptografar(cnpj.value.replace(/\D/g, "")); 
      ie.value   = criptografar(ie.value.replace(/\D/g, "")); 
      nir.value  = criptografar(nir.value.replace(/\D/g, "")); 
    }

    // ===============================
    // Máscaras
    // ===============================
    function formatarCNPJ(cnpj) {
      return cnpj
        .replace(/\D/g, "")
        .replace(/^(\d{2})(\d)/, "$1.$2")
        .replace(/^(\d{2})\.(\d{3})(\d)/, "$1.$2.$3")
        .replace(/\.(\d{3})(\d)/, ".$1/$2")
        .replace(/(\d{4})(\d)/, "$1-$2")
        .substring(0, 18);
    }

    function formatarNIR(nir) {
      return nir
        .replace(/\D/g, "")
        .replace(/^(\d{4})(\d)/, "$1.$2")
        .replace(/(\d{3})(\d)/, "$1-$2")
        .substring(0, 12);
    }

    function formatarIE(ie) {
      return ie
        .replace(/\D/g, "")
        .replace(/^(\d{3})(\d)/, "$1.$2")
        .replace(/(\d{3})(\d)/, "$1.$2")
        .replace(/(\d{3})(\d)/, "$1-$2")
        .substring(0, 15);
    }

    // ===============================
    // Eventos ao digitar
    // ===============================
    document.addEventListener("DOMContentLoaded", () => {
      // CNPJ
      document.getElementById("cnpj").addEventListener("input", (e) => {
        e.target.value = formatarCNPJ(e.target.value);
      });

      // NIRC
      document.getElementById("nir").addEventListener("input", (e) => {
        e.target.value = formatarNIR(e.target.value);
      });

      // Inscrição Estadual
      document.getElementById("inscricao_estadual").addEventListener("input", (e) => {
        e.target.value = formatarIE(e.target.value);
      });

      // CEP
      document.getElementById("cep").addEventListener("input", (e) => {
        e.target.value = e.target.value
          .replace(/\D/g, "")
          .replace(/^(\d{5})(\d)/, "$1-$2")
          .substring(0, 9);
      });

      // Telefone
      const telefoneInput = document.querySelector("input[name='fone']");
      telefoneInput.addEventListener("input", (e) => {
        let v = e.target.value.replace(/\D/g, "");
        if (v.length > 11) v = v.substring(0, 11); // limita a 11 dígitos
        if (v.length <= 10) {
          e.target.value = v.replace(/(\d{2})(\d{4})(\d{0,4})/, "($1) $2-$3");
        } else {
          e.target.value = v.replace(/(\d{2})(\d{5})(\d{0,4})/, "($1) $2-$3");
        }
      });
    });

    // ===============================
    // Consulta CEP (via ViaCEP)
    // ===============================
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
  <label for="razao">Razão</label>
  <input type="text" id="razao" name="razao" maxlength="100" required 
         pattern="[A-Za-zÀ-ú\s]+" placeholder="Ex: Indústrias XPTO LTDA">

  <label for="fantasia">Fantasia</label>
  <input type="text" id="fantasia" name="fantasia" maxlength="100" required 
         pattern="[A-Za-zÀ-ú\s]+" placeholder="Ex: Super XPTO">
</div>

<div class="form-group">
  <label for="cep">CEP</label>
  <input type="text" id="cep" name="cep" required onblur="consultarCEP()" 
         placeholder="Ex: 01001-000">

  <label for="endereco">Endereço</label>
  <input type="text" id="endereco" name="endereco" maxlength="120" required 
         placeholder="Ex: Av. Paulista">

  <label for="numero">Número</label>
  <input type="number" id="numero" name="numero" required min="1" max="99999" 
         placeholder="Ex: 1234">

  <label for="complemento">Complemento</label>
  <input type="text" id="complemento" name="complemento" maxlength="50" 
         placeholder="Ex: Bloco B, Apto 21">
</div>

<div class="form-group">
  <label for="bairro">Bairro</label>
  <input type="text" id="bairro" name="bairro" maxlength="60" required 
         placeholder="Ex: Bela Vista">

  <label for="uf">UF</label>
  <select id="uf" name="uf" required>
    <option value="">Selecione</option>
    <option>SP</option><option>RJ</option><option>MG</option><option>RS</option>
    <option>BA</option><option>PE</option><option>PR</option><option>SC</option>
    <option>GO</option><option>DF</option>
  </select>

  <label for="municipio">Município</label>
  <input type="text" id="municipio" name="municipio" maxlength="60" required 
         placeholder="Ex: São Paulo">

  <label for="pais">País</label>
  <select id="pais" name="pais" required>
    <option value="">Selecione</option>
    <option selected>Brasil</option>
    <option>Argentina</option>
    <option>Paraguai</option>
  </select>
</div>

<div class="form-group">
  <label for="fone">Fone</label>
  <input type="tel" id="fone" name="fone" required 
         placeholder="Ex: (11) 91234-5678">
</div>
<hr><br>

<div class="form-group">
  <label for="regime_federal">Regime Federal</label>
  <select id="regime_federal" name="regime_federal" required>
    <option value="">Selecione</option>
    <option>Lucro Real</option><option>Lucro Presumido</option><option>Simples Nacional</option>
  </select>

  <label for="cnpj">CNPJ</label>
  <input type="text" id="cnpj" name="cnpj" required 
         placeholder="Ex: 12.345.678/0001-90">

  <label for="cnae_f">CNAE-F</label>
  <input type="text" id="cnae_f" name="cnae_f" maxlength="20" 
         placeholder="Ex: 6201-5/01">
</div>

<div class="form-group">
  <label for="regime_estadual">Regime Estadual</label>
  <select id="regime_estadual" name="regime_estadual" required>
    <option value="">Selecione</option>
    <option>Normal</option><option>Substituição</option><option>Isento</option>
  </select>

  <label for="nir">NIRC</label>
  <input type="text" id="nir" name="nir" maxlength="20" 
         placeholder="Ex: 12345678">

  <label for="escrituracao_centralizada">Escrituração Centralizada</label>
  <select id="escrituracao_centralizada" name="escrituracao_centralizada" required>
    <option value="">Selecione</option>
    <option>Sim</option><option>Não</option>
  </select>

  <label for="inscricao_estadual">Inscrição Estadual</label>
  <input type="text" id="inscricao_estadual" name="inscricao_estadual" 
         placeholder="Ex: 9876543210">
</div>

<div class="form-group">
  <label for="data_nir">Data NIRC</label>
  <input type="date" id="data_nir" name="data_nir">

  <label for="area_construida">Área construída (m²)</label>
  <input type="number" id="area_construida" name="area_construida" 
         placeholder="Ex: 500">

  <label for="cod_estabelecimento">Cod. Estabelecimento</label>
  <input type="text" id="cod_estabelecimento" name="cod_estabelecimento" 
         placeholder="Ex: EST-001">
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
</form>

  </div>
</body>
</html>

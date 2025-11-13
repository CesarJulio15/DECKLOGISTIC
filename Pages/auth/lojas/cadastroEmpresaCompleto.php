<!-- cadastroEmpresaCompleto.php -->
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../../../assets/cadastroEmpresa.css">
  <link rel="icon" href="../../../img/logoDecklogistic.webp" type="image/x-icon" />
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
      
      return true; // Permite o envio do formulário
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

    function formatarNIRC(nirc) {
      // NIRC: 10 dígitos (São Paulo)
      return nirc
        .replace(/\D/g, "")
        .substring(0, 10);
    }

    function formatarIE(ie) {
      // IE SP: 12 dígitos (formato: 123.456.789.012)
      return ie
        .replace(/\D/g, "")
        .replace(/^(\d{3})(\d)/, "$1.$2")
        .replace(/(\d{3})(\d)/, "$1.$2")
        .replace(/(\d{3})(\d)/, "$1.$2")
        .substring(0, 15);
    }

function formatarCNAE(cnae) {
  const digits = String(cnae).replace(/\D/g, "").slice(0, 7); // só até 7 dígitos
  if (digits.length === 0) return "";

  const parte1 = digits.slice(0, 4);       // 4 primeiros dígitos
  const parte2 = digits.slice(4, 5);       // 5º dígito (se existir)
  const parte3 = digits.slice(5, 7);       // 6º e 7º dígitos (se existirem)

  let resultado = parte1;
  if (parte2) resultado += "-" + parte2;
  if (parte3) resultado += "/" + parte3;

  return resultado;
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
        e.target.value = formatarNIRC(e.target.value);
      });

      // Inscrição Estadual
      document.getElementById("inscricao_estadual").addEventListener("input", (e) => {
        e.target.value = formatarIE(e.target.value);
      });

      // CNAE-F
      document.getElementById("cnae_f").addEventListener("input", (e) => {
        e.target.value = formatarCNAE(e.target.value);
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
        if (v.length > 11) v = v.substring(0, 11);
        if (v.length <= 10) {
          e.target.value = v.replace(/(\d{2})(\d{4})(\d{0,4})/, "($1) $2-$3");
        } else {
          e.target.value = v.replace(/(\d{2})(\d{5})(\d{0,4})/, "($1) $2-$3");
        }
      });

      // Apenas números para Número e Área Construída
      document.getElementById("numero").addEventListener("input", (e) => {
        e.target.value = e.target.value.replace(/\D/g, "");
      });

      document.getElementById("area_construida").addEventListener("input", (e) => {
        e.target.value = e.target.value.replace(/\D/g, "");
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
  <div class="main-layout">
    <div class="left-side"></div>
    <div class="container">
      <h1>Finalize o cadastro da sua empresa</h1>

      <form onsubmit="return criptografarCamposSigilosos()" action="finalizarCadastro.php" method="POST">

        <div class="form-area">
          <div class="form-area-title">Dados da Empresa</div>
          <div class="form-group">
            <label for="razao">Razão Social</label>
            <input type="text" id="razao" name="razao" maxlength="150" required 
                   pattern="[A-Za-zÀ-ú\s\-\.]+" placeholder="Ex: Indústrias XPTO LTDA"
                   title="Apenas letras, espaços, hífen e ponto">
            <label for="fantasia">Nome Fantasia</label>
            <input type="text" id="fantasia" name="fantasia" maxlength="100" required 
                   pattern="[A-Za-zÀ-ú0-9\s\-\.]+" placeholder="Ex: Super XPTO"
                   title="Apenas letras, números, espaços, hífen e ponto">
          </div>
        </div>

        <div class="form-area">
          <div class="form-area-title">Endereço</div>
          <div class="form-group">
            <label for="cep">CEP</label>
            <input type="text" id="cep" name="cep" maxlength="9" required onblur="consultarCEP()" 
                   placeholder="Ex: 01001-000" pattern="\d{5}-?\d{3}"
                   title="Formato: 12345-678">
            <label for="endereco">Endereço</label>
            <input type="text" id="endereco" name="endereco" maxlength="120" required 
                   placeholder="Ex: Av. Paulista">
            <label for="numero">Número</label>
            <input type="text" id="numero" name="numero" maxlength="6" required 
                   placeholder="Ex: 1234" pattern="\d{1,6}"
                   title="Apenas números, máximo 6 dígitos">
            <label for="complemento">Complemento</label>
            <input type="text" id="complemento" name="complemento" maxlength="50" 
                   placeholder="Ex: Bloco B, Apto 21">
          </div>
          <div class="form-group">
            <label for="bairro">Bairro</label>
            <input type="text" id="bairro" name="bairro" maxlength="60" required 
                   pattern="[A-Za-zÀ-ú\s\-]+" placeholder="Ex: Bela Vista"
                   title="Apenas letras, espaços e hífen">
            <label for="uf">UF</label>
            <select id="uf" name="uf" required>
              <option value="">Selecione</option>
              <option>AC</option><option>AL</option><option>AP</option><option>AM</option>
              <option>BA</option><option>CE</option><option>DF</option><option>ES</option>
              <option>GO</option><option>MA</option><option>MT</option><option>MS</option>
              <option>MG</option><option>PA</option><option>PB</option><option>PR</option>
              <option>PE</option><option>PI</option><option>RJ</option><option>RN</option>
              <option>RS</option><option>RO</option><option>RR</option><option>SC</option>
              <option>SP</option><option>SE</option><option>TO</option>
            </select>
            <label for="municipio">Município</label>
            <input type="text" id="municipio" name="municipio" maxlength="60" required 
                   pattern="[A-Za-zÀ-ú\s\-]+" placeholder="Ex: São Paulo"
                   title="Apenas letras, espaços e hífen">
            <label for="pais">País</label>
            <select id="pais" name="pais" required>
              <option value="">Selecione</option>
              <option selected>Brasil</option>
              <option>Argentina</option>
              <option>Paraguai</option>
              <option>Uruguai</option>
              <option>Chile</option>
            </select>
          </div>
          <div class="form-group">
            <label for="fone">Telefone</label>
            <input type="tel" id="fone" name="fone" maxlength="15" required 
                   placeholder="Ex: (11) 91234-5678"
                   title="Formato: (11) 91234-5678 ou (11) 1234-5678">
          </div>
        </div>

        <div class="form-area">
          <div class="form-area-title">Dados Fiscais</div>
          <div class="form-group">
            <label for="regime_federal">Regime Federal</label>
            <select id="regime_federal" name="regime_federal" required>
              <option value="">Selecione</option>
              <option>Lucro Real</option>
              <option>Lucro Presumido</option>
              <option>Simples Nacional</option>
              <option>MEI</option>
            </select>
            <label for="cnpj">CNPJ</label>
            <input type="text" id="cnpj" name="cnpj" maxlength="18" required 
                   placeholder="Ex: 12.345.678/0001-90"
                   pattern="\d{2}\.\d{3}\.\d{3}/\d{4}-\d{2}"
                   title="Formato: 12.345.678/0001-90">
            <label for="cnae_f">CNAE-Fiscal (Principal)</label>
            <input type="text" id="cnae_f" name="cnae_f" maxlength="10" 
                   placeholder="Ex: 6201-5/00"
                   pattern="\d{4}-\d{1}/\d{2}"
                   title="Formato: 1234-5/67 (7 dígitos)">
          </div>
          <div class="form-group">
            <label for="regime_estadual">Regime Estadual</label>
            <select id="regime_estadual" name="regime_estadual" required>
              <option value="">Selecione</option>
              <option>Normal</option>
              <option>Substituição Tributária</option>
              <option>Isento</option>
              <option>Não Contribuinte</option>
            </select>
            <label for="nir">NIRC (Número de Inscrição no Cadastro de Contribuintes)</label>
            <input type="text" id="nir" name="nir" maxlength="10" 
                   placeholder="Ex: 1234567890"
                   pattern="\d{10}"
                   title="10 dígitos numéricos">
            <label for="escrituracao_centralizada">Escrituração Centralizada</label>
            <select id="escrituracao_centralizada" name="escrituracao_centralizada" required>
              <option value="">Selecione</option>
              <option>Sim</option>
              <option>Não</option>
            </select>
            <label for="inscricao_estadual">Inscrição Estadual</label>
            <input type="text" id="inscricao_estadual" name="inscricao_estadual" maxlength="15"
                   placeholder="Ex: 123.456.789.012"
                   pattern="\d{3}\.\d{3}\.\d{3}\.\d{3}"
                   title="12 dígitos (SP: 123.456.789.012)">
          </div>
          <div class="form-group">
            <label for="data_nir">Data de Inscrição NIRC</label>
            <input type="date" id="data_nir" name="data_nir">
            <label for="area_construida">Área Construída (m²)</label>
            <input type="text" id="area_construida" name="area_construida" maxlength="8"
                   placeholder="Ex: 500" pattern="\d{1,8}"
                   title="Apenas números, máximo 99999999">
            <label for="cod_estabelecimento">Código do Estabelecimento</label>
            <input type="text" id="cod_estabelecimento" name="cod_estabelecimento" maxlength="20"
                   placeholder="Ex: EST-001" pattern="[A-Za-z0-9\-]+"
                   title="Letras, números e hífen">
          </div>
        </div>

        <div class="form-area termos-area">
          <div class="form-area-title">Termos e Políticas</div>
          <div class="termos-group">
            <label>
              <input type="checkbox" required style="margin-right: 8px;">
              Eu li e concordo com os
              <a href="../../../termosUso.php" target="_blank">
                Termos de Uso
              </a>
            </label>
            <label>
              <input type="checkbox" required style="margin-right: 8px;">
              Eu li e concordo com as
              <a href="../../../politicaPrivacidade.php" target="_blank">
                Políticas de Privacidade
              </a>
            </label>
          </div>
        </div>

        <div class="form-area" style="background: none; box-shadow: none; border: none; padding: 0; margin-top: 10px;">
          <div class="form-group botoes-group">
            <button type="submit" class="btn-prosseguir">Prosseguir</button>
            <button type="button" class="btn-voltar" onclick="location.href='cadastro.php'">Voltar</button>
          </div>
        </div>
      </form>
    </div>
  </div>
</body>
</html>

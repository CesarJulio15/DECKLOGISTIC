
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
  margin: 0;
  padding: 0;
  display: flex;
  min-height: 100vh; /* altura total da tela */
}

.left-side {
  flex: 1;
  background: url('../img/mesacadastro.webp') no-repeat center center;
  background-size: cover;
}

.container {
  flex: 1;
  max-width: 800px;
  padding: 100px 30px 30px 30px;
  background-color: #fff;
}

    h1 {
     padding-left: 3px;
      text-align: center;
      margin-bottom: 150px;
    }
    form {
      max-width: 1100px;
      margin: auto;
    }
   .form-group {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 15px;
  flex-wrap: nowrap;
  
}
  .form-group input, .form-group select {
  flex: 1;
  min-width: 180px;
  padding: 10px;
  padding-left: 20px;
  padding-right: 20px; /* <-- isso move as setas pra dentro */
  border: 1px solid #ccc;
  border-radius: 20px;
}

.form-group select {
  appearance: none; /* remove seta nativa */
  background-image: url('data:image/svg+xml;utf8,<svg fill="gray" height="20" viewBox="0 0 24 24" width="20" xmlns="http://www.w3.org/2000/svg"><path d="M7 10l5 5 5-5z"/></svg>');
  background-repeat: no-repeat;
  background-position: right 15px center;
  background-size: 15px;
  padding-right: 40px;
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
  white-space: nowrap;
  flex-shrink: 0; /* <-- impede o botão de esticar ou encolher */
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
      <div class="left-side">
      <!-- Aqui você insere sua imagem no CSS -->
    </div>
    <div class="container">
  <h1>Finalize o cadastro da sua empresa</h1>
  <form onsubmit="criptografarCamposSigilosos()">
    <div class="form-group">
      <input type="text" placeholder="Razão" maxlength="100" required pattern="[A-Za-zÀ-ú\\s]+">
      <input type="text" placeholder="Fantasia" maxlength="100" required pattern="[A-Za-zÀ-ú\\s]+">
    </div>
    <div class="form-group">
    <input type="text" placeholder="CEP" required>
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
      <input type="tel" placeholder="Fone" required>
    </div>
    <hr><br>
    <div class="form-group">
      <select required>
        <option value="">Regime Federal</option>
        <option>Lucro Real</option><option>Lucro Presumido</option><option>Simples Nacional</option>
      </select>
      <input type="text" placeholder="CNPJ" id="cnpj" required>
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
      <input type="text" placeholder="Inscrição Estadual" id="inscricao_estadual">
    </div>
    <div class="form-group">
      <input type="date" placeholder="Data NIRC">
      <input type="number" placeholder="Área construída m²">
      <input type="text" placeholder="Cod. Estabelecimento">
    </div>
     
  <label style="display: inline-flex; align-items: center;">
  <input type="checkbox" required style="margin-right: 10px;"> Eu li e concordo com os termos de uso
</label>
<div>
Termos de uso

<div class="form-group" style="display: flex; align-items: center; justify-content: flex-start;">
   <button type="submit" style="flex-shrink: 0; margin-top: 30px;">Voltar</button>
  <button type="submit" style="flex-shrink: 0; width: 200px; margin-top: 30px;">Prosseguir
     <form action="login.php" method="POST">
  </button>


  </form>
</body>
</html>





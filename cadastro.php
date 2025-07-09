<?php
// cadastro.php - Página de Cadastro de Novo Usuário (com todos os campos)

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Inclui a conexão com o banco de dados
require_once __DIR__ . '/includes/conexao.php';

// Busca configurações para usar o logo e favicon dinâmicos
try {
    $stmt_configs = $pdo->query("SELECT chave, valor FROM configuracoes");
    $configs = $stmt_configs->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (PDOException $e) {
    $configs = [];
}
$logo_path = $configs['logo_path'] ?? 'imagens/sistema/logo_exemplo.png';
$favicon_path = $configs['favicon_path'] ?? 'imagens/sistema/favicon.ico';

// Processa mensagens de feedback
$mensagem_feedback = $_SESSION['mensagem_feedback']['texto'] ?? '';
$feedback_tipo = $_SESSION['mensagem_feedback']['tipo'] ?? '';
unset($_SESSION['mensagem_feedback']);

// Carrega dados para os dropdowns
$patentes = [];
$titulacoes = [];
$instituicoes = [];

try {
    $stmt_patentes = $pdo->query("SELECT sigla FROM patente ORDER BY sigla ASC");
    $patentes = $stmt_patentes->fetchAll(PDO::FETCH_COLUMN);

    $stmt_titulacoes = $pdo->query("SELECT nome FROM titulacao ORDER BY nome ASC");
    $titulacoes = $stmt_titulacoes->fetchAll(PDO::FETCH_COLUMN);

    $stmt_instituicoes = $pdo->query("SELECT sigla FROM instituicao ORDER BY sigla ASC");
    $instituicoes = $stmt_instituicoes->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {
    $mensagem_feedback = "Erro ao carregar opções para o formulário: " . $e->getMessage();
    $feedback_tipo = 'danger';
    error_log("Erro PDO ao carregar dropdowns para cadastro.php: " . $e->getMessage());
}

// Recupera dados do formulário da sessão em caso de erro anterior
$form_data = $_SESSION['form_data'] ?? [];
unset($_SESSION['form_data']);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - AVAPM</title>
    <link rel="icon" href="<?php echo htmlspecialchars($favicon_path); ?>" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        /* Estilos CSS aqui... (mantendo os mesmos estilos que você já tem) */
        :root {
            --cor-primaria: #007bff;
            --cor-primaria-hover: #0056b3;
            --cor-texto-principal: #343a40;
            --cor-texto-secundario: #6c757d;
            --cor-fundo: #f4f7f6;
        }
        html, body {
            height: 100%;
            margin: 0;
            font-family: 'Poppins', sans-serif;
            background-color: var(--cor-fundo);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px 0; /* Adiciona padding para evitar que o formulário cole nas bordas em telas pequenas */
        }
        .cadastro-container {
            width: 100%;
            padding: 20px;
            box-sizing: border-box;
        }
        .card {
            background-color: #fff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.08);
            width: 100%;
            max-width: 500px;
            margin: auto;
            text-align: center;
        }
        .logo { max-height: 60px; margin-bottom: 20px; }
        h2 { font-weight: 700; color: var(--cor-texto-principal); margin-bottom: 30px; }
        .form-group { text-align: left; margin-bottom: 15px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 5px; color: #555; font-size: 0.9rem; }
        .form-group input, .form-group select { width: 100%; padding: 10px 12px; border: 1px solid #ced4da; border-radius: 8px; box-sizing: border-box; font-size: 1rem; transition: border-color 0.3s ease; background-color: #fff; }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: var(--cor-primaria); box-shadow: 0 0 0 3px rgba(0,123,255,0.15); }
        .btn-cadastro { width: 100%; padding: 12px; border: none; border-radius: 8px; background-color: var(--cor-primaria); color: white; font-size: 16px; font-weight: 700; cursor: pointer; transition: background-color 0.3s ease; margin-top: 20px; }
        .btn-cadastro:hover { background-color: var(--cor-primaria-hover); }
        .links-login { margin-top: 20px; font-size: 14px; }
        .links-login a { color: var(--cor-texto-secundario); text-decoration: none; }
        .links-login a:hover { color: var(--cor-primaria); text-decoration: underline; }
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: left; }
        .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <div class="cadastro-container">
        <div class="card">
            <a href="index.php"><img src="<?php echo htmlspecialchars($logo_path); ?>" alt="Logo do Sistema" class="logo"></a>
            <h2>Crie sua Conta</h2>

            <?php if (!empty($mensagem_feedback)): ?>
                <div class="alert alert-<?php echo htmlspecialchars($feedback_tipo); ?>">
                    <?php echo $mensagem_feedback; // O <br> será interpretado aqui ?>
                </div>
            <?php endif; ?>

            <form action="processa_cadastro.php" method="POST">
                <div class="form-group">
                    <label for="nivel_acesso">Eu sou:</label>
                    <select id="nivel_acesso" name="nivel_acesso" required>
                        <option value="ALUNO" <?php echo (($form_data['nivel_acesso'] ?? 'ALUNO') == 'ALUNO') ? 'selected' : ''; ?>>Aluno</option>
                        <option value="PROFESSOR" <?php echo (($form_data['nivel_acesso'] ?? '') == 'PROFESSOR') ? 'selected' : ''; ?>>Professor</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="nome">Nome Completo:</label>
                    <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($form_data['nome'] ?? ''); ?>" required placeholder="Seu nome completo">
                </div>
                <div class="form-group">
                    <label for="cpf">CPF:</label>
                    <input type="text" id="cpf" name="cpf" value="<?php echo htmlspecialchars($form_data['cpf'] ?? ''); ?>" required placeholder="Seu CPF (somente números)">
                </div>
                <div class="form-group">
                    <label for="email">E-mail:</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>" required placeholder="Seu melhor e-mail">
                </div>
                 <div class="form-group">
                    <label for="rg">RG:</label>
                    <input type="text" id="rg" name="rg" value="<?php echo htmlspecialchars($form_data['rg'] ?? ''); ?>" placeholder="Seu RG (somente números)">
                </div>
                 <div class="form-group">
                    <label for="telefone">Telefone:</label>
                    <input type="text" id="telefone" name="telefone" value="<?php echo htmlspecialchars($form_data['telefone'] ?? ''); ?>" placeholder="Seu Telefone (com DDD)">
                </div>
                <div class="form-group">
                    <label for="patente">Patente:</label>
                    <select id="patente" name="patente">
                        <option value="">Selecione (se aplicável)</option>
                        <?php foreach ($patentes as $sigla_patente): ?>
                            <option value="<?php echo htmlspecialchars($sigla_patente); ?>"
                                <?php echo (($form_data['patente'] ?? '') == $sigla_patente) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($sigla_patente); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="titulacao">Titulação:</label>
                    <select id="titulacao" name="titulacao">
                        <option value="">Selecione (se aplicável)</option>
                        <?php foreach ($titulacoes as $nome_titulacao): ?>
                            <option value="<?php echo htmlspecialchars($nome_titulacao); ?>"
                                <?php echo (($form_data['titulacao'] ?? '') == $nome_titulacao) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($nome_titulacao); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="instituicao">Instituição:</label>
                     <select id="instituicao" name="instituicao">
                        <option value="">Selecione (se aplicável)</option>
                        <?php foreach ($instituicoes as $sigla_instituicao): ?>
                            <option value="<?php echo htmlspecialchars($sigla_instituicao); ?>"
                                <?php echo (($form_data['instituicao'] ?? '') == $sigla_instituicao) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($sigla_instituicao); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="fonte_pagadora">Fonte Pagadora:</label>
                     <select id="fonte_pagadora" name="fonte_pagadora">
                        <option value="">Selecione (se aplicável)</option>
                        <?php foreach ($instituicoes as $sigla_instituicao): ?>
                            <option value="<?php echo htmlspecialchars($sigla_instituicao); ?>"
                                <?php echo (($form_data['fonte_pagadora'] ?? '') == $sigla_instituicao) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($sigla_instituicao); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="nome_guerra">Nome de Guerra:</label>
                    <input type="text" id="nome_guerra" name="nome_guerra" value="<?php echo htmlspecialchars($form_data['nome_guerra'] ?? ''); ?>" placeholder="Seu nome de guerra (opcional)">
                    <div class="form-group">
                        <label for="senha">Senha:</label>
                        <input type="password" id="senha" name="senha" required placeholder="Escolha uma senha forte">
                    </div>
                    <div class="form-group">
                        <label for="confirma_senha">Confirmar Senha:</label>
                        <input type="password" id="confirma_senha" name="confirma_senha" required placeholder="Repita sua senha">
                    </div>


                <button type="submit" class="btn-cadastro">Cadastrar</button>
            </form>
            <div class="links-login">
                Já tem uma conta? <a href="login.php">Faça login aqui</a>
            </div>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    <script>
        $(document).ready(function(){
            $('#cpf').mask('000.000.000-00', {reverse: true});
            var SPMaskBehavior = function (val) {
                return val.replace(/\D/g, '').length === 11 ? '(00) 00000-0000' : '(00) 0000-00009';
            },
            spOptions = {
                onKeyPress: function(val, e, field, options) {
                    field.mask(SPMaskBehavior.apply({}, arguments), options);
                }
            };
            $('#telefone').mask(SPMaskBehavior, spOptions);
             $('#rg').on('input', function() {
                this.value = this.value.replace(/\D/g, '');
            });
        });
    </script>
</body>
</html>

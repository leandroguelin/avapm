php
<?php
// cadastro.php - Página de Cadastro de Novo Usuário

if (session_status() == PHP_SESSION_NONE) { session_start(); }

// Inclui a conexão com o banco de dados
require_once __DIR__ . '/includes/conexao.php';

// Busca configurações para usar o logo e favicon dinâmicos (se aplicável para páginas públicas)
try {
    $stmt_configs = $pdo->query("SELECT chave, valor FROM configuracoes");
    $configs = $stmt_configs->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (PDOException $e) {
    $configs = [];
}
$logo_path = $configs['logo_path'] ?? 'imagens/sistema/logo_exemplo.png'; // Ajuste o caminho se necessário
$favicon_path = $configs['favicon_path'] ?? 'imagens/sistema/favicon.ico'; // Ajuste o caminho se necessário

// Processa mensagens de feedback (ex: "Cadastro realizado com sucesso!")
$mensagem_feedback = $_SESSION['mensagem_feedback']['texto'] ?? '';
$feedback_tipo = $_SESSION['mensagem_feedback']['tipo'] ?? '';
unset($_SESSION['mensagem_feedback']);
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
        /* CSS consistente com as outras páginas públicas (adapte conforme seu CSS existente) */
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
            max-width: 500px; /* Ajuste conforme necessário */
            margin: auto;
            text-align: center;
        }
        .logo {
            max-height: 60px;
            margin-bottom: 20px;
        }
        h2 {
            font-weight: 700;
            color: var(--cor-texto-principal);
            margin-bottom: 30px;
        }
        .form-group {
            text-align: left;
            margin-bottom: 15px; /* Espaço menor entre os campos */
        }
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px; /* Espaço menor entre label e input */
            color: #555;
            font-size: 0.9rem; /* Fonte menor para labels */
        }
        .form-group input {
            width: 100%;
            padding: 10px 12px; /* Padding menor */
            border: 1px solid #ced4da;
            border-radius: 8px;
            box-sizing: border-box;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        .form-group input:focus {
            outline: none;
            border-color: var(--cor-primaria);
            box-shadow: 0 0 0 3px rgba(0,123,255,0.15);
        }
        .btn-cadastro {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 8px;
            background-color: var(--cor-primaria);
            color: white;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-top: 20px;
        }
        .btn-cadastro:hover {
            background-color: var(--cor-primaria-hover);
        }
        .links-login {
            margin-top: 20px;
            font-size: 14px;
        }
        .links-login a {
            color: var(--cor-texto-secundario);
            text-decoration: none;
        }
        .links-login a:hover {
            color: var(--cor-primaria);
            text-decoration: underline;
        }
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        /* Estilos para o footer público, se aplicável */
         .public-footer {
            margin-top: 30px;
            font-size: 0.8rem;
            color: var(--cor-texto-secundario);
        }
    </style>
</head>
<body>

    <div class="cadastro-container">
        <div class="card">
            <a href="index.php"><img src="<?php echo htmlspecialchars($logo_path); ?>" alt="Logo do Sistema" class="logo"></a>
            <h2>Crie sua Conta</h2>

            <?php if (!empty($mensagem_feedback)): ?>
                <div class="alert alert-<?php echo htmlspecialchars($feedback_tipo); ?>">
                    <?php echo htmlspecialchars($mensagem_feedback); ?>
                </div>
            <?php endif; ?>

            <form action="processa_cadastro.php" method="POST">
                <div class="form-group">
                    <label for="nome">Nome Completo:</label>
                    <input type="text" id="nome" name="nome" required placeholder="Seu nome completo">
                </div>
                <div class="form-group">
                    <label for="cpf">CPF:</label>
                    <input type="text" id="cpf" name="cpf" required placeholder="Seu CPF (somente números)" pattern="\d{11}" title="Por favor, insira um CPF válido com 11 dígitos.">
                </div>
                <div class="form-group">
                    <label for="email">E-mail:</label>
                    <input type="email" id="email" name="email" required placeholder="Seu melhor e-mail">
                </div>
                <div class="form-group">
                    <label for="senha">Senha:</label>
                    <input type="password" id="senha" name="senha" required placeholder="Escolha uma senha forte">
                </div>
                <div class="form-group">
                    <label for="confirmar_senha">Confirmar Senha:</label>
                    <input type="password" id="confirmar_senha" name="confirmar_senha" required placeholder="Repita sua senha">

                </div>
                <button type="submit" class="btn-cadastro">Cadastrar</button>
            </form>
            <div class="links-login">
                Já tem uma conta? <a href="login.php">Faça login aqui</a>
            </div>

             <?php
            // Inclui o rodapé público se existir (ajuste o caminho)
            // require_once __DIR__ . '/includes/templates/footer_public.php';
            ?>
        </div>
    </div>
</body>
</html>
<?php
// recuperar_senha.php
// Este arquivo é muito semelhante ao login.php
if (session_status() == PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/includes/conexao.php';
try {
    $stmt_configs = $pdo->query("SELECT chave, valor FROM configuracoes");
    $configs = $stmt_configs->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (PDOException $e) { $configs = []; }
$logo_path = $configs['logo_path'] ?? 'imagens/sistema/logo_exemplo.png';
$favicon_path = $configs['favicon_path'] ?? 'imagens/sistema/favicon.ico';
$mensagem_feedback = $_SESSION['mensagem_feedback']['texto'] ?? '';
$feedback_tipo = $_SESSION['mensagem_feedback']['tipo'] ?? '';
unset($_SESSION['mensagem_feedback']); 
?>
<!DOCTYPE html>
<html>
<head>
    <title>Recuperar Senha - AVAPM</title>
    <link rel="icon" href="<?php echo htmlspecialchars($favicon_path); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style> /* Cole o mesmo CSS da página de login.php aqui */ </style>
</head>
<body>
    <div class="login-container">
        <div class="card">
            <a href="index.php"><img src="<?php echo htmlspecialchars($logo_path); ?>" alt="Logo" class="logo"></a>
            <h2>Recuperar Senha</h2>
            <p>Insira seu e-mail para receber as instruções de recuperação.</p>
             <?php if (!empty($mensagem_feedback)): ?>
                <div class="alert alert-<?php echo htmlspecialchars($feedback_tipo); ?>">
                    <?php echo htmlspecialchars($mensagem_feedback); ?>
                </div>
            <?php endif; ?>
            <form action="processa_recuperacao.php" method="POST">
                <div class="form-group">
                    <label for="email">E-mail de Cadastro:</label>
                    <input type="email" id="email" name="email" required placeholder="Digite seu e-mail">
                </div>
                <button type="submit" class="btn-login">Enviar Link de Recuperação</button>
            </form>
            <div class="links-login">
                <a href="login.php">Voltar para o Login</a>
            </div>
        </div>
    </div>
</body>
</html>
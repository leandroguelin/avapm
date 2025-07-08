<?php
// resetar_senha.php
if (session_status() == PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/includes/conexao.php';
// ... (código para buscar logo e favicon, e o CSS, igual ao de login.php) ...

$token_valido = false;
if(isset($_GET['token'])) {
    $token = $_GET['token'];
    $stmt = $pdo->prepare("SELECT id FROM usuario WHERE reset_token = :token AND reset_token_expires_at > NOW()");
    $stmt->execute([':token' => $token]);
    $usuario = $stmt->fetch();
    if($usuario) {
        $token_valido = true;
    }
}
?>
<!DOCTYPE html>
<html>
<head> <title>Redefinir Senha</title> </head>
<body>
    <div class="login-container">
        <div class="card">
            <h2>Crie uma Nova Senha</h2>
            <?php if($token_valido): ?>
                <form action="processa_reset.php" method="POST">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    <div class="form-group">
                        <label for="senha">Nova Senha:</label>
                        <input type="password" name="senha" id="senha" required>
                    </div>
                    <div class="form-group">
                        <label for="senha_confirma">Confirme a Nova Senha:</label>
                        <input type="password" name="senha_confirma" id="senha_confirma" required>
                    </div>
                    <button type="submit" class="btn-login">Salvar Nova Senha</button>
                </form>
            <?php else: ?>
                <div class="alert alert-danger">Token inválido ou expirado. Por favor, solicite um novo link de recuperação.</div>
                <a href="recuperar_senha.php">Voltar</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
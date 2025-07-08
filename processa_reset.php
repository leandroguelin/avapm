<?php
// processa_reset.php
if (session_status() == PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/includes/conexao.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $token = $_POST['token'];
    $senha = $_POST['senha'];
    $senha_confirma = $_POST['senha_confirma'];

    if ($senha !== $senha_confirma) {
        // Idealmente, isso deveria ser uma mensagem de feedback na página de reset
        die('As senhas não coincidem.');
    }

    // Verifica o token novamente por segurança
    $stmt = $pdo->prepare("SELECT id FROM usuario WHERE reset_token = :token AND reset_token_expires_at > NOW()");
    $stmt->execute([':token' => $token]);
    $usuario = $stmt->fetch();

    if ($usuario) {
        $nova_senha_hash = password_hash($senha, PASSWORD_DEFAULT);
        
        // Atualiza a senha e invalida o token
        $stmt_update = $pdo->prepare("UPDATE usuario SET senha = :senha, reset_token = NULL, reset_token_expires_at = NULL WHERE id = :id");
        $stmt_update->execute([':senha' => $nova_senha_hash, ':id' => $usuario['id']]);

        $_SESSION['mensagem_feedback'] = ['tipo' => 'success', 'texto' => 'Sua senha foi alterada com sucesso! Por favor, faça o login.'];
        header('Location: login.php');
        exit();
    } else {
        die('Token inválido ou expirado.');
    }
}
?>
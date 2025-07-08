<?php
// processa_login.php - VERSÃO FINAL E ROBUSTA

if (session_status() == PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/includes/conexao.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $cpf = $_POST['cpf']; // Alterado de email para cpf
    $senha = $_POST['senha'];

    $stmt = $pdo->prepare('SELECT * FROM usuario WHERE cpf = :cpf'); // Alterado para buscar por cpf
    $stmt->execute(['cpf' => $cpf]); // Alterado para usar o parâmetro cpf
    $usuario = $stmt->fetch();

    if ($usuario && password_verify($senha, $usuario['senha'])) {
        // Login bem-sucedido
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['nome_usuario'] = $usuario['nome'];
        
        // SUGESTÃO IMPLEMENTADA: Padroniza o nível de acesso para MAIÚSCULAS ao salvar na sessão
        $_SESSION['nivel_acesso'] = strtoupper($usuario['nivel_acesso']);
        
        $_SESSION['foto_perfil'] = $usuario['foto'];

        // --- Registrar login na tabela log_logins ---
        try {
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'N/A'; // Obtém o IP do usuário

            $stmt_log = $pdo->prepare("INSERT INTO log_logins (usuario_id, data_login, ip, tipo_dispositivo, localizacao) VALUES (:usuario_id, NOW(), :ip, NULL, NULL)");
            $stmt_log->bindParam(':usuario_id', $usuario['id'], PDO::PARAM_INT);
            $stmt_log->bindParam(':ip', $ip_address);
            $stmt_log->execute();
        } catch (PDOException $e) {
            // Loga o erro, mas não impede o login do usuário
            error_log("Erro ao registrar login no log_logins para usuario_id " . $usuario['id'] . ": " . $e->getMessage());
            // Não define mensagem de feedback para o usuário final neste caso
        }

        // CORREÇÃO: Força o PHP a salvar os dados da sessão no disco ANTES de redirecionar.
        session_write_close(); 

        // Redireciona para o "porteiro"
        header('Location: redireciona_usuario.php');
        exit();

    } else {
        // Falha no login
        $_SESSION['mensagem_feedback'] = ['tipo' => 'danger', 'texto' => 'E-mail ou senha inválidos.'];
        header('Location: login.php');
        exit();
    }
}
?>
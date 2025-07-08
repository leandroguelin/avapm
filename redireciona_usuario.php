<?php
// redireciona_usuario.php - VERSÃO COM VERIFICAÇÃO EM MAIÚSCULAS

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verifica se o usuário está logado e tem um nível de acesso definido
if (isset($_SESSION['usuario_id']) && isset($_SESSION['nivel_acesso'])) {
    
    // Agora podemos usar a variável diretamente, pois sabemos que ela está padronizada
    $nivel_acesso = $_SESSION['nivel_acesso'];

    // SUGESTÃO IMPLEMENTADA: Verificação com strings em maiúsculas
    if ($nivel_acesso === 'ADMINISTRADOR' || $nivel_acesso === 'GERENTE') {
        header('Location: dashboard.php');
        exit();
    } elseif ($nivel_acesso === 'PROFESSOR') {
        header('Location: dashboard_professor.php');
        exit();
    } else {
        // Alunos e outros perfis vão para a página de perfil
        header('Location: meu_perfil.php');
        exit();
    }

} else {
    // Se a sessão não foi encontrada, volta para o login
    header('Location: login.php');
    exit();
}
?>
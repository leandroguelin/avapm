<?php
// includes/seguranca.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Verifica se o usuário logado tem permissão para acessar uma página.
 * Redireciona para a página de redirecionamento se não tiver permissão.
 *
 * @param string $nome_pagina O nome do arquivo da página atual (ex: 'dashboard.php').
 * @param PDO $pdo A conexão com o banco de dados.
 */
function verificar_permissao($nome_pagina, $pdo) {
    // 1. Verifica se o usuário está logado
    if (!isset($_SESSION['usuario_id'])) {
        $_SESSION['mensagem_feedback'] = ['tipo' => 'danger', 'texto' => 'Você precisa estar logado para acessar esta página.'];
        header('Location: login.php');
        exit();
    }

    // 2. Obtém o nível de acesso do usuário da sessão
    $nivel_usuario_logado = $_SESSION['nivel_acesso'] ?? 'CONVIDADO';

    // O ADMINISTRADOR sempre tem acesso a tudo.
    if ($nivel_usuario_logado === 'ADMINISTRADOR') {
        return; // Permite o acesso e continua
    }

    // 3. Busca as permissões da página no banco de dados
    try {
        $stmt = $pdo->prepare("SELECT niveis_acesso_permitidos FROM pagina_permissoes WHERE nome_pagina = :nome_pagina");
        $stmt->execute([':nome_pagina' => $nome_pagina]);
        $permissoes_str = $stmt->fetchColumn();

        // 4. Verifica as permissões
        if ($permissoes_str === false) {
            // Se a página não está no banco, nega o acesso por padrão (mais seguro)
            $_SESSION['mensagem_feedback'] = ['tipo' => 'danger', 'texto' => 'Acesso negado. A página não possui regras de permissão definidas.'];
            header('Location: redireciona_usuario.php');
            exit();
        }

        $niveis_permitidos = explode(',', $permissoes_str);

        if (!in_array($nivel_usuario_logado, $niveis_permitidos)) {
            // Se o nível do usuário não está na lista, nega o acesso
            $_SESSION['mensagem_feedback'] = ['tipo' => 'danger', 'texto' => 'Você não tem permissão para acessar esta página.'];
            header('Location: redireciona_usuario.php');
            exit();
        }

    } catch (PDOException $e) {
        // Em caso de erro no banco, nega o acesso e loga o erro
        error_log("Erro de permissão em seguranca.php: " . $e->getMessage());
        $_SESSION['mensagem_feedback'] = ['tipo' => 'danger', 'texto' => 'Erro ao verificar permissões. Contate o administrador.'];
        header('Location: redireciona_usuario.php');
        exit();
    }
}
?>

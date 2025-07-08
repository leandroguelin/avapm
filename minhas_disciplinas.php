php
<?php
// avapm/minhas_disciplinas.php

// Iniciar a sessão para usar variáveis de sessão e verificar login
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Incluir a conexão com o banco de dados
require_once __DIR__ . '/includes/conexao.php';

// Redirecionar se o usuário NÃO estiver logado
if (!isset($_SESSION['usuario_id'])) {
    $_SESSION['mensagem_feedback'] = [
        'tipo' => 'danger',
        'texto' => 'Você precisa estar logado para acessar esta página.'
    ];
    header('Location: index.php'); // Redirecionar para a página de login
    exit();
}

// Definir o título da página
$page_title = "Minhas Disciplinas";

// Obter o ID do usuário logado
$usuario_id_logado = $_SESSION['usuario_id'];
$nome_usuario_logado
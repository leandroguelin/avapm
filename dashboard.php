<?php
// dashboard.php - VERSÃO COM VERIFICAÇÃO DE ACESSO CORRIGIDA

if (session_status() == PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/includes/conexao.php';

// --- Verificação de Login e Nível de Acesso ---
// CORREÇÃO: A verificação agora é consistente com o padrão da sessão (MAIÚSCULAS)
$allowed_access_levels = ['ADMINISTRADOR', 'GERENTE']; 
$user_level = $_SESSION['nivel_acesso'] ?? '';

if (!isset($_SESSION['usuario_id']) || !in_array($user_level, $allowed_access_levels)) {
    // Se o usuário não estiver logado ou não tiver o nível correto,
    // ele é enviado para o "porteiro" decidir o que fazer.
    // Isso evita loops caso um professor tente acessar este dashboard manualmente.
    header('Location: redireciona_usuario.php');
    exit();
}

// --- O restante do seu código do dashboard continua aqui ---
// ... (busca de dados dinâmicos, etc.) ...

$page_title = "Painel - Visão Geral";
$nome_usuario_logado = $_SESSION['nome_usuario'];

try {
    $total_usuarios = $pdo->query("SELECT COUNT(id) FROM usuario")->fetchColumn();
    $avaliacoes_ativas = $pdo->query("SELECT COUNT(id) FROM avaliacao WHERE situacao = 'Ativa'")->fetchColumn();
    $total_disciplinas = $pdo->query("SELECT COUNT(id) FROM disciplina")->fetchColumn();
    $stmt_abertas = $pdo->prepare("SELECT a.id, a.nome, a.data_final, c.nome as curso_nome FROM avaliacao a JOIN cursos c ON a.curso_id = c.id WHERE a.situacao = 'Ativa' AND NOW() BETWEEN a.data_inicio AND a.data_final ORDER BY a.data_final ASC");
    $stmt_abertas->execute();
    $avaliacoes_em_aberto = $stmt_abertas->fetchAll(PDO::FETCH_ASSOC);
    $stmt_concluidas = $pdo->prepare("SELECT a.id, a.nome, a.data_final, c.nome as curso_nome FROM avaliacao a JOIN cursos c ON a.curso_id = c.id WHERE a.data_final < NOW() ORDER BY a.data_final DESC LIMIT 5");
    $stmt_concluidas->execute();
    $ultimas_avaliacoes_concluidas = $stmt_concluidas->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $total_usuarios = $avaliacoes_ativas = $total_disciplinas = 0;
    $avaliacoes_em_aberto = [];
    $ultimas_avaliacoes_concluidas = [];
}

require_once __DIR__ . '/includes/templates/header_dashboard.php';
require_once __DIR__ . '/includes/templates/sidebar_dashboard.php';
?>

<div class="main-content-dashboard">
    </div> 

<style>
    /* ... (seu CSS do dashboard) ... */
</style>

<?php
require_once __DIR__ . '/includes/templates/footer_dashboard.php';
?>
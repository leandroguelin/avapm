<?php
// dashboard.php - Painel de Controle Principal (Versão Simplificada)

// --- Inicialização e Segurança ---
if (session_status() == PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/includes/conexao.php';
require_once __DIR__ . '/includes/seguranca.php';
verificar_permissao(basename(__FILE__), $pdo);

// --- Definições da Página ---
$page_title = "Painel - Visão Geral";
$current_page = "Painel";

// --- Busca de Dados para os Cartões ---
$stats = [
    'usuarios' => 0,
    'avaliacoes' => 0,
    'disciplinas' => 0,
    'cursos' => 0
];
$mensagem_erro = null;

try {
    // Busca as estatísticas principais
    $stats['usuarios'] = $pdo->query("SELECT COUNT(id) FROM usuario")->fetchColumn();
    $stats['avaliacoes'] = $pdo->query("SELECT COUNT(id) FROM avaliacao")->fetchColumn();
    $stats['disciplinas'] = $pdo->query("SELECT COUNT(id) FROM disciplina")->fetchColumn();
    $stats['cursos'] = $pdo->query("SELECT COUNT(id) FROM cursos")->fetchColumn();

} catch (PDOException $e) {
    $mensagem_erro = "Erro de Banco de Dados: " . $e->getMessage();
    error_log("Erro no Dashboard: " . $e->getMessage());
}

// --- Inclusão dos Templates de Layout ---
require_once __DIR__ . '/includes/templates/header_dashboard.php';
require_once __DIR__ . '/includes/templates/sidebar_dashboard.php';
?>

<div class="main-content-dashboard">
    <header class="dashboard-header">
        <h1><?php echo htmlspecialchars($page_title); ?></h1>
    </header>

    <?php if ($mensagem_erro): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($mensagem_erro); ?></div>
    <?php else: ?>
        <!-- Seção de Cartões de Estatísticas -->
        <section class="dashboard-cards">
            <div class="card">
                <div class="card-icon"><i class="fas fa-users"></i></div>
                <div class="card-info">
                    <div class="card-title">Total de Usuários</div>
                    <div class="card-value"><?php echo htmlspecialchars($stats['usuarios']); ?></div>
                </div>
            </div>
            <div class="card">
                <div class="card-icon"><i class="fas fa-file-alt"></i></div>
                <div class="card-info">
                    <div class="card-title">Total de Avaliações</div>
                    <div class="card-value"><?php echo htmlspecialchars($stats['avaliacoes']); ?></div>
                </div>
            </div>
            <div class="card">
                <div class="card-icon"><i class="fas fa-book"></i></div>
                <div class="card-info">
                    <div class="card-title">Total de Disciplinas</div>
                    <div class="card-value"><?php echo htmlspecialchars($stats['disciplinas']); ?></div>
                </div>
            </div>
            <div class="card">
                <div class="card-icon"><i class="fas fa-graduation-cap"></i></div>
                <div class="card-info">
                    <div class="card-title">Total de Cursos</div>
                    <div class="card-value"><?php echo htmlspecialchars($stats['cursos']); ?></div>
                </div>
            </div>
        </section>

        <!-- A seção do gráfico e atividades recentes foi removida. -->

    <?php endif; ?>
</div>

<?php
require_once __DIR__ . '/includes/templates/footer_dashboard.php';
?>

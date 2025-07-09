<?php
// dashboard.php - Painel de Controle Principal (Versão Aprimorada)

// --- Inicialização e Segurança ---
if (session_status() == PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/includes/conexao.php';
require_once __DIR__ . '/includes/seguranca.php';
verificar_permissao(basename(__FILE__), $pdo);

// --- Definições da Página ---
$page_title = "Painel - Visão Geral";
$current_page = "Painel";

// --- Busca de Dados ---
$stats = [
    'usuarios' => 0,
    'avaliacoes' => 0,
    'disciplinas' => 0,
    'cursos' => 0
];
$avaliacoes_em_aberto = [];
$mensagem_erro = null;

try {
    // Busca as estatísticas para os cards
    $stats['usuarios'] = $pdo->query("SELECT COUNT(id) FROM usuario")->fetchColumn();
    $stats['avaliacoes'] = $pdo->query("SELECT COUNT(id) FROM avaliacao")->fetchColumn();
    $stats['disciplinas'] = $pdo->query("SELECT COUNT(id) FROM disciplina")->fetchColumn();
    $stats['cursos'] = $pdo->query("SELECT COUNT(id) FROM cursos")->fetchColumn();

    // Busca as avaliações que estão ativas e dentro do prazo
    $stmt_abertas = $pdo->prepare("
        SELECT a.id, a.nome, c.nome as curso_nome, a.data_inicio, a.data_final 
        FROM avaliacao a 
        JOIN cursos c ON a.curso_id = c.id 
        WHERE a.situacao = 'Ativa' AND NOW() BETWEEN a.data_inicio AND a.data_final 
        ORDER BY a.data_final ASC
    ");
    $stmt_abertas->execute();
    $avaliacoes_em_aberto = $stmt_abertas->fetchAll(PDO::FETCH_ASSOC);

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
        <!-- Seção de Cartões de Estatísticas Clicáveis -->
        <section class="dashboard-cards">
            <a href="gerenciar_usuarios.php" class="card-link">
                <div class="card">
                    <div class="card-icon"><i class="fas fa-users"></i></div>
                    <div class="card-info"><div class="card-title">Total de Usuários</div><div class="card-value"><?php echo htmlspecialchars($stats['usuarios']); ?></div></div>
                </div>
            </a>
            <a href="gerenciar_avaliacoes.php" class="card-link">
                <div class="card">
                    <div class="card-icon"><i class="fas fa-file-alt"></i></div>
                    <div class="card-info"><div class="card-title">Total de Avaliações</div><div class="card-value"><?php echo htmlspecialchars($stats['avaliacoes']); ?></div></div>
                </div>
            </a>
            <a href="gerenciar_disciplinas.php" class="card-link">
                <div class="card">
                    <div class="card-icon"><i class="fas fa-book"></i></div>
                    <div class="card-info"><div class="card-title">Total de Disciplinas</div><div class="card-value"><?php echo htmlspecialchars($stats['disciplinas']); ?></div></div>
                </div>
            </a>
            <a href="gerenciar_cursos.php" class="card-link">
                <div class="card">
                    <div class="card-icon"><i class="fas fa-graduation-cap"></i></div>
                    <div class="card-info"><div class="card-title">Total de Cursos</div><div class="card-value"><?php echo htmlspecialchars($stats['cursos']); ?></div></div>
                </div>
            </a>
        </section>

        <!-- Divisor -->
        <hr class="dashboard-divider">

        <!-- Seção de Avaliações em Aberto -->
        <section class="dashboard-section">
            <div class="section-header">
                <h2>Avaliações em Aberto</h2>
            </div>
            
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="thead-dark">
                        <tr>
                            <th>Nome da Avaliação</th>
                            <th>Curso</th>
                            <th>Data de Término</th>
                            <th class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($avaliacoes_em_aberto)): ?>
                            <tr><td colspan="4" class="text-center">Nenhuma avaliação em aberto no momento.</td></tr>
                        <?php else: ?>
                            <?php foreach ($avaliacoes_em_aberto as $avaliacao): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($avaliacao['nome']); ?></td>
                                    <td><?php echo htmlspecialchars($avaliacao['curso_nome']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($avaliacao['data_final'])); ?></td>
                                    <td class="text-center">
                                        <a href="ver_relatorio.php?id=<?php echo $avaliacao['id']; ?>" class="btn btn-sm btn-info" title="Ver Relatório HTML"><i class="fas fa-eye"></i></a>
                                        <a href="exportar_relatorio_pdf.php?avaliacao_id=<?php echo $avaliacao['id']; ?>" class="btn btn-sm btn-danger" title="Exportar para PDF"><i class="fas fa-file-pdf"></i></a>
                                        <a href="exportar_relatorio_excel.php?avaliacao_id=<?php echo $avaliacao['id']; ?>" class="btn btn-sm btn-success" title="Exportar para Excel"><i class="fas fa-file-excel"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif; ?>
</div>

<?php
require_once __DIR__ . '/includes/templates/footer_dashboard.php';
?>

<style>
/* Estilo para o link do card não ter sublinhado */
.card-link {
    text-decoration: none;
    color: inherit;
}
.card-link:hover {
    text-decoration: none;
    color: inherit;
}
/* Estilo para o divisor */
.dashboard-divider {
    margin-top: 2rem;
    margin-bottom: 2rem;
    border: 0;
    border-top: 1px solid #e9ecef;
}
/* Ações da tabela */
.table .btn-sm {
    margin: 0 2px;
}
</style>

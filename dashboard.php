<?php
// dashboard.php - Painel de Controle Principal

if (session_status() == PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/includes/conexao.php';

// --- Verificação de Permissão ---
require_once __DIR__ . '/includes/seguranca.php';
verificar_permissao(basename(__FILE__), $pdo);


// --- Definições da Página ---
$page_title = "Painel - Visão Geral";
$current_page = "Painel"; // Para destacar o item na sidebar

// --- Busca de Dados para o Dashboard ---
$stats = [
    'professores' => 0,
    'avaliacoes' => 0,
    'disciplinas' => 0,
    'cursos' => 0
];
$avaliacoes_em_aberto = [];

try {
    // Busca as estatísticas principais
    $stats['professores'] = $pdo->query("SELECT COUNT(id) FROM usuario WHERE nivel_acesso = 'PROFESSOR'")->fetchColumn();
    $stats['avaliacoes'] = $pdo->query("SELECT COUNT(id) FROM avaliacao")->fetchColumn();
    $stats['disciplinas'] = $pdo->query("SELECT COUNT(id) FROM disciplina")->fetchColumn();
    $stats['cursos'] = $pdo->query("SELECT COUNT(id) FROM cursos")->fetchColumn();

    // Busca as avaliações que estão ativas e dentro do prazo
    $stmt_abertas = $pdo->prepare("
        SELECT a.id, a.nome, c.nome as curso_nome, a.data_final 
        FROM avaliacao a 
        JOIN cursos c ON a.curso_id = c.id 
        WHERE a.situacao = 'Ativa' AND NOW() BETWEEN a.data_inicio AND a.data_final 
        ORDER BY a.data_final ASC
    ");
    $stmt_abertas->execute();
    $avaliacoes_em_aberto = $stmt_abertas->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Em caso de erro, define uma mensagem de erro e loga o erro
    $mensagem_erro = "Erro ao carregar dados do painel. Por favor, tente novamente mais tarde.";
    error_log("Erro no Dashboard: " . $e->getMessage());
}

// Inclui os templates do cabeçalho e da barra lateral
require_once __DIR__ . '/includes/templates/header_dashboard.php';
require_once __DIR__ . '/includes/templates/sidebar_dashboard.php';
?>

<div class="main-content-dashboard">
    <header class="dashboard-header">
        <h1><?php echo htmlspecialchars($page_title); ?></h1>
    </header>

    <?php if (isset($mensagem_erro)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($mensagem_erro); ?></div>
    <?php endif; ?>

    <!-- Seção de Estatísticas -->
    <section class="dashboard-stats">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-chalkboard-teacher"></i></div>
            <div class="stat-info">
                <span class="stat-number"><?php echo $stats['professores']; ?></span>
                <span class="stat-label">Professores</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-file-alt"></i></div>
            <div class="stat-info">
                <span class="stat-number"><?php echo $stats['avaliacoes']; ?></span>
                <span class="stat-label">Avaliações</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-book-open"></i></div>
            <div class="stat-info">
                <span class="stat-number"><?php echo $stats['disciplinas']; ?></span>
                <span class="stat-label">Disciplinas</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-graduation-cap"></i></div>
            <div class="stat-info">
                <span class="stat-number"><?php echo $stats['cursos']; ?></span>
                <span class="stat-label">Cursos</span>
            </div>
        </div>
    </section>

    <!-- Seção de Avaliações em Aberto -->
    <section class="dashboard-section">
        <div class="section-header">
            <h2>Avaliações em Aberto</h2>
        </div>
        
        <div class="table-responsive">
            <?php if (!empty($avaliacoes_em_aberto)): ?>
                <table class="table table-striped table-hover">
                    <thead class="thead-dark">
                        <tr>
                            <th>Nome da Avaliação</th>
                            <th>Curso</th>
                            <th>Data de Término</th>
                            <th class="text-center">Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($avaliacoes_em_aberto as $avaliacao): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($avaliacao['nome']); ?></td>
                                <td><?php echo htmlspecialchars($avaliacao['curso_nome']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($avaliacao['data_final'])); ?></td>
                                <td class="text-center">
                                    <a href="responder_avaliacao.php?id=<?php echo $avaliacao['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i> Acessar
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="alert alert-info">
                    Não há nenhuma avaliação em aberto no momento.
                </div>
            <?php endif; ?>
        </div>
    </section>

</div>

<?php
// Inclui o template do rodapé
require_once __DIR__ . '/includes/templates/footer_dashboard.php';
?>

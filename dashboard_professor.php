<?php
// avapm/dashboard_professor.php - VERSÃO COMPLETAMENTE REFEITA

// --- Inicialização e Segurança ---
if (session_status() == PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/includes/conexao.php';
require_once __DIR__ . '/includes/seguranca.php';

// Garante que apenas professores e administradores possam ver esta página
$allowed_access_levels = ['professor', 'administrador', 'gerente'];
verificar_permissao(basename(__FILE__), $pdo, $allowed_access_levels);

$page_title = "Painel do Professor";
$current_page = "PainelProfessor"; // Para a sidebar
$professor_id = $_SESSION['usuario_id'];

// --- Busca de Dados para o Dashboard ---
$stats = [
    'cursos_distintos' => 0,
    'avaliacoes_recebidas' => 0,
];
$notas_por_curso = [];
$avaliacoes_individuais = [];
$mensagem_erro = null;

try {
    // 1. DADOS PARA OS CARDS
    // Contar cursos distintos em que o professor tem disciplinas
    $stmt_cursos = $pdo->prepare("
        SELECT COUNT(DISTINCT d.curso_id) 
        FROM minhas_disciplinas md
        JOIN disciplina d ON md.disciplina_id = d.id
        WHERE md.usuario_id = :professor_id
    ");
    $stmt_cursos->execute([':professor_id' => $professor_id]);
    $stats['cursos_distintos'] = $stmt_cursos->fetchColumn();

    // Contar total de avaliações individuais recebidas
    $stmt_avaliacoes = $pdo->prepare("
        SELECT COUNT(ra.id) 
        FROM respostas_avaliacao ra
        JOIN avaliacao a ON ra.avaliacao_id = a.id
        WHERE ra.professor_id = :professor_id
    ");
    $stmt_avaliacoes->execute([':professor_id' => $professor_id]);
    $stats['avaliacoes_recebidas'] = $stmt_avaliacoes->fetchColumn();

    // 2. DADOS PARA O GRÁFICO (Média de notas por curso)
    $stmt_grafico = $pdo->prepare("
        SELECT c.nome AS curso_nome, AVG(ra.resposta) AS media_nota
        FROM respostas_avaliacao ra
        JOIN avaliacao a ON ra.avaliacao_id = a.id
        JOIN cursos c ON a.curso_id = c.id
        JOIN questionario q ON ra.pergunta_id = q.id
        WHERE ra.professor_id = :professor_id AND q.categoria = 'Professor'
        GROUP BY c.nome
        ORDER BY c.nome
    ");
    $stmt_grafico->execute([':professor_id' => $professor_id]);
    $notas_por_curso = $stmt_grafico->fetchAll(PDO::FETCH_ASSOC);

    // 3. DADOS PARA A TABELA (Relatório de avaliações individuais)
    $stmt_relatorio = $pdo->prepare("
        SELECT a.nome AS avaliacao_nome, u.nome AS aluno_nome, q.pergunta, ra.resposta
        FROM respostas_avaliacao ra
        JOIN avaliacao a ON ra.avaliacao_id = a.id
        JOIN usuario u ON ra.aluno_id = u.id
        JOIN questionario q ON ra.pergunta_id = q.id
        WHERE ra.professor_id = :professor_id AND q.categoria = 'Professor'
        ORDER BY a.nome, q.pergunta
    ");
    $stmt_relatorio->execute([':professor_id' => $professor_id]);
    $avaliacoes_individuais = $stmt_relatorio->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $mensagem_erro = "Erro de Banco de Dados: " . $e->getMessage();
    error_log("Erro no Dashboard do Professor: " . $e->getMessage());
}

// Prepara dados para o Chart.js
$chart_labels = [];
$chart_data = [];
foreach ($notas_por_curso as $nota) {
    $chart_labels[] = $nota['curso_nome'];
    $chart_data[] = round($nota['media_nota'], 2); // Arredonda para 2 casas decimais
}
$chart_labels_json = json_encode($chart_labels);
$chart_data_json = json_encode($chart_data);

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
            <div class="card"><div class="card-icon"><i class="fas fa-graduation-cap"></i></div><div class="card-info"><div class="card-title">Turmas/Cursos</div><div class="card-value"><?php echo htmlspecialchars($stats['cursos_distintos']); ?></div></div></div>
            <div class="card"><div class="card-icon"><i class="fas fa-poll"></i></div><div class="card-info"><div class="card-title">Avaliações Recebidas</div><div class="card-value"><?php echo htmlspecialchars($stats['avaliacoes_recebidas']); ?></div></div></div>
        </section>

        <hr class="dashboard-divider">

        <!-- Gráfico e Tabela -->
        <div class="dashboard-main-content">
            <div class="chart-container">
                <h3>Média de Notas por Turma</h3>
                <?php if (!empty($chart_data)): ?>
                    <canvas id="gradesByCourseChart"></canvas>
                <?php else: ?>
                    <div class="alert alert-info">Ainda não há dados de notas para exibir no gráfico.</div>
                <?php endif; ?>
            </div>
            
            <div class="dashboard-section" style="grid-column: 1 / -1;">
                <div class="section-header"><h2>Relatório de Avaliações Individuais</h2></div>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="thead-dark"><tr><th>Avaliação</th><th>Aluno</th><th>Pergunta</th><th>Nota</th></tr></thead>
                        <tbody>
                            <?php if (empty($avaliacoes_individuais)): ?>
                                <tr><td colspan="4" class="text-center">Nenhum feedback recebido até o momento.</td></tr>
                            <?php else: ?>
                                <?php foreach ($avaliacoes_individuais as $aval): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($aval['avaliacao_nome']); ?></td>
                                        <td><?php echo htmlspecialchars($aval['aluno_nome']); ?></td>
                                        <td><?php echo htmlspecialchars($aval['pergunta']); ?></td>
                                        <td><span class="badge badge-primary"><?php echo htmlspecialchars($aval['resposta']); ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('gradesByCourseChart')) {
        const ctx = document.getElementById('gradesByCourseChart').getContext('2d');
        const gradient = ctx.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, 'rgba(54, 162, 235, 0.8)');
        gradient.addColorStop(1, 'rgba(54, 162, 235, 0.2)');

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo $chart_labels_json; ?>,
                datasets: [{
                    label: 'Média de Nota',
                    data: <?php echo $chart_data_json; ?>,
                    backgroundColor: gradient,
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1,
                    borderRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { y: { beginAtZero: true, max: 5 } }, // Define a escala máxima da nota (ajuste se necessário)
                plugins: { legend: { display: false } }
            }
        });
    }
});
</script>
<style>
/* Estilos reutilizados e novos */
.dashboard-divider { margin: 2rem 0; border: 0; border-top: 1px solid #e9ecef; }
.badge-primary { color: #fff; background-color: #007bff; }
</style>

<?php require_once __DIR__ . '/includes/templates/footer_dashboard.php'; ?>

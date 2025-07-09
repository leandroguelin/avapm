<?php
// relatorios.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/includes/conexao.php';

// --- Verificação de Permissão ---
require_once __DIR__ . '/includes/seguranca.php';
verificar_permissao(basename(__FILE__), $pdo);

// --- Definições da Página ---
$page_title = "Relatórios";
$current_page = "Relatórios";
$user_level = $_SESSION['nivel_acesso'];

// --- Lógica para Administradores e Gerentes ---
if (in_array($user_level, ['ADMINISTRADOR', 'GERENTE'])) {
    $avaliacoes = [];
    try {
        $stmt = $pdo->query("
            SELECT a.id, a.nome, c.nome as curso_nome, a.data_inicio, a.data_final, a.situacao 
            FROM avaliacao a 
            JOIN cursos c ON a.curso_id = c.id 
            ORDER BY a.data_final DESC
        ");
        $avaliacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $mensagem_erro_admin = "Erro ao carregar lista de avaliações: " . $e->getMessage();
        error_log($mensagem_erro_admin);
    }
}

// --- Lógica para Professores ---
if ($user_level === 'PROFESSOR') {
    $resultados_por_curso = [];
    $id_professor_logado = $_SESSION['usuario_id'];
    try {
        $stmt = $pdo->prepare("
            SELECT c.id AS curso_id, c.nome AS curso_nome, c.sigla AS curso_sigla, r.pergunta, AVG(r.resposta) AS media_pergunta
            FROM respostas r
            JOIN cursos c ON r.curso_sigla = c.sigla
            WHERE r.categoria = 'Professor' AND r.avaliado = :id_professor_logado
            GROUP BY c.id, c.nome, c.sigla, r.pergunta
            ORDER BY c.nome ASC, r.pergunta ASC
        ");
        $stmt->execute([':id_professor_logado' => $id_professor_logado]);
        $resultados_query = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($resultados_query as $row) {
            $curso_id = $row['curso_id'];
            if (!isset($resultados_por_curso[$curso_id])) {
                $resultados_por_curso[$curso_id] = [
                    'curso_nome' => $row['curso_nome'],
                    'curso_sigla' => $row['curso_sigla'],
                    'perguntas' => []
                ];
            }
            $resultados_por_curso[$curso_id]['perguntas'][] = [
                'texto' => $row['pergunta'],
                'media' => $row['media_pergunta']
            ];
        }
    } catch (PDOException $e) {
        $erro_db_professor = "Erro ao carregar os dados de avaliação: " . $e->getMessage();
        error_log($erro_db_professor);
    }
}

require_once __DIR__ . '/includes/templates/header_dashboard.php';
require_once __DIR__ . '/includes/templates/sidebar_dashboard.php';

function getProgressBarColor($nota) {
    if ($nota >= 8) return 'bg-success';
    if ($nota >= 5) return 'bg-warning';
    return 'bg-danger';
}
?>

<div class="main-content-dashboard">
    <header class="dashboard-header">
        <h1><?php echo htmlspecialchars($page_title); ?></h1>
    </header>

    <?php // --- Exibição para Administradores e Gerentes --- ?>
    <?php if (in_array($user_level, ['ADMINISTRADOR', 'GERENTE'])): ?>
        <section class="dashboard-section">
            <div class="section-header">
                <h2>Relatórios de Avaliações</h2>
            </div>
            <?php if (isset($mensagem_erro_admin)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($mensagem_erro_admin); ?></div>
            <?php elseif (empty($avaliacoes)): ?>
                <div class="alert alert-info">Nenhuma avaliação encontrada.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="thead-dark">
                            <tr>
                                <th>Nome da Avaliação</th>
                                <th>Curso</th>
                                <th>Data Início</th>
                                <th>Data Final</th>
                                <th>Situação</th>
                                <th class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($avaliacoes as $avaliacao): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($avaliacao['nome']); ?></td>
                                    <td><?php echo htmlspecialchars($avaliacao['curso_nome']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($avaliacao['data_inicio'])); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($avaliacao['data_final'])); ?></td>
                                    <td><span class="badge badge-<?php echo $avaliacao['situacao'] === 'Ativa' ? 'success' : 'secondary'; ?>"><?php echo htmlspecialchars($avaliacao['situacao']); ?></span></td>
                                    <td class="text-center">
                                        <a href="ver_relatorio.php?id=<?php echo $avaliacao['id']; ?>" class="btn btn-sm btn-info" title="Ver Relatório HTML"><i class="fas fa-eye"></i></a>
                                        <a href="exportar_relatorio_excel.php?id=<?php echo $avaliacao['id']; ?>" class="btn btn-sm btn-success" title="Exportar para Excel"><i class="fas fa-file-excel"></i></a>
                                        <a href="exportar_relatorio_pdf.php?id=<?php echo $avaliacao['id']; ?>" class="btn btn-sm btn-danger" title="Exportar para PDF"><i class="fas fa-file-pdf"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <?php // --- Exibição para Professores --- ?>
    <?php if ($user_level === 'PROFESSOR'): ?>
        <section class="dashboard-section">
            <div class="section-header">
                <h2>Meu Desempenho nas Avaliações</h2>
            </div>
            <?php if (isset($erro_db_professor)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($erro_db_professor); ?></div>
            <?php elseif (empty($resultados_por_curso)): ?>
                <div class="alert alert-info">Ainda não há avaliações sobre seu desempenho registradas.</div>
            <?php else: ?>
                <?php foreach ($resultados_por_curso as $curso): ?>
                    <div class="dashboard-section mb-4">
                        <h3><i class="fas fa-graduation-cap text-primary"></i> Curso: <?php echo htmlspecialchars($curso['curso_nome']); ?></h3>
                        <div class="list-group list-group-flush">
                            <?php foreach ($curso['perguntas'] as $pergunta): ?>
                                <div class="list-group-item">
                                    <strong><?php echo htmlspecialchars($pergunta['texto']); ?></strong>
                                    <div class="d-flex align-items-center">
                                        <div class="progress" style="height: 20px; flex-grow: 1;">
                                            <div class="progress-bar <?php echo getProgressBarColor($pergunta['media']); ?>" role="progressbar" style="width: <?php echo ($pergunta['media'] * 10); ?>%;" aria-valuenow="<?php echo $pergunta['media']; ?>" aria-valuemin="0" aria-valuemax="10"></div>
                                        </div>
                                        <strong class="ml-3"><?php echo number_format($pergunta['media'], 2); ?></strong>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</div>

<?php
require_once __DIR__ . '/includes/templates/footer_dashboard.php';
?>

<?php
// avapm/ver_relatorio.php - VERSÃO CORRIGIDA

// --- Inicialização e Segurança ---
if (session_status() == PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/includes/conexao.php';
require_once __DIR__ . '/includes/seguranca.php';

// Ajuste os níveis de acesso conforme necessário para esta página
$allowed_access_levels = ['administrador', 'gerente'];
verificar_permissao(basename(__FILE__), $pdo, $allowed_access_levels);

$page_title = "Relatório da Avaliação";
$avaliacao_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$detalhes_avaliacao = null;
$respostas_agrupadas = [];
$mensagem_erro = '';

if ($avaliacao_id) {
    try {
        // CORREÇÃO: A consulta foi ajustada para converter r.avaliado para INTEGER
        // e para usar a tabela correta 'respostas_avaliacao'.
        $stmt = $pdo->prepare("
            SELECT
                a.nome AS avaliacao_nome,
                c.nome AS curso_nome,
                d.nome AS disciplina_nome,
                q.pergunta,
                u.nome AS professor_nome,
                ra.resposta,
                ra.observacao
            FROM
                respostas_avaliacao ra
            JOIN
                avaliacao a ON ra.avaliacao_id = a.id
            JOIN
                cursos c ON a.curso_id = c.id
            JOIN
                disciplina d ON ra.disciplina_id = d.id
            JOIN
                questionario q ON ra.pergunta_id = q.id
            LEFT JOIN
                usuario u ON ra.professor_id = u.id -- A junção correta é pelo professor_id
            WHERE
                ra.avaliacao_id = :avaliacao_id
            ORDER BY
                d.nome, q.pergunta, u.nome
        ");
        
        $stmt->execute([':avaliacao_id' => $avaliacao_id]);
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($resultados) {
            $detalhes_avaliacao = [
                'avaliacao_nome' => $resultados[0]['avaliacao_nome'],
                'curso_nome' => $resultados[0]['curso_nome'],
            ];

            // Agrupa as respostas por disciplina para melhor visualização
            foreach ($resultados as $row) {
                $respostas_agrupadas[$row['disciplina_nome']][] = $row;
            }
        } else {
            $mensagem_erro = "Nenhum resultado encontrado para esta avaliação.";
        }
    } catch (PDOException $e) {
        $mensagem_erro = "Erro ao carregar detalhes da avaliação: " . $e->getMessage();
        error_log($mensagem_erro);
    }
} else {
    $mensagem_erro = "ID da avaliação não fornecido ou inválido.";
}

require_once __DIR__ . '/includes/templates/header_dashboard.php';
require_once __DIR__ . '/includes/templates/sidebar_dashboard.php';
?>

<div class="main-content-dashboard">
    <header class="dashboard-header">
        <h1><?php echo htmlspecialchars($page_title); ?></h1>
    </header>

    <div class="dashboard-section">
        <div class="section-header">
            <?php if ($detalhes_avaliacao): ?>
                <h2><?php echo htmlspecialchars($detalhes_avaliacao['avaliacao_nome']); ?></h2>
                <p class="lead">Curso: <?php echo htmlspecialchars($detalhes_avaliacao['curso_nome']); ?></p>
            <?php endif; ?>
            <a href="gerenciar_avaliacoes.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Voltar</a>
        </div>

        <?php if ($mensagem_erro): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($mensagem_erro); ?></div>
        <?php else: ?>
            <?php foreach ($respostas_agrupadas as $disciplina_nome => $respostas): ?>
                <div class="card my-4">
                    <div class="card-header">
                        <h3>Disciplina: <?php echo htmlspecialchars($disciplina_nome); ?></h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Professor</th>
                                        <th>Pergunta do Questionário</th>
                                        <th>Resposta (Nota)</th>
                                        <th>Observação</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($respostas as $resposta): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($resposta['professor_nome'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($resposta['pergunta']); ?></td>
                                            <td><span class="badge badge-info"><?php echo htmlspecialchars($resposta['resposta']); ?></span></td>
                                            <td><?php echo nl2br(htmlspecialchars($resposta['observacao'] ?? 'Nenhuma')); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<style>
.badge-info { background-color: #17a2b8; color: white; }
</style>

<?php require_once __DIR__ . '/includes/templates/footer_dashboard.php'; ?>

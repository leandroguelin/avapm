php
<?php
// ver_relatorio.php - Página para visualizar detalhes de uma avaliação específica

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/includes/conexao.php';

// --- Verificação de Login e Nível de Acesso ---
$allowed_access_levels = ['ADMINISTRADOR', 'GERENTE'];
$user_level = $_SESSION['nivel_acesso'] ?? '';

if (!isset($_SESSION['usuario_id']) || !in_array($user_level, $allowed_access_levels)) {
    header('Location: redireciona_usuario.php');
    exit();
}

// --- Obter ID da Avaliação da URL ---
$avaliacao_id = $_GET['id'] ?? null;
$avaliacao_detalhes = null;
$erro_bd = null;

if ($avaliacao_id) {
    try {
        // Consulta para obter os detalhes da avaliação e as respostas associadas
        // Esta consulta é um exemplo e pode precisar ser ajustada com base na sua estrutura exata de tabelas (avaliacoes, respostas, usuarios, etc.)
        $stmt = $pdo->prepare("
            SELECT
                a.nome AS avaliacao_nome,
                c.nome AS curso_nome,
                r.pergunta,
                u.nome AS professor_nome,
                r.resposta,
                r.observacao
            FROM
                respostas r
            JOIN
                avaliacao a ON r.avaliacao_id = a.id
            JOIN
                cursos c ON a.curso_id = c.id
            LEFT JOIN
                usuario u ON r.avaliado = u.id AND r.categoria = 'Professor' -- Assumindo que 'avaliado' se refere ao professor para essa categoria
            WHERE
                r.avaliacao_id = :avaliacao_id
            ORDER BY
                r.pergunta ASC, u.nome ASC
        ");
        $stmt->execute([':avaliacao_id' => $avaliacao_id]);
        $avaliacao_detalhes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // O nome da avaliação pode ser obtido da primeira linha se houver resultados
        $avaliacao_nome = $avaliacao_detalhes[0]['avaliacao_nome'] ?? 'Avaliação Não Encontrada';
        $curso_nome = $avaliacao_detalhes[0]['curso_nome'] ?? '';

    } catch (PDOException $e) {
        $erro_bd = "Erro ao carregar detalhes da avaliação: " . $e->getMessage();
        error_log($erro_bd);
    }
}

// --- Definição de Variáveis para o Layout ---
$page_title = "Detalhes da Avaliação";
$nome_usuario_logado = $_SESSION['nome_usuario'] ?? 'Usuário';

require_once __DIR__ . '/includes/templates/header_dashboard.php';
require_once __DIR__ . '/includes/templates/sidebar_dashboard.php';
?>

<div class="main-content-dashboard">
    <div class="dashboard-header">
        <h1><?php echo htmlspecialchars($page_title); ?></h1>
        <?php if (isset($avaliacao_nome)): ?>
            <p class="lead" style="color: #6c757d;">
                Detalhes da avaliação: <strong><?php echo htmlspecialchars($avaliacao_nome); ?></strong>
                <?php if (!empty($curso_nome)): ?>
                    (Curso: <?php echo htmlspecialchars($curso_nome); ?>)
                <?php endif; ?>
            </p>
        <?php endif; ?>
    </div>

    <?php if (!$avaliacao_id): ?>
        <div class="alert alert-warning">Nenhuma avaliação especificada para visualização.</div>
    <?php elseif ($erro_bd): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($erro_bd); ?></div>
    <?php elseif (empty($avaliacao_detalhes)): ?>
        <div class="alert alert-info">Nenhum detalhe encontrado para esta avaliação.</div>
    <?php else: ?>
        <div class="dashboard-section">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Pergunta</th>
                        <th>Professor</th>
                        <th>Resposta</th>
                        <th>Observação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($avaliacao_detalhes as $detalhe): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($detalhe['pergunta']); ?></td>
                            <td><?php echo htmlspecialchars($detalhe['professor_nome'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($detalhe['resposta']); ?></td>
                            <td><?php echo htmlspecialchars($detalhe['observacao'] ?? ''); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <div class="mt-4">
        <a href="relatorios.php" class="btn btn-secondary">Voltar para Relatórios</a>
    </div>

</div>

<?php
require_once __DIR__ . '/includes/templates/footer_dashboard.php';
?>
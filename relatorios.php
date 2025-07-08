php
<?php
// relatorios.php - Página de Gerenciamento de Relatórios

if (session_status() == PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/includes/conexao.php';

// --- Verificação de Login e Nível de Acesso ---
$allowed_access_levels = ['ADMINISTRADOR', 'GERENTE', 'PROFESSOR'];
$user_level = $_SESSION['nivel_acesso'] ?? '';

if (!isset($_SESSION['usuario_id']) || !in_array($user_level, $allowed_access_levels)) {
    // Se o usuário não estiver logado ou não tiver o nível correto, redireciona.
    header('Location: redireciona_usuario.php');
    exit();
}

// --- Definição de Variáveis para o Layout ---
$page_title = ($user_level === 'PROFESSOR') ? "Meu Desempenho nas Avaliações" : "Gerenciar Relatórios";
$nome_usuario_logado = $_SESSION['nome_usuario']; // Já definido na sessão
$id_usuario_logado = $_SESSION['usuario_id'];

// Lógica para buscar dados dos relatórios será adicionada aqui depois,
// baseada no $user_level.

require_once __DIR__ . '/includes/templates/header_dashboard.php';
require_once __DIR__ . '/includes/templates/sidebar_dashboard.php';
?>

<div class="main-content-dashboard">
    <div class="dashboard-header">
        <h1><?php echo $page_title; ?></h1>
        <p class="lead" style="color: #6c757d;">Visualize e exporte os relatórios das avaliações.</p>
    </div>

    <?php if ($user_level === 'PROFESSOR'): ?>
        <?php
        // =============================================================
        // BUSCA E PROCESSAMENTO DE DADOS PARA PROFESSOR
        // =============================================================
        $resultados_por_curso = [];
        $erro_db = null;
        try {
            // Esta consulta SQL busca todas as respostas dadas ao professor logado,
            // calcula a média para cada pergunta e agrupa os resultados por curso.
            $stmt = $pdo->prepare("
                SELECT
                    c.id AS curso_id,
                    c.nome AS curso_nome,
                    c.sigla AS curso_sigla,
                    r.pergunta,
                    AVG(r.resposta) AS media_pergunta
                FROM
                    respostas r
                JOIN
                    cursos c ON r.curso_sigla = c.sigla
                WHERE
                    r.categoria = 'Professor'
                    AND r.avaliado = :id_professor_logado
                GROUP BY
                    c.id, c.nome, c.sigla, r.pergunta
                ORDER BY
                    c.nome ASC, r.pergunta ASC
            ");
            $stmt->execute([':id_professor_logado' => $id_usuario_logado]); // Usar $id_usuario_logado
            $resultados_query = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Organiza os resultados em um array aninhado para facilitar a exibição
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
            $erro_db = "Erro ao carregar os dados de avaliação: " . $e->getMessage();
            error_log($erro_db);
        }

        // Função auxiliar para definir a cor da barra de progresso com base na nota
        function getProgressBarColor($nota) {
            if ($nota >= 8) return 'bg-success'; // Verde para notas altas
            if ($nota >= 5) return 'bg-warning'; // Amarelo para notas médias
            return 'bg-danger'; // Vermelho para notas baixas
        }
        ?>

        <?php if (isset($erro_db)): ?>
            <div class="alert alert-danger">Não foi possível carregar os resultados. Tente novamente mais tarde.</div>
        <?php elseif (empty($resultados_por_curso)): ?>
            <div class="alert alert-info">Ainda não há avaliações sobre seu desempenho registradas no sistema.</div>
        <?php else: ?>
            <?php foreach ($resultados_por_curso as $curso): ?>
                <div class="dashboard-section">
                    <h2>
                        <i class="fas fa-graduation-cap text-primary"></i>
                        Curso: <?php echo htmlspecialchars($curso['curso_nome']); ?>
                        (<?php echo htmlspecialchars($curso['curso_sigla']); ?>)
                    </h2>

                    <div class="list-group list-group-flush">
    <?php
 foreach ($curso['perguntas'] as $pergunta): ?>
                            <div class="list-group-item">
                                <div class="mb-2"><strong><?php echo htmlspecialchars($pergunta['texto']); ?></strong></div>
                                <div class="d-flex align-items-center">
                                    <div class="progress" style="height: 25px; flex-grow: 1;">
                                        <div class="progress-bar <?php echo getProgressBarColor($pergunta['media']); ?>"
                                             role="progressbar"
                                             style="width: <?php echo ($pergunta['media'] * 10); ?>%;"
                                             aria-valuenow="<?php echo $pergunta['media']; ?>"
                                             aria-valuemin="0"
                                             aria-valuemax="10">
                                        </div>
                                    </div>
                                    <strong class="ml-3" style="min-width: 50px; text-align: right; font-size: 1.1rem;"><?php echo number_format($pergunta['media'], 2); ?></strong>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

    <?php else: // ADMINISTRADOR ou GERENTE - LISTAGEM GERAL DE AVALIAÇÕES ?>
        <?php
        // =============================================================
        // BUSCA E PROCESSAMENTO DE DADOS PARA ADMINISTRADOR/GERENTE
        // =============================================================
        $avaliacoes = [];
        try {
            $stmt = $pdo->query("SELECT a.id, a.nome, c.nome as curso_nome, a.data_inicio, a.data_final, a.situacao FROM avaliacao a JOIN cursos c ON a.curso_id = c.id ORDER BY a.data_final DESC");
            $avaliacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $erro_db = "Erro ao carregar a lista de avaliações: " . $e->getMessage();
            error_log($erro_db);
        }
        ?>

        <div class="dashboard-section">
            <h2>Lista de Avaliações</h2>

            <?php if (isset($erro_db)): ?>
                <div class="alert alert-danger">Não foi possível carregar a lista de avaliações. Tente novamente mais tarde.</div>
            <?php elseif (empty($avaliacoes)): ?>
                <div class="alert alert-info">Nenhuma avaliação encontrada no sistema.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Avaliação</th>
                                <th>Curso</th>
                                <th>Início</th>
                                <th>Fim</th>
                                <th>Situação</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($avaliacoes as $avaliacao): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($avaliacao['nome']); ?></td>
                                    <td><?php echo htmlspecialchars($avaliacao['curso_nome']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($avaliacao['data_inicio'])); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($avaliacao['data_final'])); ?></td>
                                    <td><?php echo htmlspecialchars($avaliacao['situacao']); ?></td>
                                    <td>
                                        <a href="relatorio_avaliacao.php?id=<?php echo $avaliacao['id']; ?>&format=html" class="btn btn-sm btn-info" title="Ver HTML"><i class="fas fa-file-alt"></i> HTML</a>
                                        <a href="exportar_relatorio_pdf.php?id=<?php echo $avaliacao['id']; ?>" class="btn btn-sm btn-danger" title="Exportar PDF"><i class="fas fa-file-pdf"></i> PDF</a>
                                        <a href="exportar_relatorio_excel.php?id=<?php echo $avaliacao['id']; ?>" class="btn btn-sm btn-success" title="Exportar Excel"><i class="fas fa-file-excel"></i> Excel</a>
                                   </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

</div>

<style>
    .dashboard-section {
        background-color: #fff;
        padding: 30px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        margin-bottom: 30px;
    }
    .dashboard-section h2 {
        color: #343a40;
        margin-top: 0;
        margin-bottom: 20px;
        border-bottom: 1px solid #eee;
        padding-bottom: 15px;
        font-size: 1.5rem;
        display: flex;
        align-items: center;
    }
    .dashboard-section h2 i {
        margin-right: 12px;
    }
    .progress {
        background-color: #e9ecef;
        border-radius: .375rem;
    }
    .progress-bar {
        font-weight: bold;
        color: white;
        text-shadow: 1px 1px 2px rgba(0,0,0,0.2);
        display: flex;
        justify-content: center;
        align-items: center;
    }
    .list-group-item {
        border-color: #f0f0f0;
    }
</style>

<?php
require_once __DIR__ . '/includes/templates/footer_dashboard.php';
?>
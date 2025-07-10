php
<?php
// Define o título da página
$page_title = "Dashboard Professor";

// Inclui o cabeçalho do dashboard (que já faz a verificação de login e inicia a sessão)
require_once __DIR__ . '/includes/templates/header_dashboard.php';

// Inclui a barra lateral (sidebar) do dashboard
require_once __DIR__ . '/includes/templates/sidebar_dashboard.php';

// Define a página atual para a sidebar destacar o link ativo
$current_page = "Dashboard"; // Para manter o "Dashboard" ativo na sidebar

// Inclui o arquivo de configuração do banco de dados
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/models/Usuario.php';
require_once __DIR__ . '/../src/models/MinhaDisciplina.php';
require_once __DIR__ . '/../src/models/Nota.php'; // Inclui o modelo Nota
require_once __DIR__ . '/../src/models/QuestionarioRespondido.php'; // Inclui o modelo QuestionarioRespondido
require_once __DIR__ . '/../src/models/Resposta.php'; // Inclui o modelo Resposta
require_once __DIR__ . '/../src/models/Disciplina.php'; // Inclui o modelo Disciplina

// Verifica se o usuário está logado e se é um professor
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'professor') {
    header('Location: ../login.php');
    exit();
}

$usuario_id = $_SESSION['usuario_id'];

// Inicializa o objeto de conexão com o banco de dados
$pdo = getDatabaseConnection();

// Instancia os modelos
$minhaDisciplinaModel = new MinhaDisciplina($pdo);
$notaModel = new Nota($pdo);
$questionarioRespondidoModel = new QuestionarioRespondido($pdo);
$respostaModel = new Resposta($pdo);
$disciplinaModel = new Disciplina($pdo);


// --- KPIs para o Professor ---

// 1. Total de Disciplinas Ministradas
$totalDisciplinas = $minhaDisciplinaModel->countDisciplinasByProfessor($usuario_id);

// 2. Total de Cursos Diferentes Associados às Suas Disciplinas
try {
    $stmt_cursos = $pdo->prepare("
        SELECT COUNT(DISTINCT gc.curso_id)
        FROM minhas_disciplinas md
        JOIN disciplina d ON md.disciplina_id = d.id
        JOIN grade_curso gc ON d.id = gc.disciplina_id -- Adiciona o JOIN com grade_curso para obter curso_id
        WHERE md.usuario_id = :professor_id
    ");
    $stmt_cursos->execute([':professor_id' => $usuario_id]);
    $totalCursos = $stmt_cursos->fetchColumn();
} catch (PDOException $e) {
    error_log("Erro ao contar cursos por professor: " . $e->getMessage());
    $totalCursos = 0; // Define 0 em caso de erro
    $mensagem_erro = "Ocorreu um erro ao carregar o total de cursos.";
}


// 3. Total de Alunos em Suas Disciplinas (contagem única)
try {
    $stmt_alunos = $pdo->prepare("
        SELECT COUNT(DISTINCT ma.usuario_id)
        FROM minhas_disciplinas md
        JOIN disciplina d ON md.disciplina_id = d.id
        JOIN matricula ma ON d.id = ma.disciplina_id -- Junta com matricula para obter alunos
        WHERE md.usuario_id = :professor_id
    ");
    $stmt_alunos->execute([':professor_id' => $usuario_id]);
    $totalAlunosUnicos = $stmt_alunos->fetchColumn();
} catch (PDOException $e) {
    error_log("Erro ao contar alunos únicos por professor: " . $e->getMessage());
    $totalAlunosUnicos = 0; // Define 0 em caso de erro
    $mensagem_erro = "Ocorreu um erro ao carregar o total de alunos.";
}


// 4. Média Geral das Notas dos Alunos em Suas Disciplinas
try {
    $stmt_media_notas = $pdo->prepare("
        SELECT AVG(n.nota)
        FROM notas n
        JOIN matricula m ON n.matricula_id = m.id
        JOIN disciplina d ON m.disciplina_id = d.id
        JOIN minhas_disciplinas md ON d.id = md.disciplina_id
        WHERE md.usuario_id = :professor_id AND n.nota IS NOT NULL
    ");
    $stmt_media_notas->execute([':professor_id' => $usuario_id]);
    $mediaGeralNotas = $stmt_media_notas->fetchColumn();
    $mediaGeralNotas = $mediaGeralNotas !== false ? number_format($mediaGeralNotas, 2) : 'N/A'; // Formata para 2 casas decimais ou N/A
} catch (PDOException $e) {
    error_log("Erro ao calcular média geral das notas: " . $e->getMessage());
    $mediaGeralNotas = 'N/A'; // Define N/A em caso de erro
    $mensagem_erro = "Ocorreu um erro ao calcular a média geral das notas.";
}


// 5. Total de Questionários Respondidos Relacionados às Suas Disciplinas
try {
    $stmt_questionarios_respondidos = $pdo->prepare("
        SELECT COUNT(qr.id)
        FROM questionarios_respondidos qr
        JOIN matricula m ON qr.matricula_id = m.id
        JOIN disciplina d ON m.disciplina_id = d.id
        JOIN minhas_disciplinas md ON d.id = md.disciplina_id
        WHERE md.usuario_id = :professor_id
    ");
    $stmt_questionarios_respondidos->execute([':professor_id' => $usuario_id]);
    $totalQuestionariosRespondidos = $stmt_questionarios_respondidos->fetchColumn();
} catch (PDOException $e) {
    error_log("Erro ao contar questionários respondidos por professor: " . $e->getMessage());
    $totalQuestionariosRespondidos = 0; // Define 0 em caso de erro
    $mensagem_erro = "Ocorreu um erro ao carregar o total de questionários respondidos.";
}


// --- Gráfico de Médias de Notas por Disciplina ---
$chart_labels = [];
$chart_data = [];

try {
    $stmt_medias_por_disciplina = $pdo->prepare("
        SELECT d.nome AS nome_disciplina, AVG(n.nota) AS media_nota
        FROM notas n
        JOIN matricula m ON n.matricula_id = m.id
        JOIN disciplina d ON m.disciplina_id = d.id
        JOIN minhas_disciplinas md ON d.id = md.disciplina_id
        WHERE md.usuario_id = :professor_id AND n.nota IS NOT NULL
        GROUP BY d.id, d.nome
        ORDER BY d.nome;
    ");
    $stmt_medias_por_disciplina->execute([':professor_id' => $usuario_id]);
    $mediasPorDisciplina = $stmt_medias_por_disciplina->fetchAll(PDO::FETCH_ASSOC);

    foreach ($mediasPorDisciplina as $item) {
        $chart_labels[] = $item['nome_disciplina'];
        $chart_data[] = number_format($item['media_nota'], 2);
    }

    // Converte para JSON para usar no JavaScript do gráfico
    $chart_labels_json = json_encode($chart_labels);
    $chart_data_json = json_encode($chart_data);

} catch (PDOException $e) {
    error_log("Erro ao buscar médias por disciplina para o gráfico: " . $e->getMessage());
    $mensagem_erro = "Ocorreu um erro ao carregar os dados para o gráfico de notas.";
    $chart_labels_json = '[]';
    $chart_data_json = '[]';
}


// --- Tabela das Suas Disciplinas ---
$disciplinasProfessor = $minhaDisciplinaModel->getDisciplinasComProfessor($usuario_id);


?>

<div class="container-fluid mt-4">
    <h1 class="mb-4">Dashboard do Professor</h1>

    <?php if (!empty($mensagem_sucesso)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $mensagem_sucesso; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($mensagem_erro)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $mensagem_erro; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>


    <!-- Seção de KPIs -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-white bg-primary mb-3">
                <div class="card-body">
                    <h5 class="card-title">Disciplinas Ministradas</h5>
                    <p class="card-text display-4"><?php echo $totalDisciplinas; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-success mb-3">
                <div class="card-body">
                    <h5 class="card-title">Cursos Associados</h5>
                    <p class="card-text display-4"><?php echo $totalCursos; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-info mb-3">
                <div class="card-body">
                    <h5 class="card-title">Alunos Únicos</h5>
                    <p class="card-text display-4"><?php echo $totalAlunosUnicos; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-warning mb-3">
                <div class="card-body">
                    <h5 class="card-title">Média Geral Notas</h5>
                    <p class="card-text display-4"><?php echo $mediaGeralNotas; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
             <div class="card text-white bg-danger mb-3">
                 <div class="card-body">
                     <h5 class="card-title">Questionários Respondidos</h5>
                     <p class="card-text display-4"><?php echo $totalQuestionariosRespondidos; ?></p>
                 </div>
             </div>
         </div>
    </div>

    <hr class="dashboard-divider">

    <!-- Seção do Gráfico -->
    <?php if (!empty($chart_labels) && !empty($chart_data)): ?>
        <div class="card mb-4">
            <div class="card-header">
                Média de Notas por Disciplina
            </div>
            <div class="card-body">
                <div class="chart-container" style="position: relative; height:40vh; width:80vw">
                    <canvas id="gradesByCourseChart"></canvas>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <hr class="dashboard-divider">

    <!-- Seção da Tabela de Disciplinas -->
    <?php if (!empty($disciplinasProfessor)): ?>
        <div class="card mb-4">
            <div class="card-header">
                Suas Disciplinas
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Nome da Disciplina</th>
                                <th>Código</th>
                                <th>Curso</th>
                                <!-- Adicione mais colunas se necessário -->
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($disciplinasProfessor as $disciplina): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($disciplina['nome_disciplina']); ?></td>
                                    <td><?php echo htmlspecialchars($disciplina['codigo']); ?></td>
                                    <td><?php echo htmlspecialchars($disciplina['nome_curso']); ?></td>
                                    <!-- Renderize mais dados se houver -->
                                </tr>
                            <?php endforeach; ?>
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
        // Criando um gradiente dinamicamente (opcional, pode usar uma cor sólida)
        const gradient = ctx.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, 'rgba(54, 162, 235, 0.8)'); // Azul
        gradient.addColorStop(1, 'rgba(54, 162, 235, 0.2)'); // Azul mais claro

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo $chart_labels_json; ?>, // Rótulos das disciplinas (nomes)
                datasets: [{
                    label: 'Média de Nota',
                    data: <?php echo $chart_data_json; ?>, // Dados das médias de notas
                    backgroundColor: gradient, // Usa o gradiente como cor de fundo
                    borderColor: 'rgba(54, 162, 235, 1)', // Borda sólida azul
                    borderWidth: 1,
                    borderRadius: 5 // Bordas arredondadas para as barras
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false, // Permite que o chart-container controle o tamanho
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 5, // Define a escala máxima da nota (ajuste se necessário, ex: 10)
                        title: {
                            display: true,
                            text: 'Média da Nota'
                        }
                    },
                    x: {
                        title: {
                             display: true,
                             text: 'Disciplina'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false // Oculta a legenda, pois só temos um dataset
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += new Intl.NumberFormat('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(context.parsed.y);
                                }
                                return label;
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>
<style>
/* Estilos reutilizados e novos */
.dashboard-divider { margin: 2rem 0; border: 0; border-top: 1px solid #e9ecef; }
.badge-primary { color: #fff; background-color: #007bff; }

/* Adicionando cores para os outros badges de KPI se necessário */
.bg-success { background-color: #28a745 !important; }
.bg-info { background-color: #17a2b8 !important; }
.bg-warning { background-color: #ffc107 !important; color: #212529 !important; } /* Cor do texto para contraste */
.bg-danger { background-color: #dc3545 !important; }

/* Estilo opcional para o chart-container para melhor controle do layout */
.chart-container {
    position: relative;
    height: 40vh; /* Altura responsiva baseada na viewport */
    width: 100%; /* Ocupa toda a largura do contêiner pai */
    max-width: 800px; /* Limita a largura máxima do gráfico */
    margin: auto; /* Centraliza o gráfico se a largura for menor que o max-width */
}
</style>


<?php
// Inclui o rodapé do dashboard
require_once __DIR__ . '/includes/templates/footer_dashboard.php';
?>

<?php
// e:\OneDrive\03 - Estudos\02 - Desenvolvimento\06 - Projetos\avapm2\atribuir_disciplinas.php

// --- Configurações de Depuração ---
error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- Início da Sessão PHP ---
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Incluir a conexão com o banco de dados
require_once __DIR__ . '/includes/conexao.php';

// Verificar se o usuário está logado e tem permissão (admin ou gerente)
$allowed_access_levels = ['ADMINISTRADOR', 'GERENTE'];
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['nivel_acesso'], $allowed_access_levels)) {
    $_SESSION['mensagem_feedback'] = [
        'tipo' => 'danger',
        'texto' => 'Você não tem permissão para acessar esta página.'
    ];
    header('Location: index.php');
    exit();
}

// Obter o ID do curso da URL
$curso_id = isset($_GET['curso_id']) ? (int)$_GET['curso_id'] : null;

// Se o ID do curso não for válido, redirecionar
if (!$curso_id) {
    $_SESSION['mensagem_feedback'] = [
        'tipo' => 'danger',
        'texto' => 'ID do curso inválido.'
    ];
    header('Location: gerenciar_cursos.php'); // Ou outra página apropriada
    exit();
}

// --- Variáveis para Mensagens de Feedback ---
$mensagem_feedback = '';
$feedback_tipo = '';

// Verifica se há uma mensagem de feedback na sessão
if (isset($_SESSION['mensagem_feedback'])) {
    $mensagem_feedback = $_SESSION['mensagem_feedback']['texto'];
    $feedback_tipo = $_SESSION['mensagem_feedback']['tipo'];
    unset($_SESSION['mensagem_feedback']); // Limpa a mensagem após exibir
}

// --- Carregar Dados do Curso ---
try {
    $stmt_curso = $pdo->prepare("SELECT nome FROM cursos WHERE id = :id");
    $stmt_curso->execute([':id' => $curso_id]);
    $curso = $stmt_curso->fetch(PDO::FETCH_ASSOC);
    if (!$curso) {
        throw new Exception("Curso não encontrado.");
    }
    $page_title = "Atribuir Disciplinas - " . htmlspecialchars($curso['nome']);
} catch (Exception $e) {
    $_SESSION['mensagem_feedback'] = [
        'tipo' => 'danger',
        'texto' => $e->getMessage()
    ];
    header('Location: gerenciar_cursos.php');
    exit();
}

// --- Carregar Disciplinas e Professores (que ainda não estão atribuídos a este curso) ---
$disciplinas_disponiveis = [];
$professores_disponiveis = [];

try {
    // Disciplinas não atribuídas
    $sql_disciplinas = "SELECT id, nome, sigla FROM disciplinas 
                           WHERE id NOT IN (SELECT disciplina_id FROM grade_curso WHERE curso_id = :curso_id)
                           ORDER BY nome ASC";
    $stmt_disciplinas = $pdo->prepare($sql_disciplinas);
    $stmt_disciplinas->execute([':curso_id' => $curso_id]);
    $disciplinas_disponiveis = $stmt_disciplinas->fetchAll(PDO::FETCH_ASSOC);

    // Professores (usuários com nivel_acesso = 'Professor')
    $stmt_professores = $pdo->prepare("SELECT id, nome FROM usuario WHERE nivel_acesso = 'Professor' ORDER BY nome ASC");
    $stmt_professores->execute();
    $professores_disponiveis = $stmt_professores->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Erro ao carregar dados para atribuir disciplinas: " . $e->getMessage());
    $mensagem_feedback = "Erro ao carregar disciplinas ou professores. Tente novamente mais tarde.";
    $feedback_tipo = 'danger';
}


// --- Processamento da Atribuição ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obter as atribuições do formulário
    $atribuicoes = $_POST['atribuicoes'] ?? [];

    // Validar se pelo menos uma atribuição foi feita
    if (empty($atribuicoes)) {
        $mensagem_feedback = "Selecione pelo menos uma disciplina e professor para atribuir.";
        $feedback_tipo = 'warning';
    } else {
        $erros = [];
        $atribuicoes_para_inserir = [];

        // Validar os dados das atribuições
        foreach ($atribuicoes as $disciplina_id => $professor_id) {
            if (!is_numeric($disciplina_id) || !is_numeric($professor_id)) {
                $erros[] = "Dados inválidos para disciplina ou professor.";
            } else {
                $atribuicoes_para_inserir[] = [
                    'disciplina_id' => (int)$disciplina_id,
                    'professor_id' => (int)$professor_id,
                ];
            }
        }

        if (!empty($erros)) {
            $mensagem_feedback = implode("<br>", $erros);
            $feedback_tipo = 'danger';
        } else {
            $pdo->beginTransaction(); // Inicia a transação para garantir atomicidade

            try {
                // 1. Excluir as atribuições existentes para este curso
                $stmt_delete = $pdo->prepare("DELETE FROM grade_curso WHERE curso_id = :curso_id");
                $stmt_delete->execute([':curso_id' => $curso_id]);

                // 2. Inserir as novas atribuições
                $stmt_insert = $pdo->prepare("INSERT INTO grade_curso (curso_id, disciplina_id, usuario_id) VALUES (:curso_id, :disciplina_id, :usuario_id)");
                foreach ($atribuicoes_para_inserir as $atribuicao) {
                    $stmt_insert->execute([
                        ':curso_id' => $curso_id,
                        ':disciplina_id' => $atribuicao['disciplina_id'],
                        ':usuario_id' => $atribuicao['professor_id'],
                    ]);
                }

                $pdo->commit(); // Confirma a transação
                $mensagem_feedback = "Atribuições de disciplinas e professores salvas com sucesso!";
                $feedback_tipo = 'success';

                // Redirecionar para evitar reenvio do formulário
                $_SESSION['mensagem_feedback'] = [
                    'tipo' => $feedback_tipo,
                    'texto' => $mensagem_feedback
                ];
                header("Location: atribuir_disciplinas.php?curso_id=$curso_id");
                exit();

            } catch (PDOException $e) {
                $pdo->rollBack(); // Desfaz a transação em caso de erro
                error_log("Erro ao salvar atribuições: " . $e->getMessage());
                $mensagem_feedback = "Erro ao salvar atribuições. Tente novamente mais tarde.";
                $feedback_tipo = 'danger';
            }
        }
    }
}


// Incluir o cabeçalho do dashboard
require_once __DIR__ . '/includes/templates/header_dashboard.php';
// Incluir a barra lateral do dashboard
require_once __DIR__ . '/includes/templates/sidebar_dashboard.php';

?>

<div class="main-content-dashboard">
    <header class="dashboard-header">
        <h1><?php echo $page_title; ?></h1>
        <!-- ... (informações do usuário, etc.) ... -->
    </header>

    <div class="dashboard-section">
        <h2>Atribuir Disciplinas e Professores</h2>
        
        <?php if (!empty($mensagem_feedback)): ?>
            <div class="alert alert-<?php echo $feedback_tipo; ?>">
                <?php echo $mensagem_feedback; ?>
            </div>
        <?php endif; ?>

        <p>Selecione as disciplinas e os professores para atribuir a este curso:</p>

        <form action="atribuir_disciplinas.php?curso_id=<?php echo $curso_id; ?>" method="POST">
            <div class="atribuicoes-container">
                <?php if (empty($disciplinas_disponiveis)): ?>
                    <p>Não há disciplinas disponíveis para atribuir a este curso.</p>
                <?php else: ?>
                    <?php foreach ($disciplinas_disponiveis as $disciplina): ?>
                        <div class="form-group">
                            <label for="professor_<?php echo $disciplina['id']; ?>">
                                <?php echo htmlspecialchars($disciplina['nome']) . " (" . htmlspecialchars($disciplina['sigla']) . ")"; ?>:
                            </label>
                            <select class="form-control" name="atribuicoes[<?php echo $disciplina['id']; ?>]">
                                <option value="">Selecione o Professor</option>
                                <?php foreach ($professores_disponiveis as $professor): ?>
                                    <option value="<?php echo $professor['id']; ?>"><?php echo htmlspecialchars($professor['nome']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <button type="submit" class="btn-primary-dashboard">Salvar Atribuições</button>
        </form>

        <!-- Seção para exibir as atribuições existentes (caso queira) -->
        <!-- 
        <h3>Atribuições Atuais</h3>
        <ul class="atribuicoes-atuais">
            // Lógica para buscar atribuições atuais do banco (grade_curso)
            // e exibir aqui...
        </ul>
        -->

        <!-- Se você precisar listar todos os professores e disciplinas, faça assim: -->
        <!--
        <h3>Todos os Professores</h3>
        <ul> ... </ul>
        <h3>Todas as Disciplinas</h3>
        <ul> ... </ul>
        -->


    </div>
</div>

<?php
// Incluir o rodapé do dashboard
require_once __DIR__ . '/includes/templates/footer_dashboard.php';
?>
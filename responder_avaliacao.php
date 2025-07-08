<?php
// responder_avaliacao.php - VERSÃO FINAL COM LAYOUT RESPONSIVO (MOBILE-FIRST)

if (session_status() == PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/includes/conexao.php';

// --- VERIFICAÇÃO DE SEGURANÇA ---
if (!isset($_SESSION['avaliacao_id_ativa'])) {
    header('Location: inserir_codigo_avaliacao.php');
    exit();
}

$avaliacao_id = $_SESSION['avaliacao_id_ativa'];
$erro_mensagem = '';
$page_title = "Respondendo Avaliação";

// Lógica de processamento do POST (sem alterações)
$passo_atual = isset($_GET['passo']) ? (int)$_GET['passo'] : 1;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $passo_enviado = isset($_POST['passo']) ? (int)$_POST['passo'] : 0;
    $aluno = $_SESSION['aluno_identificado'] ?? ['cpf' => 'ANONIMO', 'nome' => 'Anônimo', 'contato' => 'N/A'];
    $stmt_sigla = $pdo->prepare("SELECT c.sigla FROM avaliacao a JOIN cursos c ON a.curso_id = c.id WHERE a.id = :id");
    $stmt_sigla->execute([':id' => $avaliacao_id]);
    $sigla_curso = $stmt_sigla->fetchColumn();
    $stmt = $pdo->prepare("INSERT INTO respostas (avaliacao_id, curso_sigla, pergunta, resposta, categoria, avaliado, observacoes, cpf_aluno, nome_aluno, contato) VALUES (:avaliacao_id, :curso_sigla, :pergunta, :resposta, :categoria, :avaliado, :observacoes, :cpf_aluno, :nome_aluno, :contato)");
    if ($passo_enviado === 1) {
        if (isset($_POST['anonimo'])) {
            $_SESSION['aluno_identificado'] = ['cpf' => 'ANONIMO', 'nome' => 'Anônimo', 'contato' => 'N/A'];
        } else {
            if (empty($_POST['cpf']) || empty($_POST['nome'])) {
                $_SESSION['erro_passo1'] = 'CPF e Nome são obrigatórios para se identificar.';
                header('Location: responder_avaliacao.php?passo=1');
                exit();
            }
            $_SESSION['aluno_identificado'] = ['cpf' => $_POST['cpf'], 'nome' => $_POST['nome'], 'contato' => $_POST['contato']];
        }
        header('Location: responder_avaliacao.php?passo=2');
        exit();
    }
    if ($passo_enviado === 2 || $passo_enviado === 3) {
        try {
            foreach ($_POST['respostas'] as $dados) {
                $stmt->execute([
                    ':avaliacao_id' => $avaliacao_id, ':curso_sigla' => $sigla_curso, ':pergunta' => $dados['texto_pergunta'],
                    ':resposta' => $dados['nota'], ':categoria' => $dados['categoria'], ':avaliado' => $dados['avaliado'],
                    ':observacoes' => (isset($dados['observacoes']) && $dados['nota'] < 5) ? $dados['observacoes'] : null,
                    ':cpf_aluno' => $aluno['cpf'], ':nome_aluno' => $aluno['nome'], ':contato' => $aluno['contato']
                ]);
            }
            if ($passo_enviado === 2) {
                header('Location: responder_avaliacao.php?passo=3');
                exit();
            } else { session_unset(); session_destroy(); header('Location: agradecimento.php'); exit(); }
        } catch (PDOException $e) { $erro_mensagem = "Erro ao salvar as respostas."; error_log($e->getMessage()); }
    }
}

// Renderização da Página
require_once __DIR__ . '/includes/templates/header_public.php'; 
?>

<style>
    /* Estilos gerais para a página de avaliação */
    body { font-family: 'Roboto', sans-serif; background-color: #f0f2f5; }
    .evaluation-container { max-width: 800px; margin: 40px auto; padding: 40px; background: #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.1); border-radius: 8px; }
    .evaluation-header h1 { font-size: 28px; font-weight: 700; margin-bottom: 10px; }
    .evaluation-header p { font-size: 16px; color: #666; line-height: 1.6; }
    .btn-primary { background-color: #007bff; border-color: #007bff; padding: 10px 20px; font-size: 16px; font-weight: 700; }
    hr { margin: 40px 0; }
    
    /* --- MELHORIA: ESTILOS RESPONSIVOS PARA CELULARES --- */
    /* Este bloco de código só será aplicado em telas com largura máxima de 768px */
    @media (max-width: 768px) {
        /* Reduz o espaçamento do container principal para ganhar espaço */
        .evaluation-container {
            margin: 10px;
            padding: 20px;
        }

        /* Ajusta o tamanho dos títulos */
        .evaluation-header h1 {
            font-size: 22px;
        }
        .evaluation-header p {
            font-size: 15px;
        }

        /* Ajusta a barra de notas de 0 a 10 para quebrar a linha */
        .rating-group {
            flex-wrap: wrap; /* Permite que os itens quebrem para a próxima linha */
        }
        .rating-group label {
            /* Faz com que cada número ocupe um espaço mais definido */
            flex-basis: calc(100% / 6); /* Mostra aproximadamente 6 números por linha */
            padding: 8px 5px;
            font-size: 14px;
        }

        /* Ajusta o card do professor para empilhar verticalmente */
        .professor-card {
            flex-direction: column;
            text-align: center;
        }
        .professor-avatar {
            margin-right: 0;
            margin-bottom: 15px;
        }
    }
</style>

<div class="evaluation-container">
<?php
// Carrega a view correspondente ao passo atual
if (!empty($erro_mensagem)) {
    echo '<div class="alert alert-danger">' . $erro_mensagem . '</div>';
}
switch ($passo_atual) {
    case 1:
        include __DIR__ . '/includes/templates/_view_passo1_identificacao.php';
        break;
    case 2:
        include __DIR__ . '/includes/templates/_view_passo2_academia.php';
        break;
    case 3:
        include __DIR__ . '/includes/templates/_view_passo3_disciplinas.php';
        break;
    default:
        echo "<p>Passo inválido.</p>";
        break;
}
?>
</div>

<?php
require_once __DIR__ . '/includes/templates/footer_public.php';
?>
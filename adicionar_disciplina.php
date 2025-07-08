<?php
// htdocs/avapm/adicionar_disciplina.php

// Definir o título da página
$page_title = "Adicionar Nova Disciplina";

// Iniciar a sessão para usar variáveis de sessão e verificar login
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Incluir a conexão com o banco de dados
require_once __DIR__ . '/includes/conexao.php';

// Níveis de acesso permitidos para esta página
$allowed_access_levels = ['Administrador', 'Gerente']; 

// Redirecionar se o usuário NÃO estiver logado OU NÃO tiver um dos níveis de acesso permitidos
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['nivel_acesso'], $allowed_access_levels)) {
    $_SESSION['mensagem_feedback'] = [
        'tipo' => 'danger',
        'texto' => 'Você não tem permissão para acessar esta página.'
    ];
    header('Location: index.php'); // Redirecionar para a página de login ou dashboard
    exit();
}

// Inicializa variáveis de feedback
$mensagem_feedback = '';
$feedback_tipo = '';

// Variáveis para preencher o formulário (iniciam vazias para um novo registro)
$disciplina = [
    'sigla' => '',
    'nome' => '',
    'horas' => '',
    'ementa' => ''
];

// Lógica para processar o formulário (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Captura e sanitiza os dados do formulário
    $sigla = filter_input(INPUT_POST, 'sigla', FILTER_SANITIZE_STRING);
    $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING);
    $horas = filter_input(INPUT_POST, 'horas', FILTER_SANITIZE_NUMBER_INT);
    $ementa = filter_input(INPUT_POST, 'ementa', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES); // Manter aspas

    $erros = [];

    // Validações
    if (empty($sigla)) {
        $erros[] = 'A sigla da disciplina é obrigatória.';
    } elseif (strlen($sigla) > 10) {
        $erros[] = 'A sigla não pode ter mais de 10 caracteres.';
    }
    if (empty($nome)) {
        $erros[] = 'O nome da disciplina é obrigatório.';
    } elseif (strlen($nome) > 100) {
        $erros[] = 'O nome não pode ter mais de 100 caracteres.';
    }
    if (!is_numeric($horas) || $horas <= 0) {
        $erros[] = 'As horas devem ser um número positivo.';
    }
    if (empty($ementa)) {
        $erros[] = 'A ementa da disciplina é obrigatória.';
    }

    if (empty($erros)) {
        try {
            // Verifica se a sigla ou nome já existe
            $stmt_check_duplicidade = $pdo->prepare(
                "SELECT COUNT(*) FROM disciplina WHERE sigla = :sigla OR nome = :nome"
            );
            $stmt_check_duplicidade->bindParam(':sigla', $sigla);
            $stmt_check_duplicidade->bindParam(':nome', $nome);
            $stmt_check_duplicidade->execute();

            if ($stmt_check_duplicidade->fetchColumn() > 0) {
                $erros[] = 'Já existe uma disciplina com esta sigla ou nome.';
            } else {
                // Prepara e executa a query de inserção
                $stmt_insert = $pdo->prepare(
                    "INSERT INTO disciplina (sigla, nome, horas, ementa) VALUES (:sigla, :nome, :horas, :ementa)"
                );
                $stmt_insert->bindParam(':sigla', $sigla);
                $stmt_insert->bindParam(':nome', $nome);
                $stmt_insert->bindParam(':horas', $horas, PDO::PARAM_INT);
                $stmt_insert->bindParam(':ementa', $ementa);

                if ($stmt_insert->execute()) {
                    $_SESSION['mensagem_feedback'] = [
                        'tipo' => 'success',
                        'texto' => 'Disciplina adicionada com sucesso!'
                    ];
                    // Redireciona para evitar reenvio do formulário e mostrar a lista atualizada
                    header('Location: gerenciar_disciplinas.php');
                    exit();
                } else {
                    $mensagem_feedback = 'Erro ao adicionar disciplina. Tente novamente.';
                    $feedback_tipo = 'danger';
                }
            }
        } catch (PDOException $e) {
            error_log("Erro no banco de dados ao adicionar disciplina: " . $e->getMessage());
            $mensagem_feedback = 'Erro no banco de dados ao adicionar disciplina: ' . $e->getMessage();
            $feedback_tipo = 'danger';
        }
    } else {
        // Se houver erros de validação, exibe-os
        $mensagem_feedback = implode('<br>', $erros);
        $feedback_tipo = 'danger';
        // Re-popula a variável disciplina com os dados submetidos para o formulário
        $disciplina = [
            'sigla' => $sigla,
            'nome' => $nome,
            'horas' => $horas,
            'ementa' => $ementa
        ];
    }
}

// Recupera mensagem de feedback da sessão, se houver
if (isset($_SESSION['mensagem_feedback'])) {
    $mensagem_feedback = $_SESSION['mensagem_feedback']['texto'];
    $feedback_tipo = $_SESSION['mensagem_feedback']['tipo'];
    unset($_SESSION['mensagem_feedback']); // Limpa a sessão
}

// Incluir o cabeçalho do dashboard
require_once __DIR__ . '/includes/templates/header_dashboard.php';
require_once __DIR__ . '/includes/templates/sidebar_dashboard.php';
?>

<div class="main-content-dashboard">
    <header class="dashboard-header">
        <h1><?php echo $page_title; ?></h1>
        <div class="user-info">
            <span class="user-name"><?php echo htmlspecialchars($_SESSION['nome_usuario'] ?? 'Usuário'); ?></span>
            <div class="user-avatar">
                <?php 
                $foto_src = '';
                if (!empty($_SESSION['foto_perfil']) && file_exists('imagens/profiles/' . $_SESSION['foto_perfil'])) {
                    $foto_src = 'imagens/profiles/' . htmlspecialchars($_SESSION['foto_perfil']);
                } 
                ?>
                <?php if (!empty($foto_src)) : ?>
                    <img src="<?php echo $foto_src; ?>" alt="Avatar do Usuário Logado" style="max-width: 30px; height: auto; border-radius: 50%;">
                <?php else : ?>
                    <i class="fas fa-user-circle fa-lg"></i>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <div class="dashboard-section">
        <div class="section-header">
            <h2>Detalhes da Nova Disciplina</h2>
            <a href="gerenciar_disciplinas.php" class="btn-secondary-dashboard">
                <i class="fas fa-arrow-left"></i> Voltar para Gerenciar Disciplinas
            </a>
        </div>
        
        <?php if (!empty($mensagem_feedback)): ?>
            <div class="alert alert-<?php echo $feedback_tipo; ?>">
                <?php echo $mensagem_feedback; ?>
            </div>
        <?php endif; ?>

        <form action="adicionar_disciplina.php" method="POST" class="modern-form">
            <div class="form-group">
                <label for="sigla">Sigla da Disciplina:</label>
                <input type="text" id="sigla" name="sigla" value="<?php echo htmlspecialchars($disciplina['sigla']); ?>" required maxlength="10">
            </div>

            <div class="form-group">
                <label for="nome">Nome da Disciplina:</label>
                <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($disciplina['nome']); ?>" required maxlength="100">
            </div>

            <div class="form-group">
                <label for="horas">Horas:</label>
                <input type="number" id="horas" name="horas" value="<?php echo htmlspecialchars($disciplina['horas']); ?>" required min="1">
            </div>

            <div class="form-group">
                <label for="ementa">Ementa:</label>
                <textarea id="ementa" name="ementa" rows="8" required><?php echo htmlspecialchars($disciplina['ementa']); ?></textarea>
            </div>

            <button type="submit" class="btn-primary-dashboard">
                <i class="fas fa-plus-circle"></i> Adicionar Disciplina
            </button>
        </form>
    </div>
</div>

<?php
// Incluir o rodapé do dashboard
require_once __DIR__ . '/includes/templates/footer_dashboard.php';
?>
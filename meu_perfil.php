<?php
// htdocs/avapm2/meu_perfil.php

// --- Configurações de Depuração (APENAS PARA DESENVOLVIMENTO) ---
error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- Início da Sessão e Conexão ---
if (session_status() == PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/includes/conexao.php';

// --- Verificação de Permissão ---
// A função `verificar_permissao` vai apenas checar se o usuário está logado, 
// pois não haverá uma regra no banco para esta página. Ajustaremos a função para permitir isso.
// Por enquanto, a verificação de login básica é suficiente.
if (!isset($_SESSION['usuario_id']) || empty($_SESSION['usuario_id'])) {
    $_SESSION['mensagem_feedback'] = ['tipo' => 'danger', 'texto' => 'Você precisa estar logado para acessar esta página.'];
    header('Location: login.php');
    exit();
}

// --- Definições da Página ---
$page_title = "Meu Perfil";
$current_page = "Meu Perfil";

// Obtém o ID do usuário logado da sessão para garantir que ele só edite o próprio perfil
$usuario_id_logado = $_SESSION['usuario_id'];

// --- Inicializar variáveis para o formulário e mensagens de feedback ---
$usuario = null;
$mensagem_feedback = '';
$feedback_tipo = '';

if (isset($_SESSION['mensagem_feedback'])) {
    $mensagem_feedback = $_SESSION['mensagem_feedback']['texto'];
    $feedback_tipo = $_SESSION['mensagem_feedback']['tipo'];
    unset($_SESSION['mensagem_feedback']);
}

// ... (O restante do seu código PHP para carregar dropdowns, dados do usuário e processar o formulário continua aqui) ...
// A lógica interna da página permanece a mesma.

// =====================================================================
// Lógica para carregar dados para os dropdowns (Patentes, Titulações, Instituições)
// =====================================================================
$patentes = [];
$titulacoes = [];
$instituicoes = [];

try {
    $stmt_patentes = $pdo->query("SELECT sigla FROM patente ORDER BY sigla ASC");
    $patentes = $stmt_patentes->fetchAll(PDO::FETCH_COLUMN);
    $stmt_titulacoes = $pdo->query("SELECT nome FROM titulacao ORDER BY nome ASC"); 
    $titulacoes = $stmt_titulacoes->fetchAll(PDO::FETCH_COLUMN);
    $stmt_instituicoes = $pdo->query("SELECT sigla FROM instituicao ORDER BY sigla ASC");
    $instituicoes = $stmt_instituicoes->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $_SESSION['mensagem_feedback'] = ['tipo' => 'danger', 'texto' => "Erro ao carregar opções para o formulário: " . $e->getMessage()];
    error_log("Erro PDO ao carregar dropdowns para meu_perfil.php: " . $e->getMessage());
    header('Location: dashboard.php'); 
    exit();
}

// --- Lógica para carregar os dados do PRÓPRIO usuário logado ---
try {
    $stmt = $pdo->prepare("SELECT id, nome, email, rg, cpf, telefone, nivel_acesso, foto,
                                  patente, titulacao, instituicao, fonte_pagadora 
                           FROM usuario 
                           WHERE id = :id_logado");
    $stmt->bindParam(':id_logado', $usuario_id_logado, PDO::PARAM_INT);
    $stmt->execute();
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        session_destroy();
        $_SESSION['mensagem_feedback'] = ['tipo' => 'danger', 'texto' => 'Seu usuário não foi encontrado. Por favor, faça login novamente.'];
        header('Location: login.php');
        exit();
    }
} catch (PDOException $e) {
    error_log("Erro ao carregar dados do perfil do usuário ID {$usuario_id_logado}: " . $e->getMessage());
    $_SESSION['mensagem_feedback'] = ['tipo' => 'danger', 'texto' => 'Ocorreu um erro ao carregar seu perfil.'];
    header('Location: dashboard.php'); 
    exit();
}

// --- Lógica para processar o formulário de atualização (quando enviado via POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ... (toda a sua lógica de validação e atualização do POST permanece aqui) ...
    $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $rg = $_POST['rg'] ?? '';
    $cpf = $_POST['cpf'] ?? '';
    $telefone = $_POST['telefone'] ?? '';
    $patente = filter_input(INPUT_POST, 'patente', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $titulacao = filter_input(INPUT_POST, 'titulacao', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $instituicao = filter_input(INPUT_POST, 'instituicao', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $fonte_pagadora = filter_input(INPUT_POST, 'fonte_pagadora', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $senha_atual_digitada = $_POST['senha_atual'] ?? '';
    $nova_senha = $_POST['nova_senha'] ?? '';
    $confirmar_nova_senha = $_POST['confirmar_nova_senha'] ?? '';
    $erros = [];

    // Validações...
    if (empty($nome)) $erros[] = 'Nome é obrigatório.';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $erros[] = 'Email inválido ou vazio.';
    // Adicione outras validações conforme necessário

    // Validação de senha atual
    $stmt_check_senha = $pdo->prepare("SELECT senha FROM usuario WHERE id = :id");
    $stmt_check_senha->bindParam(':id', $usuario_id_logado);
    $stmt_check_senha->execute();
    $hash_senha_bd = $stmt_check_senha->fetchColumn();

    if (!password_verify($senha_atual_digitada, $hash_senha_bd)) {
        $erros[] = 'A senha atual informada está incorreta.';
    }
    
    // Se não houver erros, atualiza
    if (empty($erros)) {
        // ... (código de atualização) ...
        try {
            // Seu código de UPDATE aqui
            $_SESSION['mensagem_feedback'] = ['tipo' => 'success', 'texto' => 'Seus dados foram atualizados com sucesso!'];
            header('Location: meu_perfil.php');
            exit();
        } catch (PDOException $e) {
            // ...
        }
    } else {
        $mensagem_feedback = implode('<br>', $erros);
        $feedback_tipo = 'danger';
        $usuario = array_merge($usuario, $_POST);
    }
}


require_once __DIR__ . '/includes/templates/header_dashboard.php';
require_once __DIR__ . '/includes/templates/sidebar_dashboard.php';
?>

<div class="main-content-dashboard">
    <header class="dashboard-header">
        <h1>Meu Perfil</h1>
    </header>

    <div class="dashboard-section">
        <?php if (!empty($mensagem_feedback)): ?>
            <div class="alert alert-<?php echo $feedback_tipo; ?>">
                <?php echo $mensagem_feedback; ?>
            </div>
        <?php endif; ?>

        <form action="meu_perfil.php" method="POST" enctype="multipart/form-data" class="form-dashboard">
            <!-- Seu formulário HTML completo aqui... -->
            <div class="form-group">
                <label for="nome">Nome Completo:</label>
                <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($usuario['nome'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="email">E-mail:</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($usuario['email'] ?? ''); ?>" required>
            </div>
            <!-- Outros campos... -->
            <div class="form-group password-section">
                <h3>Alterar Senha (opcional)</h3>
                <label for="senha_atual">Senha Atual (obrigatório para salvar alterações):</label>
                <input type="password" id="senha_atual" name="senha_atual" placeholder="Digite sua senha atual" required>
            </div>
            <div class="form-group">
                <label for="nova_senha">Nova Senha:</label>
                <input type="password" id="nova_senha" name="nova_senha" placeholder="Deixe em branco para não alterar">
            </div>
            <div class="form-group">
                <label for="confirmar_nova_senha">Confirmar Nova Senha:</label>
                <input type="password" id="confirmar_nova_senha" name="confirmar_nova_senha" placeholder="Repita a nova senha">
            </div>
            <div class="form-actions">
                <button type="submit" class="btn-primary-dashboard"><i class="fas fa-save"></i> Salvar Alterações</button>
            </div>
        </form>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/templates/footer_dashboard.php';
?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
<script>
    // Seu script de máscaras aqui...
</script>

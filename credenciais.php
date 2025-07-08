php
<?php
// credenciais.php - Página de Gerenciamento de Credenciais

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/includes/conexao.php';

// --- Verificação de Login e Nível de Acesso ---
$user_level = $_SESSION['nivel_acesso'] ?? '';
$allowed_access_levels = ['ADMINISTRADOR'];

if (!isset($_SESSION['usuario_id']) || !in_array($user_level, $allowed_access_levels)) {
    header('Location: redireciona_usuario.php');
    exit();
}

// --- Definição de Variáveis para o Layout ---
$page_title = "Gerenciar Credenciais";
$nome_usuario_logado = $_SESSION['nome_usuario'];

// --- Conteúdo da página (provisório) ---


// Inclusão dos Templates do Dashboard
require_once __DIR__ . '/includes/templates/header_dashboard.php';
require_once __DIR__ . '/includes/templates/sidebar_dashboard.php';
?>

<div class="main-content-dashboard">
    <div class="dashboard-header">
        <h1><?php echo htmlspecialchars($page_title); ?></h1>
    </div>

    <div class="dashboard-section">
        <p>Conteúdo de gerenciamento de credenciais virá aqui.</p>
        <!-- Aqui você pode adicionar a lógica para listar usuários e editar níveis de acesso -->
    </div>
</div>

<?php
require_once __DIR__ . '/includes/templates/footer_dashboard.php';
?>
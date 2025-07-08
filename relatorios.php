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
$page_title = "Gerenciar Relatórios";
$nome_usuario_logado = $_SESSION['nome_usuario'];
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

    <?php
    // Conteúdo dinâmico (tabela de relatórios) virá aqui,
    // dependendo do nível de acesso do usuário logado.
    ?>

    <div class="dashboard-section">
        <h2>Lista de Relatórios</h2>
        <p>A tabela com a lista de avaliações e as opções de exportação virá aqui.</p>
        <?php if ($user_level === 'PROFESSOR'): ?>
            <p>Como professor, você verá relatórios específicos relacionados ao seu desempenho.</p>
        <?php else: // ADMINISTRADOR ou GERENTE ?>
            <p>Como administrador/gerente, você verá a lista completa de avaliações.</p>
        <?php endif; ?>
    </div>

</div>

<?php
require_once __DIR__ . '/includes/templates/footer_dashboard.php';
?>
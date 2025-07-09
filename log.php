<?php
// log.php - Página para visualizar e pesquisar logs de login

// Inicia a sessão e inclui a conexão
if (session_status() == PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/includes/conexao.php';

// --- Verificação de Login e Nível de Acesso ---
$allowed_access_levels = ['ADMINISTRADOR', 'GERENTE']; 
$user_level = $_SESSION['nivel_acesso'] ?? '';

if (!isset($_SESSION['usuario_id']) || !in_array($user_level, $allowed_access_levels)) {
    $_SESSION['mensagem_feedback'] = ['tipo' => 'danger', 'texto' => 'Você não tem permissão para acessar esta página.'];
    header('Location: redireciona_usuario.php');
    exit();
}

// --- Definições da Página ---
$page_title = "Log de Logins";
$current_page = "Log de Logins";

// --- Configurações de Paginação e Pesquisa ---
$limite_por_pagina = 15;
$pagina_atual = isset($_GET['pagina']) && is_numeric($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_atual - 1) * $limite_por_pagina;

$termo_pesquisa = isset($_GET['q']) ? filter_input(INPUT_GET, 'q', FILTER_SANITIZE_STRING) : '';
$user_id_filter = isset($_GET['user_id']) && is_numeric($_GET['user_id']) ? (int)$_GET['user_id'] : null;

// --- Montagem da Query SQL ---
$where_clauses = [];
$params = [];

if ($user_id_filter) {
    $where_clauses[] = "l.usuario_id = :user_id";
    $params[':user_id'] = $user_id_filter;
}

if (!empty($termo_pesquisa)) {
    $where_clauses[] = "(u.nome LIKE :termo OR l.ip LIKE :termo)";
    $params[':termo'] = '%' . $termo_pesquisa . '%';
}

$where_sql = !empty($where_clauses) ? "WHERE " . implode(' AND ', $where_clauses) : "";

// --- Busca de Dados ---
$logs = [];
$total_logs = 0;
$nome_usuario_filtrado = '';

try {
    // Contar o total de logs para a paginação
    $stmt_total = $pdo->prepare("SELECT COUNT(l.id) FROM log_logins l JOIN usuario u ON l.usuario_id = u.id $where_sql");
    $stmt_total->execute($params);
    $total_logs = $stmt_total->fetchColumn();
    $total_paginas = ceil($total_logs / $limite_por_pagina);

    // Buscar os logs para a página atual
    $sql_logs = "
        SELECT l.id, l.data_login, l.ip, u.nome as nome_usuario, u.id as usuario_id
        FROM log_logins l
        JOIN usuario u ON l.usuario_id = u.id
        $where_sql
        ORDER BY l.data_login DESC
        LIMIT :limite OFFSET :offset
    ";
    $stmt_logs = $pdo->prepare($sql_logs);
    // Bind dos parâmetros de pesquisa e filtro
    foreach ($params as $key => &$val) {
        $stmt_logs->bindParam($key, $val);
    }
    // Bind dos parâmetros de paginação
    $stmt_logs->bindParam(':limite', $limite_por_pagina, PDO::PARAM_INT);
    $stmt_logs->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt_logs->execute();
    $logs = $stmt_logs->fetchAll(PDO::FETCH_ASSOC);

    // Se filtrando por usuário, busca o nome para o título
    if ($user_id_filter) {
        $stmt_user = $pdo->prepare("SELECT nome FROM usuario WHERE id = :user_id");
        $stmt_user->execute([':user_id' => $user_id_filter]);
        $nome_usuario_filtrado = $stmt_user->fetchColumn();
        if ($nome_usuario_filtrado) {
            $page_title = "Logs de: " . htmlspecialchars($nome_usuario_filtrado);
        }
    }

} catch (PDOException $e) {
    $mensagem_erro = "Erro ao carregar os logs. Por favor, tente novamente.";
    error_log("Erro no Log de Logins: " . $e->getMessage());
}

// Inclui os templates
require_once __DIR__ . '/includes/templates/header_dashboard.php';
require_once __DIR__ . '/includes/templates/sidebar_dashboard.php';
?>

<div class="main-content-dashboard">
    <header class="dashboard-header">
        <h1><?php echo $page_title; ?></h1>
    </header>

    <?php if (isset($mensagem_erro)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($mensagem_erro); ?></div>
    <?php endif; ?>

    <section class="dashboard-section">
        <div class="section-header">
            <h2>Últimos Logins Registrados</h2>
        </div>
        
        <!-- Formulário de Pesquisa -->
        <div class="search-box">
            <form action="log.php" method="GET">
                <input type="text" id="searchInput" name="q" placeholder="Pesquisar por nome ou IP..." value="<?php echo htmlspecialchars($termo_pesquisa); ?>">
                <?php if ($user_id_filter): ?>
                    <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user_id_filter); ?>">
                <?php endif; ?>
                <button type="submit" class="btn-search"><i class="fas fa-search"></i></button>
            </form>
        </div>

        <div class="table-responsive">
            <?php if (!empty($logs)): ?>
                <table class="table table-striped table-hover">
                    <thead class="thead-dark">
                        <tr>
                            <th>Nome do Usuário</th>
                            <th>Data/Hora do Login</th>
                            <th>Endereço IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td>
                                    <a href="editar_usuario.php?id=<?php echo $log['usuario_id']; ?>" title="Ver perfil de <?php echo htmlspecialchars($log['nome_usuario']); ?>">
                                        <?php echo htmlspecialchars($log['nome_usuario']); ?>
                                    </a>
                                </td>
                                <td><?php echo date('d/m/Y H:i:s', strtotime($log['data_login'])); ?></td>
                                <td><?php echo htmlspecialchars($log['ip']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="alert alert-info">Nenhum registro de log encontrado para os critérios selecionados.</div>
            <?php endif; ?>
        </div>
        
        <!-- Paginação -->
        <?php if ($total_paginas > 1): ?>
            <nav aria-label="Paginação de Logs">
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                        <li class="page-item <?php echo ($i == $pagina_atual) ? 'active' : ''; ?>">
                            <a class="page-link" href="?pagina=<?php echo $i; ?>&q=<?php echo urlencode($termo_pesquisa); ?>&user_id=<?php echo $user_id_filter; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>

    </section>
</div>

<?php
require_once __DIR__ . '/includes/templates/footer_dashboard.php';
?>

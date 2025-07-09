php
<?php
// log.php - Página para visualizar logs de login

// Definir o título da página
$page_title = "Log de Logins";

// Iniciar a sessão
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Incluir a conexão com o banco de dados
require_once __DIR__ . '/includes/conexao.php';

// --- Verificação de Login e Nível de Acesso ---
// Níveis de acesso permitidos para esta página (apenas ADMINISTRADOR)
$allowed_access_levels = ['ADMINISTRADOR']; 
$user_level = $_SESSION['nivel_acesso'] ?? '';

// Redirecionar se o usuário NÃO estiver logado OU NÃO tiver o nível de acesso permitido
if (!isset($_SESSION['usuario_id']) || !in_array($user_level, $allowed_access_levels)) {
    $_SESSION['mensagem_feedback'] = [
        'tipo' => 'danger',
        'texto' => 'Você não tem permissão para acessar esta página.'
    ];
    header('Location: redireciona_usuario.php'); 
    exit();
}

// Verificar se um user_id específico foi solicitado
$filter_user_id = null;
$filter_user_name = "Todos os Usuários";

if (isset($_GET['user_id']) && is_numeric($_GET['user_id'])) {
 $filter_user_id = (int)$_GET['user_id'];
 // Opcional: Buscar o nome do usuário para exibir no título
 try {
$stmt_user_name = $pdo->prepare("SELECT nome FROM usuario WHERE id = :user_id LIMIT 1");
$stmt_user_name->bindParam(':user_id', $filter_user_id, PDO::PARAM_INT);
$stmt_user_name->execute();
$user_data = $stmt_user_name->fetch(PDO::FETCH_ASSOC);
 if ($user_data) {
$filter_user_name = htmlspecialchars($user_data['nome']);
 }
 } catch (PDOException $e) {
 // Apenas loga o erro, não impede a exibição dos logs
 error_log("Erro ao buscar nome do usuário para log: " . $e->getMessage());
 }
}

// =====================================================================
// Lógica para buscar os registros de log de login
// =====================================================================
$logs = [];

try {
    $stmt = $pdo->prepare("SELECT 
                                ll.id, 
                                ll.usuario_id, 
                                u.nome AS nome_usuario,
                                ll.data_login, 
                                ll.ip 
                           FROM 
                                log_logins ll
 JOIN
                                usuario u ON ll.usuario_id = u.id
 ". ($filter_user_id !== null ? "WHERE ll.usuario_id = :filter_user_id" : "") ."
                           ORDER BY 
                                ll.data_login DESC"); // Ordena pelo login mais recente primeiro

 if ($filter_user_id !== null) {
$stmt->bindParam(':filter_user_id', $filter_user_id, PDO::PARAM_INT);
 }


 $stmt->execute();
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Erro ao carregar logs de login: " . $e->getMessage());
    $_SESSION['mensagem_feedback'] = [
        'tipo' => 'danger',
        'texto' => "Erro ao carregar logs de login: " . $e->getMessage()
    ];
}

// Recuperar e limpar mensagem de feedback da sessão
$mensagem_feedback = '';
$feedback_tipo = '';
if (isset($_SESSION['mensagem_feedback'])) {
    $mensagem_feedback = $_SESSION['mensagem_feedback']['texto'];
    $feedback_tipo = $_SESSION['mensagem_feedback']['tipo'];
    unset($_SESSION['mensagem_feedback']); // Limpa a sessão após exibir
}


// Incluir o cabeçalho do dashboard
require_once __DIR__ . '/includes/templates/header_dashboard.php';
// Incluir a barra lateral do dashboard
require_once __DIR__ . '/includes/templates/sidebar_dashboard.php';
?>

<div class="main-content-dashboard">
    <header class="dashboard-header">
 <h1>Log de Logins <?php echo ($filter_user_id !== null ? "para {$filter_user_name}" : ""); ?></h1>
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
            <h2>Registros de Acesso</h2>
        </div>
        
        <?php if (!empty($mensagem_feedback)): ?>
            <div class="alert alert-<?php echo $feedback_tipo; ?>">
                <?php echo $mensagem_feedback; ?>
            </div>
        <?php endif; ?>

        <?php if (empty($logs)): ?>
            <div class="alert alert-info mt-3">
                Nenhum registro de login encontrado.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover table-bordered">
                    <thead class="thead-dark">
                        <tr>
                            <th scope="col">ID do Usuário</th>
                            <th scope="col">Nome do Usuário</th>
                            <th scope="col">Data/Hora do Login</th>
                            <th scope="col">Endereço IP</th>
                            <!-- Podemos adicionar Tipo de Dispositivo e Localização aqui no futuro -->
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($log['usuario_id']); ?></td>
                                <td><?php echo htmlspecialchars($log['nome_usuario']); ?></td>
                                <td><?php echo htmlspecialchars($log['data_login']); ?></td>
                                <td><?php echo htmlspecialchars($log['ip']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Implementação futura de paginação aqui -->

        <?php endif; ?>
    </div>
</div>

<?php
// Incluir o rodapé do dashboard
require_once __DIR__ . '/includes/templates/footer_dashboard.php';
?>
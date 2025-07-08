<?php
// configuracoes.php

$page_title = "Configurações do Sistema";
require_once __DIR__ . '/includes/conexao.php';
if (session_status() == PHP_SESSION_NONE) { session_start(); }

// Esta página é crítica, apenas o Administrador pode acessá-la.
if ($_SESSION['nivel_acesso'] !== 'Administrador') {
    $_SESSION['mensagem_feedback'] = ['tipo' => 'danger', 'texto' => 'Acesso negado.'];
    header('Location: dashboard.php');
    exit();
}

// --- FUNÇÃO PARA SALVAR UMA CONFIGURAÇÃO ---
function salvar_configuracao($pdo, $chave, $valor) {
    // Usa INSERT ... ON DUPLICATE KEY UPDATE para inserir ou atualizar a chave.
    // Isso requer que a coluna 'chave' tenha um índice UNIQUE, o que já fizemos no CREATE TABLE.
    $sql = "INSERT INTO configuracoes (chave, valor) VALUES (:chave, :valor) ON DUPLICATE KEY UPDATE valor = :valor";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':chave' => $chave, ':valor' => $valor]);
}

// --- PROCESSAMENTO DO FORMULÁRIO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Processa o texto da página inicial
    if (isset($_POST['texto_index'])) {
        salvar_configuracao($pdo, 'texto_index', $_POST['texto_index']);
    }

    // 2. Processa o upload do LOGO
    if (isset($_FILES['logo_upload']) && $_FILES['logo_upload']['error'] === UPLOAD_ERR_OK) {
        $logo_file = $_FILES['logo_upload'];
        $upload_dir = 'imagens/sistema/';
        $allowed_types = ['image/png', 'image/jpeg', 'image/gif', 'image/svg+xml'];

        if (in_array($logo_file['type'], $allowed_types) && $logo_file['size'] < 2000000) { // Limite de 2MB
            $ext = pathinfo($logo_file['name'], PATHINFO_EXTENSION);
            $new_filename = 'logo_principal.' . $ext;
            if (move_uploaded_file($logo_file['tmp_name'], $upload_dir . $new_filename)) {
                salvar_configuracao($pdo, 'logo_path', $upload_dir . $new_filename);
            }
        }
    }

    // 3. Processa o upload do FAVICON
    if (isset($_FILES['favicon_upload']) && $_FILES['favicon_upload']['error'] === UPLOAD_ERR_OK) {
        $favicon_file = $_FILES['favicon_upload'];
        $upload_dir = 'imagens/sistema/';
        $allowed_types = ['image/x-icon', 'image/vnd.microsoft.icon', 'image/png'];

        if (in_array($favicon_file['type'], $allowed_types) && $favicon_file['size'] < 500000) { // Limite de 500KB
            $ext = pathinfo($favicon_file['name'], PATHINFO_EXTENSION);
            $new_filename = 'favicon.' . $ext;
            if (move_uploaded_file($favicon_file['tmp_name'], $upload_dir . $new_filename)) {
                salvar_configuracao($pdo, 'favicon_path', $upload_dir . $new_filename);
            }
        }
    }

    $_SESSION['mensagem_feedback'] = ['tipo' => 'success', 'texto' => 'Configurações salvas com sucesso!'];
    header('Location: configuracoes.php');
    exit();
}

// --- CARREGAR CONFIGURAÇÕES ATUAIS PARA EXIBIR NO FORMULÁRIO ---
$stmt = $pdo->query("SELECT chave, valor FROM configuracoes");
$configuracoes_raw = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$config = [
    'texto_index' => $configuracoes_raw['texto_index'] ?? '',
    'logo_path' => $configuracoes_raw['logo_path'] ?? '',
    'favicon_path' => $configuracoes_raw['favicon_path'] ?? '',
];

require_once __DIR__ . '/includes/templates/header_dashboard.php';
require_once __DIR__ . '/includes/templates/sidebar_dashboard.php';
?>

<div class="main-content-dashboard">
    <header class="dashboard-header">
        <h1><?php echo $page_title; ?></h1>
    </header>

    <div class="dashboard-section">
        <div class="section-header">
            <h2>Personalização do Sistema</h2>
        </div>

        <?php if (isset($_SESSION['mensagem_feedback'])): ?>
            <div class="alert alert-<?php echo htmlspecialchars($_SESSION['mensagem_feedback']['tipo']); ?>">
                <?php echo htmlspecialchars($_SESSION['mensagem_feedback']['texto']); ?>
            </div>
            <?php unset($_SESSION['mensagem_feedback']); ?>
        <?php endif; ?>

        <form action="configuracoes.php" method="POST" enctype="multipart/form-data">
            <div class="card">
                <div class="card-header">
                    <h4>Texto da Página Inicial</h4>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="texto_index">Este texto aparecerá na página principal (`index.php`) para os visitantes.</label>
                        <textarea name="texto_index" id="texto_index" class="form-control" rows="5"><?php echo htmlspecialchars($config['texto_index']); ?></textarea>
                    </div>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h4>Identidade Visual</h4>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Logo Atual do Sistema</label><br>
                        <img src="<?php echo htmlspecialchars($config['logo_path']); ?>" alt="Logo Atual" style="max-width: 200px; max-height: 80px; background: #f0f0f0; padding: 10px; border-radius: 5px;">
                    </div>
                    <div class="form-group">
                        <label for="logo_upload">Enviar novo logo (Recomendado: .png com fundo transparente)</label>
                        <input type="file" name="logo_upload" id="logo_upload" class="form-control-file" accept="image/png, image/jpeg, image/gif, image/svg+xml">
                    </div>
                    <hr>
                    <div class="form-group">
                        <label>Favicon Atual do Site</label><br>
                        <img src="<?php echo htmlspecialchars($config['favicon_path']); ?>" alt="Favicon Atual" style="width: 32px; height: 32px;">
                    </div>
                     <div class="form-group">
                        <label for="favicon_upload">Enviar novo favicon (Recomendado: .png ou .ico)</label>
                        <input type="file" name="favicon_upload" id="favicon_upload" class="form-control-file" accept="image/x-icon, image/png">
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn-primary-dashboard">Salvar Alterações</button>
            </div>
        </form>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/templates/footer_dashboard.php';
?>
<?php
// credenciais.php - Painel de Controle de Permissões de Página

if (session_status() == PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/includes/conexao.php';

// Acesso restrito apenas para ADMINISTRADORES
if (($_SESSION['nivel_acesso'] ?? '') !== 'ADMINISTRADOR') {
    $_SESSION['mensagem_feedback'] = ['tipo' => 'danger', 'texto' => 'Acesso negado.'];
    header('Location: redireciona_usuario.php');
    exit();
}

// --- Definições da Página ---
$page_title = "Gerenciar Credenciais e Permissões";
$current_page = "Gerenciar Credenciais";

// Níveis de acesso que podem ser gerenciados
$todos_niveis_acesso = ['ADMINISTRADOR', 'GERENTE', 'PROFESSOR', 'ALUNO'];

// --- Lógica para salvar as permissões ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['permissoes'])) {
    try {
        $permissoes_post = $_POST['permissoes'];
        
        $pdo->beginTransaction();

        // Limpa a tabela para inserir as novas configurações
        $pdo->exec("TRUNCATE TABLE pagina_permissoes");

        $stmt = $pdo->prepare("INSERT INTO pagina_permissoes (nome_pagina, niveis_acesso_permitidos) VALUES (:nome_pagina, :niveis)");

        foreach ($permissoes_post as $pagina => $niveis) {
            $niveis_string = implode(',', array_keys($niveis));
            $stmt->execute([':nome_pagina' => $pagina, ':niveis' => $niveis_string]);
        }
        
        $pdo->commit();
        $_SESSION['mensagem_feedback'] = ['tipo' => 'success', 'texto' => 'Permissões atualizadas com sucesso!'];

    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['mensagem_feedback'] = ['tipo' => 'danger', 'texto' => 'Erro ao salvar permissões: ' . $e->getMessage()];
    }

    header('Location: credenciais.php');
    exit;
}


// --- Lógica para buscar as permissões salvas ---
$permissoes_salvas = [];
try {
    $permissoes_salvas = $pdo->query("SELECT nome_pagina, niveis_acesso_permitidos FROM pagina_permissoes")->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (PDOException $e) {
     $_SESSION['mensagem_feedback'] = ['tipo' => 'danger', 'texto' => 'Erro ao carregar permissões: ' . $e->getMessage()];
}

// --- Lógica para listar as páginas do sistema ---
$arquivos_para_ignorar = [
    '.', '..', 'index.php', 'login.php', 'logout.php', 'processa_login.php', 
    'cadastro.php', 'processa_cadastro.php', 'recuperar_senha.php', 'processa_recuperacao.php',
    'resetar_senha.php', 'processa_reset.php', 'redireciona_usuario.php', 
    'get_disciplinas_professor.php', 'exportar_relatorio_pdf.php', 'exportar_relatorio_excel.php',
    'debug_cadastro_post.php', 'ver_relatorio.php', 'export_data.php', 'tempCodeRunnerFile.php'
];

$paginas_do_sistema = array_filter(scandir(__DIR__), function($file) use ($arquivos_para_ignorar) {
    return is_file($file) && pathinfo($file, PATHINFO_EXTENSION) === 'php' && !in_array($file, $arquivos_para_ignorar);
});

// Inclui os templates
require_once __DIR__ . '/includes/templates/header_dashboard.php';
require_once __DIR__ . '/includes/templates/sidebar_dashboard.php';
?>

<div class="main-content-dashboard">
    <header class="dashboard-header">
        <h1><?php echo $page_title; ?></h1>
    </header>

    <?php if (isset($_SESSION['mensagem_feedback'])): ?>
        <div class="alert alert-<?php echo $_SESSION['mensagem_feedback']['tipo']; ?>">
            <?php echo $_SESSION['mensagem_feedback']['texto']; ?>
        </div>
        <?php unset($_SESSION['mensagem_feedback']); ?>
    <?php endif; ?>

    <section class="dashboard-section">
        <div class="section-header">
            <h2>Permissões por Página</h2>
        </div>
        
        <form action="credenciais.php" method="POST">
            <div class="table-responsive">
                <table class="table table-striped table-hover table-bordered">
                    <thead class="thead-dark">
                        <tr>
                            <th scope="col">Página do Sistema</th>
                            <?php foreach ($todos_niveis_acesso as $nivel): ?>
                                <th scope="col" class="text-center"><?php echo ucfirst(strtolower($nivel)); ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($paginas_do_sistema as $pagina): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($pagina); ?></strong></td>
                                <?php 
                                $niveis_permitidos_pagina = isset($permissoes_salvas[$pagina]) ? explode(',', $permissoes_salvas[$pagina]) : [];
                                foreach ($todos_niveis_acesso as $nivel): 
                                    $checked = in_array($nivel, $niveis_permitidos_pagina) ? 'checked' : '';
                                ?>
                                    <td class="text-center">
                                        <input type="checkbox" name="permissoes[<?php echo htmlspecialchars($pagina); ?>][<?php echo $nivel; ?>]" value="1" <?php echo $checked; ?>>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="form-actions mt-4">
                <button type="submit" class="btn-primary-dashboard">
                    <i class="fas fa-save"></i> Salvar Alterações
                </button>
            </div>
        </form>
    </section>
</div>

<?php
require_once __DIR__ . '/includes/templates/footer_dashboard.php';
?>

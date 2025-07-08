<?php
// Define o título da página
$page_title = "Editar Pergunta do Questionário";

// Inclui o cabeçalho do dashboard (que já faz a verificação de login e inicia a sessão)
require_once __DIR__ . '/includes/templates/header_dashboard.php';

// Inclui a barra lateral (sidebar) do dashboard
require_once __DIR__ . '/includes/templates/sidebar_dashboard.php';

// Define a página atual para a sidebar destacar o link ativo
$current_page = "Questionário"; // Para manter o "Questionário" ativo na sidebar

$mensagem_sucesso = '';
$mensagem_erro = '';

// Valores possíveis para a caixa de seleção de Categoria
$categorias_disponiveis = [
    'Academia',
    'Disciplina',
    'Professor'
];

// Inicializa as variáveis para os campos do formulário
$id_pergunta = null;
$pergunta_texto = ''; // Renomeado para evitar conflito com a variável $pergunta global
$descricao = '';
$categoria = '';

// =====================================================================
// Lógica para carregar os dados da pergunta (primeira vez que a página é acessada)
// ou para processar a atualização do formulário.
// =====================================================================

// Verifica se um ID de pergunta foi passado na URL
if (isset($_GET['id'])) {
    $id_pergunta = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

    if (!$id_pergunta) {
        $mensagem_erro = "ID da pergunta inválido.";
    } else {
        try {
            // Busca os dados da pergunta no banco de dados
            $stmt = $pdo->prepare("SELECT id, pergunta, descricao, categoria FROM questionario WHERE id = :id");
            $stmt->bindParam(':id', $id_pergunta, PDO::PARAM_INT);
            $stmt->execute();
            $pergunta_existente = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($pergunta_existente) {
                // Preenche as variáveis com os dados existentes
                $pergunta_texto = $pergunta_existente['pergunta'];
                $descricao = $pergunta_existente['descricao'];
                $categoria = $pergunta_existente['categoria'];
            } else {
                $mensagem_erro = "Pergunta não encontrada.";
            }

        } catch (PDOException $e) {
            $mensagem_erro = "Erro ao carregar dados da pergunta: " . $e->getMessage();
        }
    }
}

// Processa o formulário quando ele é submetido (método POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Coleta e sanitiza os dados do formulário, incluindo o ID (oculto)
    $id_pergunta_post = filter_input(INPUT_POST, 'id_pergunta', FILTER_VALIDATE_INT);
    $pergunta_texto_post = filter_input(INPUT_POST, 'pergunta', FILTER_SANITIZE_STRING);
    $descricao_post = filter_input(INPUT_POST, 'descricao', FILTER_SANITIZE_STRING);
    $categoria_post = filter_input(INPUT_POST, 'categoria', FILTER_SANITIZE_STRING);

    // 2. Validação básica
    if (!$id_pergunta_post || empty($pergunta_texto_post) || empty($descricao_post) || empty($categoria_post)) {
        $mensagem_erro = "Dados inválidos ou campos obrigatórios não preenchidos.";
    } elseif (!in_array($categoria_post, $categorias_disponiveis)) {
        $mensagem_erro = "Categoria inválida selecionada. Por favor, escolha uma das opções fornecidas.";
    } else {
        try {
            // Prepara a consulta SQL para ATUALIZAR a pergunta
            // Note o uso de WHERE id = :id para garantir que apenas a pergunta correta seja atualizada
            $sql = "UPDATE questionario SET pergunta = :pergunta, descricao = :descricao, categoria = :categoria WHERE id = :id";
            $stmt = $pdo->prepare($sql);

            // Liga os parâmetros
            $stmt->bindParam(':pergunta', $pergunta_texto_post);
            $stmt->bindParam(':descricao', $descricao_post);
            $stmt->bindParam(':categoria', $categoria_post);
            $stmt->bindParam(':id', $id_pergunta_post, PDO::PARAM_INT);

            // Executa a consulta
            if ($stmt->execute()) {
                $mensagem_sucesso = "Pergunta atualizada com sucesso!";
                // Redirecionar para a lista após sucesso para ver a atualização
                header('Location: questionarios.php?msg=edit_sucesso');
                exit();
            } else {
                $mensagem_erro = "Erro ao atualizar a pergunta. Tente novamente.";
                // Para depuração
                // print_r($stmt->errorInfo());
            }

        } catch (PDOException $e) {
            $mensagem_erro = "Erro no banco de dados: " . $e->getMessage();
        }
    }

    // Se houve erro no POST, precisamos recarregar as variáveis do formulário
    // com os dados que o usuário tentou enviar, para que ele não perca o que digitou.
    $id_pergunta = $id_pergunta_post; // Mantém o ID na variável para o campo hidden
    $pergunta_texto = $pergunta_texto_post;
    $descricao = $descricao_post;
    $categoria = $categoria_post;
}

?>

<div class="main-content-dashboard">
    <header class="dashboard-header">
        <h1><?php echo $page_title; ?></h1>
        <div class="user-info">
            <span class="user-name"><?php echo htmlspecialchars($nome_usuario_logado); ?></span>
            <div class="user-avatar">
                <?php if (!empty($foto_perfil_usuario) && file_exists('imagens/profiles/' . $foto_perfil_usuario)) : ?>
                    <img src="imagens/profiles/<?php echo htmlspecialchars($foto_perfil_usuario); ?>" alt="Avatar">
                <?php else : ?>
                    <i class="fas fa-user-circle"></i>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <div class="dashboard-section">
        <div class="section-header">
            <h2>Formulário de Edição de Pergunta</h2>
            <a href="questionarios.php" class="btn-secondary-dashboard">
                <i class="fas fa-arrow-left"></i> Voltar para Perguntas
            </a>
        </div>
        
        <?php if (!empty($mensagem_sucesso)): ?>
            <div class="alert alert-success">
                <?php echo $mensagem_sucesso; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($mensagem_erro)): ?>
            <div class="alert alert-danger">
                <?php echo $mensagem_erro; ?>
            </div>
        <?php endif; ?>

        <?php if ($id_pergunta && empty($mensagem_erro)): // Mostra o formulário apenas se o ID é válido e não há erro inicial ?>
            <form action="editar_pergunta.php" method="POST" class="form-dashboard">
                <input type="hidden" name="id_pergunta" value="<?php echo htmlspecialchars($id_pergunta); ?>">

                <div class="form-group">
                    <label for="pergunta">Pergunta:</label>
                    <input type="text" id="pergunta" name="pergunta" value="<?php echo htmlspecialchars($pergunta_texto); ?>" required placeholder="Digite a pergunta">
                </div>
                <div class="form-group">
                    <label for="descricao">Descrição:</label>
                    <textarea id="descricao" name="descricao" rows="5" required placeholder="Forneça uma descrição detalhada para a pergunta"><?php echo htmlspecialchars($descricao); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="categoria">Categoria:</label>
                    <select id="categoria" name="categoria" required>
                        <option value="">Selecione uma categoria</option>
                        <?php foreach ($categorias_disponiveis as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo ($categoria === $cat) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <button type="submit" class="btn-primary-dashboard">
                    <i class="fas fa-save"></i> Salvar Alterações
                </button>
            </form>
        <?php endif; ?>
    </div>

</div>

<?php
// Inclui o rodapé do dashboard
require_once __DIR__ . '/includes/templates/footer_dashboard.php';
?>
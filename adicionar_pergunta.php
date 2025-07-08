<?php
// Define o título da página
$page_title = "Adicionar Nova Pergunta";

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

// Inicializa as variáveis para os campos do formulário para evitar avisos PHP
$pergunta = '';
$descricao = '';
$categoria = ''; // Para armazenar a categoria selecionada

// Processa o formulário quando ele é submetido
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Coleta e sanitiza os dados do formulário
    $pergunta = filter_input(INPUT_POST, 'pergunta', FILTER_SANITIZE_STRING);
    $descricao = filter_input(INPUT_POST, 'descricao', FILTER_SANITIZE_STRING);
    $categoria = filter_input(INPUT_POST, 'categoria', FILTER_SANITIZE_STRING); // Continua sanitizando string

    // 2. Validação básica (campos não podem estar vazios e categoria deve ser uma opção válida)
    if (empty($pergunta) || empty($descricao) || empty($categoria)) {
        $mensagem_erro = "Todos os campos são obrigatórios. Por favor, preença todos os dados.";
    } elseif (!in_array($categoria, $categorias_disponiveis)) {
        $mensagem_erro = "Categoria inválida selecionada. Por favor, escolha uma das opções fornecidas.";
    } else {
        try {
            // Prepara a consulta SQL para inserir a nova pergunta
            $sql = "INSERT INTO questionario (pergunta, descricao, categoria) VALUES (:pergunta, :descricao, :categoria)";
            $stmt = $pdo->prepare($sql);

            // Liga os parâmetros
            $stmt->bindParam(':pergunta', $pergunta);
            $stmt->bindParam(':descricao', $descricao);
            $stmt->bindParam(':categoria', $categoria);

            // Executa a consulta
            if ($stmt->execute()) {
                $mensagem_sucesso = "Pergunta adicionada com sucesso!";
                // Opcional: Limpar os campos do formulário após o sucesso
                $pergunta = $descricao = $categoria = '';
                // Redirecionar para a lista após sucesso para evitar reenvio do formulário
                header('Location: questionarios.php?msg=add_sucesso');
                exit();
            } else {
                $mensagem_erro = "Erro ao adicionar a pergunta. Tente novamente.";
                // Para depuração: print_r($stmt->errorInfo());
            }

        } catch (PDOException $e) {
            $mensagem_erro = "Erro no banco de dados: " . $e->getMessage();
        }
    }
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
            <h2>Formulário de Nova Pergunta</h2>
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

        <form action="adicionar_pergunta.php" method="POST" class="form-dashboard">
            <div class="form-group">
                <label for="pergunta">Pergunta:</label>
                <input type="text" id="pergunta" name="pergunta" value="<?php echo htmlspecialchars($pergunta); ?>" required placeholder="Digite a pergunta">
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
                <i class="fas fa-save"></i> Salvar Pergunta
            </button>
        </form>
    </div>

</div>

<?php
// Inclui o rodapé do dashboard
require_once __DIR__ . '/includes/templates/footer_dashboard.php';
?>
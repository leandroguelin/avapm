<?php
// Este script é responsável por processar a exclusão de uma pergunta.
// Ele não possui interface, apenas lógica de backend.

// Inclui a conexão com o banco de dados.
// '__DIR__' garante que o caminho seja sempre resolvido corretamente.
require_once __DIR__ . '/includes/conexao.php';

// Inicia a sessão PHP para poder usar mensagens de feedback e verificar login.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// =====================================================================
// Lógica de Verificação de Login e Nível de Acesso
// Redireciona o usuário para a página de login se não estiver autenticado
// ou se não tiver o nível de acesso adequado (ex: apenas administrador pode excluir).
// =====================================================================
if (!isset($_SESSION['usuario_logado']) || empty($_SESSION['usuario_logado'])) {
    header("Location: login.php");
    exit();
}

// Opcional: Verifique se o usuário tem nível de acesso de administrador para excluir
// Remova ou ajuste esta linha se você permitir que outros níveis excluam.
if (($_SESSION['usuario_logado']['nivel_acesso'] ?? '') !== 'administrador') {
    $_SESSION['mensagem_erro'] = "Você não tem permissão para realizar esta ação.";
    header("Location: questionarios.php");
    exit();
}

// Inicializa a variável para a mensagem de feedback
$mensagem_feedback = '';
$tipo_feedback = ''; // 'sucesso' ou 'erro'

// Verifica se um ID de pergunta foi passado via GET
if (isset($_GET['id'])) {
    $id_pergunta = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

    if (!$id_pergunta) {
        $mensagem_feedback = "ID da pergunta inválido para exclusão.";
        $tipo_feedback = 'erro';
    } else {
        try {
            // Prepara a consulta SQL para excluir a pergunta
            // O WHERE id = :id é CRÍTICO para excluir APENAS a pergunta desejada
            $sql = "DELETE FROM questionario WHERE id = :id";
            $stmt = $pdo->prepare($sql);

            // Liga o parâmetro ID
            $stmt->bindParam(':id', $id_pergunta, PDO::PARAM_INT);

            // Executa a consulta
            if ($stmt->execute()) {
                // Verifica se alguma linha foi afetada (se a pergunta realmente existia e foi excluída)
                if ($stmt->rowCount() > 0) {
                    $mensagem_feedback = "Pergunta excluída com sucesso!";
                    $tipo_feedback = 'sucesso';
                } else {
                    $mensagem_feedback = "Pergunta com ID {$id_pergunta} não encontrada para exclusão.";
                    $tipo_feedback = 'erro';
                }
            } else {
                $mensagem_feedback = "Erro ao excluir a pergunta. Tente novamente.";
                $tipo_feedback = 'erro';
                // Para depuração
                // print_r($stmt->errorInfo());
            }

        } catch (PDOException $e) {
            $mensagem_feedback = "Erro no banco de dados durante a exclusão: " . $e->getMessage();
            $tipo_feedback = 'erro';
        }
    }
} else {
    $mensagem_feedback = "Nenhum ID de pergunta fornecido para exclusão.";
    $tipo_feedback = 'erro';
}

// =====================================================================
// Redirecionamento e Mensagem de Feedback
// Armazena a mensagem na sessão e redireciona para a página de listagem.
// =====================================================================

// Armazena a mensagem na sessão para que ela possa ser exibida após o redirecionamento
if (!empty($mensagem_feedback)) {
    $_SESSION['mensagem_feedback'] = [
        'texto' => $mensagem_feedback,
        'tipo' => $tipo_feedback
    ];
}

// Redireciona de volta para a página de questionários
header("Location: questionarios.php");
exit(); // Garante que o script pare após o redirecionamento
?>
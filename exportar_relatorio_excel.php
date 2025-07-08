php
<?php
// exportar_relatorio_excel.php - Exportação para Excel (CSV com extensão .xls)

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/conexao.php';

// Implementa a verificação de nível de acesso: apenas ADMINISTRADOR ou GERENTE
$allowed_access_levels = ['ADMINISTRADOR', 'GERENTE'];
$user_level = $_SESSION['nivel_acesso'] ?? '';

if (!isset($_SESSION['usuario_id']) || !in_array($user_level, $allowed_access_levels)) {
    // Redireciona para a página de redirecionamento se não tiver permissão
    header('Location: redireciona_usuario.php');
    exit();
}

// Adiciona lógica para obter o ID da avaliação da URL
$avaliacao_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($avaliacao_id <= 0) {
    // Trata caso o ID da avaliação seja inválido ou não fornecido
    $_SESSION['mensagem_feedback'] = ['tipo' => 'danger', 'texto' => 'ID da avaliação inválido para exportação Excel.'];
    header('Location: relatorios.php');
    exit();
}

// Busca os detalhes da avaliação (perguntas, respostas, professores, observações) do banco de dados
try {
    // Consulta para obter os detalhes da avaliação específica
    // Adapte esta consulta conforme a estrutura exata da sua tabela de respostas
    // Exemplo baseado na sua consulta anterior para professores, adaptado para uma avaliação específica
    // NOTE: Ajuste a consulta JOIN e os campos conforme a estrutura real do seu DB para respostas e usuários.
    $stmt = $pdo->prepare(" 
        SELECT
            r.pergunta,
            u.nome AS nome_professor, -- Assumindo que 'avaliado' na tabela respostas é o ID do professor
            r.resposta,
            r.observacao
        FROM
            respostas r
        JOIN
            usuario u ON r.avaliado = u.id
        WHERE
            r.avaliacao_id = :avaliacao_id
        ORDER BY
            u.nome ASC, r.pergunta ASC
    ");
    $stmt->execute([':avaliacao_id' => $avaliacao_id]);
    $resultados_avaliacao = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Busca informações básicas da avaliação para o nome do arquivo
    $stmt_info = $pdo->prepare("SELECT nome FROM avaliacao WHERE id = :id");
    $stmt_info->execute([':id' => $avaliacao_id]);
    $info_avaliacao = $stmt_info->fetch(PDO::FETCH_ASSOC);
    $nome_arquivo = "relatorio_avaliacao_" . ($info_avaliacao['nome'] ?? 'desconhecida') . "_" . $avaliacao_id . ".xls";

} catch (PDOException $e) {
    // Trata erro de banco de dados
    error_log("Erro DB ao exportar Excel: " . $e->getMessage());
    $_SESSION['mensagem_feedback'] = ['tipo' => 'danger', 'texto' => 'Erro ao buscar dados para exportação Excel.'];
    header('Location: relatorios.php');
    exit();
}

// Define os cabeçalhos HTTP necessários para forçar o download de um arquivo Excel (.xls)
// O Content-Type 'application/vnd.ms-excel' é comum para arquivos .xls gerados assim.
// O Content-Disposition com attachment força o download e define o nome do arquivo.
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="' . basename($nome_arquivo) . '"');

// Comentário: O conteúdo a seguir será formatado como CSV (valores separados por vírgulas)
// para simular um arquivo Excel simples que pode ser aberto diretamente no Microsoft Excel
// ou outros programas de planilha eletrônica.

// Abre o output stream (php://output)
$output = fopen('php://output', 'w');

// Escreve os cabeçalhos do arquivo CSV
fputcsv($output, ['Pergunta', 'Professor', 'Resposta', 'Observacao']);

// Escreve os dados dos resultados da avaliação no arquivo CSV
if (!empty($resultados_avaliacao)) {
    foreach ($resultados_avaliacao as $row) {
        // fputcsv formata automaticamente a linha como CSV, tratando aspas, etc.
        fputcsv($output, [$row['pergunta'], $row['nome_professor'], $row['resposta'], $row['observacao']]);
    }
} else {
    // Caso não haja resultados para esta avaliação
    fputcsv($output, ['Nenhum resultado encontrado para esta avaliação.']);
}

fclose($output); // Fecha o output stream
exit(); // Encerra o script após enviar o conteúdo
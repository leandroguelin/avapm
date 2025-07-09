php
<?php
// get_disciplinas_professor.php

// Incluir a conexão com o banco de dados
require_once __DIR__ . '/includes/conexao.php';

header('Content-Type: application/json'); // Define o cabeçalho para JSON

$response = ['disciplinas' => []]; // Estrutura inicial da resposta

// Verifica se a requisição é AJAX (opcional, mas boa prática)
// if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
//     // Não é uma requisição AJAX, pode retornar erro ou uma página HTML simples
//     // Por enquanto, apenas continua, mas em um ambiente de produção, considere uma resposta de erro.
//     // http_response_code(403); // Forbidden
//     // echo json_encode(['error' => 'Acesso direto não permitido.']);
//     // exit();
// }

// Verifica se o user_id foi fornecido e é válido
$user_id = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);

if ($user_id === false || $user_id === null) {
    // user_id inválido ou não fornecido
    $response['error'] = 'ID do usuário inválido ou não fornecido.';
    echo json_encode($response);
    exit();
}

try {
    // --- Lógica para buscar as disciplinas do professor ---
    // Esta query assume que existe uma tabela de associação
    // entre usuarios e disciplinas (ex: usuario_disciplina)
    // OU um campo na tabela disciplina que referencia o professor.
    // AJUSTE A QUERY ABAIXO conforme a ESTRUTURA REAL DO SEU BANCO DE DADOS
    $sql = "
        SELECT
            d.id,
            d.sigla,
            d.nome
        FROM
            disciplina d
        JOIN
            usuario_disciplina ud ON d.id = ud.disciplina_id -- Exemplo de JOIN com tabela N:M
        WHERE
            ud.usuario_id = :user_id
        ORDER BY
            d.sigla ASC
    ";

    // ALTERNATIVA: Se a tabela disciplina tem um campo professor_id
    /*
    $sql = "
        SELECT
            id,
            sigla,
            nome
        FROM
            disciplina
        WHERE
            professor_id = :user_id
        ORDER BY
            sigla ASC
    ";
    */

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();

    $disciplinas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Retorna os resultados
    $response['disciplinas'] = $disciplinas;

} catch (PDOException $e) {
    // Em caso de erro no banco de dados
    error_log("Erro ao buscar disciplinas do professor {$user_id}: " . $e->getMessage());
    $response['error'] = 'Erro ao buscar disciplinas no banco de dados.';
    // Em um ambiente de produção, você pode querer ocultar o detalhe do erro do usuário
    // $response['error'] = 'Erro interno ao buscar disciplinas.';
}

// Retorna a resposta em formato JSON
echo json_encode($response);

?>
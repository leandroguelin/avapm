php
<?php
require_once __DIR__ . '/includes/conexao.php';

// Definir cabeçalho para retornar JSON
header('Content-Type: application/json');

// Obter o termo de busca da requisição GET
$search_term = isset($_GET['q']) ? $_GET['q'] : '';

// Consulta SQL para buscar disciplinas por nome ou sigla
// Usando ILIKE para busca case-insensitive (comum em PostgreSQL)
// Adapte para LIKE se estiver usando MySQL e precisar de case-insensitivity (pode precisar de BINARY ou configuração do banco)
$sql = "SELECT id, nome FROM disciplina WHERE nome ILIKE ? OR sigla ILIKE ? ORDER BY nome LIMIT 20"; // Limite de 20 resultados para performance

$disciplinas = [];

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['%' . $search_term . '%', '%' . $search_term . '%']); // Adicionar curingas %
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formatar resultados para Select2
    foreach ($resultados as $row) {
        $disciplinas[] = [
            'id' => $row['id'],
            'text' => $row['nome'] // Ou $row['sigla'] . ' - ' . $row['nome'] se preferir
        ];
    }

} catch (PDOException $e) {
    // Em caso de erro, logar e retornar um array vazio ou mensagem de erro JSON
    error_log("Erro na busca AJAX de disciplinas: " . $e->getMessage());
    // Retornar erro JSON (opcional, pode ser apenas um array vazio)
    // echo json_encode(['error' => 'Erro ao buscar disciplinas']);
    $disciplinas = []; // Retornar array vazio em caso de erro
}

// Retornar resultados em JSON
echo json_encode(['results' => $disciplinas]);

?>
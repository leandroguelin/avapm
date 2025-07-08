<?php

// Inclui o arquivo de conexão com o banco de dados
// Isso torna a variável $pdo (objeto de conexão) disponível neste script
require_once 'includes/conexao.php';

// Se a conexão foi bem-sucedida (nenhuma exceção foi lançada no conexao.php),
// podemos exibir uma mensagem de sucesso.
echo "Conexão com o banco de dados 'avapm' realizada com sucesso!";

// Opcional: Você pode tentar executar uma query simples para confirmar ainda mais.
/*
try {
    $stmt = $pdo->query("SELECT 1"); // Tenta selecionar o número 1
    if ($stmt) {
        echo "<br>Teste de query simples também funcionou!";
    }
} catch (PDOException $e) {
    echo "<br>Erro ao executar query de teste: " . $e->getMessage();
}
*/

?>
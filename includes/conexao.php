<?php

// Definição das constantes de conexão
define('DB_HOST', 'postgres-homo.ssp.go.gov.br'); // Host do banco de dados
define('DB_USER', 'usr_avapm');                   // Usuário do banco de dados
define('DB_PASS', 'avapm-190Leandro');            // Senha do banco de dados
define('DB_NAME', 'avapm');                       // Nome do banco de dados

// define('DB_HOST', 'localhost'); // Host do banco de dados
// define('DB_USER', 'postgres');                   // Usuário do banco de dados
// define('DB_PASS', '123465');            // Senha do banco de dados
// define('DB_NAME', 'ava');                       // Nome do banco de dados


try {
    // Conexão com PostgreSQL (driver: pgsql)
    $pdo = new PDO("pgsql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);

    // Define o modo de erro para exceção
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // --- CORREÇÃO: Define a codificação do cliente para UTF-8 ---
    // Isso garante que os caracteres especiais sejam tratados corretamente.
    $pdo->exec("SET client_encoding TO 'UTF8'");

    // echo "Conexão bem-sucedida!";

} catch (PDOException $e) {
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}

?>

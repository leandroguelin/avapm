<?php

// Definição das constantes de conexão
define('DB_HOST', 'localhost'); // Host do banco de dados
define('DB_USER', 'root');      // Usuário do banco de dados
define('DB_PASS', '');      // Senha do banco de dados
define('DB_NAME', 'avapm'); // Nome do banco de dados que criamos

try {
    // Cria uma nova instância PDO para a conexão
    // mysql:host=DB_HOST;dbname=DB_NAME - Especifica o driver, host e nome do banco
    // DB_USER, DB_PASS - Credenciais de acesso
    // array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8") - Define o charset para UTF-8, garantindo que caracteres especiais sejam exibidos corretamente
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));

    // Define o modo de erro do PDO para lançar exceções em caso de erros SQL
    // Isso facilita a depuração, pois erros serão "capturados" pelo bloco catch
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Opcional: Mensagem de sucesso (apenas para teste, pode ser removida depois)
    // echo "Conexão bem-sucedida!";

} catch (PDOException $e) {
    // Em caso de erro na conexão, exibe uma mensagem de erro e interrompe a execução do script
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}

?>
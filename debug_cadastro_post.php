php
<?php
session_start();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug POST</title>
    <style>
        body { font-family: monospace; white-space: pre-wrap; }
    </style>
</head>
<body>
    <h1>Conteúdo de $_POST</h1>
    <pre>
<?php
// Use print_r ou var_dump para exibir o conteúdo do array $_POST
print_r($_POST);
// var_dump($_POST); // Outra opção, mais detalhada
?>
    </pre>
    <p><a href="cadastro.php">Voltar para a página de cadastro</a></p>
</body>
</html>
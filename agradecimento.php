<?php // agradecimento.php ?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Avaliação Concluída - AVAPM</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap');
        body { font-family: 'Roboto', sans-serif; background-color: #f0f2f5; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; text-align: center; }
        .card { background: #fff; padding: 50px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); max-width: 500px; }
        h1 { color: #28a745; font-size: 36px; margin-bottom: 20px; }
        p { font-size: 18px; color: #333; }
        .link-voltar { display: inline-block; margin-top: 30px; padding: 10px 20px; background: #007bff; color: #fff; text-decoration: none; border-radius: 5px; transition: background 0.3s; }
        .link-voltar:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Obrigado!</h1>
        <p>Sua avaliação foi registrada com sucesso.</p>
        <p>Sua participação é fundamental para a melhoria contínua da nossa instituição.</p>
        <a href="index.php" class="link-voltar">Voltar para a Página Inicial</a>
    </div>
</body>
</html>
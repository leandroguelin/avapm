<?php
// inserir_codigo_avaliacao.php - VERSÃO COM LAYOUT MODERNO

if (session_status() == PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/includes/conexao.php';

// --- LÓGICA PHP PARA BUSCAR AS CONFIGURAÇÕES DO BANCO DE DADOS ---
try {
    $stmt_configs = $pdo->query("SELECT chave, valor FROM configuracoes");
    $configs = $stmt_configs->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (PDOException $e) {
    $configs = []; // Em caso de erro, define um array vazio
    error_log("Erro ao buscar configurações: " . $e->getMessage());
}
$logo_path = $configs['logo_path'] ?? 'imagens/sistema/logo_exemplo.png';
$favicon_path = $configs['favicon_path'] ?? 'imagens/sistema/favicon.ico';


$erro_mensagem = '';

// --- LÓGICA DE VALIDAÇÃO DO CÓDIGO (sem alterações) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['codigo'])) {
        $erro_mensagem = "Por favor, insira o código da avaliação.";
    } else {
        $codigo_inserido = strtoupper(trim($_POST['codigo']));
        try {
            $stmt = $pdo->prepare("SELECT id, data_inicio, data_final, situacao FROM avaliacao WHERE codigo = :codigo");
            $stmt->execute([':codigo' => $codigo_inserido]);
            $avaliacao = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$avaliacao) {
                $erro_mensagem = "Código de avaliação inválido ou não encontrado.";
            } else if ($avaliacao['situacao'] !== 'Ativa') {
                $erro_mensagem = "Esta avaliação não está disponível no momento.";
            } else {
                $agora = new DateTime();
                $inicio = new DateTime($avaliacao['data_inicio']);
                $final = new DateTime($avaliacao['data_final']);

                if ($agora < $inicio) {
                    $erro_mensagem = "Esta avaliação ainda não começou. Tente novamente após " . $inicio->format('d/m/Y \à\s H:i') . ".";
                } else if ($agora > $final) {
                    $erro_mensagem = "O período para esta avaliação já foi encerrado.";
                } else {
                    $_SESSION['avaliacao_id_ativa'] = $avaliacao['id'];
                    $_SESSION['codigo_avaliacao_ativo'] = $codigo_inserido;
                    header('Location: responder_avaliacao.php');
                    exit();
                }
            }
        } catch (PDOException $e) {
            $erro_mensagem = "Ocorreu um erro no servidor. Tente novamente mais tarde.";
            error_log("Erro de validação de código: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Avaliação - AVAPM</title>
    
    <link rel="icon" href="<?php echo htmlspecialchars($favicon_path); ?>" type="image/x-icon">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --cor-primaria: #007bff;
            --cor-primaria-hover: #0056b3;
            --cor-texto-principal: #343a40;
            --cor-texto-secundario: #6c757d;
            --cor-fundo: #f4f7f6;
            --cor-erro-fundo: #f8d7da;
            --cor-erro-texto: #721c24;
            --cor-erro-borda: #f5c6cb;
        }
        html, body {
            height: 100%;
            margin: 0;
            font-family: 'Poppins', sans-serif;
            background-color: var(--cor-fundo);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            width: 100%;
            padding: 20px;
            box-sizing: border-box;
        }
        .card {
            background-color: #fff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
            width: 100%;
            max-width: 450px;
            margin: auto;
            text-align: center;
        }
        .logo {
            max-height: 70px;
            margin-bottom: 25px;
        }
        h1 {
            font-size: 26px;
            font-weight: 700;
            color: var(--cor-texto-principal);
            margin-bottom: 10px;
        }
        p {
            color: var(--cor-texto-secundario);
            margin-bottom: 30px;
        }
        .form-group {
            text-align: left;
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #555;
        }
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ced4da;
            border-radius: 8px;
            box-sizing: border-box;
            font-size: 1.2rem;
            letter-spacing: 2px;
            text-align: center;
            text-transform: uppercase;
            transition: border-color 0.3s ease;
        }
        .form-group input:focus {
            outline: none;
            border-color: var(--cor-primaria);
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.15);
        }
        .btn-submit {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 8px;
            background-color: var(--cor-primaria);
            color: white;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .btn-submit:hover {
            background-color: var(--cor-primaria-hover);
        }
        .link-voltar {
            display: block;
            margin-top: 25px;
            color: var(--cor-texto-secundario);
            text-decoration: none;
            font-size: 14px;
        }
        .link-voltar:hover {
            color: var(--cor-primaria);
            text-decoration: underline;
        }
        .alert-danger {
            background-color: var(--cor-erro-fundo);
            color: var(--cor-erro-texto);
            border: 1px solid var(--cor-erro-borda);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
    </style>
</head>
<body>

    <div class="login-container">
        <div class="card">
            <img src="<?php echo htmlspecialchars($logo_path); ?>" alt="Logo do Sistema" class="logo">
            
            <h1>Iniciar Avaliação</h1>
            <p>Insira o código fornecido para acessar.</p>

            <form action="inserir_codigo_avaliacao.php" method="POST">
                
                <?php if (!empty($erro_mensagem)): ?>
                    <div class="alert alert-danger">
                        <?php echo htmlspecialchars($erro_mensagem); ?>
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label for="codigo">Código da Avaliação</label>
                    <input type="text" id="codigo" name="codigo" placeholder="A1B2C" required maxlength="5" minlength="5" autofocus>
                </div>
                <button type="submit" class="btn-submit">Iniciar</button>
            </form>

            <a href="index.php" class="link-voltar">Voltar para a Página Inicial</a>
        </div>
    </div>

</body>
</html>
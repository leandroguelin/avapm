<?php
// cadastro.php - Página de Cadastro de Novo Usuário (Dinâmico para Aluno/Professor)

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Inclui a conexão com o banco de dados
require_once __DIR__ . '/includes/conexao.php';

// Busca configurações para usar o logo e favicon dinâmicos (se aplicável para páginas públicas)
try {
    $stmt_configs = $pdo->query("SELECT chave, valor FROM configuracoes");
    $configs = $stmt_configs->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (PDOException $e) {
    $configs = [];
}
$logo_path = $configs['logo_path'] ?? 'imagens/sistema/logo_exemplo.png'; // Ajuste o caminho se necessário
$favicon_path = $configs['favicon_path'] ?? 'imagens/sistema/favicon.ico'; // Ajuste o caminho se necessário

// Processa mensagens de feedback (ex: "Cadastro realizado com sucesso!")
$mensagem_feedback = $_SESSION['mensagem_feedback']['texto'] ?? '';
$feedback_tipo = $_SESSION['mensagem_feedback']['tipo'] ?? '';
unset($_SESSION['mensagem_feedback']);

// --- Lógica para Obter o Tipo de Cadastro e Carregar Dados para Dropdowns (se for professor) ---
$tipo_cadastro = $_GET['tipo'] ?? 'aluno'; // 'aluno' por padrão

$patentes = [];
$titulacoes = [];
$instituicoes = [];

// Só busca os dados para dropdowns se o cadastro for de professor
if ($tipo_cadastro === 'professor') {
    try {
        // Carregar Patentes (puxando sigla)
        $stmt_patentes = $pdo->query("SELECT sigla FROM patente ORDER BY sigla ASC");
        $patentes = $stmt_patentes->fetchAll(PDO::FETCH_COLUMN);

        // Carregar Titulações (puxando o nome)
        $stmt_titulacoes = $pdo->query("SELECT nome FROM titulacao ORDER BY nome ASC");
        $titulacoes = $stmt_titulacoes->fetchAll(PDO::FETCH_COLUMN);

        // Carregar Instituições (para 'instituicao' e 'fonte_pagadora')
        $stmt_instituicoes = $pdo->query("SELECT sigla FROM instituicao ORDER BY sigla ASC");
        $instituicoes = $stmt_instituicoes->fetchAll(PDO::FETCH_COLUMN);

    } catch (PDOException $e) {
        // Em caso de erro ao carregar dropdowns, define uma mensagem e loga o erro
        $mensagem_feedback = "Erro ao carregar opções para o formulário: " . $e->getMessage();
        $feedback_tipo = 'danger';
        error_log("Erro PDO ao carregar dropdowns para cadastro.php: " . $e->getMessage());
        // Não redireciona, apenas exibe a mensagem de erro no formulário
    }
}

// Recupera dados do formulário da sessão em caso de erro anterior
$form_data = $_SESSION['form_data'] ?? [];
unset($_SESSION['form_data']);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro<?php echo ($tipo_cadastro === 'professor' ? ' de Professor' : ' de Aluno'); ?> - AVAPM</title>
    <link rel="icon" href="<?php echo htmlspecialchars($favicon_path); ?>" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        /* CSS consistente com as outras páginas públicas (adapte conforme seu CSS existente) */
        :root {
            --cor-primaria: #007bff;
            --cor-primaria-hover: #0056b3;
            --cor-texto-principal: #343a40;
            --cor-texto-secundario: #6c757d;
            --cor-fundo: #f4f7f6;
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
        .cadastro-container {
            width: 100%;
            padding: 20px;
            box-sizing: border-box;
        }
        .card {
            background-color: #fff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.08);
            width: 100%;
            max-width: 500px; /* Ajuste conforme necessário */
            margin: auto;
            text-align: center;
        }
        .logo {
            max-height: 60px;
            margin-bottom: 20px;
        }
        h2 {
            font-weight: 700;
            color: var(--cor-texto-principal);
            margin-bottom: 30px;
        }
        .form-group {
            text-align: left;
            margin-bottom: 15px; /* Espaço menor entre os campos */
        }
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px; /* Espaço menor entre label e input */
            color: #555;
            font-size: 0.9rem; /* Fonte menor para labels */
        }
        .form-group input,
        .form-group select { /* Adicionado select */
            width: 100%;
            padding: 10px 12px; /* Padding menor */
            border: 1px solid #ced4da;
            border-radius: 8px;
            box-sizing: border-box;
            font-size: 1rem;
            transition: border-color 0.3s ease;
            background-color: #fff; /* Garante fundo branco para selects */
        }
        .form-group input:focus,
        .form-group select:focus { /* Adicionado select:focus */
            outline: none;
            border-color: var(--cor-primaria);
            box-shadow: 0 0 0 3px rgba(0,123,255,0.15);
        }
        .btn-cadastro {
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
            margin-top: 20px;
        }
        .btn-cadastro:hover {
            background-color: var(--cor-primaria-hover);
        }
        .links-login {
            margin-top: 20px;
            font-size: 14px;
        }
        .links-login a {
            color: var(--cor-texto-secundario);
            text-decoration: none;
        }
        .links-login a:hover {
            color: var(--cor-primaria);
            text-decoration: underline;
        }
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        /* Estilos para o footer público, se aplicável */
         .public-footer {
            margin-top: 30px;
            font-size: 0.8rem;
            color: var(--cor-texto-secundario);
        }
    </style>
</head>
<body>

    <div class="cadastro-container">
        <div class="card">
            <a href="index.php"><img src="<?php echo htmlspecialchars($logo_path); ?>" alt="Logo do Sistema" class="logo"></a>
            <h2>Crie sua Conta<?php echo ($tipo_cadastro === 'professor' ? ' de Professor' : ' de Aluno'); ?></h2>

            <?php if (!empty($mensagem_feedback)): ?>
                <div class="alert alert-<?php echo htmlspecialchars($feedback_tipo); ?>">
                    <?php echo htmlspecialchars($mensagem_feedback); ?>
                </div>
            <?php endif; ?>

            <form action="debug_cadastro_post.php" method="POST">
                <div class="form-group">
                    <label for="nome">Nome Completo:</label>
                    <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($form_data['nome'] ?? ''); ?>" required placeholder="Seu nome completo">
                </div>
                <div class="form-group">
                    <label for="cpf">CPF:</label>
                    <input type="text" id="cpf" name="cpf" value="<?php echo htmlspecialchars($form_data['cpf'] ?? ''); ?>" required placeholder="Seu CPF (somente números)" title="Por favor, insira um CPF válido com 11 dígitos.">
                </div>
                <div class="form-group">
                    <label for="email">E-mail:</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>" required placeholder="Seu melhor e-mail">
                </div>
                <div class="form-group">
                    <label for="senha">Senha:</label>
                    <input type="password" id="senha" name="senha" required placeholder="Escolha uma senha forte">
                </div>
                <div class="form-group">
                    <label for="confirmar_senha">Confirmar Senha:</label>
                    <input type="password" id="confirmar_senha" name="confirmar_senha" required placeholder="Repita sua senha">
                </div>

                <?php if ($tipo_cadastro === 'professor'): ?>
                    <!-- Campos específicos para cadastro de Professor -->
                    <div class="form-group">
                        <label for="rg">RG:</label>
                        <input type="text" id="rg" name="rg" value="<?php echo htmlspecialchars($form_data['rg'] ?? ''); ?>" required placeholder="Seu RG (somente números)">
                    </div>
                     <div class="form-group">
                        <label for="telefone">Telefone:</label>
                        <input type="text" id="telefone" name="telefone" value="<?php echo htmlspecialchars($form_data['telefone'] ?? ''); ?>" required placeholder="Seu Telefone (com DDD)">
                    </div>

                    <div class="form-group">
                        <label for="patente">Patente:</label>
                        <select id="patente" name="patente" required>
                            <option value="">Selecione a Patente</option>
                            <?php foreach ($patentes as $sigla_patente): ?>
                                <option value="<?php echo htmlspecialchars($sigla_patente); ?>"
                                    <?php echo (($form_data['patente'] ?? '') == $sigla_patente) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($sigla_patente); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="titulacao">Titulação:</label>
                        <select id="titulacao" name="titulacao" required>
                            <option value="">Selecione a Titulação</option>
                            <?php foreach ($titulacoes as $nome_titulacao): ?>
                                <option value="<?php echo htmlspecialchars($nome_titulacao); ?>"
                                    <?php echo (($form_data['titulacao'] ?? '') == $nome_titulacao) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($nome_titulacao); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="instituicao">Instituição:</label>
                         <select id="instituicao" name="instituicao" required>
                            <option value="">Selecione a Instituição</option>
                            <?php foreach ($instituicoes as $sigla_instituicao): ?>
                                <option value="<?php echo htmlspecialchars($sigla_instituicao); ?>"
                                    <?php echo (($form_data['instituicao'] ?? '') == $sigla_instituicao) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($sigla_instituicao); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="fonte_pagadora">Fonte Pagadora:</label>
                         <select id="fonte_pagadora" name="fonte_pagadora" required>
                            <option value="">Selecione a Fonte Pagadora</option>
                            <?php foreach ($instituicoes as $sigla_instituicao): ?>
                                <option value="<?php echo htmlspecialchars($sigla_instituicao); ?>"
                                    <?php echo (($form_data['fonte_pagadora'] ?? '') == $sigla_instituicao) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($sigla_instituicao); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="nome_guerra">Nome de Guerra:</label>
                        <input type="text" id="nome_guerra" name="nome_guerra" value="<?php echo htmlspecialchars($form_data['nome_guerra'] ?? ''); ?>" placeholder="Seu nome de guerra (opcional)">
                    </div>

                <?php endif; ?>


                <button type="submit" class="btn-cadastro">Cadastrar</button>
            </form>
            <div class="links-login">
                Já tem uma conta? <a href="login.php">Faça login aqui</a>
                <?php if ($tipo_cadastro !== 'professor'): ?>
                    <br>É professor? <a href="cadastro.php?tipo=professor">Cadastre-se aqui</a>
                <?php else: ?>
                     <br>É aluno? <a href="cadastro.php">Cadastre-se aqui</a>
                <?php endif; ?>
            </div>

             <?php
            // Inclui o rodapé público se existir (ajuste o caminho)
            // require_once __DIR__ . '/includes/templates/footer_public.php';
            ?>
        </div>
    </div>

    <!-- Inclua a biblioteca jQuery e jQuery Mask se ainda não estiverem incluídas -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>

    <script>
        $(document).ready(function(){
            $('#cpf').mask('000.000.000-00', {reverse: true});

            // Máscara para Telefone (10 ou 11 dígitos): (00) 00000-0000 ou (00) 0000-0000
            var SPMaskBehavior = function (val) {
                return val.replace(/\D/g, '').length === 11 ? '(00) 00000-0000' : '(00) 0000-00009';
            },
            spOptions = {
                onKeyPress: function(val, e, field, options) {
                    field.mask(SPMaskBehavior.apply({}, arguments), options);
                }
            };
            $('#telefone').mask(SPMaskBehavior, spOptions);

            // RG: Garante apenas números
             $('#rg').on('input', function() {
                this.value = this.value.replace(/\D/g, '');
            });
        });
    </script>

</body>
</html>

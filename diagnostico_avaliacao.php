<?php
// diagnostico_avaliacao.php

require_once __DIR__ . '/includes/conexao.php';

$codigo_teste = isset($_POST['codigo']) ? strtoupper(trim($_POST['codigo'])) : '';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Diagnóstico de Avaliação</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body { background-color: #f4f4f4; padding: 40px; }
        .card { max-width: 800px; margin: auto; }
        .table th { width: 35%; }
        .ok { color: green; font-weight: bold; }
        .fail { color: red; font-weight: bold; }
    </style>
</head>
<body>
    <div class="card">
        <div class="card-header">
            <h2>Ferramenta de Diagnóstico de Avaliação</h2>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <div class="form-group">
                    <label for="codigo">Insira o código da avaliação para testar:</label>
                    <input type="text" name="codigo" id="codigo" class="form-control" value="<?php echo htmlspecialchars($codigo_teste); ?>" required>
                </div>
                <button type="submit" class="btn btn-primary">Diagnosticar</button>
            </form>

            <?php if (!empty($codigo_teste)): ?>
                <hr>
                <h3>Resultados para o Código: <?php echo htmlspecialchars($codigo_teste); ?></h3>

                <?php
                try {
                    $stmt = $pdo->prepare("SELECT * FROM avaliacao WHERE codigo = :codigo");
                    $stmt->execute([':codigo' => $codigo_teste]);
                    $avaliacao = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$avaliacao) {
                        echo '<div class="alert alert-danger"><b>Erro Crítico:</b> O código não foi encontrado no banco de dados. Verifique se o código está correto.</div>';
                    } else {
                        // Pega a data e hora ATUAL do servidor PHP
                        $agora = new DateTime();
                        // Pega as datas do banco de dados
                        $inicio = new DateTime($avaliacao['data_inicio']);
                        $final = new DateTime($avaliacao['data_final']);
                        
                        // Comparações
                        $check_situacao = ($avaliacao['situacao'] === 'Ativa');
                        $check_inicio = ($agora >= $inicio);
                        $check_fim = ($agora <= $final);
                ?>
                        <table class="table table-bordered">
                            <tbody>
                                <tr>
                                    <th>Data e Hora ATUAL do Servidor</th>
                                    <td><?php echo $agora->format('d/m/Y H:i:s'); ?> (Fuso Horário: <?php echo $agora->getTimezone()->getName(); ?>)</td>
                                </tr>
                                <tr>
                                    <th>Data de Início (do Banco)</th>
                                    <td><?php echo $inicio->format('d/m/Y H:i:s'); ?></td>
                                </tr>
                                <tr>
                                    <th>Data de Fim (do Banco)</th>
                                    <td><?php echo $final->format('d/m/Y H:i:s'); ?></td>
                                </tr>
                                <tr>
                                    <th>Situação (do Banco)</th>
                                    <td><?php echo htmlspecialchars($avaliacao['situacao']); ?></td>
                                </tr>
                            </tbody>
                        </table>

                        <h4 class="mt-4">Verificação Lógica:</h4>
                        <table class="table table-bordered">
                            <thead>
                                <tr><th>Condição</th><th>Resultado</th><th>Esperado</th></tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Situação é "Ativa"?</td>
                                    <td class="<?php echo $check_situacao ? 'ok' : 'fail'; ?>"><?php echo $check_situacao ? 'SIM' : 'NÃO'; ?></td>
                                    <td class="ok">SIM</td>
                                </tr>
                                <tr>
                                    <td>Hora Atual >= Hora de Início?</td>
                                    <td class="<?php echo $check_inicio ? 'ok' : 'fail'; ?>"><?php echo $check_inicio ? 'SIM' : 'NÃO'; ?></td>
                                    <td class="ok">SIM</td>
                                </tr>
                                <tr>
                                    <td>Hora Atual <= Hora de Fim?</td>
                                    <td class="<?php echo $check_fim ? 'ok' : 'fail'; ?>"><?php echo $check_fim ? 'SIM' : 'NÃO'; ?></td>
                                    <td class="ok">SIM</td>
                                </tr>
                            </tbody>
                        </table>

                        <div class="alert alert-info mt-4">
                            <h5>Conclusão:</h5>
                            <?php
                                if (!$check_situacao) {
                                    echo "O problema é a <b>Situação</b>. O sistema está lendo um valor diferente de 'Ativa' no banco de dados.";
                                } else if (!$check_inicio) {
                                    echo "O problema é a <b>Data de Início</b>. A hora atual do servidor é ANTERIOR à data de início registrada.";
                                } else if (!$check_fim) {
                                    echo "O problema é a <b>Data de Fim</b>. A hora atual do servidor é POSTERIOR à data de fim registrada. Isso pode ser um problema de fuso horário.";
                                } else {
                                    echo "A lógica está correta. Se o erro persiste, pode ser um problema de cache ou de redirecionamento na página `inserir_codigo_avaliacao.php`.";
                                }
                            ?>
                        </div>
                <?php
                    }
                } catch (PDOException $e) {
                    echo '<div class="alert alert-danger"><b>Erro de Conexão:</b> Não foi possível conectar ao banco de dados. Detalhes: ' . $e->getMessage() . '</div>';
                }
                ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
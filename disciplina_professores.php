php
<?php
// htdocs/avapm/disciplina_professores.php

if (session_status() == PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/includes/conexao.php';
require_once __DIR__ . '/includes/seguranca.php';

// Verificar se o usuário tem permissão para acessar esta página
// require_once __DIR__ . '/includes/seguranca.php'; // Assumindo que seguranca.php lida com isso

// IDs das disciplinas para filtro (agora pode ser um array)
$filtro_disciplina_ids_selecionados = isset($_GET['disciplina_ids']) && is_array($_GET['disciplina_ids']) ? $_GET['disciplina_ids'] : [];
$filtro_disciplina_nome_digitado = isset($_GET['nome_disciplina']) ? $_GET['nome_disciplina'] : null; // Manter por enquanto, caso necessário para compatibilidade

// --- Lógica de Exportação ---
if (isset($_GET['export'])) {
    $export_format = $_GET['export'];

    // Re-executar a consulta para a exportação, garantindo que os dados sejam os mesmos da exibição
    $sql_export = "SELECT
                       u.patente,
                       u.nome,
                       u.titulacao,
                       u.telefone,
                       d.nome AS nome_disciplina,
                       md.disponibilidade
                   FROM
                       minhas_disciplinas md
                   JOIN
                       usuario u ON md.usuario_id = u.id
                   JOIN
                       disciplina d ON md.disciplina_id = d.id";

    $parametros_sql_export = [];
    $where_clauses_export = [];

    // Adicionar filtro por IDs das disciplinas (do Select2, agora permite múltiplos)
    if (!empty($filtro_disciplina_ids_selecionados)) {
 // Se for um array de IDs, usar IN
 $placeholders = implode(',', array_fill(0, count($filtro_disciplina_ids_selecionados), '?'));
            $where_clauses_export[] = "md.disciplina_id IN ($placeholders)";
 $parametros_sql_export = array_merge($parametros_sql_export, $filtro_disciplina_ids_selecionados);
    }


    // Adicionar filtro por nome da disciplina (do campo de texto anterior, se ainda relevante)
    /*
    if ($filtro_disciplina_nome_digitado && $filtro_disciplina_nome_digitado !== '') {
        $where_clauses_export[] = "d.nome ILIKE ?"; // Use ILIKE para case-insensitive no PostgreSQL
        $parametros_sql_export[] = '%' . $filtro_disciplina_nome_digitado . '%';
    }
    */


    if (!empty($where_clauses_export)) {
 $sql_export .= " WHERE " . implode(" AND ", $where_clauses_export);
    }


    $sql_export .= " ORDER BY u.nome, d.nome";

    try {
 $stmt_export = $pdo->prepare($sql_export);
        $stmt_export->execute($parametros_sql_export);
 $resultados_export = $stmt_export->fetchAll(PDO::FETCH_ASSOC);

        $filename = 'professores_disciplinas';
        if (!empty($filtro_disciplina_ids_selecionados)) {
 // Buscar nome(s) da(s) disciplina(s) para o nome do arquivo (se filtrado)
                $placeholders = implode(',', array_fill(0, count($filtro_disciplina_id), '?'));
                $stmt_disciplina_nome = $pdo->prepare("SELECT nome FROM disciplina WHERE id IN ($placeholders) ORDER BY nome");
 $stmt_disciplina_nome->execute($filtro_disciplina_id);
 $nomes_disciplinas_filtro_export = $stmt_disciplina_nome->fetchAll(PDO::FETCH_COLUMN);
 if ($nome_disciplina_filtro_export) {
 $filename .= '_' . str_replace(' ', '_', $nome_disciplina_filtro_export);
 }
 }
        }
        $filename .= '_' . date('Ymd');


        switch ($export_format) {
            case 'html':
 header('Content-Type: text/html');
                header('Content-Disposition: attachment; filename="' . $filename . '.html"');
                echo '<html><head><title>Relatório de Professores por Disciplina</title><style>table, th, td { border: 1px solid black; border-collapse: collapse; padding: 8px; }</style></head><body>';
 echo '<h2>Relatório de Professores por Disciplina</h2>';
                if (!empty($filtro_disciplina_id)) {
 if (is_array($filtro_disciplina_id)) {
                        echo '<p>Filtrado por Disciplinas: ' . htmlspecialchars(implode(', ', $nomes_disciplinas_filtro_export)) . '</p>';
 } else {
 echo '<p>Filtrado por Disciplina: ' . htmlspecialchars($nome_disciplina_filtro_export) . '</p>';
 }
                }
                echo '<table><thead><tr><th>Patente</th><th>Nome</th><th>Titulação</th><th>Telefone</th><th>Disciplina</th><th>Disponibilidade</th></tr></thead><tbody>';
                foreach ($resultados_export as $row) {
 echo '<tr>';
                    echo '<td>' . htmlspecialchars($row['patente']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['nome']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['titulacao']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['telefone']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['nome_disciplina']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['disponibilidade']) . '</td>';
 echo '</tr>';
                }
                echo '</tbody></table></body></html>';
 break;

            case 'csv':
 header('Content-Type: text/csv');
                header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
 $output = fopen('php://output', 'w');
 fputcsv($output, ['Patente', 'Nome', 'Titulação', 'Telefone', 'Disciplina', 'Disponibilidade']);
                foreach ($resultados_export as $row) {
 fputcsv($output, $row);
                }
 fclose($output);
 break;

            case 'pdf':
                require(__DIR__ . '/includes/fpdf/fpdf.php'); // Corrigido o caminho

                class PDF extends FPDF
 {
                    // Cabeçalho da página
 function Header()
 {
                        // Logo (se tiver) - ajuste o caminho e as coordenadas
                        // $this->Image('caminho/para/sua/logo.png', 10, 6, 30);
 $this->SetFont('Arial', 'B', 15);
                        // Move right
 $this->Cell(80);
                        // Título
 $this->Cell(30, 10, 'Relatório de Professores por Disciplina', 0, 0, 'C');
                        // Line break
 $this->Ln(20);
 }

                    // Rodapé da página
 function Footer()
 {
                        // Position at 1.5 cm from bottom
 $this->SetY(-15);
                        // Arial italic 8
 $this->SetFont('Arial', 'I', 8);
                        // Page number
 $this->Cell(0, 10, 'Página ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
 }

                    // Tabela colorida
 function FancyTable($header, $data)
 {
                        // Colors, line width and bold font
 $this->SetFillColor(230, 230, 230); // Cor de fundo para o cabeçalho
 $this->SetTextColor(0);
 $this->SetDrawColor(128, 128, 128);
 $this->SetLineWidth(.3);
 $this->SetFont('', 'B');
                        // Header
 $w = array(20, 50, 30, 30, 40, 30); // Larguras das colunas - ajuste conforme necessário
 for ($i = 0; $i < count($header); $i++)
 $this->Cell($w[$i], 7, $header[$i], 1, 0, 'C', true);
 $this->Ln();
                        // Color and font restoration
 $this->SetFillColor(224, 235, 255);
 $this->SetTextColor(0);
 $this->SetFont('');
                        // Data
 $fill = false;
                        foreach ($data as $row) {
 $this->Cell($w[0], 6, $row['patente'], 'LR', 0, 'L', $fill);
 $this->Cell($w[1], 6, $row['nome'], 'LR', 0, 'L', $fill);
 $this->Cell($w[2], 6, $row['titulacao'], 'LR', 0, 'L', $fill);
 $this->Cell($w[3], 6, $row['telefone'], 'LR', 0, 'L', $fill);
 $this->Cell($w[4], 6, $row['nome_disciplina'], 'LR', 0, 'L', $fill);
 $this->Cell($w[5], 6, $row['disponibilidade'], 'LR', 0, 'L', $fill);
 $this->Ln();
 $fill = !$fill;
                        }
                        // Closing line
 $this->Cell(array_sum($w), 0, '', 'T');
 }
 }

                // Instanciação e configurações básicas
 $pdf = new PDF();
 $pdf->AliasNbPages();
 $pdf->AddPage();
 $pdf->SetFont('Arial', '', 12);

                // Cabeçalho da tabela no PDF
 $header = array('Patente', 'Nome', 'Titulação', 'Telefone', 'Disciplina', 'Disponibilidade');

                // Adiciona a tabela ao PDF
 $pdf->FancyTable($header, $resultados_export);

                // Output do PDF
 $pdf->Output('D', $filename . '.pdf'); // 'D' força o download

 break;

            default:
                // Formato não suportado
 die("Formato de exportação não suportado.");
        }
 exit; // Importante para parar a execução após a exportação
    } catch (PDOException $e) {
 error_log("Erro na exportação: " . $e->getMessage());
 die("Ocorreu um erro durante a exportação.");
    }
}
// --- Fim Lógica de Exportação ---


// Consulta para buscar disciplinas para o filtro Select2
$sql_disciplinas = "SELECT id, nome FROM disciplina ORDER BY nome";
try {
    $stmt_disciplinas = $pdo->query($sql_disciplinas);
    $disciplinas = $stmt_disciplinas->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erro ao buscar disciplinas para filtro: " . $e->getMessage());
    $disciplinas = []; // Retorna um array vazio em caso de erro
}

// Obter os IDs das disciplinas selecionadas do GET, esperando um array
$filtro_disciplina_ids_selecionados = isset($_GET['disciplina_ids']) && is_array($_GET['disciplina_ids']) ? $_GET['disciplina_ids'] : [];


// Consulta principal para buscar professores e disciplinas
$sql = "SELECT
            u.patente,
            u.nome,
            u.titulacao,
            u.telefone,
            d.nome AS nome_disciplina,
            md.disponibilidade
        FROM
            minhas_disciplinas md
        JOIN
            usuario u ON md.usuario_id = u.id
        JOIN
            disciplina d ON md.disciplina_id = d.id";

$parametros_sql = [];
$where_clauses = [];

/*
// Adicionar filtro por ID da disciplina (do Select2) - Lógica anterior para um único ID
if ($filtro_disciplina_id && $filtro_disciplina_id !== '') {
    $where_clauses[] = "md.disciplina_id = ?";
    $parametros_sql[] = $filtro_disciplina_id;
}
*/

// Adicionar filtro por IDs das disciplinas selecionadas (usando IN)
if (!empty($filtro_disciplina_ids_selecionados)) {
    // Construir a string de placeholders (?) dinamicamente para a cláusula IN
    $placeholders = implode(',', array_fill(0, count($filtro_disciplina_ids_selecionados), '?'));
    $where_clauses[] = "md.disciplina_id IN ($placeholders)";
    // Adicionar os IDs selecionados aos parâmetros da consulta
    $parametros_sql = array_merge($parametros_sql, $filtro_disciplina_ids_selecionados);
}




/*
// Adicionar filtro por nome da disciplina (do campo de texto anterior, caso ainda relevante para compatibilidade)
if ($filtro_disciplina_nome_digitado && $filtro_disciplina_nome_digitado !== '') {
    $where_clauses[] = "d.nome ILIKE ?"; // Use ILIKE para case-insensitive no PostgreSQL
    $parametros_sql[] = '%' . $filtro_disciplina_nome_digitado . '%';
}
*/


if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
    }


    $sql .= " ORDER BY u.nome, d.nome";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($parametros_sql);
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erro ao buscar professores e disciplinas: " . $e->getMessage());
        $resultados = []; // Retorna um array vazio em caso de erro
    }

    // --- Variáveis para o template do header e sidebar ---
    $page_title = "Professores por Disciplina";
    // Se houver outras variáveis que header/sidebar esperam, defina-as aqui

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - AVAPM</title>
    <link rel="stylesheet" href="css/style.css">
    <!-- Inclua aqui os links para as bibliotecas de ícones (Font Awesome, etc.) se estiver usando -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Incluir CSS do Select2 - Verifique o caminho correto no seu projeto -->
    <link href="/avapm/caminho/para/select2/css/select2.min.css" rel="stylesheet" />

</head>
<body class="dashboard-page">

    <?php
        // Inclusão correta dos templates usando __DIR__
        require_once __DIR__ . '/includes/templates/sidebar_dashboard.php';
        require_once __DIR__ . '/includes/templates/header_dashboard.php';
    ?>

    <div class="main-content-dashboard">

        <div class="dashboard-section">
            <div class="section-header">
                 <h2>Consulta e Exportação</h2>
            </div>

            <!-- Formulário de Filtro com Select2 -->
            <form method="GET" action="/avapm/disciplina_professores.php" class="form-dashboard">
                <div class="form-group">
                    <label for="filtro_disciplina_select2">Filtrar por Disciplinas:</label>
                    <select name="disciplina_ids[]" id="filtro_disciplina_select2" class="form-control" multiple="multiple">
                        <!-- Remover a opção "Todas as Disciplinas" em um select múltiplo -->
                        <?php foreach ($disciplinas as $disciplina): ?>
                            <option value="<?php echo htmlspecialchars($disciplina['id']); ?>"
                                <?php echo in_array($disciplina['id'], $filtro_disciplina_ids_selecionados) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($disciplina['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn-primary-dashboard">Aplicar Filtro</button>
                 <a href="/avapm/disciplina_professores.php" class="btn-secondary-dashboard">Limpar Filtro</a>
            </form>

            <!-- Opções de Exportação -->
            <div class="export-options" style="margin-top: 20px;">
                <h3 style="margin-bottom: 10px;">Exportar Dados:</h3>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'html'])); ?>" class="btn-primary-dashboard"><i class="fas fa-file-code"></i> Exportar HTML</a>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'csv'])); ?>" class="btn-primary-dashboard"><i class="fas fa-file-csv"></i> Exportar CSV</a>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'pdf'])); ?>" class="btn-primary-dashboard"><i class="fas fa-file-pdf"></i> Exportar PDF</a>
            </div>


            <!-- Tabela de Resultados -->
            <div class="table-responsive dashboard-section">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Patente</th>
                            <th>Nome</th>
                            <th>Titulação</th>
                            <th>Telefone</th>
                            <th>Disciplina</th>
                            <th>Disponibilidade</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($resultados)): ?>
                            <?php foreach ($resultados as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['patente']); ?></td>
                                    <td><?php echo htmlspecialchars($row['nome']); ?></td>
                                    <td><?php echo htmlspecialchars($row['titulacao']); ?></td>
                                    <td><?php echo htmlspecialchars($row['telefone']) . (isset($row['whatsapp']) && $row['whatsapp'] ? ' <i class="fab fa-whatsapp text-success"></i>' : ''); ?></td>
                                    <td><?php echo htmlspecialchars($row['nome_disciplina']); ?></td>
                                    <td><?php echo htmlspecialchars($row['disponibilidade']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center;">Nenhum professor encontrado para as disciplinas selecionadas.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>

    <?php require_once __DIR__ . '/includes/templates/footer_dashboard.php'; ?>

    <!-- Inclusão dos Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Incluir JS do Select2 - Verifique o caminho correto -->
    <script src="/avapm/caminho/para/select2/js/select2.min.js"></script>
    <!-- Inclua outros scripts JavaScript aqui, se houver -->
    <!-- Incluir seu script.js por último -->
    <script src="/avapm/js/script.js"></script>
     <!-- Se houver um script de alternância da sidebar em script.js, ele será executado aqui.
          Se o problema de travamento persistir, talvez seja necessário rever o script.js
          ou a interação entre ele e os scripts do dashboard/bootstrap. -->


</body>
<script>
    $(document).ready(function() {
        $('#filtro_disciplina_select2').select2({
            placeholder: "Selecione as disciplinas", // Texto de placeholder para múltipla seleção
            allowClear: true,
            // Para select múltiplo, a busca por padrão já funciona filtrando as opções existentes.
            // Não precisamos da configuração AJAX aqui, pois não buscaremos dinamicamente.
            // Removeremos as opções AJAX.
        });
    });
</script>
</html>
        $parametros_sql_export[] = $filtro_disciplina_id;
    }

    // Adicionar filtro por nome da disciplina (do campo de texto anterior, se ainda relevante)
    /*
    if ($filtro_disciplina_nome_digitado && $filtro_disciplina_nome_digitado !== '') {
        $where_clauses_export[] = "d.nome ILIKE ?"; // Use ILIKE para case-insensitive no PostgreSQL
        $parametros_sql_export[] = '%' . $filtro_disciplina_nome_digitado . '%';
    }
    */


    if (!empty($where_clauses_export)) {
        $sql_export .= " WHERE " . implode(" AND ", $where_clauses_export);
    }


    $sql_export .= " ORDER BY u.nome, d.nome";

    try {
        $stmt_export = $pdo->prepare($sql_export);
        $stmt_export->execute($parametros_sql_export);
        $resultados_export = $stmt_export->fetchAll(PDO::FETCH_ASSOC);

        $filename = 'professores_disciplinas';
        if ($filtro_disciplina_id && $filtro_disciplina_id !== '') {
             // Buscar nome da disciplina para o nome do arquivo, se filtrado
             $stmt_disciplina_nome = $pdo->prepare("SELECT nome FROM disciplina WHERE id = ?");
             $stmt_disciplina_nome->execute([$filtro_disciplina_id]);
             $nome_disciplina_filtro_export = $stmt_disciplina_nome->fetchColumn();
             if ($nome_disciplina_filtro_export) {
                 $filename .= '_' . str_replace(' ', '_', $nome_disciplina_filtro_export);
             }
        }
        $filename .= '_' . date('Ymd');


        switch ($export_format) {
            case 'html':
                header('Content-Type: text/html');
                header('Content-Disposition: attachment; filename="' . $filename . '.html"');
                echo '<html><head><title>Relatório de Professores por Disciplina</title><style>table, th, td { border: 1px solid black; border-collapse: collapse; padding: 8px; }</style></head><body>';
                echo '<h2>Relatório de Professores por Disciplina</h2>';
                if ($filtro_disciplina_id && $filtro_disciplina_id !== '') {
                     echo '<p>Filtrado por Disciplina: ' . htmlspecialchars($nome_disciplina_filtro_export) . '</p>';
                }
                echo '<table><thead><tr><th>Patente</th><th>Nome</th><th>Titulação</th><th>Telefone</th><th>Disciplina</th><th>Disponibilidade</th></tr></thead><tbody>';
                foreach ($resultados_export as $row) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($row['patente']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['nome']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['titulacao']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['telefone']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['nome_disciplina']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['disponibilidade']) . '</td>';
                    echo '</tr>';
                }
                echo '</tbody></table></body></html>';
                break;

            case 'csv':
                header('Content-Type: text/csv');
                header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
                $output = fopen('php://output', 'w');
                fputcsv($output, ['Patente', 'Nome', 'Titulação', 'Telefone', 'Disciplina', 'Disponibilidade']);
                foreach ($resultados_export as $row) {
                    fputcsv($output, $row);
                }
                fclose($output);
                break;

            case 'pdf':
                require(__DIR__ . '/includes/fpdf/fpdf.php'); // Corrigido o caminho

                class PDF extends FPDF
                {
                    // Cabeçalho da página
                    function Header()
                    {
                        // Logo (se tiver) - ajuste o caminho e as coordenadas
                        // $this->Image('caminho/para/sua/logo.png', 10, 6, 30);
                        $this->SetFont('Arial', 'B', 15);
                        // Move right
                        $this->Cell(80);
                        // Título
                        $this->Cell(30, 10, 'Relatório de Professores por Disciplina', 0, 0, 'C');
                        // Line break
                        $this->Ln(20);
                    }

                    // Rodapé da página
                    function Footer()
                    {
                        // Position at 1.5 cm from bottom
                        $this->SetY(-15);
                        // Arial italic 8
                        $this->SetFont('Arial', 'I', 8);
                        // Page number
                        $this->Cell(0, 10, 'Página ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
                    }

                    // Tabela colorida
                    function FancyTable($header, $data)
                    {
                        // Colors, line width and bold font
                        $this->SetFillColor(230, 230, 230); // Cor de fundo para o cabeçalho
                        $this->SetTextColor(0);
                        $this->SetDrawColor(128, 128, 128);
                        $this->SetLineWidth(.3);
                        $this->SetFont('', 'B');
                        // Header
                        $w = array(20, 50, 30, 30, 40, 30); // Larguras das colunas - ajuste conforme necessário
                        for ($i = 0; $i < count($header); $i++)
                            $this->Cell($w[$i], 7, $header[$i], 1, 0, 'C', true);
                        $this->Ln();
                        // Color and font restoration
                        $this->SetFillColor(224, 235, 255);
                        $this->SetTextColor(0);
                        $this->SetFont('');
                        // Data
                        $fill = false;
                        foreach ($data as $row) {
                            $this->Cell($w[0], 6, $row['patente'], 'LR', 0, 'L', $fill);
                            $this->Cell($w[1], 6, $row['nome'], 'LR', 0, 'L', $fill);
                            $this->Cell($w[2], 6, $row['titulacao'], 'LR', 0, 'L', $fill);
                            $this->Cell($w[3], 6, $row['telefone'], 'LR', 0, 'L', $fill);
                            $this->Cell($w[4], 6, $row['nome_disciplina'], 'LR', 0, 'L', $fill);
                            $this->Cell($w[5], 6, $row['disponibilidade'], 'LR', 0, 'L', $fill);
                            $this->Ln();
                            $fill = !$fill;
                        }
                        // Closing line
                        $this->Cell(array_sum($w), 0, '', 'T');
                    }
                }

                // Instanciação e configurações básicas
                $pdf = new PDF();
                $pdf->AliasNbPages();
                $pdf->AddPage();
                $pdf->SetFont('Arial', '', 12);

                // Cabeçalho da tabela no PDF
                $header = array('Patente', 'Nome', 'Titulação', 'Telefone', 'Disciplina', 'Disponibilidade');

                // Adiciona a tabela ao PDF
                $pdf->FancyTable($header, $resultados_export);

                // Output do PDF
                $pdf->Output('D', $filename . '.pdf'); // 'D' força o download

                break;

            default:
                // Formato não suportado
                die("Formato de exportação não suportado.");
        }
        exit; // Importante para parar a execução após a exportação
    } catch (PDOException $e) {
        error_log("Erro na exportação: " . $e->getMessage());
        die("Ocorreu um erro durante a exportação.");
    }
}
// --- Fim Lógica de Exportação ---


// Consulta para buscar disciplinas para o filtro Select2
$sql_disciplinas = "SELECT id, nome FROM disciplina ORDER BY nome";
try {
    $stmt_disciplinas = $pdo->query($sql_disciplinas);
    $disciplinas = $stmt_disciplinas->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erro ao buscar disciplinas para filtro: " . $e->getMessage());
    $disciplinas = []; // Retorna um array vazio em caso de erro
}


// Consulta principal para buscar professores e disciplinas
$sql = "SELECT
            u.patente,
            u.nome,
            u.titulacao,
            u.telefone,
            d.nome AS nome_disciplina,
            md.disponibilidade
        FROM
            minhas_disciplinas md
        JOIN
            usuario u ON md.usuario_id = u.id
        JOIN
            disciplina d ON md.disciplina_id = d.id";

$parametros_sql = [];
$where_clauses = [];

// Adicionar filtro por ID da disciplina (do Select2)
if ($filtro_disciplina_id && $filtro_disciplina_id !== '') {
    $where_clauses[] = "md.disciplina_id = ?";
    $parametros_sql[] = $filtro_disciplina_id;
}

// Adicionar filtro por nome da disciplina (do campo de texto anterior, caso ainda relevante para compatibilidade)
/*
if ($filtro_disciplina_nome_digitado && $filtro_disciplina_nome_digitado !== '') {
    $where_clauses[] = "d.nome ILIKE ?"; // Use ILIKE para case-insensitive no PostgreSQL
    $parametros_sql[] = '%' . $filtro_disciplina_nome_digitado . '%';
}
*/


if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
}


$sql .= " ORDER BY u.nome, d.nome";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($parametros_sql);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erro ao buscar professores e disciplinas: " . $e->getMessage());
    $resultados = []; // Retorna um array vazio em caso de erro
}

// --- Variáveis para o template do header e sidebar ---
$page_title = "Professores por Disciplina";
// Se houver outras variáveis que header/sidebar esperam, defina-as aqui

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - AVAPM</title>
    <link rel="stylesheet" href="css/style.css">
    <!-- Inclua aqui os links para as bibliotecas de ícones (Font Awesome, etc.) se estiver usando -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Incluir CSS do Select2 - Verifique o caminho correto no seu projeto -->
    <link href="caminho/para/select2/css/select2.min.css" rel="stylesheet" />

</head>
<body class="dashboard-page">

    <?php
        // Inclusão correta dos templates usando __DIR__
        require_once __DIR__ . '/includes/templates/sidebar_dashboard.php';
        require_once __DIR__ . '/includes/templates/header_dashboard.php';
    ?>

    <div class="main-content-dashboard">

        <div class="dashboard-section">
            <div class="section-header">
                 <h2>Consulta e Exportação</h2>
            </div>

            <!-- Formulário de Filtro com Select2 -->
            <form method="GET" action="disciplina_professores.php" class="form-dashboard">
                <div class="form-group">
                    <label for="filtro_disciplina_select2">Filtrar por Disciplina:</label>
                    <select name="disciplina_id" id="filtro_disciplina_select2" class="form-control">
                        <option value="">Todas as Disciplinas</option>
                        <?php foreach ($disciplinas as $disciplina): ?>
                            <option value="<?php echo htmlspecialchars($disciplina['id']); ?>"
                                <?php echo ($filtro_disciplina_id == $disciplina['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($disciplina['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn-primary-dashboard">Aplicar Filtro</button>
                 <a href="disciplina_professores.php" class="btn-secondary-dashboard">Limpar Filtro</a>
            </form>

            <!-- Opções de Exportação -->
            <div class="export-options" style="margin-top: 20px;">
                <h3 style="margin-bottom: 10px;">Exportar Dados:</h3>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'html'])); ?>" class="btn-primary-dashboard"><i class="fas fa-file-code"></i> Exportar HTML</a>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'csv'])); ?>" class="btn-primary-dashboard"><i class="fas fa-file-csv"></i> Exportar CSV</a>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'pdf'])); ?>" class="btn-primary-dashboard"><i class="fas fa-file-pdf"></i> Exportar PDF</a>
            </div>


            <!-- Tabela de Resultados -->
            <div class="table-responsive dashboard-section">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Patente</th>
                            <th>Nome</th>
                            <th>Titulação</th>
                            <th>Telefone</th>
                            <th>Disciplina</th>
                            <th>Disponibilidade</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($resultados)): ?>
                            <?php foreach ($resultados as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['patente']); ?></td>
                                    <td><?php echo htmlspecialchars($row['nome']); ?></td>
                                    <td><?php echo htmlspecialchars($row['titulacao']); ?></td>
                                    <td><?php echo htmlspecialchars($row['telefone']); ?></td>
                                    <td><?php echo htmlspecialchars($row['nome_disciplina']); ?></td>
                                    <td><?php echo htmlspecialchars($row['disponibilidade']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center;">Nenhum professor encontrado para esta disciplina ou filtro.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>

    <?php require_once __DIR__ . '/includes/templates/footer_dashboard.php'; ?>

    <!-- Inclusão dos Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Incluir JS do Select2 - Verifique o caminho correto -->
    <script src="caminho/para/select2/js/select2.min.js"></script>
    <!-- Inclua outros scripts JavaScript aqui, se houver -->
    <!-- Incluir seu script.js por último -->
    <script src="js/script.js"></script>
     <!-- Se houver um script de alternância da sidebar em script.js, ele será executado aqui.
          Se o problema de travamento persistir, talvez seja necessário rever o script.js
          ou a interação entre ele e os scripts do dashboard/bootstrap. -->


</body>
<script>
    $(document).ready(function() {
        $('#filtro_disciplina_select2').select2({
            placeholder: "Selecione ou digite a disciplina",
            allowClear: true,
            minimumInputLength: 0, // Pode ajustar este valor para, por exemplo, 3 para começar a buscar após 3 caracteres
            minimumResultsForSearch: 1,
            ajax: {
                url: 'avapm/get_disciplinas_ajax.php', // Caminho absoluto ou relativo para o endpoint AJAX
                dataType: 'json',
                delay: 250, // Pequeno atraso para não sobrecarregar o servidor enquanto o usuário digita
                data: function (params) {
                    return {
                        q: params.term // Termo de busca digitado pelo usuário
                    };
                },
                processResults: function (data) {
                    // O formato esperado pelo Select2 é { results: [{ id: 1, text: 'Nome Disciplina' }, ...] }
                    return {
                        results: data // Assume que o seu endpoint retorna os dados no formato correto
                    };
                },
                cache: true // Opcional: armazena resultados em cache
            }
        });
    });
</script>
</html>

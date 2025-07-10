php
<?php
require_once 'includes/conexao.php';
require_once 'includes/seguranca.php';

// Incluir a biblioteca FPDF para exportação em PDF
// Verificar se o usuário tem permissão (exemplo: apenas admin ou professor)
// require_once 'includes/seguranca.php'; // Assumindo que seguranca.php lida com isso

// ID da disciplina para filtro
$filtro_disciplina_id = isset($_GET['disciplina_id']) ? $_GET['disciplina_id'] : null;

// Consulta para buscar disciplinas para o filtro
$sql_disciplinas = "SELECT id, nome FROM disciplina ORDER BY nome";
$result_disciplinas = $conn->query($sql_disciplinas);
$disciplinas = [];
if ($result_disciplinas->num_rows > 0) {
    while ($row = $result_disciplinas->fetch_assoc()) {
        $disciplinas[] = $row;
    }
}

// Consulta principal para buscar professores e disciplinas
// Reexecutar a consulta para a exportação para garantir que os dados sejam os mesmos da exibição
// Pode ser otimizado para evitar reexecução se a lógica de exportação for separada
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

// Adicionar filtro se um disciplina_id for fornecido
if ($filtro_disciplina_id && $filtro_disciplina_id !== '') {
    $sql .= " WHERE md.disciplina_id = " . $conn->real_escape_string($filtro_disciplina_id);
}

$sql .= " ORDER BY u.nome, d.nome";

$result = $conn->query($sql);


// --- Lógica de Exportação ---
if (isset($_GET['export'])) {
    $export_format = $_GET['export'];

    // Reexecutar a consulta para a exportação
    $export_result = $conn->query($sql);
    $data_to_export = [];
    if ($export_result->num_rows > 0) {
        while ($row = $export_result->fetch_assoc()) {
            $data_to_export[] = $row;
        }
    }

    switch ($export_format) {
        case 'html':
            header('Content-Type: text/html');
            header('Content-Disposition: attachment; filename="professores_disciplinas.html"');
            
            echo "<html><head><title>Professores por Disciplina</title><style>table, th, td { border: 1px solid black; border-collapse: collapse; padding: 8px; }</style></head><body>";
            echo "<h2>Professores por Disciplina</h2>";
            echo "<table>";
            echo "<thead><tr><th>Patente</th><th>Nome</th><th>Titulação</th><th>Telefone</th><th>Disciplina</th><th>Disponibilidade</th></tr></thead>";
            echo "<tbody>";
            foreach ($data_to_export as $row) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['patente']) . "</td>";
                echo "<td>" . htmlspecialchars($row['nome']) . "</td>";
                echo "<td>" . htmlspecialchars($row['titulacao']) . "</td>";
                echo "<td>" . htmlspecialchars($row['telefone']) . "</td>";
                echo "<td>" . htmlspecialchars($row['nome_disciplina']) . "</td>";
                echo "<td>" . htmlspecialchars($row['disponibilidade']) . "</td>";
                echo "</tr>";
            }
            echo "</tbody>";
            echo "</table>";
            echo "</body></html>";
            exit;

        case 'csv':
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="professores_disciplinas.csv"');

            $output = fopen('php://output', 'w');

            // Cabeçalho do CSV
            fputcsv($output, ['Patente', 'Nome', 'Titulação', 'Telefone', 'Disciplina', 'Disponibilidade']);

            // Dados
            foreach ($data_to_export as $row) {
                fputcsv($output, $row);
            }

            fclose($output);
            exit;

        case 'pdf':
            require('includes/fpdf/fpdf.php'); // Incluir a biblioteca FPDF

            class PDF extends FPDF
            {
                // Cabeçalho
                function Header()
                {
                    $this->SetFont('Arial', 'B', 12);
                    $this->Cell(0, 10, 'Relatório de Professores por Disciplina', 0, 1, 'C');
                    $this->Ln(10);
                }

                // Rodapé
                function Footer()
                {
                    $this->SetY(-15);
                    $this->SetFont('Arial', 'I', 8);
                    $this->Cell(0, 10, 'Página ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
                }
            }

            // Criação do objeto PDF
            $pdf = new PDF();
            $pdf->AliasNbPages();
            $pdf->AddPage();
            $pdf->SetFont('Arial', '', 10);

            // Títulos das colunas
            $header = ['Patente', 'Nome', 'Titulação', 'Telefone', 'Disciplina', 'Disponibilidade'];
            // Larguras das colunas
            $w = [20, 50, 30, 30, 40, 30]; // Ajuste conforme necessário

            // Cabeçalho da tabela
            for ($i = 0; $i < count($header); $i++) {
                $pdf->Cell($w[$i], 7, $header[$i], 1, 0, 'C');
            }
            $pdf->Ln();

            // Dados da tabela
            foreach ($data_to_export as $row) {
                $pdf->Cell($w[0], 6, $row['patente'], 1);
                $pdf->Cell($w[1], 6, $row['nome'], 1);
                $pdf->Cell($w[2], 6, $row['titulacao'], 1);
                $pdf->Cell($w[3], 6, $row['telefone'], 1);
                $pdf->Cell($w[4], 6, $row['nome_disciplina'], 1);
                $pdf->Cell($w[5], 6, $row['disponibilidade'], 1);
                $pdf->Ln();
            }

            $pdf->Output('D', 'professores_disciplinas.pdf'); // 'D' para download, 'I' para exibir no navegador
            exit;

        default:
            // Formato de exportação inválido
            break;
    }
}

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Professores por Disciplina</title>
    <link rel="stylesheet" href="css/style.css">
    <!-- Adicione aqui outros links para CSS, como Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="dashboard-page">
    <?php // Incluir a sidebar
          // include 'includes/templates/sidebar_dashboard.php'; // Substitua pelo seu arquivo de sidebar real
    ?>

    <div class="main-content-dashboard">
        <div class="dashboard-header">
            <h1>Professores por Disciplina</h1>
            <?php // Incluir informações do usuário logado, se necessário
                  // include 'includes/templates/header_dashboard.php'; // Substitua pelo seu arquivo de header real
            ?>
        </div>

        <div class="dashboard-section">
            <div class="section-header">
                <h2>Lista de Professores e Disciplinas</h2>
                </div>

            <form method="GET" action="">
                <div class="form-group">
                    <label for="disciplina_id">Filtrar por Disciplina:</label>
                    <select name="disciplina_id" id="disciplina_id" onchange="this.form.submit()">
                        <option value="">Todas as Disciplinas</option>
                        <?php foreach ($disciplinas as $disciplina): ?>
                            <option value="<?php echo $disciplina['id']; ?>" <?php echo ($filtro_disciplina_id == $disciplina['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($disciplina['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>

            <div class="table-responsive">
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
                        <?php
                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($row['patente']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['nome']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['titulacao']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['telefone']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['nome_disciplina']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['disponibilidade']) . "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6'>Nenhum professor encontrado para esta disciplina.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <?php /* TODO: Adicionar botões/links de exportação aqui */ ?>
            <div class="export-buttons" style="margin-top: 20px;">
                <?php $current_url = strtok($_SERVER["REQUEST_URI"], '?'); ?>
                <a href="<?php echo $current_url; ?>?<?php echo http_build_query(array_merge($_GET, ['export' => 'html'])); ?>" class="btn-primary-dashboard">Exportar HTML</a>
                <a href="<?php echo $current_url; ?>?<?php echo http_build_query(array_merge($_GET, ['export' => 'csv'])); ?>" class="btn-primary-dashboard">Exportar CSV</a>
                <a href="<?php echo $current_url; ?>?<?php echo http_build_query(array_merge($_GET, ['export' => 'pdf'])); ?>" class="btn-primary-dashboard">Exportar PDF</a>
            </div>


        </div>
    </div>

    <?php $conn->close(); ?>
    <script src="js/script.js"></script>
    <?php // Incluir outros scripts, se houver
          // include 'includes/templates/footer_dashboard.php'; // Substitua pelo seu arquivo de footer real
    ?>
</body>
</html>
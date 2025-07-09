<?php
require_once 'includes/conexao.php';

$sql = "SELECT unnest(enum_range(NULL::nivel_acesso_enum)) AS enum_value";
$result = pg_query($conexao, $sql);

if ($result) {
    echo "Valores possíveis para nivel_acesso_enum:
";
    while ($row = pg_fetch_assoc($result)) {
        echo "- " . $row['enum_value'] . "
";
    }
} else {
    echo "Erro ao buscar valores do enum: " . pg_last_error($conexao);
}

pg_close($conexao);
?>

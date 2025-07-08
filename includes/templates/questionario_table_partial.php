<?php
// includes/templates/questionario_table_partial.php
// Contém apenas o HTML da tabela e da paginação inteligente.
?>

<?php if (empty($perguntas)): ?>
    <div class="alert alert-info mt-3">
        Nenhuma pergunta encontrada com os critérios de busca.
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover table-bordered">
            <thead class="thead-dark">
                <tr>
                    <th scope="col" style="width: 5%;">ID</th>
                    <th scope="col">Pergunta</th>
                    <th scope="col">Descrição</th>
                    <th scope="col" style="width: 15%;">Categoria</th>
                    <th scope="col" style="width: 12%;" class="text-center">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($perguntas as $pergunta): ?>
                <tr>
                    <td><?php echo htmlspecialchars($pergunta['id']); ?></td>
                    <td><?php echo htmlspecialchars($pergunta['pergunta']); ?></td>
                    <td><?php echo htmlspecialchars($pergunta['descricao']); ?></td>
                    <td><?php echo htmlspecialchars($pergunta['categoria']); ?></td>
                    <td class="text-center">
                        <a href="editar_pergunta.php?id=<?php echo htmlspecialchars($pergunta['id']); ?>" class="btn btn-sm btn-info" title="Editar Pergunta">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="gerenciar_questionario.php?action=excluir&id=<?php echo htmlspecialchars($pergunta['id']); ?>" class="btn btn-sm btn-danger" title="Excluir Pergunta" onclick="return confirm('Tem certeza que deseja excluir esta pergunta?');">
                            <i class="fas fa-trash-alt"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if (isset($total_paginas) && $total_paginas > 1): ?>
    <nav aria-label="Paginação">
        <ul class="pagination justify-content-center">
            <?php
            $adjacents = 2;
            $linkAnterior = "?pagina=" . ($pagina_atual - 1) . "&q=" . urlencode($termo_pesquisa);
            echo '<li class="page-item ' . ($pagina_atual <= 1 ? 'disabled' : '') . '"><a class="page-link pagination-link" href="' . $linkAnterior . '">&laquo;</a></li>';
            if ($total_paginas < 7 + ($adjacents * 2)) {
                for ($i = 1; $i <= $total_paginas; $i++) {
                    $link = "?pagina=" . $i . "&q=" . urlencode($termo_pesquisa);
                    echo '<li class="page-item ' . ($i == $pagina_atual ? 'active' : '') . '"><a class="page-link pagination-link" href="' . $link . '">' . $i . '</a></li>';
                }
            } else {
                if ($pagina_atual > $adjacents + 2) {
                    echo '<li class="page-item"><a class="page-link pagination-link" href="?pagina=1&q=' . urlencode($termo_pesquisa) . '">1</a></li>';
                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }
                $start = max(1, $pagina_atual - $adjacents);
                $end = min($total_paginas, $pagina_atual + $adjacents);
                for ($i = $start; $i <= $end; $i++) {
                    $link = "?pagina=" . $i . "&q=" . urlencode($termo_pesquisa);
                    echo '<li class="page-item ' . ($i == $pagina_atual ? 'active' : '') . '"><a class="page-link pagination-link" href="' . $link . '">' . $i . '</a></li>';
                }
                if ($pagina_atual < $total_paginas - $adjacents - 1) {
                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    echo '<li class="page-item"><a class="page-link pagination-link" href="?pagina=' . $total_paginas . '&q=' . urlencode($termo_pesquisa) . '">' . $total_paginas . '</a></li>';
                }
            }
            $linkProxima = "?pagina=" . ($pagina_atual + 1) . "&q=" . urlencode($termo_pesquisa);
            echo '<li class="page-item ' . ($pagina_atual >= $total_paginas ? 'disabled' : '') . '"><a class="page-link pagination-link" href="' . $linkProxima . '">&raquo;</a></li>';
            ?>
        </ul>
    </nav>
    <?php endif; ?>
<?php endif; ?>
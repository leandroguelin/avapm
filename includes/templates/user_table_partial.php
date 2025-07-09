<?php
// htdocs/avapm/includes/templates/user_table_partial.php - VERSÃO COM PAGINAÇÃO INTELIGENTE

// As variáveis $usuarios, $total_paginas, $pagina_atual e $termo_pesquisa
// são fornecidas pelo arquivo principal 'gerenciar_usuarios.php'.
?>

<?php if (empty($usuarios)): ?>
    <div class="alert alert-info mt-3">
        Nenhum usuário encontrado com os critérios de pesquisa.
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover table-bordered">
            <thead class="thead-dark">
                <tr>
                    <th scope="col" style="width: 5%;">ID</th>
                    <th scope="col">Nome</th>
                    <th scope="col" style="width: 10%;">RG</th>
                    <th scope="col" style="width: 10%;">CPF</th>
                    <th scope="col" style="width: 10%;">Patente</th>
                    <th scope="col">Email</th>
                    <th scope="col" style="width: 10%;">Telefone</th>
                    <th scope="col" style="width: 10%;">Titulação</th>
                    <th scope="col" style="width: 10%;" class="text-center">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $usuario): ?>
                <tr>
                    <td><?php echo htmlspecialchars($usuario['id']); ?></td>
                    <td><?php echo htmlspecialchars($usuario['nome']); ?></td>
                    <td><?php echo htmlspecialchars($usuario['rg'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($usuario['cpf'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($usuario['patente'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                    <td><?php echo htmlspecialchars($usuario['telefone'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($usuario['titulacao'] ?? 'N/A'); ?></td>
                    <td class="text-center">
                        <?php if (($usuario['nivel_acesso'] ?? '') === 'PROFESSOR'): // Ação apenas para professores ?>
 <button class="btn btn-sm btn-primary btn-ver-disciplinas" data-user-id="<?php echo htmlspecialchars($usuario['id']); ?>" data-user-name="<?php echo htmlspecialchars($usuario['nome']); ?>" title="Ver Disciplinas Ministradas">
                                <i class="fas fa-book"></i>
 </button>
                        <?php endif; ?>
                        <a href="log.php?user_id=<?php echo htmlspecialchars($usuario['id']); ?>" class="btn btn-sm btn-secondary" title="Ver Logs de Login">
 <i class="fas fa-history"></i>
 </a>

                        <a href="editar_usuario.php?id=<?php echo htmlspecialchars($usuario['id']); ?>" class="btn btn-sm btn-info" title="Editar Usuário">
                            <i class="fas fa-user-edit"></i>
                        </a>
                        <a href="excluir_usuario.php?id=<?php echo htmlspecialchars($usuario['id']); ?>" class="btn btn-sm btn-danger" title="Excluir Usuário" onclick="return confirm('Tem certeza que deseja excluir o usuário \'<?php echo addslashes(htmlspecialchars($usuario['nome'])); ?>\'?');">
                            <i class="fas fa-user-times"></i>
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
            $adjacents = 2; // Quantos números de links mostrar de cada lado da página atual

            // Botão "Anterior"
            $linkAnterior = "?pagina=" . ($pagina_atual - 1) . "&q=" . urlencode($termo_pesquisa);
            echo '<li class="page-item ' . ($pagina_atual <= 1 ? 'disabled' : '') . '"><a class="page-link pagination-link" href="' . $linkAnterior . '">&laquo;</a></li>';

            // Lógica dos links de página
            if ($total_paginas < 7 + ($adjacents * 2)) { // se não houver muitas páginas, mostre todas
                for ($i = 1; $i <= $total_paginas; $i++) {
                    $link = "?pagina=" . $i . "&q=" . urlencode($termo_pesquisa);
                    echo '<li class="page-item ' . ($i == $pagina_atual ? 'active' : '') . '"><a class="page-link pagination-link" href="' . $link . '">' . $i . '</a></li>';
                }
            } else { // se houver muitas páginas, use a lógica de "..."
                // Exibe o link para a página 1 e "..." se necessário
                if ($pagina_atual > $adjacents + 2) {
                    echo '<li class="page-item"><a class="page-link pagination-link" href="?pagina=1&q=' . urlencode($termo_pesquisa) . '">1</a></li>';
                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }

                // Define o intervalo de páginas a serem exibidas ao redor da página atual
                $start = max(1, $pagina_atual - $adjacents);
                $end = min($total_paginas, $pagina_atual + $adjacents);

                // Exibe os links do intervalo
                for ($i = $start; $i <= $end; $i++) {
                    $link = "?pagina=" . $i . "&q=" . urlencode($termo_pesquisa);
                    echo '<li class="page-item ' . ($i == $pagina_atual ? 'active' : '') . '"><a class="page-link pagination-link" href="' . $link . '">' . $i . '</a></li>';
                }

                // Exibe "..." e o link para a última página se necessário
                if ($pagina_atual < $total_paginas - $adjacents - 1) {
                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    echo '<li class="page-item"><a class="page-link pagination-link" href="?pagina=' . $total_paginas . '&q=' . urlencode($termo_pesquisa) . '">' . $total_paginas . '</a></li>';
                }
            }

            // Botão "Próxima"
            $linkProxima = "?pagina=" . ($pagina_atual + 1) . "&q=" . urlencode($termo_pesquisa);
            echo '<li class="page-item ' . ($pagina_atual >= $total_paginas ? 'disabled' : '') . '"><a class="page-link pagination-link" href="' . $linkProxima . '">&raquo;</a></li>';
            ?>
        </ul>
    </nav>
    <?php endif; ?>
    <?php endif; ?>
<?php
// includes/templates/avaliacao_table_partial.php
?>

<?php if (empty($avaliacoes)): ?>
    <div class="alert alert-info mt-3">
        Nenhuma avaliação encontrada. Clique em "Adicionar Avaliação" para começar.
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover table-bordered">
            <thead class="thead-dark">
                <tr>
                    <th scope="col">Código</th>
                    <th scope="col">Nome da Avaliação</th>
                    <th scope="col">Curso</th>
                    <th scope="col">Início</th>
                    <th scope="col">Fim</th>
                    <th scope="col" class="text-center">Situação</th>
                    <th scope="col" class="text-center">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($avaliacoes as $avaliacao): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($avaliacao['codigo']); ?></strong></td>
                        <td><?php echo htmlspecialchars($avaliacao['nome']); ?></td>
                        <td><?php echo htmlspecialchars($avaliacao['curso_nome'] ?? 'N/A'); ?></td>
                        <td><?php echo (new DateTime($avaliacao['data_inicio']))->format('d/m/Y H:i'); ?></td>
                        <td><?php echo (new DateTime($avaliacao['data_final']))->format('d/m/Y H:i'); ?></td>
                        <td class="text-center">
                            <?php
                            // CORREÇÃO: A lógica volta a se basear no texto 'Ativa'
                            $is_ativa = ($avaliacao['situacao'] === 'Ativa');
                            $texto_situacao = $is_ativa ? 'Ativa' : 'Inativa';
                            $btn_class = $is_ativa ? 'btn-success' : 'btn-secondary';
                            $icon_class = $is_ativa ? 'fa-toggle-on' : 'fa-toggle-off';
                            $title = $is_ativa ? 'Ativa (Clique para desativar)' : 'Inativa (Clique para ativar)';
                            ?>
                            <button class="btn btn-sm toggle-situacao-btn <?php echo $btn_class; ?>"
                                data-id="<?php echo $avaliacao['id']; ?>"
                                data-situacao="<?php echo htmlspecialchars($avaliacao['situacao']); // Envia 'Ativa' ou 'Inativa' 
                                                ?>"
                                title="<?php echo $title; ?>">
                                <i class="fas <?php echo $icon_class; ?>"></i>
                                <span class="status-text"><?php echo $texto_situacao; ?></span>
                            </button>
                        </td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-info edit-avaliacao-btn"
                                data-avaliacao='<?php echo htmlspecialchars(json_encode($avaliacao), ENT_QUOTES, 'UTF-8'); ?>'
                                data-toggle="modal" data-target="#avaliacaoModal" title="Editar Avaliação">
                                <i class="fas fa-edit"></i>
                            </button>

                            <a href="gerenciar_avaliacoes.php?action=excluir&id=<?php echo $avaliacao['id']; ?>" class="btn btn-sm btn-danger" title="Excluir Avaliação" onclick="return confirm('Tem certeza que deseja excluir esta avaliação?');">
                                <i class="fas fa-trash-alt"></i>
                            </a>

                            <div class="btn-group">
                                <button type="button" class="btn btn-sm btn-success dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Relatórios e Exportação">
                                    <i class="fas fa-chart-bar"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-right">
                                    <a class="dropdown-item" href="relatorio_avaliacao.php?id=<?php echo $avaliacao['id']; ?>" target="_blank">
                                        <i class="fas fa-eye fa-fw mr-2"></i>Ver Relatório HTML
                                    </a>
                                    <a class="dropdown-item" href="relatorio_avaliacao.php?id=<?php echo $avaliacao['id']; ?>&format=pdf" target="_blank">
                                        <i class="fas fa-file-pdf fa-fw mr-2"></i>Exportar PDF
                                    </a>
                                    <a class="dropdown-item" href="relatorio_avaliacao.php?id=<?php echo $avaliacao['id']; ?>&format=excel">
                                        <i class="fas fa-file-excel fa-fw mr-2"></i>Exportar Excel (CSV)
                                    </a>
                                </div>
                            </div>
                        </td>
                        <!-- <td class="text-center">
                            <button class="btn btn-sm btn-info edit-avaliacao-btn"
                                data-avaliacao='<?php echo htmlspecialchars(json_encode($avaliacao), ENT_QUOTES, 'UTF-8'); ?>'
                                data-toggle="modal" data-target="#avaliacaoModal" title="Editar Avaliação">
                                <i class="fas fa-edit"></i>
                            </button>
                            <a href="gerenciar_avaliacoes.php?action=excluir&id=<?php echo $avaliacao['id']; ?>" class="btn btn-sm btn-danger" title="Excluir Avaliação" onclick="return confirm('Tem certeza que deseja excluir esta avaliação?');">
                                <i class="fas fa-trash-alt"></i>
                            </a>
                        </td> -->
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if (isset($total_paginas) && $total_paginas > 1): ?>
        <nav aria-label="Paginação">
            <ul class="pagination justify-content-center">
                <?php
                // Lógica da paginação inteligente (copiada da solução anterior)
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
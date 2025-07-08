<?php
// htdocs/avapm/includes/templates/disciplina_table_partial.php - VERSÃO COM LAYOUT MELHORADO

// As variáveis $disciplinas, $total_paginas, $pagina_atual e $termo_pesquisa
// são fornecidas pelo arquivo principal 'gerenciar_disciplinas.php'.
?>

<?php if (empty($disciplinas)): ?>
    <div class="alert alert-info mt-3">
        Nenhuma disciplina encontrada para os critérios de busca.
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover table-bordered">
            <thead class="thead-dark">
                <tr>
                    <th scope="col" style="width: 5%;">ID</th>
                    <th scope="col" style="width: 15%;">Sigla</th>
                    <th scope="col">Nome</th>
                    <th scope="col" style="width: 10%;" class="text-center">Horas</th>
                    <th scope="col">Ementa (Início)</th>
                    <th scope="col" style="width: 15%;" class="text-center">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($disciplinas as $disciplina): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($disciplina['id']); ?></td>
                        <td><?php echo htmlspecialchars($disciplina['sigla']); ?></td>
                        <td><?php echo htmlspecialchars($disciplina['nome']); ?></td>
                        <td class="text-center"><?php echo htmlspecialchars($disciplina['horas']); ?></td>
                        <td>
                            <?php 
                            $ementa_curta = htmlspecialchars(mb_substr($disciplina['ementa'], 0, 50, 'UTF-8'));
                            echo !empty(trim($disciplina['ementa'])) ? $ementa_curta . '...' : '<span class="text-muted">Sem ementa</span>'; 
                            ?>
                        </td>
                        <td class="text-center">
                            <button 
                                onclick="abrirModalEmenta('<?php echo htmlspecialchars(addslashes($disciplina['nome'])); ?>', '<?php echo htmlspecialchars(addslashes($disciplina['ementa'])); ?>')" 
                                class="btn btn-sm btn-secondary" title="Visualizar Ementa Completa">
                                <i class="fas fa-eye"></i>
                            </button>
                            <a href="editar_disciplina.php?id=<?php echo htmlspecialchars($disciplina['id']); ?>" 
                               class="btn btn-sm btn-info" title="Editar Disciplina">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button
                                onclick="confirmarExclusao(<?php echo htmlspecialchars($disciplina['id']); ?>, '<?php echo htmlspecialchars(addslashes($disciplina['nome'])); ?>')" 
                                class="btn btn-sm btn-danger" title="Excluir Disciplina">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if (isset($total_paginas) && $total_paginas > 1): ?>
    <nav aria-label="Paginação de disciplinas">
        <ul class="pagination justify-content-center">
            <li class="page-item <?php echo ($pagina_atual <= 1) ? 'disabled' : ''; ?>">
                <a class="page-link pagination-link" href="?pagina=<?php echo $pagina_atual - 1; ?>&q=<?php echo urlencode($termo_pesquisa); ?>">&laquo;</a>
            </li>
            <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                <li class="page-item <?php echo ($i == $pagina_atual) ? 'active' : ''; ?>">
                    <a class="page-link pagination-link" href="?pagina=<?php echo $i; ?>&q=<?php echo urlencode($termo_pesquisa); ?>">
                        <?php echo $i; ?>
                    </a>
                </li>
            <?php endfor; ?>
            <li class="page-item <?php echo ($pagina_atual >= $total_paginas) ? 'disabled' : ''; ?>">
                <a class="page-link pagination-link" href="?pagina=<?php echo $pagina_atual + 1; ?>&q=<?php echo urlencode($termo_pesquisa); ?>">&raquo;</a>
            </li>
        </ul>
    </nav>
    <?php endif; ?>
<?php endif; ?>
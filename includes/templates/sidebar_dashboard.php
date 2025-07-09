<?php
// htdocs/avapm/includes/templates/sidebar_dashboard.php - VERSÃO MODERNA E INTERATIVA

$current_page = $current_page ?? ''; 
// Garante que o nível de acesso esteja em maiúsculas para consistência
$nivel_acesso_usuario = isset($_SESSION['nivel_acesso']) ? strtoupper($_SESSION['nivel_acesso']) : 'CONVIDADO';

// --- Lógica para manter os menus abertos ---
$admin_pages = ['Usuários', 'Cursos', 'Disciplinas', 'Log de Logins', 'Gerenciar Credenciais'];
$avaliacoes_pages = ['Gerenciar Avaliações', 'Questionário', 'Relatórios'];
$professor_pages = ['Minhas Avaliações', 'Minhas Disciplinas']; // Páginas do menu Professor

$isAdminMenuActive = in_array($current_page, $admin_pages);
$isAvaliacoesMenuActive = in_array($current_page, $avaliacoes_pages);
$isProfessorMenuActive = in_array($current_page, $professor_pages);
?>

<aside class="sidebar">
    <div class="sidebar-header">
        <a href="dashboard.php" class="sidebar-logo">
            <img src="imagens/sistema/logo_exemplo.png" alt="Logo AVAPM">
            <span>AVA</span> 
        </a>
    </div>

    <nav class="sidebar-nav">
        <ul>
            <li class="sidebar-item">
                <a href="dashboard.php" class="sidebar-link <?php echo ($current_page == 'Painel') ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt fa-fw"></i> 
                    <span>Painel</span>
                </a>
            </li>

            <li class="sidebar-item">
                <a href="meu_perfil.php" class="sidebar-link <?php echo ($current_page == 'Meu Perfil') ? 'active' : ''; ?>">
                    <i class="fas fa-user-circle fa-fw"></i> 
                    <span>Meu Perfil</span>
                </a>
            </li>

            <?php if ($nivel_acesso_usuario === 'ADMINISTRADOR' || $nivel_acesso_usuario === 'GERENTE'): ?>

                <li class="menu-header"><span>Gerenciamento</span></li>

                <li class="sidebar-item">
                    <a href="#avaliacoesSubmenu" data-toggle="collapse" class="sidebar-link <?php echo $isAvaliacoesMenuActive ? '' : 'collapsed'; ?>" aria-expanded="<?php echo $isAvaliacoesMenuActive ? 'true' : 'false'; ?>">
                        <i class="fas fa-clipboard-check fa-fw"></i>
                        <span>Avaliações</span>
                        <i class="fas fa-chevron-down submenu-arrow"></i>
                    </a>
                    <ul class="collapse list-unstyled submenu <?php echo $isAvaliacoesMenuActive ? 'show' : ''; ?>" id="avaliacoesSubmenu">
                        <li><a href="gerenciar_avaliacoes.php" class="sidebar-link <?php echo ($current_page == 'Gerenciar Avaliações') ? 'active' : ''; ?>">Gerenciar Avaliações</a></li>
                        <li><a href="gerenciar_questionario.php" class="sidebar-link <?php echo ($current_page == 'Questionário') ? 'active' : ''; ?>">Questionário</a></li>
                        <li><a href="relatorios.php" class="sidebar-link <?php echo ($current_page == 'Relatórios') ? 'active' : ''; ?>">Relatórios</a></li>
                    </ul>
                </li>
                
                <li class="sidebar-item">
                    <a href="#adminSubmenu" data-toggle="collapse" class="sidebar-link <?php echo $isAdminMenuActive ? '' : 'collapsed'; ?>" aria-expanded="<?php echo $isAdminMenuActive ? 'true' : 'false'; ?>">
                        <i class="fas fa-shield-alt fa-fw"></i>
                        <span>Administração</span>
                        <i class="fas fa-chevron-down submenu-arrow"></i>
                    </a>
                    <ul class="collapse list-unstyled submenu <?php echo $isAdminMenuActive ? 'show' : ''; ?>" id="adminSubmenu">
                        <li><a href="gerenciar_usuarios.php" class="sidebar-link <?php echo ($current_page == 'Usuários') ? 'active' : ''; ?>">Usuários</a></li>
                        <li><a href="gerenciar_cursos.php" class="sidebar-link <?php echo ($current_page == 'Cursos') ? 'active' : ''; ?>">Cursos</a></li>
                        <li><a href="gerenciar_disciplinas.php" class="sidebar-link <?php echo ($current_page == 'Disciplinas') ? 'active' : ''; ?>">Disciplinas</a></li>
                        <?php if ($nivel_acesso_usuario === 'ADMINISTRADOR'): ?>
                            <li class="sidebar-item">
                                <a href="credenciais.php" class="sidebar-link <?php echo ($current_page == 'Gerenciar Credenciais') ? 'active' : ''; ?>">
                                    <i class="fas fa-user-shield fa-fw"></i> 
                                    <span>Gerenciar Credenciais</span>
                                </a>
                            </li>
                            <li class="sidebar-item">
                                <a href="log.php" class="sidebar-link <?php echo ($current_page == 'Log de Logins') ? 'active' : ''; ?>">
                                    <i class="fas fa-history fa-fw"></i> 
                                    <span>Log de Logins</span>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </li>

                <li class="sidebar-item">
                    <a href="configuracoes.php" class="sidebar-link <?php echo ($current_page == 'Configurações') ? 'active' : ''; ?>">
                        <i class="fas fa-cogs fa-fw"></i> 
                        <span>Configurações</span>
                    </a>
                </li>
            <?php endif; ?>

            <?php // Menu para Professores ?>
            <?php if ($nivel_acesso_usuario === 'PROFESSOR'): ?>
                <li class="menu-header"><span>Área do Professor</span></li>
                <li class="sidebar-item">
                    <a href="#professorSubmenu" data-toggle="collapse" class="sidebar-link <?php echo $isProfessorMenuActive ? '' : 'collapsed'; ?>" aria-expanded="<?php echo $isProfessorMenuActive ? 'true' : 'false'; ?>">
                        <i class="fas fa-chalkboard-teacher fa-fw"></i>
                        <span>Professor</span>
                        <i class="fas fa-chevron-down submenu-arrow"></i>
                    </a>
                    <ul class="collapse list-unstyled submenu <?php echo $isProfessorMenuActive ? 'show' : ''; ?>" id="professorSubmenu">
                        <li><a href="dashboard_professor.php" class="sidebar-link <?php echo ($current_page == 'Minhas Avaliações') ? 'active' : ''; ?>">Minhas Avaliações</a></li>
                        <li><a href="minhas_disciplinas.php" class="sidebar-link <?php echo ($current_page == 'Minhas Disciplinas') ? 'active' : ''; ?>">Minhas Disciplinas</a></li>
                    </ul>
                </li>
            <?php endif; ?>

            <?php // Menu para Alunos ?>
            <?php if ($nivel_acesso_usuario === 'ALUNO'): ?>
                <li class="menu-header"><span>Área do Aluno</span></li>
                <li class="sidebar-item">
                    <a href="minhas_disciplinas.php" class="sidebar-link <?php echo ($current_page == 'Minhas Disciplinas') ? 'active' : ''; ?>">
                        <i class="fas fa-book fa-fw"></i>
                        <span>Minhas Disciplinas</span>
                    </a>
                </li>
            <?php endif; ?>

            <li class="sidebar-item logout-item">
                <a href="logout.php" class="sidebar-link">
                    <i class="fas fa-sign-out-alt fa-fw"></i> 
                    <span>Sair</span>
                </a>
            </li>
        </ul>
    </nav>
</aside>

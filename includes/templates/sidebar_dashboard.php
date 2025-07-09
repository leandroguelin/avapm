<?php
// htdocs/avapm/includes/templates/sidebar_dashboard.php - VERSÃO MODERNA E INTERATIVA

$current_page = $current_page ?? '';
$nivel_acesso_usuario = $_SESSION['nivel_acesso'] ?? 'convidado';

// --- Lógica para manter os menus abertos ---
// Define quais páginas pertencem a cada menu expansível
$admin_pages = ['Usuários', 'Cursos', 'Disciplinas'];
$avaliacoes_pages = ['Gerenciar Avaliações', 'Questionário', 'Log de Logins']; // Adiciona Log de Logins para manter o menu aberto
$professor_pages = ['Minhas Disciplinas']; // Adiciona a página "Minhas Disciplinas" à lista de páginas do professor

$isAdminMenuActive = in_array($current_page, $admin_pages);
$isAvaliacoesMenuActive = in_array($current_page, $avaliacoes_pages);
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
                        <!-- <li><a href="relatorios.php" class="sidebar-link <?php echo ($current_page == 'Relatórios') ? 'active' : ''; ?>">Relatórios</a></li> -->
                        <li><a href="/avapm/log.php" class="sidebar-link <?php echo ($current_page == 'Log de Logins') ? 'active' : ''; ?>">Log de Logins</a></li>
<<<<<<< HEAD
                    </ul>
                </li>

                <?php if ($nivel_acesso_usuario === 'ADMINISTRADOR'): ?>
                    <li class="sidebar-item <?php echo $isAdminMenuActive ? '' : 'collapsed'; ?>">
                        <a href="#adminSubmenu" data-toggle="collapse" class="sidebar-link <?php echo $isAdminMenuActive ? '' : 'collapsed'; ?>" aria-expanded="<?php echo $isAdminMenuActive ? 'true' : 'false'; ?>">
                            <i class="fas fa-shield-alt fa-fw"></i>
                            <span>Administração</span>
                            <i class="fas fa-chevron-down submenu-arrow"></i>
                        </a>
                        <ul class="collapse list-unstyled submenu <?php echo $isAdminMenuActive ? 'show' : ''; ?>" id="adminSubmenu">
=======
                    </ul>
                </li>
                
                <?php if ($nivel_acesso_usuario === 'ADMINISTRADOR'): ?>
                <li class="sidebar-item <?php echo $isAdminMenuActive ? '' : 'collapsed'; ?>">
                    <a href="#adminSubmenu" data-toggle="collapse" class="sidebar-link <?php echo $isAdminMenuActive ? '' : 'collapsed'; ?>" aria-expanded="<?php echo $isAdminMenuActive ? 'true' : 'false'; ?>">
                        <i class="fas fa-shield-alt fa-fw"></i>
                        <span>Administração</span>
                        <i class="fas fa-chevron-down submenu-arrow"></i>
                    </a>
                    <ul class="collapse list-unstyled submenu <?php echo $isAdminMenuActive ? 'show' : ''; ?>" id="adminSubmenu">

                        <li><a href="credenciais.php" class="sidebar-link <?php echo ($current_page == 'Gerenciar Credenciais') ? 'active' : ''; ?>">Gerenciar Credenciais</a></li>
                        <li><a href="gerenciar_usuarios.php" class="sidebar-link <?php echo ($current_page == 'Usuários') ? 'active' : ''; ?>">Usuários</a></li>
                        <li><a href="gerenciar_cursos.php" class="sidebar-link <?php echo ($current_page == 'Cursos') ? 'active' : ''; ?>">Cursos</a></li>
                        <li><a href="gerenciar_disciplinas.php" class="sidebar-link <?php echo ($current_page == 'Disciplinas') ? 'active' : ''; ?>">Disciplinas</a></li>


                    </ul>
                </li>

                <li class="sidebar-item">
                    <a href="configuracoes.php" class="sidebar-link <?php echo ($current_page == 'Configurações') ? 'active' : ''; ?>">
                        <i class="fas fa-cogs fa-fw"></i> 
                        <span>Configurações</span>
                    </a>
                </li>
            <?php endif; ?>
>>>>>>> fec0187 (v5)

<<<<<<< HEAD
                            <li><a href="credenciais.php" class="sidebar-link <?php echo ($current_page == 'Gerenciar Credenciais') ? 'active' : ''; ?>">Gerenciar Credenciais</a></li>
                            <li><a href="gerenciar_usuarios.php" class="sidebar-link <?php echo ($current_page == 'Usuários') ? 'active' : ''; ?>">Usuários</a></li>
                            <li><a href="gerenciar_cursos.php" class="sidebar-link <?php echo ($current_page == 'Cursos') ? 'active' : ''; ?>">Cursos</a></li>
                            <li><a href="gerenciar_disciplinas.php" class="sidebar-link <?php echo ($current_page == 'Disciplinas') ? 'active' : ''; ?>">Disciplinas</a></li>


                        </ul>
                    </li>

                    <li class="sidebar-item">
                        <a href="configuracoes.php" class="sidebar-link <?php echo ($current_page == 'Configurações') ? 'active' : ''; ?>">
                            <i class="fas fa-cogs fa-fw"></i>
                            <span>Configurações</span>
                        </a>
                    </li>
=======
            <?php if ($nivel_acesso_usuario === 'PROFESSOR' || $nivel_acesso_usuario === 'ALUNO'): ?>
                <li class="menu-header"><span>Área do Professor</span></li>
                <li class="sidebar-item">
                    <a href="minhas_disciplinas.php" class="sidebar-link <?php echo ($current_page == 'Minhas Disciplinas') ? 'active' : ''; ?>">
                        <i class="fas fa-book fa-fw"></i> 
                        <span>Minhas Disciplinas</span>
                    </a>
                </li>
>>>>>>> 1ef5e8e (f1)
                <?php endif; ?>

                <?php if ($nivel_acesso_usuario === 'PROFESSOR' || $nivel_acesso_usuario === 'ALUNO'): ?>
                    <li class="menu-header"><span>Área do Professor</span></li>
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
<<<<<<< HEAD

php
<?php endif; ?>

<?php if ($nivel_acesso_usuario === 'PROFESSOR' || $nivel_acesso_usuario === 'ALUNO'): ?>
    <li class="menu-header"><span>Área do Professor</span></li>
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
=======
php
                  <?php endif; ?>

              <?php if ($nivel_acesso_usuario === 'PROFESSOR' || $nivel_acesso_usuario === 'ALUNO'): ?>
                  <li class="menu-header"><span>Área do Professor</span></li>
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
>>>>>>> 1ef5e8e (f1)

<?php
// htdocs/avapm/includes/templates/footer_dashboard.php
// Este arquivo fecha o HTML e o layout do dashboard


?>
</div>
<script>
    $(document).ready(function() {
        // A lógica de collapse do Bootstrap 4 já deve funcionar com os atributos
        // data-toggle="collapse" e href="#submenuId". Este script é um reforço
        // e pode ser customizado se necessário.

        $('.sidebar-item .sidebar-link[data-toggle="collapse"]').on('click', function(e) {
            // Previne o comportamento padrão do link, se necessário
            e.preventDefault();

            // Pega o submenu alvo
            let target = $(this).attr('href');

            // Esconde outros submenus abertos para criar um efeito "accordion"
            $('.submenu.show').not(target).collapse('hide');

            // Mostra ou esconde o submenu clicado
            $(target).collapse('toggle');
        });
    });
</script>
</body>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/js/all.min.js"></script>

<script>
    // Exemplo: document.addEventListener('DOMContentLoaded', function() { ... });
    // Ou, se tiver scripts globais que não pertencem a uma página específica, coloque-os aqui.
    // Lembre-se: Este bloco <script> deve vir DEPOIS dos seus arquivos .js externos se eles forem globais.
    // Se este for para scripts MUITO pequenos ou que se aplicam a todas as páginas, pode ficar.
    // Para a maioria dos casos, prefira arquivos .js dedicados.
</script>

</html>
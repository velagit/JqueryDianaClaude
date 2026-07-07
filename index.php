<?php
require_once __DIR__ . '/config/session.php';
requerirLogin();

$tituloPagina = 'Listado de Marcas';
$paginaActiva = 'marcas';
require_once __DIR__ . '/includes/header.php';
?>

<div class="topbar-marca">
    <h4 class="mb-0"><i class="bi bi-tags-fill me-2"></i>Listado de Marcas</h4>
</div>

<div class="card mb-3">
  
</div>

<div class="card">
    <div class="card-body">

        <div id="spinner-carga" class="text-center py-3">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
        </div>

        <div class="table-responsive">
          
        </div>

      

    </div>
</div>



<?php require_once __DIR__ . '/includes/footer.php'; ?>
<script src="assets/js/marcas.js"></script>
</body>
</html>

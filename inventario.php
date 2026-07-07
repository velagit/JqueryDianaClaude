<?php
require_once __DIR__ . '/config/session.php';
requerirLogin();

$tituloPagina = 'Listado de Existencias Inventario';
$paginaActiva = 'inventario';
require_once __DIR__ . '/includes/header.php';
?>


<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Consulta de Inventario</title>

<!-- Bootstrap 5 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<!-- Estilos propios -->
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="container-fluid py-4">

    <div class="topbar-marca">
        <h4 class="mb-0"><i class="bi bi-clipboard-data me-2"></i>Consulta de Inventario</h4>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row mb-3 g-3">
                <div class="col-md-4">
                    <label class="form-label">Marca</label>
                    <select id="marca" class="form-select">
                        <option value="">Seleccione</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Modelo</label>
                    <input type="text" id="modelodes" class="form-control" placeholder="Buscar modelo...">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Corrida</label>
                    <select id="corrida" class="form-select">
                        <option value="">Todas</option>
                    </select>
                </div>
            </div>

            <button id="consultar" class="btn btn-primary">
                <i class="bi bi-search me-1"></i> Consultar
            </button>
        </div>
    </div>

    <div id="tabla-resultados" class="card" style="display:none;">
        <div class="card-body table-responsive"></div>
    </div>
</div>



<?php require_once __DIR__ . '/includes/footer.php'; ?>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- Bootstrap 5 JS (incluye Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- Lógica -->
<script src="assets/js/inventario.js"></script>

</body>
</html>

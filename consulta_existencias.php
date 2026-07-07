<?php
require_once __DIR__ . '/config/session.php';
requerirLogin();

$tituloPagina = 'Consulta  de Existencias Inventario';
$paginaActiva = 'consulta_existencias';
require_once __DIR__ . '/includes/header.php';
?>


<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Consulta de Existencias</title>

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
        <h4 class="mb-0"><i class="bi bi-search me-2"></i>Consulta de Existencias</h4>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form id="form-consulta">
                <div class="row g-3">
                    <div class="col-12 col-md-3">
                        <label class="form-label">Marca</label>
                        <select id="select-marca" class="form-select" required>
                            <option value="">Seleccione una marca</option>
                        </select>
                    </div>

                    <div id="fila-filtros-extra" class="col-12 col-md-9 row g-3" style="display:none;">
                        <div class="col-12 col-md-4">
                            <label class="form-label">Modelo</label>
                            <input type="text" id="input-modelo" class="form-control" placeholder="Buscar por descripción...">
                        </div>

                        <div class="col-12 col-md-5">
                            <label class="form-label">Corrida</label>
                            <select id="select-corrida" class="form-select">
                                <option value="">Todas</option>
                            </select>
                        </div>

                        <div class="col-12 col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-success w-100">
                                <i class="bi bi-funnel me-1"></i> Filtrar
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div id="spinner-carga" class="text-center py-3" style="display:none;">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Cargando...</span>
        </div>
    </div>

    <div id="contenedor-resultados" class="card" style="display:none;">
        <div class="card-body">
            <div class="table-responsive">
                <table id="tabla-resultados" class="table table-bordered table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Modelo</th>
                            <th>Material</th>
                            <th>Color</th>
                            <th>Suela</th>
                            <!-- columnas de tallas generadas dinámicamente -->
                        </tr>
                    </thead>
                    <tbody>
                        <!-- filas generadas dinámicamente por consulta_existencias.js -->
                    </tbody>
                </table>
            </div>

            <nav>
                <ul id="paginacion-consulta" class="pagination justify-content-center">
                    <!-- paginación generada dinámicamente -->
                </ul>
            </nav>
        </div>
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
<script src="assets/js/consulta_existencias.js"></script>

</body>
</html>

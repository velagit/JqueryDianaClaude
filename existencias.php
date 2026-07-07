<?php
require_once __DIR__ . '/config/session.php';
requerirLogin();

$tituloPagina = 'administración de Existencias ';
$paginaActiva = 'existencias';
require_once __DIR__ . '/includes/header.php';
?>



<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Captura de Existencias</title>

<!-- Bootstrap 5 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<!-- Estilos propios -->
<link rel="stylesheet" href="assets/css/style.css">
<style>
    .celda-talla input {
        min-width: 60px;
        text-align: center;
    }
    #tabla-existencias {
        font-size: 0.9rem;
    }
</style>
</head>
<body>

<div class="container-fluid py-4">

    <div class="topbar-marca">
        <h4 class="mb-0" id="titulo-marca"><i class="bi bi-boxes me-2"></i>Captura de existencias</h4>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row align-items-center g-2">
                <div class="col-12 col-md-3">
                    <a href="marcas.php" class="btn btn-secondary w-100">
                        <i class="bi bi-arrow-left-circle me-1"></i> Volver a Marcas
                    </a>
                </div>
                <div class="col-12 col-md-9">
                    <form id="form-buscar-modelo" class="d-flex gap-2">
                        <input type="text" id="buscador-modelo" class="form-control form-control-sm" placeholder="Buscar modelo...">
                        <button class="btn btn-sm btn-info text-nowrap" type="submit">
                            <i class="bi bi-search"></i> Buscar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div id="spinner-carga" class="text-center py-3">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Cargando...</span>
        </div>
    </div>

    <div id="contenedor-formulario" class="card" style="display:none;">
        <div class="card-body">

            <form id="form-existencias">
                <div class="d-flex justify-content-end gap-3 mb-3 flex-wrap">
                    <div>
                        <label for="precio_general" class="form-label">Precio Costo</label>
                        <input type="number" step="0.01" id="precio_general" class="form-control form-control-sm">
                    </div>
                    <div>
                        <label for="fecha_general" class="form-label">Fecha</label>
                        <input type="date" id="fecha_general" class="form-control form-control-sm">
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="tabla-existencias" class="table table-striped table-bordered table-hover">
                        <thead class="table-primary">
                            <tr>
                                <th>MODELODES</th>
                                <th>MATERIAL</th>
                                <th>COLOR</th>
                                <th>SUELA</th>
                                <!-- columnas de tallas generadas dinámicamente -->
                            </tr>
                        </thead>
                        <tbody>
                            <!-- filas generadas dinámicamente por existencias.js -->
                        </tbody>
                    </table>
                </div>

                <div class="row justify-content-end">
                    <div class="form-group col-md-3">
                        <button type="button" class="btn btn-secondary w-100" disabled title="Próximamente">
                            <i class="bi bi-printer"></i> Imprimir Etiquetas
                        </button>
                    </div>
                    <div class="form-group col-md-3">
                        <button type="submit" class="btn btn-primary w-100" id="guardar-btn">
                            <i class="bi bi-file-earmark-check me-1"></i> Guardar Existencias
                        </button>
                    </div>
                </div>
            </form>

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
<!-- Lógica del CRUD -->
<script src="assets/js/existencias.js"></script>

</body>
</html>

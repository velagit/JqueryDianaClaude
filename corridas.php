<?php
require_once __DIR__ . '/config/session.php';
requerirLogin();

$tituloPagina = 'administracion de Corridas';
$paginaActiva = 'corridas';
require_once __DIR__ . '/includes/header.php';
?>


<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Corridas por Marca</title>

<!-- Bootstrap 5 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<!-- Estilos propios -->
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="container py-4">

    <div class="topbar-marca">
        <h4 class="mb-0" id="titulo-marca"><i class="bi bi-rulers me-2"></i>Corridas de la marca aaa</h4>
    </div>

    <div class="card mb-3">
        <div class="card-body d-flex flex-wrap gap-2 align-items-center">
            <button id="btn-agregar-corrida" type="button" class="btn btn-primary">
                <i class="bi bi-file-earmark-plus me-1"></i> Agregar Corrida
            </button>

            <a id="link-volver" href="marcas.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left-circle me-1"></i> Volver a Marcas
            </a>
            
        </div>
    </div>

    <div class="card">
        <div class="card-body">

            <div id="spinner-carga" class="text-center py-3">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
            </div>

            <div class="table-responsive">
                <table id="tabla-corridas" class="table table-striped table-bordered table-hover table-sm align-middle">
                    <thead>
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Talla Inicial</th>
                            <th scope="col">Talla Final</th>
                            <th scope="col">Clasificación</th>
                            <th scope="col">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Filas generadas dinámicamente por corridas.js -->
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>

<!-- Modal Crear / Editar Corrida -->
<div class="modal fade" id="modalCorrida" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="form-corrida" novalidate>
                <input type="hidden" id="modo-formulario" value="crear">
                <input type="hidden" name="MARCANO">
                <input type="hidden" name="CORRIDANO">

                <div class="modal-header">
                    <h5 class="modal-title" id="modalCorridaLabel">Agregar Corrida</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label for="TALLAINI" class="form-label">Talla Inicial</label>
                        <input type="number" name="TALLAINI" id="TALLAINI" max="999" class="form-control" required>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="mb-3">
                        <label for="TALLAFIN" class="form-label">Talla Final</label>
                        <input type="number" name="TALLAFIN" id="TALLAFIN" max="999" class="form-control" required>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="mb-3">
                        <label for="CLASIFICA" class="form-label">Clasificación</label>
                        <select name="CLASIFICA" id="CLASIFICA" class="form-select" required>
                            <option value="">Seleccione una opción</option>
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i> Cancelar
                    </button>
                    <button type="submit" id="btn-guardar-corrida" class="btn btn-success">
                        <i class="bi bi-file-earmark-check me-1"></i> Guardar
                    </button>
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
<script src="assets/js/corridas.js"></script>

</body>
</html>

<?php
require_once __DIR__ . '/config/session.php';
requerirLogin();

$tituloPagina = 'administración de Modelos ';
$paginaActiva = 'modelos';
require_once __DIR__ . '/includes/header.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Modelos por Marca</title>

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
        <h4 class="mb-0" id="titulo-marca"><i class="bi bi-box-seam me-2"></i>Modelos de la marca</h4>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row align-items-center g-2">
                <div class="col-12 col-md-3">
                    <button id="btn-agregar-modelo" type="button" class="btn btn-primary w-100">
                        <i class="bi bi-file-earmark-plus me-1"></i> Agregar Modelo
                    </button>
                </div>
                <div class="col-12 col-md-3">
                    <a href="marcas.php" class="btn btn-secondary w-100">
                        <i class="bi bi-arrow-left-circle me-1"></i> Volver a Marcas
                    </a>
                </div>
                <div class="col-12 col-md-6">
                    <input type="text" id="buscador-modelo" class="form-control"
                           placeholder="Buscar por descripción del modelo...">
                </div>
            </div>
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
                <table id="tabla-modelos" class="table table-striped table-bordered table-hover table-sm align-middle">
                    <thead>
                        <tr>
                            <th scope="col">NO</th>
                            <th scope="col">MODELODES</th>
                            <th scope="col">COLOR</th>
                            <th scope="col">MATERIAL</th>
                            <th scope="col">SUELA</th>
                            <th scope="col">PRECIOCO</th>
                            <th scope="col">PRECIOVE</th>
                            <th scope="col">Corrida</th>
                            <th scope="col">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Filas generadas dinámicamente por modelos.js -->
                    </tbody>
                </table>
            </div>

            <nav>
                <ul id="paginacion-modelos" class="pagination justify-content-center">
                    <!-- Paginación generada dinámicamente -->
                </ul>
            </nav>

        </div>
    </div>
</div>

<!-- Modal Crear / Editar Modelo -->
<div class="modal fade" id="modalModelo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="form-modelo" novalidate>
                <input type="hidden" id="modo-formulario" value="crear">
                <input type="hidden" name="MARCANO">
                <input type="hidden" name="MODELONO">

                <div class="modal-header">
                    <h5 class="modal-title" id="modalModeloLabel">Agregar Modelo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="MODELODES" class="form-label">Descripción del Modelo</label>
                            <input type="text" name="MODELODES" id="MODELODES" class="form-control" maxlength="20" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-3">
                            <label for="COLOR" class="form-label">Color</label>
                            <input type="text" name="COLOR" id="COLOR" class="form-control" maxlength="20">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-3">
                            <label for="MATERIAL" class="form-label">Material</label>
                            <input type="text" name="MATERIAL" id="MATERIAL" class="form-control" maxlength="20">
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label for="SUELA" class="form-label">Suela</label>
                            <input type="text" name="SUELA" id="SUELA" class="form-control" maxlength="20">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-3">
                            <label for="PRECIOCO" class="form-label">Precio Compra</label>
                            <input type="number" step="0.01" name="PRECIOCO" id="PRECIOCO" class="form-control">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-3">
                            <label for="PRECIOVE" class="form-label">Precio Venta</label>
                            <input type="number" step="0.01" name="PRECIOVE" id="PRECIOVE" class="form-control">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-3">
                            <label for="SITUACION" class="form-label">Situación</label>
                            <select name="SITUACION" id="SITUACION" class="form-select">
                                <option value="A">Activo</option>
                                <option value="I">Inactivo</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="CORRIDANO" class="form-label">Corrida</label>
                            <select name="CORRIDANO" id="CORRIDANO" class="form-select" required>
                                <option value="">Seleccione una corrida</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="fecha" class="form-label">Fecha</label>
                            <input type="date" name="fecha" id="fecha" class="form-control">
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i> Cancelar
                    </button>
                    <button type="submit" id="btn-guardar-modelo" class="btn btn-success">
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
<script src="assets/js/modelos.js"></script>

</body>
</html>

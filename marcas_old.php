<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Listado de Marcas</title>

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
        <h4 class="mb-0"><i class="bi bi-tags-fill me-2"></i>Listado de Marcas</h4>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-12 col-md-2 mb-2 mb-md-0">
                    <button id="btn-agregar-marca" type="button" class="btn btn-primary w-100">
                        <i class="bi bi-file-earmark-plus me-1"></i> Agregar Marca
                    </button>
                </div>
                <div class="col-12 col-md-2 mb-2 mb-md-0">
                    <a href="consulta_existencias.php" class="btn btn-info w-100">
                        <i class="bi bi-search me-1"></i> Existencias
                    </a>
                </div>
                <div class="col-12 col-md-2 mb-2 mb-md-0">
                    <a href="inventario.php" class="btn btn-secondary w-100">
                        <i class="bi bi-clipboard-data me-1"></i> Inventario
                    </a>
                </div>
                <div class="col-12 col-md-6">
                    <input type="text" id="buscador-marca" class="form-control"
                           placeholder="Buscar por nombre de marca...">
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
                <table id="tabla-marcas" class="table table-striped table-bordered table-hover table-sm align-middle">
                    <thead>
                        <tr>
                            <th scope="col">Código</th>
                            <th scope="col">Descripción</th>
                            <th scope="col">Acciones</th>
                            <th scope="col">Corridas</th>
                            <th scope="col">Modelos</th>
                            <th scope="col">Artículos</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Filas generadas dinámicamente por marcas.js -->
                    </tbody>
                </table>
            </div>

            <nav>
                <ul id="paginacion-marcas" class="pagination justify-content-center">
                    <!-- Paginación generada dinámicamente -->
                </ul>
            </nav>

        </div>
    </div>
</div>

<!-- Modal Crear / Editar Marca -->
<div class="modal fade" id="modalMarca" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="form-marca" novalidate>
                <input type="hidden" id="modo-formulario" value="crear">

                <div class="modal-header">
                    <h5 class="modal-title" id="modalMarcaLabel">Agregar Nueva Marca</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label for="MARCANO" class="form-label">Código Marca</label>
                        <input type="text" name="MARCANO" id="MARCANO" class="form-control" readonly>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="mb-3">
                        <label for="MARCADES" class="form-label">Descripción</label>
                        <input type="text" name="MARCADES" id="MARCADES" class="form-control" maxlength="20" required>
                        <div class="invalid-feedback"></div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i> Cancelar
                    </button>
                    <button type="submit" id="btn-guardar-marca" class="btn btn-success">
                        <i class="bi bi-file-earmark-check me-1"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- Bootstrap 5 JS (incluye Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- Lógica del CRUD -->
<script src="assets/js/marcas.js"></script>

</body>
</html>

<?php
require_once __DIR__ . '/config/session.php';
requerirLogin();

$tituloPagina = 'Vendedores';
$paginaActiva = 'vendedores';
require_once __DIR__ . '/includes/header.php';
?>

<div class="topbar-marca">
    <h4 class="mb-0"><i class="bi bi-person-badge me-2"></i>Vendedores(as) — para ventas con vale</h4>
</div>

<div class="card mb-3">
    <div class="card-body">
        <div class="row align-items-center g-2">
            <div class="col-12 col-md-4">
                <button id="btn-agregar-vendedor" type="button" class="btn btn-primary w-100">
                    <i class="bi bi-person-plus-fill me-1"></i> Agregar Vendedor(a)
                </button>
            </div>
            <div class="col-12 col-md-8">
                <input type="text" id="buscador-vendedor" class="form-control" placeholder="Buscar por nombre o número...">
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div id="spinner-carga" class="text-center py-3">
            <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div>
        </div>
        <div class="table-responsive">
            <table id="tabla-vendedores" class="table table-striped table-bordered table-hover table-sm align-middle">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Nombre</th>
                        <th>Teléfono</th>
                        <th>Rango de vales</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Crear / Editar Vendedor -->
<div class="modal fade" id="modalVendedor" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="form-vendedor" novalidate>
                <input type="hidden" id="modo-formulario" value="crear">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalVendedorLabel">Agregar Vendedor(a)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Número de vendedor(a)</label>
                        <input type="number" name="vendedorno" class="form-control" required>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nombre</label>
                        <input type="text" name="nombre" class="form-control" required>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Dirección</label>
                        <input type="text" name="direccion" class="form-control">
                    </div>
                    <div class="row mb-3">
                        <div class="col-4">
                            <label class="form-label">Teléfono 1</label>
                            <input type="text" name="telefono1" class="form-control">
                        </div>
                        <div class="col-4">
                            <label class="form-label">Teléfono 2</label>
                            <input type="text" name="telefono2" class="form-control">
                        </div>
                        <div class="col-4">
                            <label class="form-label">Teléfono 3</label>
                            <input type="text" name="telefono3" class="form-control">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-4">
                            <label class="form-label">Vale inicial</label>
                            <input type="number" name="valeinicia" class="form-control">
                        </div>
                        <div class="col-4">
                            <label class="form-label">Vale final</label>
                            <input type="number" name="valetermina" class="form-control">
                        </div>
                        <div class="col-4">
                            <label class="form-label">Estado</label>
                            <select name="estado" class="form-select">
                                <option value="A">Activo</option>
                                <option value="C">Cancelado</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-file-earmark-check me-1"></i> Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
<script src="assets/js/vendedores.js"></script>
</body>
</html>

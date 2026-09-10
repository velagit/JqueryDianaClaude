<?php
require_once __DIR__ . '/config/session.php';
requerirLogin();

$tituloPagina = 'Vales Bloqueados';
$paginaActiva = 'vendedores';
require_once __DIR__ . '/includes/header.php';
?>

<div class="topbar-marca">
    <h4 class="mb-0"><i class="bi bi-lock-fill me-2"></i>Vales Bloqueados</h4>
</div>

<div class="alert alert-secondary">
    <i class="bi bi-info-circle me-1"></i>
    Un vale bloqueado no podrá usarse para pagar ventas en el módulo de <a href="ventas.php">Ventas</a> (opción "Cargar a vale de empleado(a)").
</div>

<div class="card mb-3">
    <div class="card-body">
        <button id="btn-agregar-bloqueo" type="button" class="btn btn-primary">
            <i class="bi bi-lock-fill me-1"></i> Bloquear Vale
        </button>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div id="spinner-carga" class="text-center py-3">
            <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div>
        </div>
        <div class="table-responsive">
            <table id="tabla-bloqueados" class="table table-striped table-bordered table-hover table-sm align-middle">
                <thead>
                    <tr>
                        <th>Vendedor No.</th>
                        <th>Nombre</th>
                        <th>Vale No.</th>
                        <th>Fecha</th>
                        <th>Motivo</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Bloquear / Editar -->
<div class="modal fade" id="modalBloqueo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="form-bloqueo" novalidate>
                <input type="hidden" id="modo-formulario" value="crear">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalBloqueoLabel">Bloquear Vale</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label">Número de vendedor(a)</label>
                            <input type="number" name="novendedor" class="form-control" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Número de vale</label>
                            <input type="number" name="novale" class="form-control" required>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Fecha</label>
                        <input type="date" name="fecha" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Motivo</label>
                        <input type="text" name="motivo" class="form-control" required>
                        <div class="invalid-feedback"></div>
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
<script src="assets/js/bloqueados.js"></script>
</body>
</html>

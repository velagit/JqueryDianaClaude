<?php
require_once __DIR__ . '/config/session.php';
requerirLogin();

$tituloPagina = 'Pedidos a Proveedor';
$paginaActiva = 'pedidos';
require_once __DIR__ . '/includes/header.php';
?>

<div class="topbar-marca d-flex justify-content-between align-items-center flex-wrap gap-2">
    <h4 class="mb-0"><i class="bi bi-truck me-2"></i>Pedidos a Proveedor</h4>
    <div class="text-end">
        <div id="texto-acumulado" class="small">0 modelo(s) pendientes de imprimir</div>
    </div>
</div>

<div class="alert alert-info">
    <i class="bi bi-info-circle me-1"></i>
    Selecciona marca y corrida, revisa la <strong>existencia actual</strong> (fila gris) y captura debajo la
    <strong>cantidad a pedir</strong> por talla. Puedes repetir esto con varias marcas — se van acumulando —
    y al final presiona <strong>Imprimir Pedido</strong> para generar el reporte consolidado (esto vacía el acumulado).
</div>

<div class="card mb-3">
    <div class="card-body">
        <div class="row g-2 align-items-end">
            <div class="col-12 col-md-3">
                <label class="form-label">Marca</label>
                <select id="select-marca-pedido" class="form-select">
                    <option value="">Seleccione una marca</option>
                </select>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label">Corrida</label>
                <select id="select-corrida-pedido" class="form-select">
                    <option value="">Seleccione corrida</option>
                </select>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label">Buscar modelo</label>
                <input type="text" id="buscador-modelo-pedido" class="form-control" placeholder="Filtrar por descripción...">
            </div>
            <div class="col-6 col-md-1">
                <button id="btn-ver-acumulado" type="button" class="btn btn-outline-secondary w-100" title="Ver acumulado">
                    <i class="bi bi-list-check"></i>
                </button>
            </div>
            <div class="col-6 col-md-2">
                <button id="btn-imprimir-pedido" type="button" class="btn btn-primary w-100" disabled>
                    <i class="bi bi-printer me-1"></i> Imprimir Pedido
                </button>
            </div>
        </div>
    </div>
</div>

<div id="contenedor-grid-pedido" class="card mb-3" style="display:none;">
    <div class="card-body">
        <div class="table-responsive">
            <table id="tabla-pedido" class="table table-bordered table-sm align-middle">
                <thead class="table-primary">
                    <tr>
                        <th>Modelo</th><th>Color</th><th>Piel</th><th>Suela</th><th></th>
                        <!-- columnas de talla generadas dinámicamente -->
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
        <div class="text-end">
            <button id="btn-agregar-pedido" type="button" class="btn btn-success">
                <i class="bi bi-plus-circle me-1"></i> Agregar al Pedido Acumulado
            </button>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
<script src="assets/js/pedidos.js"></script>
</body>
</html>

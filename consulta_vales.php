<?php
require_once __DIR__ . '/config/session.php';
requerirLogin();

$tituloPagina = 'Consulta de Vales';
$paginaActiva = 'vendedores';
require_once __DIR__ . '/includes/header.php';
?>

<div class="topbar-marca">
    <h4 class="mb-0"><i class="bi bi-journal-text me-2"></i>Consulta de Vales (ventas por remisión)</h4>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form id="form-consulta-vales" class="row g-3 align-items-end">
            <div class="col-6 col-md-2">
                <label class="form-label">No. de vale</label>
                <input type="number" id="input-vale" class="form-control">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label">No. de vendedor(a)</label>
                <input type="number" id="input-vendedor" class="form-control">
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label">&nbsp;</label>
                <div id="texto-nombre-vendedor" class="form-text fw-bold"></div>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label">Fecha</label>
                <input type="date" id="input-fecha" class="form-control">
            </div>
            <div class="col-6 col-md-3">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search me-1"></i> Buscar</button>
                <button type="button" id="btn-limpiar-filtros" class="btn btn-link w-100">Limpiar filtros</button>
            </div>
        </form>
    </div>
</div>

<div id="spinner-carga" class="text-center py-3">
    <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div>
</div>

<div class="row g-3">
    <div class="col-12 col-lg-7">
        <div class="card">
            <div class="card-body">
                <h6 class="mb-2">Notas de vale <small class="text-muted">(clic en una fila para ver el detalle)</small></h6>
                <div class="table-responsive" style="max-height:420px; overflow-y:auto;">
                    <table id="tabla-notas" class="table table-striped table-bordered table-hover table-sm">
                        <thead class="table-primary">
                            <tr><th>Folio</th><th>Fecha</th><th>Vale</th><th>Vendedor(a)</th><th class="text-end">Importe</th></tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                <p class="text-end fs-5 mb-0">Total: <strong id="texto-total-notas">$0.00</strong></p>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-5">
        <div class="card">
            <div class="card-body">
                <h6 class="mb-2">Detalle de la nota seleccionada</h6>
                <div class="table-responsive" style="max-height:420px; overflow-y:auto;">
                    <table id="tabla-detalle" class="table table-striped table-bordered table-sm">
                        <thead class="table-secondary">
                            <tr><th>Código</th><th>Modelo</th><th>Color</th><th>Talla</th><th class="text-end">Precio</th></tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
<script src="assets/js/consulta_vales.js"></script>
</body>
</html>

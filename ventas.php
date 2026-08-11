<?php
require_once __DIR__ . '/config/session.php';
requerirLogin();

$tituloPagina = 'Punto de Venta';
$paginaActiva = 'ventas';
require_once __DIR__ . '/includes/header.php';
?>

<div class="topbar-marca d-flex justify-content-between align-items-center flex-wrap gap-2">
    <h4 class="mb-0"><i class="bi bi-cash-coin me-2"></i>Punto de Venta</h4>
    <span class="fs-5">Folio: <strong id="texto-folio">—</strong></span>
</div>

<div class="alert alert-info">
    <i class="bi bi-info-circle me-1"></i>
    Fase 1 del módulo: captura de artículos (código de barras o búsqueda manual) y cobro de <strong>contado</strong>.
    El pago con <strong>vale/remisión</strong>, el <strong>ticket imprimible</strong> y las <strong>devoluciones completas</strong> se agregarán en las siguientes fases.
</div>

<div class="card mb-3">
    <div class="card-body">
        <div class="row g-2 align-items-end">
            <div class="col-12 col-md-8">
                <label class="form-label">Código de barras</label>
                <input type="text" id="codbarra" class="form-control form-control-lg" placeholder="Escanea o escribe el código y presiona Enter" autocomplete="off">
                <div class="form-text">Para devoluciones, escribe <strong>D</strong> seguido del código (ej. D205001305) y presiona Enter.</div>
            </div>
            <div class="col-6 col-md-2">
                <button id="btn-busqueda-manual" type="button" class="btn btn-secondary w-100">
                    <i class="bi bi-search me-1"></i> Buscar
                </button>
            </div>
            <div class="col-6 col-md-2">
                <button id="btn-editar-precio" type="button" class="btn btn-outline-primary w-100">
                    <i class="bi bi-pencil me-1"></i> Precio
                </button>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <div class="table-responsive">
            <table id="tabla-carrito" class="table table-striped table-bordered table-hover align-middle">
                <thead class="table-primary">
                    <tr>
                        <th>Código</th>
                        <th>Marca</th>
                        <th>Modelo</th>
                        <th>Color</th>
                        <th>Piel</th>
                        <th>Suela</th>
                        <th>Talla</th>
                        <th>Precio</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Filas generadas dinámicamente por ventas.js -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="row align-items-center g-3">
            <div class="col-12 col-md-4">
                <div class="fs-5">Artículos: <strong id="texto-cantidad-articulos">0</strong></div>
                <div class="fs-3">Total: <strong id="texto-total">$0.00</strong></div>
            </div>
            <div class="col-12 col-md-8">
                <div class="row g-2">
                    <div class="col-6 col-md-3">
                        <button id="btn-nueva-venta" type="button" class="btn btn-secondary w-100">
                            <i class="bi bi-arrow-repeat me-1"></i> Nueva
                        </button>
                    </div>
                    <div class="col-6 col-md-3">
                        <button id="btn-abrir-cajon" type="button" class="btn btn-outline-secondary w-100">
                            <i class="bi bi-box-seam me-1"></i> Cajón
                        </button>
                    </div>
                    <div class="col-6 col-md-3">
                        <button id="btn-remision" type="button" class="btn btn-outline-warning w-100">
                            <i class="bi bi-receipt me-1"></i> Vale
                        </button>
                    </div>
                    <div class="col-6 col-md-3">
                        <button id="btn-cobrar" type="button" class="btn btn-primary w-100">
                            <i class="bi bi-cash-stack me-1"></i> Cobrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Búsqueda Manual -->
<div class="modal fade" id="modalBusquedaManual" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="form-busqueda-manual">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-search me-1"></i> Búsqueda manual</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Marca</label>
                        <select id="manual-marcano" class="form-select" required>
                            <option value="">Seleccione una marca</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Modelo</label>
                        <select id="manual-modelo-select" class="form-select" required>
                            <option value="">Seleccione modelo</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Talla</label>
                        <input type="number" id="manual-talla" class="form-control" required>
                    </div>
                    <div id="resultado-busqueda-manual"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Agregar al carrito</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Cobro (contado) -->
<div class="modal fade" id="modalCobro" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="form-cobro">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-cash-stack me-1"></i> Cobro de contado</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <p class="fs-4">Total a pagar: <strong id="texto-total-modal">$0.00</strong></p>
                    <div class="mb-3">
                        <label class="form-label">Monto pagado</label>
                        <input type="number" step="0.01" id="input-pagado" class="form-control form-control-lg" required autofocus>
                    </div>
                    <p class="fs-5">Cambio: <strong id="texto-cambio">—</strong></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" id="btn-confirmar-cobro" class="btn btn-success">
                        <i class="bi bi-file-earmark-check me-1"></i> Confirmar venta
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
<script src="assets/js/ventas.js"></script>
</body>
</html>

<?php
require_once __DIR__ . '/config/session.php';
requerirLogin();

$tituloPagina = 'Corte de Caja';
$paginaActiva = 'corte';
require_once __DIR__ . '/includes/header.php';
?>

<div class="topbar-marca">
    <h4 class="mb-0"><i class="bi bi-calculator me-2"></i>Corte de Caja Diario</h4>
</div>

<div id="spinner-carga" class="text-center py-3">
    <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div>
</div>

<div class="card">
    <div class="card-body">
        <p class="fs-5">Fecha: <strong id="texto-fecha">—</strong></p>

        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="p-3 border rounded text-center">
                    <div class="text-muted small">Recibos</div>
                    <div class="fs-3" id="texto-recibos">0</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-3 border rounded text-center">
                    <div class="text-muted small">Subtotal</div>
                    <div class="fs-4" id="texto-subtotal">$0.00</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-3 border rounded text-center">
                    <div class="text-muted small">IVA</div>
                    <div class="fs-4" id="texto-iva">$0.00</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-3 border rounded text-center bg-light">
                    <div class="text-muted small">Total del día</div>
                    <div class="fs-3 fw-bold" id="texto-total">$0.00</div>
                </div>
            </div>
        </div>

        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle me-1"></i>
            Al confirmar el corte, estos contadores se <strong>reiniciarán a cero</strong> y no podrán recuperarse.
            Imprime primero el corte si necesitas conservar un comprobante.
        </div>

        <div class="row g-2">
            <div class="col-6">
                <button id="btn-imprimir-corte" type="button" class="btn btn-secondary w-100">
                    <i class="bi bi-printer me-1"></i> Imprimir (vista previa)
                </button>
            </div>
            <div class="col-6">
                <button id="btn-confirmar-corte" type="button" class="btn btn-danger w-100">
                    <i class="bi bi-check-circle me-1"></i> Confirmar Corte
                </button>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
<script src="assets/js/corte.js"></script>
</body>
</html>

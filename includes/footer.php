</div> <!-- /container-fluid abierto en includes/header.php -->

<footer class="footer-sistema">
    <i class="bi bi-boxes me-1"></i> Sistema de Inventario &copy; <?= date('Y') ?>
    <?php if (function_exists('usuarioActual') && usuarioActual()): ?>
        &nbsp;|&nbsp; Sesión: <?= htmlspecialchars(usuarioActual()['nombre']) ?>
        (<?= htmlspecialchars(usuarioActual()['tipo']) ?>)
    <?php endif; ?>
</footer>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- Bootstrap 5 JS (incluye Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- A partir de aquí, cada página agrega su propio <script src="assets/js/xxx.js"></script> y cierra </body></html> -->

<?php
require_once __DIR__ . '/config/session.php';
requerirAdmin();

$tituloPagina = 'Gestión de Usuarios';
$paginaActiva = 'usuarios';
require_once __DIR__ . '/includes/header.php';
?>

<div class="topbar-marca">
    <h4 class="mb-0"><i class="bi bi-people-fill me-2"></i>Gestión de Usuarios</h4>
</div>

<div class="card mb-3">
    <div class="card-body">
        <button id="btn-agregar-usuario" type="button" class="btn btn-primary">
            <i class="bi bi-person-plus-fill me-1"></i> Agregar Usuario
        </button>
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
            <table id="tabla-usuarios" class="table table-striped table-bordered table-hover table-sm align-middle">
                <thead>
                    <tr>
                        <th scope="col">Nombre</th>
                        <th scope="col">Usuario</th>
                        <th scope="col">Rol</th>
                        <th scope="col">Estado</th>
                        <th scope="col">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Filas generadas dinámicamente por usuarios.js -->
                </tbody>
            </table>
        </div>

    </div>
</div>

<!-- Modal Crear / Editar Usuario -->
<div class="modal fade" id="modalUsuario" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="form-usuario" novalidate>
                <input type="hidden" id="modo-formulario" value="crear">
                <input type="hidden" name="id">

                <div class="modal-header">
                    <h5 class="modal-title" id="modalUsuarioLabel">Agregar Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre completo</label>
                        <input type="text" name="nombre" class="form-control" maxlength="100" required>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Usuario (para iniciar sesión)</label>
                        <input type="text" name="usuario" class="form-control" maxlength="50" required autocomplete="off">
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" id="texto-password">Contraseña</label>
                        <input type="password" id="password" name="password" class="form-control" autocomplete="new-password">
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Rol</label>
                        <select name="tipo" class="form-select" required>
                            <option value="general">General</option>
                            <option value="administrador">Administrador</option>
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" name="activo" checked>
                        <label class="form-check-label">Usuario activo</label>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i> Cancelar
                    </button>
                    <button type="submit" id="btn-guardar-usuario" class="btn btn-success">
                        <i class="bi bi-file-earmark-check me-1"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
<script src="assets/js/usuarios.js"></script>
</body>
</html>

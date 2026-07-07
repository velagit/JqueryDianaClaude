<?php
require_once __DIR__ . '/config/session.php';

// Si ya hay sesión activa, redirige directo al sistema
if (estaAutenticado()) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Iniciar Sesión</title>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="assets/css/style.css">
<style>
    body {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #0d47a1 0%, #1565c0 100%);
    }
    .card-login {
        width: 100%;
        max-width: 380px;
        border: none;
        border-radius: 0.8rem;
        box-shadow: 0 10px 30px rgba(0,0,0,0.25);
    }
    .card-login .card-header {
        background-color: var(--azul-primario);
        color: #fff;
        text-align: center;
        border-radius: 0.8rem 0.8rem 0 0;
        padding: 1.5rem;
    }
    .card-login .card-header i {
        font-size: 2.5rem;
    }
</style>
</head>
<body>

<div class="card card-login">
    <div class="card-header">
        <i class="bi bi-boxes"></i>
        <h4 class="mt-2 mb-0">Sistema de Inventario</h4>
        <small>Inicia sesión para continuar</small>
    </div>
    <div class="card-body p-4">
        <form id="form-login" novalidate>
            <div class="mb-3">
                <label for="usuario" class="form-label">Usuario</label>
                <input type="text" id="usuario" name="usuario" class="form-control" autofocus required>
                <div class="invalid-feedback"></div>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Contraseña</label>
                <input type="password" id="password" name="password" class="form-control" required>
                <div class="invalid-feedback"></div>
            </div>
            <button type="submit" id="btn-login" class="btn btn-primary w-100">
                <i class="bi bi-box-arrow-in-right me-1"></i> Entrar
            </button>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="assets/js/login.js"></script>
</body>
</html>

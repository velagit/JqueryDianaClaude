<?php
/**
 * Header compartido. Antes de incluir este archivo, la página debe:
 *   1. require_once 'config/session.php'; y llamar a requerirLogin() o requerirAdmin();
 *   2. (opcional) definir $tituloPagina y $paginaActiva ('marcas'|'existencias'|'inventario'|'usuarios')
 */

$tituloPagina = $tituloPagina ?? 'Sistema de Inventario';
$paginaActiva = $paginaActiva ?? '';
$usuario      = usuarioActual();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($tituloPagina) ?></title>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="assets/css/style2.css">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark navbar-sistema mb-4">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php"><i class="bi bi-boxes me-2"></i>Sistema de Inventario</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSistema">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarSistema">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?= $paginaActiva === 'marcas' ? 'active' : '' ?>" href="marcas.php">
                        <i class="bi bi-tags-fill me-1"></i> Marcas
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $paginaActiva === 'existencias' ? 'active' : '' ?>" href="consulta_existencias.php">
                        <i class="bi bi-search me-1"></i> Existencias
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $paginaActiva === 'inventario' ? 'active' : '' ?>" href="inventario.php">
                        <i class="bi bi-clipboard-data me-1"></i> Inventario
                    </a>
                </li>
                <?php if (esAdministrador()): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $paginaActiva === 'usuarios' ? 'active' : '' ?>" href="usuarios.php">
                        <i class="bi bi-people-fill me-1"></i> Usuarios
                    </a>
                </li>
                <?php endif; ?>
            </ul>
            <span class="navbar-text text-white me-3">
                <i class="bi bi-person-circle me-1"></i> <?= htmlspecialchars($usuario['nombre'] ?? '') ?>
                <span class="badge bg-light text-primary ms-1"><?= htmlspecialchars($usuario['tipo'] ?? '') ?></span>
            </span>
            <a href="logout.php" class="btn btn-outline-light btn-sm">
                <i class="bi bi-box-arrow-right me-1"></i> Salir
            </a>
        </div>
    </div>
</nav>

<div class="container-fluid pb-4">

<?php
session_start(); // 🔐 Iniciar sesión para validar usuario
include("../CONEXION/conexion.php"); // Conexión a la base de datos

// 🔐 VALIDAR SESIÓN
if (!isset($_SESSION['Cedula'])) {
    header("Location: ../LOGIN/login.php"); // Redirige al login si no hay sesión
    exit;
}

/* 🔐 VALIDAR ROL */
$rol = strtoupper(trim($_SESSION['Nombre_tipo'])); // Convertimos el rol a mayúsculas y eliminamos espacios
$rol = str_replace([' ', 'Á', 'É', 'Í', 'Ó', 'Ú'], ['_', 'A', 'E', 'I', 'O', 'U'], $rol); // Normalizar caracteres especiales

if ($rol !== 'ADMINISTRADOR') {
    header("Location: ../DASHBOARD/dashboard.php"); // Redirige si no es administrador
    exit;
}

/* 📄 LISTAR TODOS LOS DESPACHOS */
$res = $conexion->query("SELECT * FROM despachos ORDER BY Id_Despacho ASC"); // Consulta todos los despachos
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Despachos - Administrador</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Estilos personalizados -->
    <link rel="stylesheet" href="../CSS/admin.css">
</head>

<body>
    <div class="container mt-4 admin-panel">

        <!-- Título principal -->
        <h3 class="admin-main-title">👑 Vista General de Despachos • Kaiu Home</h3>

        <!-- Tabla responsive -->
        <div class="table-responsive">
            <table class="table table-hover admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Cédula</th>
                        <th>Nombre</th>
                        <th>Producto</th>
                        <th>Destino</th>
                        <th>Guía</th>
                        <th>Salida</th>
                        <th>Entrega</th>
                        <th>Estado</th>
                    </tr>
                </thead>

                <tbody>
                    <?php $i = 1; ?> <!-- 🔥 CONTADOR VISUAL para numerar filas -->
                    <?php while ($d = $res->fetch_assoc()): ?> <!-- Iterar sobre cada despacho -->
                        <tr>
                            <!-- ID visual -->
                            <td><strong><span class="badge-admin">#<?= $i++ ?></span></strong></td>

                            <!-- Datos del cliente y producto -->
                            <td><strong><span class="badge-admin"><?= $d['Cedula_Cliente'] ?></span></strong></td>
                            <td><strong><?= $d['Nombre_Cliente'] ?></strong></td>
                            <td><strong><?= $d['Producto'] ?></strong></td>
                            <td><strong><?= $d['Destino'] ?></strong></td>

                            <!-- Número de guía -->
                            <td><strong><span class="texto-guia">#<?= $d['Numero_Guia'] ?></span></strong></td>

                            <!-- Fechas de salida y entrega -->
                            <td><strong><span class="texto-salida"><?= $d['Fecha_Salida'] ?></span></strong></td>
                            <td><strong><span class="texto-entrega"><?= $d['Fecha_Entrega'] ?></span></strong></td>

                            <!-- Estado del despacho -->
                            <td>
                                <strong>
                                <?php if ($d['Estado'] === 'ACTIVO'): ?>
                                    <span class="estado-activo">ACTIVO</span>
                                <?php elseif ($d['Estado'] === 'INACTIVO'): ?>
                                    <span class="estado-inactivo">INACTIVO</span>
                                <?php else: ?>
                                    <span class="estado-entregado">ENTREGADO</span>
                                <?php endif; ?>
                                </strong>
                            </td>
                        </tr>
                    <?php endwhile; ?> <!-- Fin del while -->
                </tbody>
            </table>
        </div>

        <!-- Botón para volver al dashboard -->
        <div class="text-center mt-4">
            <a href="../DASHBOARD/dashboard.php" class="btn btn-secondary">⬅ Volver</a>
        </div>

    </div>
</body>
</html>
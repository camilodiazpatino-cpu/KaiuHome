<?php
session_start(); // 🔐 Inicia sesión para acceder a los datos del usuario
include("../CONEXION/conexion.php"); // 🔌 Conexión a la base de datos

/* =========================
   🔐 VALIDAR SESIÓN
   Verifica que el usuario esté logueado
========================= */
if (!isset($_SESSION['Cedula'])) {
    header("Location: ../LOGIN/login.php"); // Redirige al login si no hay sesión
    exit;
}

/* =========================
   🔐 VALIDAR ROL
   Solo pueden entrar:
   - LOGISTICA
   - JEFE DE LOGISTICA
   - ADMINISTRADOR
========================= */
$rol = strtoupper(trim($_SESSION['Nombre_tipo']));

if ($rol !== 'LOGISTICA' && $rol !== 'JEFE_DE_LOGISTICA' && $rol !== 'ADMINISTRADOR') {
    header("Location: ../LOGIN/login.php"); // Bloquea acceso si no tiene permiso
    exit;
}

/* =========================
   📄 LISTAR DESPACHOS
   Consulta todos los despachos registrados
========================= */
$sql = "
    SELECT *
    FROM despachos
    ORDER BY Id_Despacho ASC
";

// Ejecuta la consulta
$res = $conexion->query($sql);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <!-- 🚚 Título del módulo -->
    <title>Despachos</title>

    <!-- 🎨 Bootstrap -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">

    <!-- 🎨 Estilos personalizados -->
    <link rel="stylesheet" href="../CSS/kaiu.css">
</head>

<body>

    <div class="container mt-5 kaiu-container">

        <!-- 📦 Tarjeta principal -->
        <div class="card-kaiu">

            <!-- 🔥 Título -->
            <h3 class="kaiu-title">🚚 Despachos Kaiu Home</h3>

            <!-- 📋 Tabla de despachos -->
            <table class="table kaiu-table">

                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Cédula Cliente</th>
                        <th>Nombre Cliente</th>
                        <th>Producto</th>
                        <th>Destino</th>
                        <th>Guía</th>
                        <th>Salida</th>
                        <th>Entrega</th>
                        <th>Estado</th>
                    </tr>
                </thead>

                <tbody>

                    <?php $i = 1; ?> <!-- 🔥 CONTADOR VISUAL -->

                    <!-- 🔁 Recorre todos los despachos -->
                    <?php while ($d = $res->fetch_assoc()): ?>
                        <tr>

                            <!-- 🔢 ID visual -->
                            <td><span class="badge-mantenimiento">#<?= $i++ ?></span></td>

                            <!-- 👤 Cédula cliente -->
                            <td>
                                <span class="badge-mantenimiento">
                                    <?= $d['Cedula_Cliente'] ?>
                                </span>
                            </td>

                            <!-- 👤 Nombre cliente -->
                            <td><strong><?= $d['Nombre_Cliente'] ?></strong></td>

                            <!-- 📦 Producto -->
                            <td><strong><?= $d['Producto'] ?></strong></td>

                            <!-- 📍 Destino -->
                            <td>
                                <strong>
                                    <span class="badge-produccion">
                                        <?= $d['Destino'] ?>
                                    </span>
                                </strong>
                            </td>

                            <!-- 📦 Número de guía -->
                            <td>
                                <span class="badge-mantenimiento">
                                    #<?= $d['Numero_Guia'] ?>
                                </span>
                            </td>

                            <!-- 📅 Fecha salida -->
                            <td><strong><?= $d['Fecha_Salida'] ?></strong></td>

                            <!-- 📅 Fecha entrega (puede ser null) -->
                            <td>
                                <strong>
                                    <?= $d['Fecha_Entrega'] ? $d['Fecha_Entrega'] : '-' ?>
                                </strong>
                            </td>

                            <!-- 📊 Estado del despacho -->
                            <td>
                                <strong>

                                    <?php if ($d['Estado'] === 'ACTIVO'): ?>
                                        <span class="badge bg-success">ACTIVO</span>

                                    <?php elseif ($d['Estado'] === 'INACTIVO'): ?>
                                        <span class="badge bg-danger">INACTIVO</span>

                                    <?php elseif ($d['Estado'] === 'ENTREGADO'): ?>
                                        <span class="badge bg-primary">ENTREGADO</span>

                                    <?php endif; ?>

                                </strong>
                            </td>

                        </tr>
                    <?php endwhile; ?>

                </tbody>
            </table>

            <!-- 🔙 Botón volver -->
            <div class="text-center mt-4">
                <a href="../DASHBOARD/dashboard.php" class="btn btn-kaiu">
                    ⬅ Volver
                </a>
            </div>

        </div>
    </div>

</body>

</html>
<?php
session_start(); // 🔐 Inicia la sesión para acceder a variables de usuario
include("../CONEXION/conexion.php"); // 🔌 Conexión a la base de datos

/* =========================
   🔐 VALIDAR SESIÓN
   Verifica que el usuario esté logueado
========================= */
if (!isset($_SESSION['Cedula']) || !isset($_SESSION['Nombre_tipo'])) {
    header("Location: ../LOGIN/login.php"); // Redirige si no hay sesión
    exit;
}

// Guarda la cédula del usuario logueado
$cedula = $_SESSION['Cedula'];

/* =========================
   🔐 VALIDAR ROL
   Normaliza el nombre del rol para evitar errores
========================= */
$rol = strtoupper(trim($_SESSION['Nombre_tipo']));
$rol = str_replace([' ', 'Á', 'É', 'Í', 'Ó', 'Ú'], ['_', 'A', 'E', 'I', 'O', 'U'], $rol);

/* =========================
   📌 CONSULTA SEGÚN ROL
   Si es jefe o admin ve TODAS las compras
   Si es trabajador solo ve las suyas
========================= */
if ($rol === 'JEFE_DE_LOGISTICA' || $rol === 'ADMINISTRADOR' || $rol === 'LOGISTICA') {

    // 🔎 Consulta general de compras
    $sql = "
        SELECT 
            c.Id_Compra,
            p.Nombre AS Proveedor,
            u.Nombre AS Usuario,
            c.Fecha_Compra,
            c.Total,
            c.Estado
        FROM compras c
        JOIN proveedores p ON c.Id_Proveedor = p.Id_Proveedor
        JOIN usuarios u ON c.Cedula_Usuario = u.Cedula
        ORDER BY c.Id_Compra ASC
    ";
} else {

    // 🔎 Consulta solo compras del usuario logueado
    $sql = "
        SELECT 
            c.Id_Compra,
            p.Nombre AS Proveedor,
            u.Nombre AS Usuario,
            c.Fecha_Compra,
            c.Total,
            c.Estado
        FROM compras c
        JOIN proveedores p ON c.Id_Proveedor = p.Id_Proveedor
        JOIN usuarios u ON c.Cedula_Usuario = u.Cedula
        WHERE c.Cedula_Usuario = '$cedula'
        ORDER BY c.Id_Compra ASC
    ";
}

// Ejecuta la consulta
$res = $conexion->query($sql);
?>


<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <!-- 🧾 Título de la página -->
    <title>Compras</title>

    <!-- 🎨 Bootstrap para estilos -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- 🎨 CSS personalizado del sistema -->
    <link rel="stylesheet" href="../CSS/kaiu.css">
</head>

<body>

    <div class="container mt-5 kaiu-container">

        <!-- 🧾 Tarjeta principal -->
        <div class="card-kaiu">

            <!-- 🔥 Título -->
            <h3 class="kaiu-title">🧾 Compras Kaiu Home</h3>

            <!-- 📋 Tabla de compras -->
            <table class="table kaiu-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Proveedor</th>
                        <th>Usuario</th>
                        <th>Fecha</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody>

                    <?php $i = 1; ?> <!-- 🔥 CONTADOR VISUAL -->

                    <!-- 🔁 Recorre cada compra -->
                    <?php while ($c = $res->fetch_assoc()): ?>
                        <tr>

                            <!-- 🔢 Número visual -->
                            <?php $num = $i; ?>
                            <td><span class="badge-mantenimiento">#<?= $num ?></span></td>
                            <?php $i++; ?>

                            <!-- 🏢 Proveedor -->
                            <td><strong><?= $c['Proveedor'] ?></strong></td>

                            <!-- 👤 Usuario que hizo la compra -->
                            <td><?= $c['Usuario'] ?></td>

                            <!-- 📅 Fecha -->
                            <td><?= $c['Fecha_Compra'] ?></td>

                            <!-- 💰 Total formateado -->
                            <td class="text-success fw-bold">
                                $<?= number_format($c['Total'], 0, ',', '.') ?>
                            </td>

                            <!-- 📌 Estado con estilos -->
                            <td>
                                <?php
                                $estado = strtoupper($c['Estado']);

                                if ($estado === 'RECIBIDA') {
                                    echo '<span class="badge-disponible">Recibida</span>';
                                } elseif ($estado === 'PENDIENTE') {
                                    echo '<span class="badge-produccion">Pendiente</span>';
                                } else {
                                    echo '<span class="badge-inactivo">' . $c['Estado'] . '</span>';
                                }
                                ?>
                            </td>

                            <!-- 👁 Ver detalle de la compra -->
                            <td>
                                <a href="detalle_compra.php?id=<?= urlencode($c['Id_Compra']) ?>&num=<?= $num ?>"
                                    class="btn btn-sm btn-kaiu">
                                    👁 Ver detalle
                                </a>
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
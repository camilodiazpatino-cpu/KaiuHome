<?php
// 🔐 Inicia la sesión para validar usuario logueado
session_start();

// 🔌 Incluye la conexión a la base de datos
include("../CONEXION/conexion.php");

// 🔐 Verifica si el usuario ha iniciado sesión
if (!isset($_SESSION['Cedula'])) {
    // ❌ Si no hay sesión, lo envía al login
    header("Location: ../LOGIN/login.php");
    exit;
}

/* =========================
   📄 LISTAR PROVEEDORES
========================= */

// 🧾 Consulta para obtener todos los proveedores registrados
$res = $conexion->query("
    SELECT 
        Id_Proveedor,
        Nombre,
        Contacto,
        Telefono,
        Correo,
        Direccion
    FROM proveedores
    ORDER BY Id_Proveedor ASC
");
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <!-- 🧾 Título de la pestaña -->
    <title>Proveedores</title>

    <!-- 🎨 Bootstrap para estilos -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">

    <!-- 🎨 Estilos personalizados del sistema -->
    <link rel="stylesheet" href="../CSS/kaiu.css">
</head>

<body>
    <div class="container mt-5 kaiu-container">

        <!-- 🎴 Tarjeta visual del módulo -->
        <div class="card-kaiu">

            <!-- 🧾 Título del módulo -->
            <h3 class="kaiu-title">🤝 Proveedores Kaiu Home</h3>

            <table class="table kaiu-table">

                <!-- 🧠 Encabezados de la tabla -->
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Contacto</th>
                        <th>Teléfono</th>
                        <th>Correo</th>
                        <th>Dirección</th>
                    </tr>
                </thead>

                <tbody>

                    <!-- 🔢 Contador visual para numeración -->
                    <?php $i = 1; ?>

                    <!-- 🔁 Recorre todos los proveedores -->
                    <?php while ($p = $res->fetch_assoc()): ?>
                        <tr>

                            <!-- 🔢 Número visual -->
                            <td>
                                <span class="badge-mantenimiento">
                                    #<?= $i++ ?>
                                </span>
                            </td>

                            <!-- 🏢 Nombre del proveedor -->
                            <td>
                                <strong><?= $p['Nombre'] ?></strong>
                            </td>

                            <!-- 👤 Persona de contacto -->
                            <td>
                                <?= $p['Contacto'] ?>
                            </td>

                            <!-- 📞 Teléfono -->
                            <td>
                                <?= $p['Telefono'] ?>
                            </td>

                            <!-- 📧 Correo electrónico -->
                            <td>
                                <?= $p['Correo'] ?>
                            </td>

                            <!-- 📍 Dirección con estilo visual -->
                            <td>
                                <span class="badge-produccion">
                                    <?= $p['Direccion'] ?>
                                </span>
                            </td>

                        </tr>
                    <?php endwhile; ?>
                </tbody>

            </table>

            <!-- 🔙 Botón para regresar al dashboard -->
            <div class="text-center mt-4">
                <a href="../DASHBOARD/dashboard.php" class="btn btn-kaiu">
                    ⬅ Volver
                </a>
            </div>

        </div>
    </div>

</body>

</html>
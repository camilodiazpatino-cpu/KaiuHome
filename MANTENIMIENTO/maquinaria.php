<?php
session_start();
include("../CONEXION/conexion.php");

/* =========================
   🔐 VALIDAR SESIÓN
   Verifica que el usuario haya iniciado sesión
========================= */
if (!isset($_SESSION['Cedula'])) {
    header("Location: ../LOGIN/login.php");
    exit;
}

/* =========================
   📄 CONSULTA DE MAQUINARIA
   Obtiene todos los registros de la tabla maquinaria
========================= */
$sql = "
    SELECT 
        Id_Maquina,
        Nombre,
        Marca,
        Modelo,
        Fecha_Compra,
        Estado
    FROM maquinaria
    ORDER BY Id_Maquina ASC
";

/* ▶ Ejecutar la consulta */
$res = $conexion->query($sql);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <!-- 🏷 Título de la página -->
    <title>Maquinaria</title>

    <!-- 🎨 Bootstrap para estilos base -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">

    <!-- 🎨 CSS personalizado del sistema -->
    <link rel="stylesheet" href="../CSS/kaiu.css">
</head>

<body>

    <!-- 📦 Contenedor principal -->
    <div class="container mt-5 kaiu-container">

        <!-- 🎴 Tarjeta visual -->
        <div class="card-kaiu">

            <!-- 🏷 Título del módulo -->
            <h3 class="kaiu-title">⚙️ Maquinaria • Kaiu Home</h3>

            <!-- =====================================================
                 📊 TABLA DE MAQUINARIA
            ====================================================== -->
            <table class="table kaiu-table">

                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Marca</th>
                        <th>Modelo</th>
                        <th>Fecha compra</th>
                        <th>Estado</th>
                    </tr>
                </thead>

                <tbody>

                    <!-- 🔢 Contador visual independiente del ID real -->
                    <?php $i = 1; ?>

                    <!-- 🔁 Recorre cada máquina -->
                    <?php while ($m = $res->fetch_assoc()): ?>

                        <tr>

                            <!-- 🔢 Número visual -->
                            <td>
                                <span class="badge-mantenimiento">
                                    #<?= $i++ ?>
                                </span>
                            </td>

                            <!-- 🏭 Nombre de la máquina -->
                            <td><strong><?= $m['Nombre'] ?></strong></td>

                            <!-- 🏷 Marca -->
                            <td><?= $m['Marca'] ?></td>

                            <!-- 🔢 Modelo -->
                            <td><?= $m['Modelo'] ?></td>

                            <!-- 📅 Fecha de compra -->
                            <td><?= $m['Fecha_Compra'] ?></td>

                            <!-- 📌 Estado con estilos dinámicos -->
                            <td>
                                <?php
                                // Convierte el estado a mayúsculas para evitar errores de comparación
                                $estado = strtoupper($m['Estado']);

                                // 🎨 Aplica clase CSS según estado
                                if ($estado === 'ACTIVA') {
                                    echo '<span class="badge-activa">ACTIVA</span>';
                                } elseif ($estado === 'OCUPADA') {
                                    echo '<span class="badge-ocupada">OCUPADA</span>';
                                } elseif ($estado === 'EN REPARACION') {
                                    echo '<span class="badge-reparacion">EN REPARACIÓN</span>';
                                } elseif ($estado === 'DAÑADA') {
                                    echo '<span class="badge-danada">DAÑADA</span>';
                                } else {
                                    // Estado no contemplado
                                    echo '<span class="badge-inactivo">' . $m['Estado'] . '</span>';
                                }
                                ?>
                            </td>

                        </tr>

                    <?php endwhile; ?>

                </tbody>
            </table>

            <!-- 🔙 Botón volver al dashboard -->
            <div class="text-center mt-4">
                <a href="../DASHBOARD/dashboard.php" class="btn btn-kaiu">
                    ⬅ Volver
                </a>
            </div>

        </div>
    </div>

</body>

</html>
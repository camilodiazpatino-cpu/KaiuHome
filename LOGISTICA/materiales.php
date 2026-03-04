<?php
// 🔐 Inicia la sesión para poder usar variables como Cedula y Rol
session_start();

// 🔌 Conexión a la base de datos
include("../CONEXION/conexion.php");

// 🔐 Verifica que el usuario esté logueado
if (!isset($_SESSION['Cedula'])) {
    header("Location: ../LOGIN/login.php"); // redirige si no hay sesión
    exit;
}

// 🔐 Obtiene el rol del usuario logueado
$rol = strtoupper(trim($_SESSION['Nombre_tipo']));

// 🔤 Normaliza el rol (quita tildes y espacios)
$rol = str_replace(
    [' ', 'Á', 'É', 'Í', 'Ó', 'Ú'],
    ['_', 'A', 'E', 'I', 'O', 'U'],
    $rol
);

// 📦 Consulta todos los materiales registrados
$sql = "SELECT * FROM materiales ORDER BY Id_Material ASC";

// ▶ Ejecuta la consulta
$res = $conexion->query($sql);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <!-- 🧾 Título de la pestaña -->
    <title>Gestión de Materiales</title>

    <!-- 🎨 Bootstrap para estilos -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">

    <!-- 🎨 Estilos personalizados del sistema Kaiu -->
    <link rel="stylesheet" href="../CSS/kaiu.css">
</head>

<body>

    <div class="container mt-5 Kaiu-container">
        <div class="card-kaiu">

            <!-- 🧾 Título del módulo -->
            <h3 class="kaiu-title">🪵 Materiales Kaiu Home</h3>


            <table class="table kaiu-table">

                <!-- 🧠 Encabezados -->
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Material</th>
                        <th>Tipo</th>
                        <th>Unidad</th>
                        <th>Stock</th>
                        <th>Mínimo</th>
                        <th>Costo</th>
                    </tr>
                </thead>

                <tbody>

                    <!-- 🔢 Contador visual para numeración -->
                    <?php $i = 1; ?>

                    <!-- 🔁 Recorre cada material -->
                    <?php while ($m = $res->fetch_assoc()): ?>

                        <!-- ⚠ Si el stock actual es menor o igual al mínimo → fila crítica -->
                        <tr class="<?= $m['Stock_Actual'] <= $m['Stock_Minimo'] ? 'fila-critica' : '' ?>">
                            <!-- 🔢 Número visual -->
                            <td>
                                <span class="badge-mantenimiento">
                                    #<?= $i++ ?>
                                </span>
                            </td>

                            <!-- 🧾 Nombre del material -->
                            <td class="fw-semibold">
                                <?= $m['Nombre'] ?>
                            </td>

                            <!-- 🏷 Tipo de material -->
                            <td>
                                <span class="badge badge-tipo">
                                    <?= $m['Tipo'] ?>
                                </span>
                            </td>

                            <!-- 📏 Unidad de medida -->
                            <td>
                                <?= $m['Unidad_Medida'] ?>
                            </td>

                            <!-- 📦 Stock actual -->
                            <td>
                                <span class="badge badge-stock">
                                    <?= $m['Stock_Actual'] ?>
                                </span>
                            </td>

                            <!-- ⚠ Stock mínimo permitido -->
                            <td>
                                <?= $m['Stock_Minimo'] ?>
                            </td>

                            <!-- 💰 Costo unitario formateado -->
                            <td class="text-success fw-bold">
                                $<?= number_format($m['Costo_Unitario'], 0, ',', '.') ?>
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
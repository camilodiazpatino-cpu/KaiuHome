<?php
session_start();
include("../CONEXION/conexion.php");

/* =========================
   🔐 VALIDAR SESIÓN
========================= */
if (!isset($_SESSION['Cedula'])) {
    header("Location: ../LOGIN/login.php");
    exit;
}

/* =========================
   📄 CONSULTA ACTUALIZADA
   (SIN TABLA mantenimiento)
========================= */
$sql = "
SELECT 
    rm.Id,
    r.Nombre AS Repuesto,
    rm.Cantidad,
    rm.Estado_Registrooo
FROM repuestos_mantenimiento rm
JOIN repuestos r ON rm.Id_Repuesto = r.Id_Repuesto
ORDER BY rm.Id ASC
";

$res = $conexion->query($sql);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Repuestos usados</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../CSS/kaiu.css">
</head>

<body>

    <div class="container mt-5 kaiu-container">

        <div class="card-kaiu">

            <h3 class="kaiu-title">🧩 Repuestos usados • Kaiu Home</h3>

            <!-- ================= TABLA ================= -->
            <table class="table kaiu-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Repuesto</th>
                        <th>Cantidad</th>
                        <th>Estado</th>
                    </tr>
                </thead>

                <tbody>

                    <?php $i = 1; ?>

                    <?php if ($res && $res->num_rows > 0): ?>
                        <?php while ($rm = $res->fetch_assoc()): ?>
                            <tr>

                                <!-- CONTADOR -->
                                <td>
                                    <span class="badge-mantenimiento">
                                        #<?= $i++ ?>
                                    </span>
                                </td>

                                <!-- NOMBRE REPUESTO -->
                                <td>
                                    <strong><?= $rm['Repuesto'] ?></strong>
                                </td>

                                <!-- CANTIDAD -->
                                <td>
                                    <?= $rm['Cantidad'] ?>
                                </td>

                                <!-- ESTADO -->
                                <td>
                                    <?php
                                    $estado = strtoupper($rm['Estado_Registrooo']);

                                    if ($estado === 'ACTIVO') {
                                        echo '<span class="badge-activa">ACTIVO</span>';
                                    } else {
                                        echo '<span class="badge-inactivo">INACTIVO</span>';
                                    }
                                    ?>
                                </td>

                            </tr>
                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">
                                🚫 No hay repuestos registrados en mantenimiento
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>
            </table>

            <!-- BOTÓN VOLVER -->
            <div class="text-center mt-4">
                <a href="../DASHBOARD/dashboard.php" class="btn btn-kaiu">
                    ⬅ Volver
                </a>
            </div>

        </div>

    </div>

</body>

</html>
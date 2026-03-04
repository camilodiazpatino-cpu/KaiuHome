<?php
session_start(); // 🔐 Iniciar sesión para acceder a variables de usuario
require_once("../CONEXION/conexion.php"); // Conexión a la base de datos

$cedula = $_SESSION['Cedula']; // Guardar la cédula del usuario en sesión

/* ===================== RESUMEN ===================== */
// Contar el total de registros activos en el cronograma
$activos = $conexion->query("SELECT COUNT(*) as total FROM cronograma_mantenimiento WHERE Activo=1")->fetch_assoc()['total'];

// Contar el total de registros inactivos en el cronograma
$inactivos = $conexion->query("SELECT COUNT(*) as total FROM cronograma_mantenimiento WHERE Activo=0")->fetch_assoc()['total'];

/* ===================== CONSULTA ===================== */
// Obtener todos los registros del cronograma con el nombre del técnico
$sql = "
SELECT 
    c.*,  -- Todos los campos del cronograma
    CONCAT(u.Nombre,' ',u.Apellido) AS Nombre_Tecnico  -- Nombre completo del técnico
FROM cronograma_mantenimiento c
INNER JOIN usuarios u ON c.Cedula = u.Cedula  -- Relacionar técnico con cronograma
ORDER BY c.Fecha_Registro DESC
";

$resultado = $conexion->query($sql); // Ejecutar consulta
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Admin • Cronograma Kaiu Home</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Estilos personalizados -->
    <link rel="stylesheet" href="../CSS/admin.css">

</head>

<body>

    <div class="container mt-5">

        <div class="card-kaiu-admin">

            <!-- Título principal -->
            <h3 class="kaiu-title-admin">📊 Cronograma de Mantenimiento • Kaiu Home</h3>

            <!-- ================= TABLA ================= -->
            <div class="table-responsive">
                <table class="table table-hover admin-table">

                    <thead>
                        <tr>
                            <th>Técnico</th>
                            <th>Equipo</th>
                            <th>Trabajo</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th>Activo</th>
                        </tr>
                    </thead>

                    <tbody>
                        <!-- Iterar sobre cada registro del cronograma -->
                        <?php while ($row = $resultado->fetch_assoc()): ?>

                            <tr>
                                <!-- Nombre del técnico -->
                                <td><strong><?= $row['Nombre_Tecnico'] ?></strong></td>

                                <!-- Equipo asignado -->
                                <td><strong><?= $row['Descripcion_Equipo'] ?></strong></td>

                                <!-- Trabajo a realizar -->
                                <td><strong><?= $row['Descripcion_Trabajo'] ?></strong></td>

                                <!-- Fecha de registro -->
                                <td><strong><span class="texto-entrega"><?= $row['Fecha_Registro'] ?></span></strong></td>

                                <!-- Estado del trabajo (BUENA / MALA) -->
                                <td>
                                    <strong>
                                        <span class="<?= $row['Estado'] == 'BUENA' ? 'badge-disponible' : 'badge-inactivo' ?>">
                                            <?= $row['Estado'] ?>
                                        </span>
                                    </strong>
                                </td>

                                <!-- Activo o inactivo -->
                                <td>
                                    <strong>
                                        <span class="<?= $row['Activo'] == 1 ? 'estado-activo' : 'estado-inactivo' ?>">
                                            <?= $row['Activo'] == 1 ? 'Activo' : 'Inactivo' ?>
                                        </span>
                                    </strong>
                                </td>

                            </tr>

                        <?php endwhile; ?> <!-- Fin del while -->

                    </tbody>
                </table>
            </div>
        </div>

        <!-- Botón para volver al dashboard -->
        <div class="text-center mt-4">
            <a href="../DASHBOARD/dashboard.php" class="btn btn-secondary">⬅ Volver</a>
        </div>
    </div>

</body>

</html>
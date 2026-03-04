<?php
/* =========================================================
   🟢 INICIO DE SESIÓN Y CONEXIÓN
========================================================= */
session_start(); // Inicia sesión del usuario
include("../CONEXION/conexion.php"); // Conexión a la base de datos

/* =========================================================
   🔐 VALIDAR SESIÓN ACTIVA
   Si no hay sesión válida se redirige al login
========================================================= */
if (!isset($_SESSION['Cedula']) || !isset($_SESSION['Nombre_tipo'])) {
    header("Location: ../LOGIN/login.php");
    exit;
}

/* =========================================================
   🔎 VALIDAR QUE SE ENVÍE EL ID DE COMPRA
   Si no viene el parámetro se regresa a compras
========================================================= */
if (!isset($_GET['id'])) {
    header("Location: compras.php");
    exit;
}

/* =========================================================
   📌 OBTENER VARIABLES
========================================================= */
$id_compra = $_GET['id']; // ID de la compra seleccionada
$cedula = $_SESSION['Cedula'];   // Usuario logueado
$num_visual = $_GET['num'];

/* =========================================================
   🔐 NORMALIZAR ROL
========================================================= */
$rol = strtoupper(trim($_SESSION['Nombre_tipo']));
$rol = str_replace(
    [' ', 'Á', 'É', 'Í', 'Ó', 'Ú'],
    ['_', 'A', 'E', 'I', 'O', 'U'],
    $rol
);

/* =========================================================
   🔒 VALIDAR ACCESO A LA COMPRA
   - JEFE DE LOGISTICA y ADMINISTRADOR pueden ver todas
   - Otros solo pueden ver sus propias compras
========================================================= */
if ($rol !== 'JEFE_DE_LOGISTICA' && $rol !== 'ADMINISTRADOR' && $rol !== 'LOGISTICA') {

    $check = $conexion->query("
        SELECT Id_Compra 
        FROM compras 
        WHERE Id_Compra = '$id_compra' 
        AND Cedula_Usuario = '$cedula'
    ");

    // Si no existe acceso a esa compra, redirige
    if ($check->num_rows === 0) {
        header("Location: compras.php");
        exit;
    }
}

/* =========================================================
   📄 CONSULTA DETALLE DE LA COMPRA
   Trae materiales, cantidad, precio y subtotal
========================================================= */
$sql = "
SELECT 
    d.Id_Detalle,
    m.Nombre AS Material,
    d.Cantidad,
    d.Precio_Unitario,
    (d.Cantidad * d.Precio_Unitario) AS Subtotal
FROM detalle_compra d
JOIN materiales m ON d.Id_Material = m.Id_Material
WHERE d.Id_Compra = '$id_compra'
ORDER BY d.Id_Detalle ASC
";

/* =========================================================
   ▶ EJECUTAR CONSULTA
========================================================= */
$res = $conexion->query($sql);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Detalle de Compra</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Estilos del sistema -->
    <link rel="stylesheet" href="../CSS/kaiu.css">
</head>

<body>

    <!-- =====================================================
         📦 CONTENEDOR PRINCIPAL
    ====================================================== -->
    <div class="container mt-5">

        <!-- TÍTULO -->
        <h3 class="kaiu-title">
            Detalle de la Compra #<?= $num_visual ?>
        </h3>

        <!-- =====================================================
             📊 TABLA DE DETALLES
        ====================================================== -->
        <table class="table kaiu-table">

            <!-- ENCABEZADO -->
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Material</th>
                    <th>Cantidad</th>
                    <th>Precio Unitario</th>
                    <th>Subtotal</th>
                </tr>
            </thead>

            <tbody>

                <?php $i = 1; ?> <!-- 🔥 CONTADOR VISUAL -->

                <?php if ($res->num_rows > 0): ?>

                    <!-- RECORRER DETALLES -->
                    <?php while ($d = $res->fetch_assoc()): ?>
                        <tr>

                            <!-- NUMERO VISUAL -->
                            <td>
                                <span class="badge-mantenimiento">
                                    #<?= $i++ ?>
                                </span>
                            </td>

                            <!-- MATERIAL -->
                            <td><strong><?= $d['Material'] ?></strong></td>

                            <!-- CANTIDAD -->
                            <td><strong><?= $d['Cantidad'] ?></strong></td>

                            <!-- PRECIO UNITARIO -->
                            <td class="text-success fw-bold">
                                $<?= number_format($d['Precio_Unitario'], 0, ',', '.') ?>
                            </td>

                            <!-- SUBTOTAL -->
                            <td class="text-success fw-bold">
                                $<?= number_format($d['Subtotal'], 0, ',', '.') ?>
                            </td>

                        </tr>
                    <?php endwhile; ?>

                <?php else: ?>

                    <!-- MENSAJE SI NO HAY REGISTROS -->
                    <tr>
                        <td colspan="5" class="text-center text-muted">
                            No hay materiales registrados en esta compra
                        </td>
                    </tr>

                <?php endif; ?>

            </tbody>
        </table>

        <!-- BOTÓN VOLVER -->
        <div class="text-center mt-4">
            <a href="compras.php" class="btn btn-kaiu">
                ⬅ Volver
            </a>
        </div>

    </div>

</body>

</html>
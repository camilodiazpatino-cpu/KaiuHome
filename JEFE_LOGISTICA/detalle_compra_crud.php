<?php

/* =========================
   🔐 INICIO DE SESIÓN
   Permite acceder a variables de sesión del usuario logueado
========================= */
session_start();

/* =========================
   🔌 CONEXIÓN A BASE DE DATOS
========================= */
include("../CONEXION/conexion.php");

/* =========================
   🐞 ACTIVAR ERRORES MYSQLI
   Permite ver errores de SQL durante desarrollo
========================= */
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

/* =========================
   🔐 VALIDAR SESIÓN ACTIVA
   Si el usuario no está logueado, lo envía al login
========================= */
if (!isset($_SESSION['Cedula'])) {
    header("Location: ../LOGIN/login.php");
    exit;
}

/* =========================
   📥 OBTENER PARÁMETROS GET
   id  → Id real de la compra en BD
   num → número visual en la tabla (opcional)
========================= */
$idCompra = $_GET['id'] ?? 0;
$numeroVisual = $_GET['num'] ?? 1;

/* =========================
   🔢 CALCULAR NÚMERO VISUAL
   Recorre todas las compras para saber
   en qué posición visual se encuentra la compra
========================= */
$resNumero = $conexion->query("
    SELECT Id_Compra 
    FROM compras 
    ORDER BY Id_Compra ASC
");

$numeroVisual = 1;

while ($row = $resNumero->fetch_assoc()) {

    // Cuando encuentra la compra buscada, se detiene
    if ($row['Id_Compra'] == $idCompra) {
        break;
    }

    // Incrementa contador visual
    $numeroVisual++;
}

/* =========================
   📦 CONSULTAR DETALLE DE COMPRA
   Une detalle_compra + materiales
   para mostrar nombre del material
========================= */
$res = $conexion->query("
    SELECT d.*, m.Nombre
    FROM detalle_compra d
    INNER JOIN materiales m ON d.Id_Material = m.Id_Material
    WHERE d.Id_Compra = '$idCompra'
    ORDER BY d.Id_Detalle ASC
");
?>


<!DOCTYPE html>
<html lang="es">

<head>

    <!-- =========================
         CONFIGURACIÓN HTML
    ========================= -->
    <meta charset="UTF-8">
    <title>Detalle de Compra</title>

    <!-- =========================
         BOOTSTRAP
    ========================= -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- =========================
         CSS PERSONALIZADO
    ========================= -->
    <link rel="stylesheet" href="../CSS/jefe_logistica.css">
</head>

<body>

    <!-- =========================
         CONTENEDOR PRINCIPAL
    ========================= -->
    <div class="container mt-4 jefe-logistica">

        <!-- =========================
             TÍTULO DEL MÓDULO
             Muestra número visual de compra
        ========================= -->
        <h3 class="jefe-title">
            📦 Detalle de Compra #<?= $numeroVisual ?>
        </h3>

        <!-- =========================
             TABLA DE DETALLES
        ========================= -->
        <div class="table-responsive">

            <table class="table table-hover tabla-jefe-logis">

                <!-- ENCABEZADO -->
                <thead class="thead-jefe-logis">
                    <tr>
                        <th>ID</th>
                        <th>ID Compra</th>
                        <th>Material</th>
                        <th>Cantidad</th>
                        <th>Precio Unitario</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>

                <tbody>

                    <!-- CONTADOR VISUAL -->
                    <?php $i = 1; ?>

                    <!-- VALIDAR SI EXISTEN DETALLES -->
                    <?php if ($res->num_rows > 0): ?>

                        <!-- RECORRIDO DE CADA DETALLE -->
                        <?php while ($d = $res->fetch_assoc()): ?>
                            <tr>

                                <!-- NUMERO VISUAL DEL ITEM -->
                                <td>
                                    <span class="badge-logistica">
                                        #<?= $i ?>
                                    </span>
                                </td>

                                <!-- NUMERO DE COMPRA -->
                                <td>
                                    <span class="badge-logistica">
                                        #<?= $numeroVisual ?>
                                    </span>
                                </td>

                                <!-- NOMBRE DEL MATERIAL -->
                                <td>
                                    <strong><?= $d['Nombre'] ?></strong>
                                </td>

                                <!-- CANTIDAD -->
                                <td>
                                    <strong><?= $d['Cantidad'] ?></strong>
                                </td>

                                <!-- PRECIO UNITARIO FORMATEADO -->
                                <td>
                                    <span class="precio-unitarioo">
                                        $<?= number_format($d['Precio_Unitario'], 0, ',', '.') ?>
                                    </span>
                                </td>

                                <!-- SUBTOTAL CALCULADO -->
                                <td>
                                    <span class="subtotal">
                                        $<?= number_format($d['Cantidad'] * $d['Precio_Unitario'], 0, ',', '.') ?>
                                    </span>
                                </td>

                            </tr>

                            <!-- AUMENTAR CONTADOR -->
                            <?php $i++; ?>

                        <?php endwhile; ?>

                        <!-- SI NO HAY DATOS -->
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted">
                                ⚠️ Esta compra no tiene detalles registrados
                            </td>
                        </tr>
                    <?php endif; ?>

                </tbody>

            </table>
        </div>

        <!-- =========================
             BOTÓN VOLVER
        ========================= -->
        <div class="text-center mt-4">
            <a href="compras_crud.php" class="btn btn-secondary">
                ⬅ Volver a compras
            </a>
        </div>

    </div>

</body>

</html>
<?php
/* ==========================
   INICIO DE SESIÓN
   ========================== 
   Se inicia la sesión para poder acceder a las variables
   de usuario que vienen desde el login
*/
session_start();

/* ====================================
   CONEXIÓN A LA BASE DE DATOS
   ==================================== 
   Se incluye el archivo de conexión que contiene
   la variable $conexion (mysqli)
*/
include("../CONEXION/conexion.php");

/* ====================================
   ACTIVAR REPORTE DE ERRORES MYSQL
   ==================================== 
   Permite que los errores de MySQL se muestren
   como excepciones para facilitar el debug
*/
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

/* ===================================
   VALIDAR SESIÓN DE USUARIO
   =================================== 
   Si no existe la cédula en sesión,
   se redirige al login
*/
if (!isset($_SESSION['Cedula'])) {
    header("Location: ../LOGIN/login.php");
    exit;
}

/* =====================================================
   VALIDAR ROL DEL USUARIO
   ===================================================== 
   Se normaliza el nombre del rol (sin tildes ni espacios)
   para compararlo correctamente.
   Se determina si el usuario es administrador
*/
$rol = strtoupper(trim($_SESSION['Nombre_tipo']));
$rol = str_replace([' ', 'Á', 'É', 'Í', 'Ó', 'Ú'], ['_', 'A', 'E', 'I', 'O', 'U'], $rol);

// Solo jefe de logística y administrador pueden gestionar compras
$esAdmin = in_array($rol, ['JEFE_DE_LOGISTICA', 'ADMINISTRADOR']);

// Se guarda la cédula del usuario logueado
$cedulaUsuario = $_SESSION['Cedula'];

/* =========================================================
   VARIABLES DE CONTROL DEL SISTEMA
   ========================================================= */
$alerta = $_GET['alerta'] ?? '';  // Mensajes del sistema
$editar = false;                  // Bandera para saber si estamos editando
$compra_edit = [];                // Datos de la compra en edición

/* ================================================
   REGISTRAR COMPRA
   ================================================ 
   - Valida proveedor
   - Valida material
   - Inserta la compra
   - Inserta detalle
   - Calcula total
*/
if (isset($_POST['registrar']) && $esAdmin) {
    try {

        // Captura de datos del formulario
        $prov = $_POST['Id_Proveedor'];
        $fecha = $_POST['Fecha_Compra'];
        $material = $_POST['Id_Material'];
        $cantidad = $_POST['Cantidad'];
        $precio = $_POST['Precio_Unitario'];

        /* ===== VALIDAR PROVEEDOR ===== */
        $checkProv = $conexion->query("SELECT 1 FROM proveedores WHERE Id_Proveedor='$prov'");
        if ($checkProv->num_rows == 0) {
            header("Location: compras_crud.php?alerta=proveedor");
            exit;
        }

        /* ===== VALIDAR MATERIAL ===== */
        $checkMat = $conexion->query("SELECT 1 FROM materiales WHERE Id_Material='$material'");
        if ($checkMat->num_rows == 0) {
            header("Location: compras_crud.php?alerta=material");
            exit;
        }

        /* ===== INSERTAR COMPRA PRINCIPAL ===== */
        $conexion->query("
            INSERT INTO compras 
            (Id_Proveedor, Cedula_Usuario, Fecha_Compra, Total, Estado)
            VALUES ('$prov','$cedulaUsuario','$fecha',0,'PENDIENTE')
        ");

        // Se obtiene el ID generado automáticamente
        $idCompra = $conexion->insert_id;

        /* ===== INSERTAR DETALLE DE COMPRA ===== */
        $conexion->query("
            INSERT INTO detalle_compra
            (Id_Compra, Id_Material, Cantidad, Precio_Unitario)
            VALUES ('$idCompra','$material','$cantidad','$precio')
        ");

        /* ===== CALCULAR TOTAL ===== */
        $conexion->query("
            UPDATE compras SET Total = (
                SELECT SUM(Cantidad * Precio_Unitario)
                FROM detalle_compra
                WHERE Id_Compra = '$idCompra'
            )
            WHERE Id_Compra = '$idCompra'
        ");

        header("Location: compras_crud.php?alerta=registrado");
        exit;
    } catch (Exception $e) {
        header("Location: compras_crud.php?alerta=error");
        exit;
    }
}

/* ========================================
   ELIMINAR COMPRA
   ======================================== */
if (isset($_GET['eliminar']) && $esAdmin) {
    try {
        $id = $_GET['eliminar'];

        // Elimina la compra
        $conexion->query("DELETE FROM compras WHERE Id_Compra='$id'");

        header("Location: compras_crud.php?alerta=eliminado");
        exit;
    } catch (Exception $e) {
        // Si hay relación con otra tabla (FK)
        header("Location: compras_crud.php?alerta=relacion");
        exit;
    }
}

/* =========================
   CAMBIAR ESTADO A RECIBIDA
   ========================= */
if (isset($_GET['estado'], $_GET['id']) && $_GET['estado'] === 'recibida' && $esAdmin) {
    $id = $_GET['id'];

    $conexion->query("UPDATE compras SET Estado='RECIBIDA' WHERE Id_Compra='$id'");

    header("Location: compras_crud.php?alerta=recibida");
    exit;
}

/* =========================
   CAMBIAR ESTADO A PENDIENTE
   ========================= */
if (isset($_GET['estado'], $_GET['id']) && $_GET['estado'] === 'pendiente' && $esAdmin) {
    $id = $_GET['id'];

    $conexion->query("UPDATE compras SET Estado='PENDIENTE' WHERE Id_Compra='$id'");

    header("Location: compras_crud.php?alerta=pendiente");
    exit;
}

/* ============================================
   CARGAR COMPRA PARA EDICIÓN
   ============================================ */
if (isset($_GET['editar']) && $esAdmin) {
    $id = $_GET['editar'];
    $res_edit = $conexion->query("SELECT * FROM compras WHERE Id_Compra='$id'");

    if ($res_edit->num_rows > 0) {
        $compra_edit = $res_edit->fetch_assoc();
        $editar = true;
    }
}

/* ===========================================
   ACTUALIZAR COMPRA
   =========================================== */
if (isset($_POST['actualizar']) && $esAdmin) {
    try {
        $id = $_POST['Id_Compra'];
        $prov = $_POST['Id_Proveedor'];

        // Validar proveedor
        $check = $conexion->query("SELECT 1 FROM proveedores WHERE Id_Proveedor='$prov'");
        if ($check->num_rows == 0) {
            header("Location: compras_crud.php?alerta=proveedor");
            exit;
        }

        // Actualización
        $conexion->query("
            UPDATE compras SET
            Id_Proveedor='$prov',
            Fecha_Compra='{$_POST['Fecha_Compra']}',
            Total='{$_POST['Total']}'
            WHERE Id_Compra='$id'
        ");

        header("Location: compras_crud.php?alerta=actualizado");
        exit;
    } catch (Exception $e) {
        header("Location: compras_crud.php?alerta=error");
        exit;
    }
}

/* ========================================
   LISTAR COMPRAS
   ======================================== */
$res = $esAdmin
    ? $conexion->query("
        SELECT c.*, p.Nombre AS Nombre_Proveedor
        FROM compras c
        INNER JOIN proveedores p ON c.Id_Proveedor = p.Id_Proveedor
        ORDER BY c.Id_Compra ASC
    ")
    : $conexion->query("
        SELECT c.*, p.Nombre AS Nombre_Proveedor
        FROM compras c
        INNER JOIN proveedores p ON c.Id_Proveedor = p.Id_Proveedor
        WHERE c.Cedula_Usuario='$cedulaUsuario'
    ");

// Lista de proveedores para el select
$proveedores = $conexion->query("SELECT Id_Proveedor, Nombre FROM proveedores ORDER BY Nombre ASC");
?>


<!DOCTYPE html>
<html lang="es">

<head>
    <!-- =========================
         CONFIGURACIÓN BÁSICA HTML
         ========================= -->
    <meta charset="UTF-8">
    <title>Gestión de Compras</title>

    <!-- =========================
         BOOTSTRAP (ESTILOS RÁPIDOS)
         ========================= -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- =========================
         CSS PERSONALIZADO DEL MÓDULO
         ========================= -->
    <link rel="stylesheet" href="../CSS/jefe_logistica.css">

    <!-- =========================
         LIBRERÍA SWEETALERT PARA ALERTAS
         ========================= -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>

    <!-- =========================
         CONTENEDOR PRINCIPAL
         ========================= -->
    <div class="container mt-4 jefe-logistica">

        <!-- =========================
             TÍTULO DEL MÓDULO
             ========================= -->
        <h3 class="jefe-title">🛒 Gestión de Compras • Kaiu Home</h3>

        <!-- ======================================================
             FORMULARIO DE REGISTRO / EDICIÓN (SOLO ADMIN)
             ====================================================== -->
        <?php if ($esAdmin): ?>
            <div class="card card-jefe-logis mb-4">

                <!-- ENCABEZADO DEL FORMULARIO -->
                <div class="card-header card-header-jefe-logis">
                    <?= $editar ? 'Editar compra' : 'Registrar compra' ?>
                </div>

                <div class="card-body">

                    <!-- FORMULARIO PRINCIPAL -->
                    <form method="POST" class="row g-2">

                        <!-- CAMPO OCULTO SOLO EN EDICIÓN -->
                        <?php if ($editar): ?>
                            <input type="hidden" name="Id_Compra" value="<?= $compra_edit['Id_Compra'] ?>">
                        <?php endif; ?>

                        <!-- =========================
                             SELECT DE PROVEEDORES
                             ========================= -->
                        <select name="Id_Proveedor" class="form-control" required>
                            <option value="">Seleccione un proveedor</option>

                            <!-- SE RECORRE LA LISTA DE PROVEEDORES DESDE PHP -->
                            <?php while ($p = $proveedores->fetch_assoc()): ?>
                                <option value="<?= $p['Id_Proveedor'] ?>"
                                    <?= (isset($compra_edit['Id_Proveedor']) && $compra_edit['Id_Proveedor'] == $p['Id_Proveedor']) ? 'selected' : '' ?>>

                                    <?= $p['Nombre'] ?>
                                </option>
                            <?php endwhile; ?>
                        </select>

                        <!-- =========================
                             INPUT MATERIAL
                             ========================= -->
                        <input type="number" name="Id_Material" class="form-control" required placeholder="ID Material">

                        <!-- =========================
                             INPUT CANTIDAD
                             ========================= -->
                        <input type="number" name="Cantidad" class="form-control" required placeholder="Cantidad">

                        <!-- =========================
                             INPUT PRECIO UNITARIO
                             ========================= -->
                        <input type="number" name="Precio_Unitario" class="form-control" required placeholder="Precio unitario">

                        <!-- =========================
                             INPUT FECHA
                             ========================= -->
                        <input type="date" name="Fecha_Compra" class="form-control" required
                            value="<?= $compra_edit['Fecha_Compra'] ?? '' ?>">

                        <!-- =========================
                             BOTÓN PRINCIPAL
                             ========================= -->
                        <button name="<?= $editar ? 'actualizar' : 'registrar' ?>"
                            class="btn <?= $editar ? 'btn-actualizar' : 'btn-registrar' ?>">

                            <?= $editar ? 'Actualizar' : 'Registrar' ?>
                        </button>

                        <!-- BOTÓN CANCELAR SOLO EN EDICIÓN -->
                        <?php if ($editar): ?>
                            <a href="compras_crud.php" class="btn btn-secondaryi">Cancelar</a>
                        <?php endif; ?>

                    </form>
                </div>
            </div>
        <?php endif; ?>

        <!-- =========================
             TABLA DE COMPRAS
             ========================= -->
        <div class="table-responsive">

            <table class="table table-hover tabla-jefe-logis">

                <!-- ENCABEZADO DE LA TABLA -->
                <thead class="thead-jefe-logis">
                    <tr>
                        <th>ID</th>
                        <th>Proveedor</th>
                        <th>Fecha</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>

                <tbody>

                    <!-- CONTADOR VISUAL -->
                    <?php $i = 1; ?>

                    <!-- RECORRIDO DE TODAS LAS COMPRAS -->
                    <?php while ($c = $res->fetch_assoc()): ?>
                        <tr>

                            <!-- NUMERACIÓN VISUAL -->
                            <td><span class="badge-logistica">#<?= $i++ ?></span></td>

                            <!-- NOMBRE PROVEEDOR -->
                            <td><span class="nombre-proveedor"><?= $c['Nombre_Proveedor'] ?></span></td>

                            <!-- FECHA -->
                            <td><strong><?= $c['Fecha_Compra'] ?></strong></td>

                            <!-- TOTAL FORMATEADO -->
                            <td>
                                <span class="precio-logis <?= $c['Total'] >= 200000 ? 'alto' : '' ?>">
                                    $<?= number_format($c['Total'], 0, ',', '.') ?>
                                </span>
                            </td>

                            <!-- ESTADO -->
                            <td>
                                <?php if ($c['Estado'] == 'RECIBIDA'): ?>
                                    <span class="btn-estado recibida">Recibida</span>
                                <?php else: ?>
                                    <span class="btn-estado pendiente">Pendiente</span>
                                <?php endif; ?>
                            </td>

                            <!-- =========================
                                 ACCIONES
                                 ========================= -->
                            <td>
                                <div class="acciones-botones">

                                    <!-- BOTÓN VER DETALLE -->
                                    <a href="detalle_compra_crud.php?id=<?= $c['Id_Compra'] ?>&num=<?= $i - 1 ?>"
                                        class="btn-detalle-compra">
                                        📦 Ver Detalle Compra
                                    </a>

                                    <?php if ($esAdmin): ?>

                                        <!-- BOTÓN EDITAR -->
                                        <a href="?editar=<?= $c['Id_Compra'] ?>" class="btn-editar">✏️</a>

                                        <!-- BOTÓN CAMBIO DE ESTADO -->
                                        <?php if ($c['Estado'] === 'PENDIENTE'): ?>
                                            <a href="?estado=recibida&id=<?= $c['Id_Compra'] ?>" class="btn-estado pendiente">
                                                ⏳ Pendiente
                                            </a>
                                        <?php else: ?>
                                            <a href="?estado=pendiente&id=<?= $c['Id_Compra'] ?>" class="btn-estado recibida">
                                                📥 Recibida
                                            </a>
                                        <?php endif; ?>

                                    <?php endif; ?>

                                </div>
                            </td>

                        </tr>
                    <?php endwhile; ?>

                </tbody>
            </table>
        </div>

        <!-- BOTÓN VOLVER AL DASHBOARD -->
        <div class="text-center mt-4">
            <a href="../DASHBOARD/dashboard.php" class="btn btn-secondaryi">⬅ Volver</a>
        </div>
    </div>

    <!-- =========================
         ALERTAS SWEETALERT
         ========================= -->
    <script>
        // ALERTAS GENERALES (registrado, actualizado, error)
        <?php if ($alerta): ?>
            Swal.fire({
                icon: '<?= in_array($alerta, ['registrado', 'actualizado']) ? 'success' : 'error' ?>',
                title: '<?= strtoupper($alerta) ?>',
                showConfirmButton: true
            });
        <?php endif; ?>

        // ALERTA CUANDO COMPRA SE MARCA COMO RECIBIDA
        <?php if ($alerta == "recibida"): ?>
            Swal.fire('Listo', 'Compra marcada como recibida', 'success');
        <?php endif; ?>

        // ALERTA CUANDO REGRESA A PENDIENTE
        <?php if ($alerta == "pendiente"): ?>
            Swal.fire('Listo', 'La compra volvió a pendiente', 'info');
        <?php endif; ?>
    </script>

</body>

</html>
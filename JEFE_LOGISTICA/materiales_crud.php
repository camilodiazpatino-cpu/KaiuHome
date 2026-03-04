<?php
// 🔐 Inicia la sesión para poder usar variables de sesión
session_start();

// 🔗 Incluye la conexión a la base de datos
include("../CONEXION/conexion.php");

// 🔥 Activa reporte de errores de MySQLi para debug
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);


/* =========================
   🔐 VALIDAR SESIÓN
========================= */
// Si no existe la cédula en sesión, significa que no ha iniciado sesión
if (!isset($_SESSION['Cedula'])) {
    // Redirige al login
    header("Location: ../LOGIN/login.php");
    exit; // detiene ejecución
}


/* =========================
   🔐 VALIDAR ROL
========================= */
// Obtiene el nombre del rol del usuario
$rol = strtoupper(trim($_SESSION['Nombre_tipo'] ?? ''));

// Normaliza el texto del rol (reemplaza espacios y tildes)
$rol = str_replace([' ', 'Á', 'É', 'Í', 'Ó', 'Ú'], ['_', 'A', 'E', 'I', 'O', 'U'], $rol);

// Verifica si el usuario es administrador o jefe logística
$esAdmin = in_array($rol, ['JEFE_DE_LOGISTICA', 'ADMINISTRADOR']);


// 📌 Variables de control
$alerta = $_GET['alerta'] ?? ''; // alerta para SweetAlert
$ver = $_GET['ver'] ?? 'activos'; // vista (activos o inactivos)

$editar = false; // bandera modo edición
$material_edit = []; // datos del material a editar


/* =========================
   🟢 REGISTRAR MATERIAL
========================= */
// Si se envía el formulario registrar y el usuario es admin
if (isset($_POST['registrar']) && $esAdmin) {

    // Prepara la consulta SQL segura
    $stmt = $conexion->prepare("
        INSERT INTO materiales
        (Nombre, Tipo, Unidad_Medida, Stock_Actual, Stock_Minimo, Costo_Unitario, Estado)
        VALUES (?, ?, ?, ?, ?, ?, 'ACTIVO')
    ");

    // Asigna parámetros a la consulta
    $stmt->bind_param(
        "sssddi", // tipos: string, string, string, double, double, integer
        $_POST['Nombre'],
        $_POST['Tipo'],
        $_POST['Unidad_Medida'],
        $_POST['Stock_Actual'],
        $_POST['Stock_Minimo'],
        $_POST['Costo_Unitario']
    );

    // Ejecuta la inserción
    $stmt->execute();

    // Redirecciona con alerta
    header("Location: materiales_crud.php?alerta=registrado");
    exit;
}


/* =========================
   ✏️ OBTENER MATERIAL
========================= */
// Si se da click en editar
if (isset($_GET['editar']) && $esAdmin) {

    // Consulta el material por ID
    $stmt = $conexion->prepare("SELECT * FROM materiales WHERE Id_Material=?");
    $stmt->bind_param("i", $_GET['editar']);
    $stmt->execute();
    $res_edit = $stmt->get_result();

    // Si existe
    if ($res_edit->num_rows > 0) {
        // Guarda los datos del material
        $material_edit = $res_edit->fetch_assoc();
        $editar = true; // activa modo edición
    }
}


/* =========================
   💾 ACTUALIZAR MATERIAL
========================= */
// Si se envía formulario actualizar
if (isset($_POST['actualizar']) && $esAdmin) {

    // Consulta de actualización
    $stmt = $conexion->prepare("
        UPDATE materiales SET
            Nombre=?,
            Tipo=?,
            Unidad_Medida=?,
            Stock_Actual=?,
            Stock_Minimo=?,
            Costo_Unitario=?
        WHERE Id_Material=?
    ");

    // Vincula los datos
    $stmt->bind_param(
        "sssddii",
        $_POST['Nombre'],
        $_POST['Tipo'],
        $_POST['Unidad_Medida'],
        $_POST['Stock_Actual'],
        $_POST['Stock_Minimo'],
        $_POST['Costo_Unitario'],
        $_POST['Id_Material']
    );

    // Ejecuta actualización
    $stmt->execute();

    // Redirige con alerta
    header("Location: materiales_crud.php?alerta=actualizado");
    exit;
}


/* =========================
   🚫 INACTIVAR MATERIAL
========================= */
// Cambia estado a INACTIVO
if (isset($_GET['inactivar']) && $esAdmin) {

    $stmt = $conexion->prepare("
        UPDATE materiales SET Estado='INACTIVO'
        WHERE Id_Material=?
    ");

    $stmt->bind_param("i", $_GET['inactivar']);
    $stmt->execute();

    header("Location: materiales_crud.php?alerta=inactivado");
    exit;
}


/* =========================
   ♻️ REACTIVAR MATERIAL
========================= */
// Cambia estado a ACTIVO
if (isset($_GET['reactivar']) && $esAdmin) {

    $stmt = $conexion->prepare("
        UPDATE materiales SET Estado='ACTIVO'
        WHERE Id_Material=?
    ");

    $stmt->bind_param("i", $_GET['reactivar']);
    $stmt->execute();

    header("Location: materiales_crud.php?ver=inactivos&alerta=reactivado");
    exit;
}


/* =========================
   📄 LISTAR MATERIALES
========================= */
// Consulta según filtro
if ($ver === 'inactivos') {
    $res = $conexion->query("SELECT * FROM materiales WHERE Estado='INACTIVO' ORDER BY Id_Material ASC");
} else {
    $res = $conexion->query("SELECT * FROM materiales WHERE Estado='ACTIVO' ORDER BY Id_Material ASC");
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <!-- 🏷 Título de la página -->
    <title>Gestión de Materiales</title>

    <!-- 🎨 Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- 🎨 CSS personalizado -->
    <link rel="stylesheet" href="../CSS/jefe_logistica.css">

    <!-- 🚨 Librería SweetAlert -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>

    <!-- 📦 Contenedor principal -->
    <div class="container mt-4 jefe-logistica">

        <!-- 🧱 Título -->
        <h3 class="jefe-title">🧱 Gestión de Materiales • Kaiu Home</h3>


        <!-- 🔐 FORMULARIO SOLO PARA ADMIN -->
        <?php if ($esAdmin): ?>

            <div class="card card-jefe-logis mb-4">

                <!-- Encabezado dinámico -->
                <div class="card-header card-header-jefe-logis">
                    <?= $editar ? 'Editar material' : 'Registrar material' ?>
                </div>

                <div class="card-body">

                    <!-- 📝 Formulario -->
                    <form method="POST" class="row g-2">

                        <!-- Campo oculto si está editando -->
                        <?php if ($editar): ?>
                            <input type="hidden" name="Id_Material" value="<?= $material_edit['Id_Material'] ?>">
                        <?php endif; ?>

                        <!-- Campos -->
                        <input type="text" name="Nombre" class="form-control" required value="<?= $material_edit['Nombre'] ?? '' ?>" placeholder="Nombre">

                        <input type="text" name="Tipo" class="form-control" required value="<?= $material_edit['Tipo'] ?? '' ?>" placeholder="Tipo">

                        <input type="text" name="Unidad_Medida" class="form-control" required value="<?= $material_edit['Unidad_Medida'] ?? '' ?>" placeholder="Unidad de medida">

                        <input type="number" name="Stock_Actual" class="form-control" required value="<?= $material_edit['Stock_Actual'] ?? '' ?>" placeholder="Stock actual">

                        <input type="number" name="Stock_Minimo" class="form-control" required value="<?= $material_edit['Stock_Minimo'] ?? '' ?>" placeholder="Stock mínimo">

                        <input type="number" name="Costo_Unitario" class="form-control" required value="<?= $material_edit['Costo_Unitario'] ?? '' ?>" placeholder="Costo unitario">

                        <!-- Botón dinámico -->
                        <button name="<?= $editar ? 'actualizar' : 'registrar' ?>" class="btn <?= $editar ? 'btn-actualizar' : 'btn-registrar' ?>">
                            <?= $editar ? 'Actualizar' : 'Registrar' ?>
                        </button>

                        <!-- Botón cancelar -->
                        <?php if ($editar): ?>
                            <a href="materiales_crud.php<?= $ver === 'inactivos' ? '?ver=inactivos' : '' ?>" class="btn btn-secondaryi">Cancelar</a>
                        <?php endif; ?>

                    </form>
                </div>
            </div>
        <?php endif; ?>


        <!-- 🔁 BOTÓN CAMBIO DE VISTA -->
        <div class="mb-3 d-flex gap-2">
            <?php if ($ver === 'inactivos'): ?>
                <a href="materiales_crud.php" class="btn btn-registrar">⬅ Ver Activos</a>
            <?php else: ?>
                <a href="?ver=inactivos" class="btn btn-secondaryi">🚫 Ver Inactivos</a>
            <?php endif; ?>
        </div>


        <!-- 📊 TABLA -->
        <div class="table-responsive">
            <table class="table table-hover tabla-jefe-logis">

                <thead class="thead-jefe-logis">
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Tipo</th>
                        <th>Unidad</th>
                        <th>Stock</th>
                        <th>Stock mínimo</th>
                        <th>Costo</th>
                        <?php if ($esAdmin): ?><th>Acciones</th><?php endif; ?>
                    </tr>
                </thead>

                <tbody>

                    <?php $i = 1; ?> <!-- contador visual -->

                    <?php while ($m = $res->fetch_assoc()): ?>
                        <tr>

                            <!-- ID -->
                            <td><span class="badge-logistica">#<?= $i++ ?></span></td>

                            <!-- Nombre -->
                            <td><strong><?= $m['Nombre'] ?></strong></td>

                            <!-- Tipo -->
                            <td><strong><?= $m['Tipo'] ?></strong></td>

                            <!-- Unidad -->
                            <td><strong><?= $m['Unidad_Medida'] ?></strong></td>

                            <!-- Stock -->
                            <td>
                                <span class="stock-badge <?= ($m['Stock_Actual'] <= $m['Stock_Minimo']) ? 'stock-bajo' : 'stock-ok' ?>">
                                    <?= $m['Stock_Actual'] ?>
                                </span>
                            </td>

                            <!-- Stock mínimo -->
                            <td>
                                <span class="stock-min-badge">
                                    <?= $m['Stock_Minimo'] ?>
                                </span>
                            </td>

                            <!-- Costo -->
                            <td>
                                <span class="precio-logis <?= $m['Costo_Unitario'] >= 200000 ? 'alto' : '' ?>">
                                    $<?= number_format($m['Costo_Unitario'], 0, ',', '.') ?>
                                </span>
                            </td>

                            <!-- Acciones -->
                            <?php if ($esAdmin): ?>
                                <td>
                                    <div class="acciones-botones">

                                        <!-- editar -->
                                        <a href="?editar=<?= $m['Id_Material'] ?>" class="btn-editar">✏️</a>

                                        <!-- inactivar o reactivar -->
                                        <?php if ($m['Estado'] === 'ACTIVO'): ?>
                                            <a href="?inactivar=<?= $m['Id_Material'] ?>" class="btn btn-secondary btn-sm">🚫 Inactivar</a>
                                        <?php else: ?>
                                            <a href="?reactivar=<?= $m['Id_Material'] ?>" class="btn btn-secondaryy btn-sm">✅ Reactivar</a>
                                        <?php endif; ?>

                                    </div>
                                </td>
                            <?php endif; ?>

                        </tr>
                    <?php endwhile; ?>

                </tbody>
            </table>
        </div>


        <!-- 🔙 VOLVER -->
        <div class="text-center mt-4">
            <a href="../DASHBOARD/dashboard.php" class="btn btn-secondary">⬅ Volver</a>
        </div>
    </div>


    <!-- 🔔 ALERTAS -->
    <script>
        <?php if ($alerta == "registrado"): ?>
            Swal.fire('Listo', 'Material registrado', 'success');
        <?php elseif ($alerta == "actualizado"): ?>
            Swal.fire('Actualizado', 'Material actualizado', 'success');
        <?php elseif ($alerta == "inactivado"): ?>
            Swal.fire('Inactivado', 'Material inactivado', 'info');
        <?php elseif ($alerta == "reactivado"): ?>
            Swal.fire('Reactivado', 'Material reactivado', 'success');
        <?php endif; ?>
    </script>

</body>

</html>
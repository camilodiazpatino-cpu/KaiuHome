<?php
// Inicia la sesión para poder acceder a variables de sesión (como el rol del usuario)
session_start();

// Incluye el archivo de conexión a la base de datos
include("../CONEXION/conexion.php");

/* =========================
   🔐 VALIDAR ROL
========================= */

// Obtiene el rol del usuario desde la sesión, lo limpia y lo normaliza
$rol = strtoupper(trim($_SESSION['Nombre_tipo'] ?? ''));

// Reemplaza espacios y tildes para evitar inconsistencias en la comparación
$rol = str_replace([' ', 'Á', 'É', 'Í', 'Ó', 'Ú'], ['_', 'A', 'E', 'I', 'O', 'U'], $rol);

// Verifica si el rol NO está autorizado
if (!in_array($rol, ['JEFE_DE_LOGISTICA', 'ADMINISTRADOR'])) {
    // Redirige al dashboard si no tiene permisos
    header("Location: ../DASHBOARD/dashboard.php");
    exit;
}

// Variables de control de estado de la vista
$alerta = $_GET['alerta'] ?? ''; // Mensaje de alerta
$ver = $_GET['ver'] ?? 'activos'; // Vista actual (activos o inactivos)
$editar = false; // Bandera para saber si está en modo edición
$proveedor_edit = []; // Datos del proveedor a editar

/* =========================
   🟢 REGISTRAR
========================= */

// Si se envía el formulario con botón "registrar"
if (isset($_POST['registrar'])) {

    // Inserta el nuevo proveedor en la base de datos
    $conexion->query("
        INSERT INTO proveedores 
        (Nombre, Contacto, Telefono, Correo, Direccion, Estado)
        VALUES (
            '{$_POST['Nombre']}',
            '{$_POST['Contacto']}',
            '{$_POST['Telefono']}',
            '{$_POST['Correo']}',
            '{$_POST['Direccion']}',
            'ACTIVO'
        )
    ");

    // Redirecciona con alerta de éxito
    header("Location: proveedores_crud.php?alerta=registrado");
    exit;
}

/* =========================
   ✏️ OBTENER PARA EDITAR
========================= */

// Si se recibe el parámetro editar por GET
if (isset($_GET['editar'])) {
    $editar = true; // Activa modo edición

    // Consulta el proveedor a editar
    $res_edit = $conexion->query(
        "SELECT * FROM proveedores WHERE Id_Proveedor='{$_GET['editar']}'"
    );

    // Guarda los datos en un arreglo asociativo
    $proveedor_edit = $res_edit->fetch_assoc();
}

/* =========================
   💾 ACTUALIZAR
========================= */

// Si se envía el formulario con botón "actualizar"
if (isset($_POST['actualizar'])) {

    // Actualiza los datos del proveedor
    $conexion->query("
        UPDATE proveedores SET
            Nombre='{$_POST['Nombre']}',
            Contacto='{$_POST['Contacto']}',
            Telefono='{$_POST['Telefono']}',
            Correo='{$_POST['Correo']}',
            Direccion='{$_POST['Direccion']}'
        WHERE Id_Proveedor='{$_POST['Id_Proveedor']}'
    ");

    // Redirecciona con alerta de actualización exitosa
    header("Location: proveedores_crud.php?alerta=actualizado");
    exit;
}

/* =========================
   🚫 INACTIVAR
========================= */

// Si se solicita inactivar un proveedor
if (isset($_GET['inactivar'])) {

    // Cambia el estado a INACTIVO
    $conexion->query("
        UPDATE proveedores 
        SET Estado='INACTIVO'
        WHERE Id_Proveedor='{$_GET['inactivar']}'
    ");

    // Redirecciona con alerta
    header("Location: proveedores_crud.php?alerta=inactivado");
    exit;
}

/* =========================
   ♻️ REACTIVAR
========================= */

// Si se solicita reactivar un proveedor
if (isset($_GET['reactivar'])) {

    // Cambia el estado a ACTIVO
    $conexion->query("
        UPDATE proveedores 
        SET Estado='ACTIVO'
        WHERE Id_Proveedor='{$_GET['reactivar']}'
    ");

    // Redirecciona mostrando los inactivos con alerta
    header("Location: proveedores_crud.php?ver=inactivos&alerta=reactivado");
    exit;
}

/* =========================
   📄 LISTAR
========================= */

// Determina qué lista mostrar (activos o inactivos)
if ($ver === 'inactivos') {
    $res = $conexion->query("SELECT * FROM proveedores WHERE Estado='INACTIVO'");
} else {
    $res = $conexion->query("SELECT * FROM proveedores WHERE Estado='ACTIVO'");
}
?>


<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <!-- Título de la página -->
    <title>Gestión de Proveedores</title>

    <!-- Bootstrap para estilos -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- CSS personalizado del módulo -->
    <link rel="stylesheet" href="../CSS/jefe_logistica.css">

    <!-- Librería SweetAlert para alertas visuales -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <div class="container mt-4 jefe-logistica">

        <!-- Título principal del módulo -->
        <h3 class="jefe-title">🤝 Gestión de Proveedores • Kaiu Home</h3>

        <!-- 🟢 FORMULARIO -->
        <div class="card card-jefe-logis mb-4">

            <!-- Título dinámico según modo -->
            <div class="card-header card-header-jefe-logis">
                <?= $editar ? 'Editar proveedor' : 'Registrar proveedor' ?>
            </div>

            <div class="card-body">

                <!-- Formulario principal -->
                <form method="POST" class="row g-2">

                    <!-- ID oculto solo en edición -->
                    <?php if ($editar): ?>
                        <input type="hidden" name="Id_Proveedor" value="<?= $proveedor_edit['Id_Proveedor'] ?>">
                    <?php endif; ?>

                    <!-- Campos del proveedor -->
                    <input name="Nombre" class="form-control" placeholder="Nombre"
                        value="<?= $proveedor_edit['Nombre'] ?? '' ?>" required>

                    <input name="Contacto" class="form-control" placeholder="Contacto"
                        value="<?= $proveedor_edit['Contacto'] ?? '' ?>" required>

                    <input name="Telefono" class="form-control" placeholder="Teléfono"
                        value="<?= $proveedor_edit['Telefono'] ?? '' ?>" required>

                    <input type="email" name="Correo" class="form-control" placeholder="Correo"
                        value="<?= $proveedor_edit['Correo'] ?? '' ?>" required>

                    <input name="Direccion" class="form-control" placeholder="Dirección"
                        value="<?= $proveedor_edit['Direccion'] ?? '' ?>" required>

                    <!-- Botón dinámico -->
                    <button name="<?= $editar ? 'actualizar' : 'registrar' ?>"
                        class="btn <?= $editar ? 'btn-actualizar' : 'btn-registrar' ?>">
                        <?= $editar ? 'Actualizar' : 'Registrar' ?>
                    </button>

                    <!-- Botón cancelar solo en edición -->
                    <?php if ($editar): ?>
                        <a href="proveedores_crud.php" class="btn btn-secondaryi">Cancelar</a>
                    <?php endif; ?>

                </form>
            </div>
        </div>

        <div class="mb-3 d-flex gap-2">

            <!-- Botón para ver inactivos -->
            <?php if ($ver === 'activos'): ?>
                <a href="?ver=inactivos" class="btn btn-secondaryi">🚫 Ver Inactivos</a>

                <!-- Botón para volver a activos -->
            <?php else: ?>
                <a href="proveedores_crud.php" class="btn btn-registrar">⬅ Ver Activos</a>
            <?php endif; ?>
        </div>

        <!-- 📋 TABLA -->
        <div class="table-responsive">
            <table class="table table-hover tabla-jefe-logis">

                <!-- Encabezados -->
                <thead class="thead-jefe-logis">
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Contacto</th>
                        <th>Teléfono</th>
                        <th>Correo</th>
                        <th>Dirección</th>
                        <th>Acciones</th>
                    </tr>
                </thead>

                <!-- Cuerpo -->
                <tbody>

                    <?php $i = 1; ?> <!-- Contador visual -->

                    <?php while ($p = $res->fetch_assoc()): ?>
                        <tr>

                            <!-- ID visual incremental -->
                            <td><span class="badge-logistica">#<?= $i++ ?></span></td>

                            <!-- Datos del proveedor -->
                            <td><strong><?= $p['Nombre'] ?></strong></td>
                            <td><strong><?= $p['Contacto'] ?></strong></td>
                            <td><strong><?= $p['Telefono'] ?></strong></td>
                            <td><strong><?= $p['Correo'] ?></strong></td>
                            <td><strong><?= $p['Direccion'] ?></strong></td>

                            <!-- Acciones -->
                            <td>
                                <div class="acciones-botones">

                                    <!-- Botón editar -->
                                    <a href="?editar=<?= $p['Id_Proveedor'] ?>" class="btn-editar">✏️</a>

                                    <!-- Botón dinámico activar/inactivar -->
                                    <?php if ($p['Estado'] === 'ACTIVO'): ?>
                                        <a href="?inactivar=<?= $p['Id_Proveedor'] ?>" class="btn btn-secondary btn-sm">🚫 Inactivar</a>
                                    <?php else: ?>
                                        <a href="?reactivar=<?= $p['Id_Proveedor'] ?>" class="btn btn-secondaryy btn-sm">✅ Reactivar</a>
                                    <?php endif; ?>

                                </div>
                            </td>

                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- 🔙 VOLVER AL DASHBOARD PRINCIPAL -->
        <div class="text-center mt-4">
            <a href="../DASHBOARD/dashboard.php" class="btn btn-secondary">⬅ Volver</a>
        </div>
    </div>

    <!-- 🔔 ALERTAS -->
    <script>
        <?php if ($alerta == "registrado"): ?>
            Swal.fire('Listo', 'Proveedor registrado', 'success');
        <?php elseif ($alerta == "actualizado"): ?>
            Swal.fire('Actualizado', 'Proveedor actualizado', 'success');
        <?php elseif ($alerta == "inactivado"): ?>
            Swal.fire('Inactivado', 'Proveedor inactivado', 'info');
        <?php elseif ($alerta == "reactivado"): ?>
            Swal.fire('Reactivado', 'Proveedor reactivado', 'success');
        <?php endif; ?>
    </script>
</body>

</html>
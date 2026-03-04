<?php
// Inicia sesión para controlar acceso del usuario
session_start();

// Incluye archivo de conexión a la base de datos
include("../CONEXION/conexion.php");

// Activa el modo de errores en MySQLi
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

/* 🔐 VALIDAR SESIÓN */

// Si no existe la variable de sesión Cedula → usuario no logueado
if (!isset($_SESSION['Cedula'])) {
    header("Location: ../LOGIN/login.php"); // redirige al login
    exit; // detiene ejecución
}

/* 🔐 VALIDAR ROL */

// Obtiene el rol del usuario y lo formatea
$rol = strtoupper(trim($_SESSION['Nombre_tipo']));
$rol = str_replace([' ', 'Á', 'É', 'Í', 'Ó', 'Ú'], ['_', 'A', 'E', 'I', 'O', 'U'], $rol);

// Determina permisos
$esJefe = ($rol === 'JEFE_DE_LOGISTICA');
$esAdmin = ($rol === 'ADMINISTRADOR');

// Solo el jefe de logística puede editar
$puedeEditar = $esJefe;

// Parámetro GET de alerta
$alerta = $_GET['alerta'] ?? "";

// Control de edición
$editar = false;
$despacho_edit = [];

/* =========================
   🟢 REGISTRAR DESPACHO
========================= */

// Si se envía el formulario registrar
if (isset($_POST['registrar']) && $puedeEditar) {

    // Inserta un nuevo despacho en la base de datos
    $conexion->query("
        INSERT INTO despachos
        (Cedula_Cliente, Nombre_Cliente, Producto, Destino, Numero_Guia, Fecha_Salida, Estado)
        VALUES (
            '{$_POST['Cedula_Cliente']}',
            '{$_POST['Nombre_Cliente']}',
            '{$_POST['Producto']}',
            '{$_POST['Destino']}',
            '{$_POST['Numero_Guia']}',
            '{$_POST['Fecha_Salida']}',
            'ACTIVO'
        )
    ");

    // Redirige con alerta
    header("Location: despachos_crud.php?alerta=registrado");
    exit;
}

/* =========================
   🔁 INACTIVAR DESPACHO
========================= */

// Si se recibe GET inactivar
if (isset($_GET['inactivar']) && $puedeEditar) {

    // Cambia estado a INACTIVO
    $conexion->query("
        UPDATE despachos 
        SET Estado='INACTIVO'
        WHERE Id_Despacho='{$_GET['inactivar']}'
    ");

    header("Location: despachos_crud.php");
    exit;
}

/* =========================
   🔁 REACTIVAR DESPACHO
========================= */

// Si se recibe GET reactivar
if (isset($_GET['reactivar']) && $puedeEditar) {

    // Cambia estado a ACTIVO
    $conexion->query("
        UPDATE despachos 
        SET Estado='ACTIVO'
        WHERE Id_Despacho='{$_GET['reactivar']}'
    ");

    header("Location: despachos_crud.php");
    exit;
}

/* =========================
   🔴 ELIMINAR DESPACHO
========================= */

// Solo se puede eliminar si está INACTIVO o ENTREGADO
if (isset($_GET['eliminar']) && $puedeEditar) {

    $conexion->query("
        DELETE FROM despachos 
        WHERE Id_Despacho='{$_GET['eliminar']}'
        AND Estado IN ('INACTIVO','ENTREGADO')
    ");

    header("Location: despachos_crud.php?alerta=eliminado");
    exit;
}

/* =========================
   ✏️ OBTENER PARA EDITAR
========================= */

if (isset($_GET['editar']) && $puedeEditar) {

    // Consulta despacho por ID
    $res_edit = $conexion->query("
        SELECT * FROM despachos 
        WHERE Id_Despacho='{$_GET['editar']}'
    ");

    // Si existe, carga datos
    if ($res_edit->num_rows > 0) {
        $despacho_edit = $res_edit->fetch_assoc();
        $editar = true;
    }
}

/* =========================
   💾 ACTUALIZAR DESPACHO
========================= */

if (isset($_POST['actualizar']) && $puedeEditar) {

    // Obtiene fecha de entrega
    $fechaEntrega = $_POST['Fecha_Entrega'];

    // Determina estado final
    $estadoFinal = empty($fechaEntrega) ? 'ACTIVO' : 'ENTREGADO';

    // Actualiza el despacho
    $conexion->query("
        UPDATE despachos SET
        Cedula_Cliente = '{$_POST['Cedula_Cliente']}',
        Nombre_Cliente = '{$_POST['Nombre_Cliente']}',
        Producto = '{$_POST['Producto']}',
        Destino = '{$_POST['Destino']}',
        Numero_Guia = '{$_POST['Numero_Guia']}',
        Fecha_Salida = '{$_POST['Fecha_Salida']}',
        Fecha_Entrega = " . ($fechaEntrega ? "'$fechaEntrega'" : "NULL") . ",
        Estado = '$estadoFinal'
        WHERE Id_Despacho = '{$_POST['Id_Despacho']}'
    ");

    header("Location: despachos_crud.php?alerta=actualizado");
    exit;
}

/* =========================
   📄 LISTAR DESPACHOS
========================= */

// Consulta todos los despachos
$res = $conexion->query("SELECT * FROM despachos ORDER BY Id_Despacho ASC");
?>


<!DOCTYPE html>
<html lang="es">

<!-- Contenedor de metadatos y configuraciones del documento -->

<head>
    <meta charset="UTF-8">

    <!-- Título de la pestaña del navegador -->
    <title>Gestión de Despachos</title>

    <!-- Framework Bootstrap para estilos y diseño responsive -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Hoja de estilos personalizada del módulo jefe logística -->
    <link rel="stylesheet" href="../CSS/jefe_logistica.css">

    <!-- Librería SweetAlert para alertas bonitas -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<!-- Cuerpo visible de la página -->

<body>

    <!-- Contenedor principal del módulo -->
    <div class="container mt-4 jefe-logistica">

        <!-- Título principal del módulo -->
        <h3 class="jefe-title">🛻 Gestión de Despachos • Kaiu Home</h3>

        <!-- Si el usuario es administrador se muestra el formulario -->
        <?php if ($puedeEditar): ?>

            <!-- Tarjeta contenedora del formulario -->
            <div class="card card-jefe-logis mb-4">

                <!-- Encabezado dinámico: editar o registrar -->
                <div class="card-header card-header-jefe-logis">
                    <?= $editar ? 'Editar despacho' : 'Registrar despacho' ?>
                </div>

                <div class="card-body">

                    <!-- Formulario para registrar o actualizar compras -->
                    <form method="POST" class="row g-2">

                        <!-- Campo oculto SOLO cuando se edita -->
                        <?php if ($editar): ?>
                            <input type="hidden" name="Id_Despacho" value="<?= $despacho_edit['Id_Despacho'] ?>">
                        <?php endif; ?>

                        <!-- Campo para la cédula del cliente -->
                        <input type="number" name="Cedula_Cliente" class="form-control"
                            value="<?= $despacho_edit['Cedula_Cliente'] ?? '' ?>" required placeholder="Cédula Cliente">

                        <!-- Campo para el nombre del cliente -->
                        <input type="text" name="Nombre_Cliente" class="form-control"
                            value="<?= $despacho_edit['Nombre_Cliente'] ?? '' ?>" required placeholder="Nombre Cliente">

                        <!-- Campo para el producto enviado -->
                        <input type="text" name="Producto" class="form-control"
                            value="<?= $despacho_edit['Producto'] ?? '' ?>" required placeholder="Producto">

                        <!-- Campo para el destino del despacho -->
                        <input type="text" name="Destino" class="form-control"
                            value="<?= $despacho_edit['Destino'] ?? '' ?>" required placeholder="Destino">

                        <!-- Campo para número de guía (no obligatorio) -->
                        <input type="text" name="Numero_Guia" class="form-control"
                            value="<?= $despacho_edit['Numero_Guia'] ?? '' ?>" required placeholder="Número de guía">

                        <!-- Campo para fecha de salida -->
                        <input type="date" name="Fecha_Salida" class="form-control"
                            value="<?= $despacho_edit['Fecha_Salida'] ?? '' ?>" required>

                        <!-- Campo para fecha de entrega solo aparece cuando se da en editar -->
                        <?php if ($editar): ?>
                            <input type="date" name="Fecha_Entrega" class="form-control"
                                value="<?= $despacho_edit['Fecha_Entrega'] ?? '' ?>">
                        <?php endif; ?>

                        <!-- Botón dinámico registrar o actualizar -->
                        <button name="<?= $editar ? 'actualizar' : 'registrar' ?>"
                            class="btn <?= $editar ? 'btn-actualizar' : 'btn-registrar' ?>">
                            <?= $editar ? 'Actualizar' : 'Registrar' ?>
                        </button>

                        <!-- Botón cancelar solo si se está editando -->
                        <?php if ($editar): ?>
                            <a href="despachos_crud.php" class="btn btn-secondaryi">Cancelar</a>
                        <?php endif; ?>

                    </form>
                </div>
            </div>
        <?php endif; ?>


        <!-- Contenedor responsivo de la tabla -->
        <div class="table-responsive">

            <!-- Tabla de compras -->
            <table class="table table-hover tabla-jefe-logis">

                <!-- Encabezado de la tabla -->
                <thead class="thead-jefe-logis">
                    <tr>
                        <th>ID</th>
                        <th>Cédula</th>
                        <th>Nombre</th>
                        <th>Producto</th>
                        <th>Destino</th>
                        <th>Guía</th>
                        <th>Salida</th>
                        <th>Entrega</th>
                        <th>Estado</th>

                        <!-- Columna Acciones solo si tiene permisos -->
                        <?php if ($puedeEditar): ?>
                            <th>Acciones</th>
                        <?php endif; ?>
                    </tr>
                </thead>

                <tbody>

                    <!-- Contador visual para numeración -->
                    <?php $i = 1; ?>

                    <!-- Recorre cada compra obtenida de la BD -->
                    <?php while ($d = $res->fetch_assoc()): ?>

                        <tr>

                            <!-- ID visual incremental -->
                            <td><span class="badge-logistica">#<?= $i++ ?></span></td>

                            <!-- Cedula Cliente -->
                            <td><span class="badge-logistica"><?= $d['Cedula_Cliente'] ?></span></td>

                            <!-- Nombre Cliente -->
                            <td><strong><?= $d['Nombre_Cliente'] ?></strong></td>

                            <!-- Producto -->
                            <td><strong><?= $d['Producto'] ?></strong></td>

                            <!-- Destino del Producto -->
                            <td><strong><?= $d['Destino'] ?></strong></td>

                            <!-- Numero De Guia Del Producto -->
                            <td>
                                <span class="texto-guia">
                                    #<?= $d['Numero_Guia'] ?>
                                </span>
                            </td>

                            <!-- Fecha De Salida Del Producto -->
                            <td>
                                <span class="texto-salida">
                                    <?= $d['Fecha_Salida'] ?>
                                </span>
                            </td>

                            <!-- Fecha De Entrega Del Producto -->
                            <td>
                                <span class="texto-entrega">
                                    <?= $d['Fecha_Entrega'] ?>
                                </span>
                            </td>

                            <!-- Estado Del Despacho -->
                            <td>
                                <strong>
                                    <?php if ($d['Estado'] === 'ACTIVO'): ?>
                                        <span class="estado-activo-mant">ACTIVO</span>
                                    <?php elseif ($d['Estado'] === 'INACTIVO'): ?>
                                        <span class="estado-inactivo-mant">INACTIVO</span>
                                    <?php else: ?>
                                        <span class="estado-entregado">ENTREGADO</span>
                                    <?php endif; ?>
                                </strong>
                            </td>

                            <!-- Acciones disponibles -->
                            <?php if ($puedeEditar): ?>
                                <td>
                                    <div class="acciones-botones">

                                        <!-- Botón editar -->
                                        <a href="?editar=<?= $d['Id_Despacho'] ?>" class="btn-editar">✏️</a>

                                        <!-- Botónes Cambiar Estado -->
                                        <?php if ($d['Estado'] === 'ACTIVO'): ?>

                                            <a href="?inactivar=<?= $d['Id_Despacho'] ?>" class="btn btn-secondary btn-sm">
                                                🚫 Inactivar
                                            </a>

                                        <?php elseif ($d['Estado'] === 'INACTIVO'): ?>

                                            <a href="?reactivar=<?= $d['Id_Despacho'] ?>" class="btn btn-secondaryy btn-sm">
                                                ✅ Reactivar
                                            </a>
                                            <!-- Botón De Eliminar Solo Si Esta Inactivo y Entregado-->
                                            <button class="btn-eliminar-log"
                                                onclick="eliminarDespacho(<?= $d['Id_Despacho'] ?>)">
                                                🗑️
                                            </button>

                                        <?php elseif ($d['Estado'] === 'ENTREGADO'): ?>

                                            <button class="btn-eliminar-log"
                                                onclick="eliminarDespacho(<?= $d['Id_Despacho'] ?>)">
                                                🗑️
                                            </button>

                                        <?php endif; ?>

                                    </div>


                                </td>
                            <?php endif; ?>

                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Botón volver al dashboard -->
        <div class="text-center mt-4">
            <a href="../DASHBOARD/dashboard.php" class="btn btn-secondaryi">⬅ Volver</a>
        </div>
    </div>

    <!-- SCRIPT DE ALERTAS -->
    <script>
        // Función para confirmar eliminación con SweetAlert
        function eliminarDespacho(id) {

            // Muestra alerta con icono dinámico
            Swal.fire({
                title: '¿Eliminar despacho?',
                text: 'Esta acción no se puede deshacer',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((r) => {
                if (r.isConfirmed) {

                    // Si confirma, redirige enviando parámetro eliminar               
                    window.location = '?eliminar=' + id;
                }
            });
        }

        // Alerta Cuando Se Hace Un Registro
        <?php if ($alerta == "registrado"): ?>
            Swal.fire('Listo', 'Despacho registrado', 'success');

            // Alerta Cuando Se Edita
        <?php elseif ($alerta == "actualizado"): ?>
            Swal.fire('Actualizado', 'Despacho actualizado', 'success');

            // Alerta Cuando Se Elimina Un Despacho
        <?php elseif ($alerta == "eliminado"): ?>
            Swal.fire('Eliminado', 'Despacho eliminado', 'info');

            // Alerta Cuando Hay Un Error
        <?php elseif ($alerta == "error"): ?>
            Swal.fire('Error', 'El producto no existe o hay un problema', 'error');
        <?php endif; ?>
    </script>

</body>

</html>
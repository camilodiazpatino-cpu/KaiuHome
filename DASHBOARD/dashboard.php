<?php
// Inicia la sesión para poder acceder a las variables de sesión
session_start();

// Verifica que las variables principales de sesión existan
if (
    !isset($_SESSION['Cedula']) ||        // Verifica si existe la cédula
    !isset($_SESSION['Nombre']) ||        // Verifica si existe el nombre
    !isset($_SESSION['Nombre_tipo'])      // Verifica si existe el tipo de usuario
) {
    // Si alguna no existe, redirige al login
    header("Location: ../LOGIN/login.php");
    exit; // Detiene la ejecución
}

// Obtiene el tipo de usuario en mayúsculas y sin espacios extra
$tipo = strtoupper(trim($_SESSION['Nombre_tipo']));

// Reemplaza espacios y vocales con tilde para normalizar el texto
// Ejemplo: "Jefe de Logística" → "JEFE_DE_LOGISTICA"
$tipo = str_replace(
    [' ', 'Á', 'É', 'Í', 'Ó', 'Ú'],
    ['_', 'A', 'E', 'I', 'O', 'U'],
    $tipo
);

// --- SALUDO DINÁMICO ---

// Obtiene el género si existe en la sesión
$genero = isset($_SESSION['Genero']) ? $_SESSION['Genero'] : null;

// Si el género es F → Bienvenida
if ($genero === 'F') {
    $saludo = 'Bienvenida';

    // Si el género es M → Bienvenido
} elseif ($genero === 'M') {
    $saludo = 'Bienvenido';
} else {
    // Si no hay género definido, se usa heurística
    // Toma el último carácter del nombre
    $nombre = $_SESSION['Nombre'] ?? '';
    $ultimo_caracter = strtolower(substr(trim($nombre), -1));

    // Si termina en "a" → Bienvenida, si no → Bienvenido
    $saludo = ($ultimo_caracter === 'a') ? 'Bienvenida' : 'Bienvenido';
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Panel Principal</title>

    <!-- Importa Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Importa estilos personalizados -->
    <link rel="stylesheet" href="../CSS/kaiu.css">
</head>

<body>

    <!-- CONTENEDOR DEL LOGO CENTRADO -->
    <div class="text-center mb-3">

        <!-- Imagen del logo principal del sistema -->
        <!-- text-center: centra el contenido -->
        <!-- mb-3: margen inferior -->
        <img src="../IMG/logo-kaiu.png"
            alt="Kaiu Home"
            style="max-width: 200px; margin-top: 25px;"
            class="mb-2">
    </div>

    <!-- CONTENEDOR PRINCIPAL DEL DASHBOARD -->
    <div class="container mt-4">

        <!-- SALUDO DINÁMICO -->
        <!-- Muestra "Bienvenido" o "Bienvenida" según lógica previa -->
        <!-- htmlspecialchars evita inyección de código (seguridad) -->
        <h3><?= $saludo ?>, <?= htmlspecialchars($_SESSION['Nombre']) ?> 👋</h3>

        <hr> <!-- Línea divisora -->

        <!-- ESTRUCTURA CONDICIONAL SEGÚN EL TIPO DE USUARIO -->

        <?php if ($tipo === 'ADMINISTRADOR'): ?>

            <!-- PANEL PARA ADMINISTRADOR -->
            <h4>Panel Administrador</h4>

            <div class="panel-section fade-in">
                <!-- Enlaces a módulos administrativos -->
                <a href="../ADMIN/usuarios.php" class="btn btn-dark mb-2">Usuarios</a>
                <a href="../ADMIN/despachosAdmin.php" class="btn btn-dark mb-2">Despachos</a>
                <a href="../ADMIN/InventarioAdmin.php" class="btn btn-dark mb-2">Inventario</a>
                <a href="../ADMIN/cronogramaAdmin.php" class="btn btn-dark mb-2">Cronograma de mantenimiento</a>
            </div>

        <?php elseif ($tipo === 'LOGISTICA'): ?>

            <!-- PANEL PARA ÁREA DE LOGÍSTICA -->
            <div class="panel-section fade-in">
                <h4>Panel de Logística</h4>

                <!-- Opciones disponibles para logística -->
                <a href="../LOGISTICA/inventario.php" class="btn btn-dark mb-2">Inventario</a>
                <a href="../LOGISTICA/materiales.php" class="btn btn-dark mb-2">Materiales</a>
                <a href="../LOGISTICA/compras.php" class="btn btn-dark mb-2">Compras</a>
                <a href="../LOGISTICA/despachos.php" class="btn btn-dark mb-2">Despachos</a>
                <a href="../LOGISTICA/proveedores.php" class="btn btn-dark mb-2">Proveedores</a>
            </div>

        <?php elseif ($tipo === 'JEFE_DE_LOGISTICA'): ?>

            <!-- PANEL PARA JEFE DE LOGÍSTICA -->
            <div class="panel-section fade-in">
                <h4>Panel Jefe de Logística</h4>

                <!-- Acceso a versiones CRUD (Crear, Leer, Actualizar, Eliminar) -->
                <a href="../JEFE_LOGISTICA/inventario_crud.php" class="btn btn-dark mb-2">Inventario</a>
                <a href="../JEFE_LOGISTICA/materiales_crud.php" class="btn btn-dark mb-2">Materiales</a>
                <a href="../JEFE_LOGISTICA/compras_crud.php" class="btn btn-dark mb-2">Compras</a>
                <a href="../JEFE_LOGISTICA/despachos_crud.php" class="btn btn-dark mb-2">Despachos</a>
                <a href="../JEFE_LOGISTICA/proveedores_crud.php" class="btn btn-dark mb-2">Proveedores</a>
                <a href="../JEFE_LOGISTICA/trabajadores.php" class="btn btn-dark mb-2">Trabajadores</a>
            </div>

        <?php elseif ($tipo === 'MANTENIMIENTO'): ?>

            <!-- PANEL PARA ÁREA DE MANTENIMIENTO -->
            <div class="panel-section fade-in">
                <h4>Panel de Mantenimiento</h4>

                <!-- Botones en color amarillo (btn-warning) -->
                <a href="../MANTENIMIENTO/maquinaria.php" class="btn btn-warning mb-2">Maquinaria</a>
                <a href="../MANTENIMIENTO/repuestos.php" class="btn btn-warning mb-2">Repuestos</a>
                <a href="../MANTENIMIENTO/repuestos_mantenimiento.php" class="btn btn-warning mb-2">Repuestos usados</a>
                <a href="../MANTENIMIENTO/cronograma.php" class="btn btn-warning mb-2">Cronograma de mantenimiento</a>
            </div>

        <?php elseif ($tipo === 'JEFE_DE_MANTENIMIENTO'): ?>

            <!-- PANEL PARA JEFE DE MANTENIMIENTO -->
            <div class="panel-section fade-in">
                <h4>Panel Jefe de Mantenimiento</h4>

                <!-- Acceso completo con CRUD -->
                <a href="../JEFE_MANTENIMIENTO/maquinaria_crud.php" class="btn btn-dark mb-2">Maquinaria</a>
                <a href="../JEFE_MANTENIMIENTO/repuestos_crud.php" class="btn btn-dark mb-2">Repuestos</a>
                <a href="../JEFE_MANTENIMIENTO/repuestos_mantenimiento_crud.php" class="btn btn-dark mb-2">Repuestos usados</a>
                <a href="../JEFE_MANTENIMIENTO/cronograma_crud.php" class="btn btn-dark mb-2">Cronograma de mantenimiento</a>
                <a href="../JEFE_MANTENIMIENTO/tecnicos.php" class="btn btn-dark mb-2">Técnicos</a>
            </div>

        <?php else: ?>

            <!-- SI EL ROL NO ES RECONOCIDO -->
            <div class="alert alert-danger">
                Rol no reconocido: <?= $_SESSION['Nombre_tipo'] ?>
            </div>

        <?php endif; ?>

        <hr> <!-- Línea divisora final -->

        <!-- BOTÓN PARA CERRAR SESIÓN -->
        <!-- Redirige al archivo que destruye la sesión -->
        <a href="../VISTAS/cerrar_sesion.php" class="btn btn-danger">
            Cerrar sesión
        </a>

    </div>

</body>

</html>
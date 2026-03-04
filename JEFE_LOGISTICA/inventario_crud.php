<?php

/* =========================
   🔐 INICIO DE SESIÓN
   Permite usar variables de sesión del usuario
========================= */
session_start();

/* =========================
   🔌 CONEXIÓN A BASE DE DATOS
========================= */
include("../CONEXION/conexion.php");

/* =========================
   🐞 ACTIVAR ERRORES MYSQLI
   Lanza excepciones en errores SQL
========================= */
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

/* =========================
   🔐 VALIDAR ROL DEL USUARIO
   Solo JEFE DE LOGISTICA y ADMIN pueden entrar
========================= */
$rol = strtoupper(trim($_SESSION['Nombre_tipo'] ?? ''));

/* Normaliza tildes y espacios */
$rol = str_replace(
    [' ', 'Á', 'É', 'Í', 'Ó', 'Ú'],
    ['_', 'A', 'E', 'I', 'O', 'U'],
    $rol
);

/* Si el rol no tiene permisos → redirige */
if ($rol !== 'JEFE_DE_LOGISTICA' && $rol !== 'ADMINISTRADOR') {
    header("Location: ../DASHBOARD/dashboard.php");
    exit;
}

$alerta = $_GET['alerta'] ?? ''; // mensajes tipo: registrado, actualizado, eliminado
$editar = false;                 // define si estamos editando
$producto_edit = [];            // almacena producto a editar

/* ========================================
   📂 OBTENER CATEGORÍAS PARA EL SELECT
======================================== */
$categorias = $conexion->query("
    SELECT Id_Categoria, Nombre_Categoria 
    FROM categorias 
    ORDER BY Nombre_Categoria ASC
");

/* =================================
   🟢 REGISTRAR NUEVO PRODUCTO
================================ */
if (isset($_POST['registrar'])) {

    /* -----------------------------
    INSERTAR PRODUCTO BASE
    ----------------------------- */
    $stmt = $conexion->prepare("
        INSERT INTO productos (Nombre, Color, Id_Categoria)
        VALUES (?, ?, ?)
    ");

    $stmt->bind_param(
        "ssi",
        $_POST['Nombre'],          // nombre del producto
        $_POST['Color'],           // colores en formato texto|hex
        $_POST['Id_Categoria']     // categoría del producto
    );

    $stmt->execute();

    $id_producto = $conexion->insert_id;

    /* -----------------------------
    INSERTAR VARIACIONES DEL PRODUCTO
    (medidas, precios, stock)
    ----------------------------- */
    if (isset($_POST['medida'])) {

        $medidas = $_POST['medida'];
        $precios = $_POST['precio_medida'];
        $stocks  = $_POST['stock_medida'];

        for ($i = 0; $i < count($precios); $i++) {

            $medida = $medidas[$i] ?? '';
            $precio = $precios[$i];
            $stock  = $stocks[$i] ?? 0;

            if (!empty($precio)) {

                if (empty($medida)) {
                    $medida = 'General'; // 🔥 ya no manda NULL
                }

                if (empty($stock)) {
                    $stock = 0;
                }

                $stmtVar = $conexion->prepare("
            INSERT INTO variaciones_producto 
            (Id_Producto, Medida, Precio, Cantidad)
            VALUES (?, ?, ?, ?)
        ");

                $stmtVar->bind_param(
                    "isdi",
                    $id_producto, // o $id en actualizar
                    $medida,
                    $precio,
                    $stock
                );

                $stmtVar->execute();
            }
        }
    }

    header("Location: inventario_crud.php?alerta=registrado");
    exit;
}

/* =========================
   💾 ACTUALIZAR PRODUCTO
========================= */
if (isset($_POST['actualizar'])) {

    $id = $_POST['Id_Producto'];

    // 🔹 VALIDACIÓN BÁSICA
    if (!$id) {
        header("Location: inventario_crud.php?alerta=error");
        exit;
    }

    // 🔹 ACTUALIZAR DATOS BASE DEL PRODUCTO
    $stmt = $conexion->prepare("
        UPDATE productos SET
            Nombre = ?,
            Color = ?,
            Id_Categoria = ?
        WHERE Id_Producto = ?
    ");

    $stmt->bind_param(
        "ssii",
        $_POST['Nombre'],
        $_POST['Color'],
        $_POST['Id_Categoria'],
        $id
    );

    $stmt->execute();

    // 🔹 BORRAR VARIACIONES ANTERIORES
    $stmtDelete = $conexion->prepare("DELETE FROM variaciones_producto WHERE Id_Producto = ?");
    $stmtDelete->bind_param("i", $id);
    $stmtDelete->execute();

    // 🔹 INSERTAR NUEVAS VARIACIONES
    if (isset($_POST['medida']) && is_array($_POST['medida'])) {

        $medidas = $_POST['medida'];
        $precios = $_POST['precio_medida'];
        $stocks  = $_POST['stock_medida'];

        for ($i = 0; $i < count($precios); $i++) {

            $medida = $medidas[$i] ?? '';
            $precio = $precios[$i];
            $stock  = $stocks[$i] ?? 0;

            if (!empty($precio)) {

                if (empty($medida)) {
                    $medida = 'General'; // 🔥 ya no manda NULL
                }

                if (empty($stock)) {
                    $stock = 0;
                }

                $stmtVar = $conexion->prepare("
            INSERT INTO variaciones_producto 
            (Id_Producto, Medida, Precio, Cantidad)
            VALUES (?, ?, ?, ?)
        ");

                $stmtVar->bind_param(
                    "isdi",
                    $id, // o $id en actualizar
                    $medida,
                    $precio,
                    $stock
                );

                $stmtVar->execute();
            }
        }
    }

    // 🔹 REDIRECCIÓN FINAL
    header("Location: inventario_crud.php?alerta=actualizado");
    exit;
}


/* =========================================================
🗑 ELIMINAR PRODUCTO (solo si stock = 0)
========================================================= */
if (isset($_GET['eliminar'])) {

    $id = intval($_GET['eliminar']);

    /* Verificar stock total */
    $stmt = $conexion->prepare("SELECT Cantidad FROM productos WHERE Id_Producto=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $resTemp = $stmt->get_result();
    $producto = $resTemp->fetch_assoc();

    if ($producto && $producto['Cantidad'] == 0) {

        $stmt = $conexion->prepare("DELETE FROM productos WHERE Id_Producto=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        header("Location: inventario_crud.php?alerta=eliminado");
        exit;
    } else {
        header("Location: inventario_crud.php?alerta=no_eliminar");
        exit;
    }
}


/* ========================= 
   ✏️ EDITAR
========================= */
$editar = false;
$producto_edit = null;
$medidas_edit = [];

if (isset($_GET['editar'])) {

    // 🔹 TRAER PRODUCTO
    $stmt = $conexion->prepare("SELECT * FROM productos WHERE Id_Producto=?");
    $stmt->bind_param("i", $_GET['editar']);
    $stmt->execute();

    $res_edit = $stmt->get_result();

    if ($res_edit->num_rows > 0) {
        $producto_edit = $res_edit->fetch_assoc();
        $editar = true;

        // 🔹 TRAER VARIACIONES (MEDIDAS, PRECIO Y STOCK)
        $stmt_medidas = $conexion->prepare("
            SELECT Id_Variacion, Medida, Precio, Cantidad 
            FROM variaciones_producto 
            WHERE Id_Producto = ?
        ");

        $stmt_medidas->bind_param("i", $_GET['editar']);
        $stmt_medidas->execute();

        $res_medidas = $stmt_medidas->get_result();

        while ($m = $res_medidas->fetch_assoc()) {
            $medidas_edit[] = $m;
        }
    }
}

/* ===========================
   🔄 CAMBIAR ESTADO PRODUCTO
============================== */
if (isset($_GET['toggle'])) {

    $id = intval($_GET['toggle']);

    $stmt = $conexion->prepare("SELECT Cantidad FROM productos WHERE Id_Producto=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $producto = $resultado->fetch_assoc();

    if ($producto) {

        if ($producto['Cantidad'] > 0) {
            // Si está activo → lo pausamos
            $nuevaCantidad = 0;
        } else {
            // Si está pausado → lo activamos
            $nuevaCantidad = 1;
        }

        $stmt = $conexion->prepare("UPDATE productos SET Cantidad=? WHERE Id_Producto=?");
        $stmt->bind_param("ii", $nuevaCantidad, $id);
        $stmt->execute();
    }

    header("Location: inventario_crud.php");
    exit;
}

/* ===============================
   📄 LISTAR INVENTARIO COMPLETO
================================== */
$res = $conexion->query("
    SELECT p.*, c.Nombre_Categoria
    FROM productos p
    INNER JOIN categorias c 
    ON p.Id_Categoria = c.Id_Categoria
    ORDER BY c.Nombre_Categoria ASC, p.Id_Producto ASC
");
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <!-- Título de la pestaña -->
    <title>Gestión de Inventario</title>

    <!-- Bootstrap para diseño -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- CSS personalizado del módulo -->
    <link rel="stylesheet" href="../CSS/jefe_logistica.css">

    <!-- Librería de alertas -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>

    <div class="container mt-4 jefe-logistica">

        <!-- =========================================
              TÍTULO DEL MÓDULO
             ========================================== -->
        <h3 class="jefe-title">📦 Gestión de Inventario • Kaiu Home</h3>

        <!-- =========================================
              FORMULARIO DE REGISTRO / EDICIÓN
            ========================================== -->
        <div class="card card-jefe-logis mb-4">
            <div class="card-header card-header-jefe-logis">
                <?= $editar ? 'Editar producto' : 'Registrar producto' ?>
            </div>

            <div class="card-body">

                <form method="POST" class="row g-2" onsubmit="return validarColores()">

                    <!-- ID del producto -->
                    <?php if ($editar): ?>
                        <input type="hidden" name="Id_Producto" value="<?= $producto_edit['Id_Producto'] ?>">
                    <?php endif; ?>

                    <!-- Nombre del producto -->
                    <input type="text" name="Nombre" class="form-control" required
                        value="<?= $producto_edit['Nombre'] ?? '' ?>" placeholder="Nombre">

                    <!-- Colores del producto -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Colores</label>

                        <div class="d-flex align-items-center gap-2 mb-2">

                            <input type="text" id="colorNombre" class="form-control"
                                placeholder="Escribe el color del producto">

                            <!-- 🔵 circulito preview -->
                            <span id="previewColor" style="
            width:28px;
            height:28px;
            border-radius:50%;
            border:1px solid #ccc;
            display:inline-block;
            background:#000;
        "></span>

                        </div>

                        <button type="button" class="btn-agregarcolor"
                            onclick="agregarColor()">
                            Agregar color
                        </button>

                        <div id="colorPreview" class="color-preview"></div>

                        <input type="hidden" name="Color" id="Color">
                    </div>

                    <!-- Categorias del producto -->
                    <select name="Id_Categoria" class="form-select" required>
                        <option value="">Seleccionar categoría</option>

                        <?php while ($cat = $categorias->fetch_assoc()): ?>
                            <option value="<?= $cat['Id_Categoria'] ?>"
                                <?= (isset($producto_edit['Id_Categoria']) &&
                                    $producto_edit['Id_Categoria'] == $cat['Id_Categoria'])
                                    ? 'selected' : '' ?>>
                                <?= $cat['Nombre_Categoria'] ?>
                            </option>
                        <?php endwhile; ?>
                    </select>


                    <!-- ======================================================
                        🔥 SECCIÓN DE VARIACIONES DEL PRODUCTO (MEDIDAS)
                         Permite agregar múltiples medidas con su respectivo precio
                         y cantidad de stock para un mismo producto
                        ====================================================== -->
                    <div class="mb-3">

                        <!-- Etiqueta descriptiva del bloque -->
                        <label class="form-label fw-bold">Medidas, precio y stock</label>

                        <!-- ======================================================
                             CONTENEDOR DINÁMICO DE MEDIDAS
                             Aquí se insertan las filas de medidas (inputs dinámicos)
                             mediante PHP (al editar) o JavaScript (al agregar nuevas)
                            ====================================================== -->
                        <div id="contenedorMedidas">

                            <!-- ==================================================
                                MODO EDICIÓN:
                                Si se está editando un producto y existen medidas
                                previamente guardadas en la base de datos
                                se cargan automáticamente en los inputs
                                ================================================== -->
                            <?php if ($editar && !empty($medidas_edit)): ?>

                                <!-- Recorre cada variación existente del producto -->
                                <?php foreach ($medidas_edit as $m): ?>

                                    <!-- ==========================================
                                   FILA INDIVIDUAL DE MEDIDA
                                   Cada fila representa una variación del producto
                                    ========================================== -->
                                    <div class="fila-medida-kaiu">

                                        <!-- ===============================
                                        INPUT MEDIDA
                                        Ejemplo: 100x190, Queen, King, etc
                                        =============================== -->
                                        <input type="text"
                                            name="medida[]"
                                            class="medida-kaiu"
                                            placeholder="Ej: 100x190"
                                            value="<?= $m['Medida'] ?>">

                                        <!-- ===============================
                                             INPUT PRECIO DE ESA MEDIDA
                                             Se guarda en la base de datos
                                            =============================== -->
                                        <input type="number"
                                            name="precio_medida[]"
                                            class="precio-kaiu"
                                            placeholder="Precio"
                                            value="<?= $m['Precio'] ?>">

                                        <!-- ===============================
                                            INPUT STOCK
                                            Cantidad disponible de esa medida
                                             =============================== -->
                                        <input type="number"
                                            name="stock_medida[]"
                                            class="stock-kaiu"
                                            placeholder="Stock"
                                            value="<?= $m['Cantidad'] ?>">

                                        <!-- ===============================
                                               BOTÓN ELIMINAR FILA
                                               Elimina dinámicamente la variación
                                               sin recargar la página
                                               =============================== -->
                                        <button type="button" class="btn-eliminar" onclick="this.parentElement.remove()">X</button>

                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>

                        </div>


                        <!-- ======================================================
                            BOTÓN AGREGAR NUEVA MEDIDA
                            Ejecuta la función JS agregarMedida()
                            que crea dinámicamente una nueva fila
                           ====================================================== -->
                        <button type="button" class="btn-agregar-medida-kaiu" onclick="agregarMedida()">
                            + Agregar medida
                        </button>
                    </div>



                    <button name="<?= $editar ? 'actualizar' : 'registrar' ?>"
                        class="btn <?= $editar ? 'btn-actualizar' : 'btn-registrar' ?>">
                        <?= $editar ? 'Actualizar' : 'Registrar' ?>
                    </button>

                    <?php if ($editar): ?>
                        <a href="inventario_crud.php" class="btn btn-secondaryi">Cancelar</a>
                    <?php endif; ?>

                </form>

            </div>
        </div>

        <!-- ======================================================
📊 TABLA RESPONSIVE DE INVENTARIO
Contenedor general que permite hacer scroll horizontal
en pantallas pequeñas (responsive)
====================================================== -->
        <div class="table-responsive">

            <!-- ======================================================
    🔎 BUSCADOR DE PRODUCTOS
    Permite filtrar productos por nombre en tiempo real
    mediante JavaScript
    ====================================================== -->
            <div class="mb-33">

                <!-- Input de búsqueda -->
                <input type="text"
                    id="buscador"
                    class="form-control"
                    placeholder="🔍 Buscar producto por nombre...">

                <!-- Mensaje cuando no se encuentran coincidencias -->
                <div id="sinResultados"
                    class="alert alert-warning mt-2 text-center d-none">
                    No se encontraron productos 🔎
                </div>

            </div>

            <!-- ======================================================
    📂 ACORDEÓN DE INVENTARIO POR CATEGORÍAS
    Cada categoría se convierte en una sección expandible
    ====================================================== -->
            <div class="accordion" id="accordionInventario">

                <?php
                // ======================================================
                // VARIABLES DE CONTROL
                // ======================================================

                $categoria_actual = ""; // almacena la categoría actual del loop
                $index = 0; // contador para IDs únicos del acordeón

                // Reinicia el puntero del resultado SQL
                $res->data_seek(0);

                // ======================================================
                // MAPA DE ICONOS POR CATEGORÍA
                // Cada categoría tiene su ícono representativo
                // ======================================================
                $iconos = [
                    "Base Cama" => "https://cdn-icons-png.flaticon.com/128/2286/2286069.png",
                    "Camas" => "https://cdn-icons-png.flaticon.com/128/3168/3168626.png",
                    "Carpas" => "https://cdn-icons-png.flaticon.com/128/5987/5987625.png",
                    "Combos Con Colchon" => "https://cdn-icons-png.flaticon.com/128/11646/11646037.png",
                    "Combos Sin Colchon" => "https://cdn-icons-png.flaticon.com/128/5059/5059805.png",
                    "Poltronas" => "https://cdn-icons-png.flaticon.com/128/2944/2944104.png",
                    "Puff" => "https://cdn-icons-png.flaticon.com/128/7214/7214591.png",
                    "Salas En L y Esquineros" => "https://cdn-icons-png.flaticon.com/128/4897/4897529.png",
                    "Sillas" => "https://cdn-icons-png.flaticon.com/128/2944/2944135.png",
                    "Sillas Bar" => "https://cdn-icons-png.flaticon.com/128/3822/3822733.png",
                    "Sofá Cama" => "https://cdn-icons-png.flaticon.com/128/7378/7378037.png",
                    "Sofás" => "https://cdn-icons-png.flaticon.com/128/2361/2361657.png",
                    "Cabeceros" => "https://cdn-icons-png.flaticon.com/128/11931/11931883.png",
                    "Cama Nido" => "https://cdn-icons-png.flaticon.com/128/1698/1698762.png",
                    "Colchones" => "https://cdn-icons-png.flaticon.com/128/3821/3821521.png",
                    "Juegos De Sala" => "https://cdn-icons-png.flaticon.com/128/2398/2398481.png",
                    "Mesas De Centro" => "https://cdn-icons-png.flaticon.com/128/15837/15837972.png",
                    "Sillas Reclinables" => "https://cdn-icons-png.flaticon.com/128/11935/11935874.png",
                    "Sofás Camas y Sofás En Combo" => "https://cdn-icons-png.flaticon.com/128/6009/6009570.png"
                ];

                // ======================================================
                // RECORRIDO DE TODOS LOS PRODUCTOS
                // ======================================================
                while ($p = $res->fetch_assoc()):

                    // ==================================================
                    // DETECTAR CAMBIO DE CATEGORÍA
                    // Cuando cambia la categoría se cierra la tabla anterior
                    // y se crea un nuevo acordeón
                    // ==================================================
                    if ($categoria_actual != $p['Nombre_Categoria']):

                        // Si ya había una categoría previa, se cierra su tabla
                        if ($categoria_actual != ""):
                            echo "</tbody></table></div></div></div>";
                        endif;

                        // Incrementa contador de acordeón
                        $index++;

                        // Se actualiza la categoría actual
                        $categoria_actual = $p['Nombre_Categoria'];

                        // Se obtiene el icono de la categoría o uno por defecto
                        $icono = isset($iconos[$categoria_actual])
                            ? $iconos[$categoria_actual]
                            : "bi-box";
                ?>


                        <div class="accordion-item mb-3">
                            <!-- ======================================================
    🔽 CABECERA DEL ACORDEÓN DE LA CATEGORÍA
    Cada categoría se muestra como un bloque desplegable
    ====================================================== -->
                            <h2 class="accordion-header" id="heading<?= $index ?>">

                                <!-- BOTÓN QUE ABRE / CIERRA EL ACORDEÓN -->
                                <button class="accordion-button collapsed categoria-header"
                                    type="button"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#collapse<?= $index ?>">

                                    <!-- 🖼 ICONO DE LA CATEGORÍA -->
                                    <img src="<?= $icono ?>" class="icono-cat">

                                    <!-- 🏷 NOMBRE DE LA CATEGORÍA -->
                                    <?= $categoria_actual ?>

                                </button>
                            </h2>

                            <!-- ======================================================
    🔽 CONTENIDO DESPLEGABLE DE LA CATEGORÍA
    ====================================================== -->
                            <div id="collapse<?= $index ?>"
                                class="accordion-collapse collapse"
                                data-bs-parent="#accordionInventario">

                                <div class="accordion-body table-responsive">

                                    <!-- ======================================================
            📊 TABLA DE PRODUCTOS DE ESTA CATEGORÍA
            ====================================================== -->
                                    <table class="table table-hover tabla-jefe-logis">

                                        <!-- ENCABEZADOS DE LA TABLA -->
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Nombre</th>
                                                <th>Color</th>
                                                <th>Medidas Y Precios</th>
                                                <th>Cantidad</th>
                                                <th class="text-center">Acciones</th>
                                            </tr>
                                        </thead>

                                        <tbody>

                                            <?php $i = 1; ?> <!-- 🔥 CONTADOR VISUAL DE FILAS -->

                                        <?php endif; ?>

                                        <!-- ======================================================
                🧾 FILA DE PRODUCTO
                Cada iteración del while imprime un producto
                ====================================================== -->
                                        <tr class="fila-producto">

                                            <!-- 🔢 NUMERO VISUAL DEL PRODUCTO -->
                                            <td>
                                                <span class="badge-logistica">
                                                    #<?= $i++ ?>
                                                </span>
                                            </td>

                                            <!-- 🏷 NOMBRE DEL PRODUCTO -->
                                            <td class="nombre-producto">
                                                <strong><?= $p['Nombre'] ?></strong>
                                            </td>

                                            <!-- 🎨 COLORES DEL PRODUCTO -->
                                            <td>
                                                <?php
                                                // Verifica si el producto tiene colores registrados
                                                if (!empty($p['Color'])) {

                                                    // Se separan los colores por coma
                                                    $colores = explode(",", $p['Color']);

                                                    foreach ($colores as $color):

                                                        // Divide nombre y color HEX
                                                        $partes = explode("|", $color);

                                                        // ✅ Formato nuevo: Nombre|#HEX
                                                        if (count($partes) == 2) {
                                                            $nombre = $partes[0];
                                                            $hex = $partes[1];
                                                        } else {
                                                            // ⚠️ Formato viejo: solo HEX
                                                            $nombre = "Color";
                                                            $hex = $partes[0];
                                                        }
                                                ?>
                                                        <!-- 🎨 CIRCULO VISUAL DE COLOR -->
                                                        <span
                                                            style="
        display:inline-flex;
        align-items:center;
        gap:6px;
        margin-right:8px;
        position:relative;
    ">
                                                            <span style="
        width:18px;
        height:18px;
        border-radius:50%;
        background: <?= $hex ?>;
        border:1px solid #ccc;
        display:inline-block;
    "
                                                                class="color-circle"
                                                                data-color="<?= $nombre ?>"></span>
                                                        </span>

                                                <?php
                                                    endforeach;
                                                } else {
                                                    // Si no tiene color
                                                    echo '<span class="text-muted">Sin color</span>';
                                                }
                                                ?>
                                            </td>

                                            <!-- 📏 MEDIDAS Y PRECIOS DEL PRODUCTO -->
                                            <td>
                                                <?php
                                                // Consulta de variaciones del producto (medidas y precios)
                                                $variaciones = $conexion->query(
                                                    "
    SELECT Medida, Precio, Cantidad
    FROM variaciones_producto
    WHERE Id_Producto = " . $p['Id_Producto']
                                                );

                                                // Se recorre cada variación
                                                while ($v = $variaciones->fetch_assoc()):
                                                ?>
                                                    <div>
                                                        <!-- 📐 MEDIDA + PRECIO FORMATEADO -->
                                                        <strong><?= $v['Medida'] ?></strong> :
                                                        $<?= number_format($v['Precio'], 0, ',', '.') ?>
                                                    </div>
                                                <?php endwhile; ?>
                                            </td>

                                            <!-- 📦 STOCK POR MEDIDA -->
                                            <td>
                                                <?php
                                                // Consulta nuevamente las variaciones para stock
                                                $variaciones = $conexion->query(
                                                    "
    SELECT Medida, Cantidad
    FROM variaciones_producto
    WHERE Id_Producto = " . $p['Id_Producto']
                                                );

                                                while ($v = $variaciones->fetch_assoc()):
                                                ?>

                                                    <!-- 🟢 SI HAY STOCK -->
                                                    <?php if ($v['Cantidad'] > 0): ?>
                                                        <div class="stock-medida disponible">
                                                            <strong><?= $v['Medida'] ?></strong> → <?= $v['Cantidad'] ?> disponibles
                                                        </div>

                                                        <!-- 🔴 SI NO HAY STOCK -->
                                                    <?php else: ?>
                                                        <div class="stock-medida agotado">
                                                            <strong><?= $v['Medida'] ?></strong> → Agotado
                                                        </div>
                                                    <?php endif; ?>

                                                <?php endwhile; ?>
                                            </td>

                                            <!-- ======================================================
                    ⚙️ COLUMNA DE ACCIONES
                    Botones de editar, activar/pausar y eliminar
                    ====================================================== -->
                                            <td class="text-center align-middle">
                                                <div class="d-flex justify-content-center gap-2">

                                                    <!-- ✏️ BOTÓN EDITAR -->
                                                    <a href="?editar=<?= $p['Id_Producto'] ?>"
                                                        class="btn-editar">
                                                        ✏️
                                                    </a>

                                                    <!-- 🔄 BOTÓN ACTIVAR / PAUSAR SEGÚN STOCK -->
                                                    <?php if ($p['Cantidad'] > 0): ?>
                                                        <a href="#"
                                                            onclick="confirmarToggle(event, <?= $p['Id_Producto'] ?>, <?= $p['Cantidad'] ?>)"
                                                            class="btn btn-secondaryy btn-sm">
                                                            🟢 Activo
                                                        </a>
                                                    <?php else: ?>
                                                        <a href="#"
                                                            onclick="confirmarToggle(event, <?= $p['Id_Producto'] ?>, <?= $p['Cantidad'] ?>)"
                                                            class="btn btn-secondary btn-sm">
                                                            ⏸ Pausado
                                                        </a>
                                                    <?php endif; ?>

                                                    <!-- 🗑 BOTÓN ELIMINAR SOLO SI NO HAY STOCK -->
                                                    <?php if ($p['Cantidad'] == 0): ?>
                                                        <a href="?eliminar=<?= $p['Id_Producto'] ?>"
                                                            class="btn-eliminar-log"
                                                            onclick="return confirmarEliminacion(event);">
                                                            🗑
                                                        </a>
                                                    <?php endif; ?>

                                                </div>
                                            </td>

                                        </tr>

                                    <?php endwhile; ?>

                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
            </div>

        </div>

        <!-- 🔙 BOTÓN VOLVER AL DASHBOARD -->
        <div class="text-center mt-4">

            <!-- 🔙 BOTÓN VOLVER AL DASHBOARD -->
            <a href="../DASHBOARD/dashboard.php" class="btn btn-secondaryi">⬅ Volver</a>
        </div>

    </div> <!-- Cierre de contenedor principal -->

    <!-- 🔎 SCRIPT DEL BUSCADOR DE PRODUCTOS -->
    <script>
        // Escucha cada vez que el usuario escribe en el input buscador
        document.getElementById("buscador").addEventListener("keyup", function() {

            let filtro = this.value.toLowerCase().trim(); // texto buscado
            let filas = document.querySelectorAll(".fila-producto"); // todas las filas de productos
            let acordeones = document.querySelectorAll(".accordion-item"); // categorías
            let encontrado = false; // categorías

            // 🔄 Elimina resaltados anteriores
            document.querySelectorAll(".resaltado").forEach(el => {
                el.outerHTML = el.innerText;
            });

            // 🔁 Recorre cada producto
            filas.forEach(function(fila) {

                let nombreCell = fila.querySelector(".nombre-producto"); // celda nombre
                let colorCell = fila.querySelector("td:nth-child(3)"); // celda color

                let nombre = nombreCell.innerText.toLowerCase();
                let color = colorCell.innerText.toLowerCase();

                // 🔍 Si coincide con nombre o color
                if (filtro !== "" && (nombre.includes(filtro) || color.includes(filtro))) {

                    fila.style.display = ""; // mostrar fila
                    encontrado = true;

                    // 🔥 Resaltar coincidencias en nombre
                    nombreCell.innerHTML = nombreCell.innerText.replace(
                        new RegExp(filtro, "gi"),
                        match => `<span class="resaltado">${match}</span>`
                    );

                } else if (filtro === "") {

                    fila.style.display = ""; // si está vacío, mostrar todo

                } else {

                    fila.style.display = "none"; // ocultar si no coincide

                }

            });

            // 🔁 Manejo de acordeones por categoría
            acordeones.forEach(function(item) {

                let collapse = item.querySelector(".accordion-collapse");
                let button = item.querySelector(".accordion-button");
                let filasVisibles = item.querySelectorAll(".fila-producto:not([style*='display: none'])");

                if (filtro === "") {

                    item.style.display = "";
                    collapse.classList.remove("show");
                    button.classList.add("collapsed");

                } else if (filasVisibles.length > 0) {

                    item.style.display = "";
                    collapse.classList.add("show");
                    button.classList.remove("collapsed");

                } else {

                    item.style.display = "none";

                }

            });

            // 🔥 Mensaje sin resultados
            let mensaje = document.getElementById("sinResultados");

            if (!encontrado && filtro !== "") {
                mensaje.classList.remove("d-none");
            } else {
                mensaje.classList.add("d-none");
            }

        });
    </script>


    <!-- 🎨 MAPA DE COLORES PREDEFINIDOS -->
    <script>
        // Diccionario de nombres de colores a HEX
        const mapaColores = {
            "rojo": "#cf3131",
            "azul claro": "#0000ff",
            "verde": "#008000",
            "negro": "#000000",
            "blanco": "#ffffff",
            "gris": "#808080",
            "rosado": "#ff69b4",
            "palo de rosa": "#e8a2a2",
            "azul noche": "#0a1a2f",
            "dorado": "#d4af37",
            "plateado": "#c0c0c0",
            "beige": "#f5f5dc",
            "marron": "#8b4513",
            "cafe": "#6f4e37",
            "gris oscuro": "#444444",
            "azul petroleo": "#084d6e",
            "azul": "#035096",
            "gris claro": "#D3D3D3",
            "mostaza": "#e0b046",
            "verde oscuro esmeralda": "#00594f",
            "azul verde oscuro": "#3F6372",
            "cafe claro": "#9a7c43",
            "turquesa": "#3bc9be",
            "mocca": "#967969",
            "verde oscuro": "#153c15",
            "cobre": "#B87333",
            "verde militar": "#7E8C54",
            "chocolate": "#45322e",
            "verde gris": "#6b8262a8",
            "plata": "#e3e4e5",
            "verde menta": "#b6d8c5",
            "azul oscuro": "#000061",
            "agua marina": "#339992"

        };
    </script>



    <!-- 🎨 RENDERIZADO VISUAL DE COLORES SELECCIONADOS -->
    <script>
        let colores = []; // array de colores seleccionados

        function renderColores() {

            let preview = document.getElementById("colorPreview"); // contenedor visual
            let inputHidden = document.getElementById("Color"); // input oculto para enviar al backend

            preview.innerHTML = ""; // limpiar vista

            colores.forEach((color, index) => {

                preview.innerHTML += `
            <span style="
                display:inline-flex;
                align-items:center;
                gap:6px;
                padding:6px 14px;
                border-radius:20px;
                background:#f1f1f1;
                margin:4px;
                font-size:13px;
                font-weight:500;
            ">
                <span style="
                    width:16px;
                    height:16px;
                    border-radius:50%;
                    background:${color.hex ? color.hex : '#000'};
                    border:1px solid #ccc;
                "></span>
                ${color.nombre}
                <span onclick="eliminarColor(${index})"
                      style="cursor:pointer;font-weight:bold;">×</span>
            </span>
        `;
            });

            // 🔥 guardar formato nombre|hex para PHP
            inputHidden.value = colores.map(c => c.nombre + "|" + c.hex).join(",");
        }

        // ❌ eliminar color
        function eliminarColor(index) {
            colores.splice(index, 1);
            renderColores();
        }
    </script>

    <!-- ➕ AGREGAR COLOR -->
    <script>
        function agregarColor() {

            let nombre = document.getElementById("colorNombre").value.trim().toLowerCase();

            // validar vacío
            if (nombre === "") {

                Swal.fire({
                    icon: "warning",
                    title: "Campo vacío",
                    text: "Escribe el nombre del color antes de agregarlo",
                    confirmButtonColor: "#111"
                });

                return;
            }

            // 🔥 limitar máximo de 10 colores
            if (colores.length >= 10) {

                Swal.fire({
                    icon: "info",
                    title: "Límite alcanzado",
                    text: "Solo puedes agregar máximo 10 colores",
                    confirmButtonColor: "#111"
                });

                return;
            }

            let hex = mapaColores[nombre] || "#000000";

            colores.push({
                nombre: nombre,
                hex: hex
            });

            document.getElementById("colorNombre").value = "";
            document.getElementById("previewColor").style.background = "#000";

            renderColores();
        }
    </script>


    <!-- ✏️ CARGAR COLORES CUANDO SE EDITA UN PRODUCTO -->
    <?php if ($editar && !empty($producto_edit['Color'])): ?>
        <script>
            document.addEventListener("DOMContentLoaded", function() {

                let data = "<?= $producto_edit['Color'] ?>".split(",").map(c => c.trim());

                colores = data.map(item => {
                    let partes = item.split("|");
                    return {
                        nombre: partes[0],
                        hex: partes[1] ?? "#000000"
                    };
                });

                renderColores();
            });
        </script>
    <?php endif; ?>

    <!-- 🎨 PREVISUALIZACIÓN DE COLOR EN TIEMPO REAL -->
    <script>
        document.getElementById("colorNombre").addEventListener("input", function() {

            let valor = this.value.trim().toLowerCase();

            let hex;

            if (valor.startsWith("#")) {
                hex = valor;
            } else {
                hex = mapaColores[valor] || "#000000";
            }

            document.getElementById("previewColor").style.background = hex;

        });
    </script>


    <!-- 🔄 CONFIRMAR ACTIVAR / PAUSAR PRODUCTO -->
    <script>
        function confirmarToggle(e, id, cantidad) {

            e.preventDefault(); // 🚫 evita que se vaya directo al link

            let accion = cantidad > 0 ? "pausar" : "activar";
            let color = cantidad > 0 ? "#dc3545" : "#198754";
            let texto = cantidad > 0 ?
                "El producto quedará sin disponibilidad" :
                "El producto volverá a estar disponible";

            Swal.fire({
                title: "¿Seguro que quieres " + accion + "?",
                text: texto,
                icon: "question",
                showCancelButton: true,
                confirmButtonColor: color,
                cancelButtonColor: "#6c757d",
                confirmButtonText: "Sí, " + accion,
                cancelButtonText: "Cancelar"
            }).then((result) => {

                if (result.isConfirmed) {
                    window.location.href = "?toggle=" + id;
                }

            });
        }
    </script>


    <!-- ➕ AGREGAR NUEVA MEDIDA -->
    <script>
        function agregarMedida() {

            let contenedor = document.getElementById("contenedorMedidas");

            let fila = document.createElement("div");
            fila.classList.add("fila-medida");

            fila.innerHTML = `
    <select name="medida[]">
        <option value="">Sin medida</option>
        <option>Sencillo | 100 x 190</option>
        <option>SemiDoble | 120 x 190</option>
        <option>Doble | 140 x 190</option>
        <option>Queen | 160 x 190</option>
        <option>King | 200 x 200</option>
        <option>140 x 190 | Dividida</option>
        <option>160 x 190 | Dividida</option>
        <option>160 x 200 | Dividida</option>
        <option>200 x 200 | Dividida</option>
        <option>Opción 1</option>
        <option>Opción 2</option>
        <option>1 Unidad</option>
        <option>2 Unidades</option>
        <option>Mediano</option>
        <option>Grande</option>
    </select>

    <input type="number" name="precio_medida[]" placeholder="Precio $" required>
    <input type="number" name="stock_medida[]" placeholder="Stock" min="0" value="0">

    <button type="button" onclick="this.parentElement.remove()">✖</button>
`;


            contenedor.appendChild(fila);
        }
    </script>

    <!-- 📦 LIBRERÍAS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- 🔔 ALERTAS DESDE PHP -->
    <?php if ($alerta == 'registrado'): ?>
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Producto registrado correctamente'
            });
        </script>
    <?php endif; ?>

    <?php if ($alerta == 'actualizado'): ?>
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Producto actualizado correctamente'
            });
        </script>
    <?php endif; ?>

    <?php if ($alerta == 'eliminado'): ?>
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Producto eliminado'
            });
        </script>
    <?php endif; ?>

    <?php if ($alerta == 'no_eliminar'): ?>
        <script>
            Swal.fire({
                icon: 'error',
                title: 'No puedes eliminar un producto con stock'
            });
        </script>
    <?php endif; ?>

    <!-- 🔥 BOOTSTRAP JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>
<?php
// 🔐 Inicia la sesión para poder usar variables como Cedula del usuario
session_start();

// 🔌 Conexión a la base de datos
include("../CONEXION/conexion.php");

// 🔒 Validación de seguridad: si no hay sesión iniciada, redirige al login
if (!isset($_SESSION['Cedula'])) {
    header("Location: ../LOGIN/login.php");
    exit;
}

/* =========================
   📦 CONSULTA DE INVENTARIO
========================= */

// Consulta principal que trae:
// - Productos
// - Categoría
// - Variaciones (medidas, precios por medida y stock por medida)
$sql = "
    SELECT 
        p.Id_Producto,            -- ID único del producto
        p.Nombre,                 -- Nombre del producto
        p.Color,                  -- Color del producto
        p.Precio_Venta,           -- Precio general de venta
        p.Cantidad,               -- Stock general del producto
        c.Nombre_Categoria,       -- Nombre de la categoría
        v.Medida,                 -- Medida de la variación (ej: 100x190)
        v.Precio AS Precio_Medida,-- Precio específico de esa medida
        v.Cantidad AS Stock_Medida-- Stock específico de esa medida
    FROM productos p
    INNER JOIN categorias c 
        ON p.Id_Categoria = c.Id_Categoria
    LEFT JOIN variaciones_producto v
        ON p.Id_Producto = v.Id_Producto
    ORDER BY c.Nombre_Categoria ASC, p.Id_Producto ASC
";

// Ejecuta la consulta en la base de datos
$resultado = $conexion->query($sql);

/* =========================
   🧠 ORGANIZAR DATOS
========================= */

// Se crea un arreglo para agrupar productos con sus variaciones
$productos = [];

// Recorre cada fila que devuelve la consulta
while ($row = $resultado->fetch_assoc()) {

    // Obtiene el ID del producto actual
    $id = $row['Id_Producto'];

    // Si el producto aún no ha sido agregado al arreglo, se crea
    if (!isset($productos[$id])) {
        $productos[$id] = [
            'Id_Producto' => $row['Id_Producto'],          // ID
            'Nombre' => $row['Nombre'],                    // Nombre
            'Color' => $row['Color'],                      // Color
            'Precio_Venta' => $row['Precio_Venta'],        // Precio general
            'Cantidad' => $row['Cantidad'],                // Stock general
            'Nombre_Categoria' => $row['Nombre_Categoria'], // Categoría
            'variaciones' => []                            // Lista de variaciones
        ];
    }

    // Si el producto tiene medidas (variaciones), se agregan al arreglo
    if (!empty($row['Medida'])) {
        $productos[$id]['variaciones'][] = [
            'medida' => $row['Medida'],            // Ej: 100x190
            'precio' => $row['Precio_Medida'],     // Precio por medida
            'stock' => $row['Stock_Medida']        // Stock por medida
        ];
    }
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <!-- 🏷️ Título de la página -->
    <title>Inventario</title>

    <!-- 🎨 Bootstrap para estilos -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- 🎨 Estilos propios del sistema Kaiu -->
    <link rel="stylesheet" href="../CSS/kaiu.css">
</head>

<body>

    <div class="container mt-5">

        <!-- 📦 Tarjeta principal -->
        <div class="card-kaiu">

            <!-- 🏷️ Título -->
            <h3 class="kaiu-title">📦 Inventario Kaiu Home</h3>

            <!-- 🔍 BUSCADOR DE PRODUCTOS -->
            <div class="mb-4 buscador-container">

                <!-- Campo de búsqueda -->
                <input type="text" id="buscador" class="form-control buscador-kaiu"
                    placeholder="🔍 Buscar producto por nombre o color...">

                <!-- Mensaje cuando no encuentra nada -->
                <div id="mensajeNoEncontrado" class="mensaje-no mt-2 d-none">
                    ❌ No se encontró ningún producto
                </div>
            </div>


            <!-- 📚 ACORDEÓN POR CATEGORÍAS -->
            <div class="accordion" id="accordionInventario">

                <?php
                // Variable para detectar cambio de categoría
                $categoria_actual = "";

                // Índice del acordeón
                $index = 0;

                // 🖼️ Iconos por categoría
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

                // 🔁 Recorre todos los productos organizados
                foreach ($productos as $p):

                    // 📌 Detecta cambio de categoría
                    if ($categoria_actual != $p['Nombre_Categoria']):

                        // 🔚 Cierra tabla anterior si ya había una
                        if ($categoria_actual != ""):
                            echo "</tbody></table></div></div></div>";
                        endif;

                        // 🔢 Nuevo índice de acordeón
                        $index++;

                        // 📦 Actualiza categoría actual
                        $categoria_actual = $p['Nombre_Categoria'];

                        // 🖼️ Asigna icono
                        $icono = isset($iconos[$categoria_actual]) ? $iconos[$categoria_actual] : "bi-box";
                ?>

                        <!-- 🔽 ITEM DEL ACORDEÓN -->
                        <div class="accordion-item">

                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#collapse<?= $index ?>">

                                    <!-- Icono de categoría -->
                                    <img src="<?= $icono ?>" class="icono-cat">

                                    <!-- Nombre de la categoría -->
                                    <?= $categoria_actual ?>
                                </button>
                            </h2>

                            <div id="collapse<?= $index ?>" class="accordion-collapse collapse">

                                <div class="accordion-body table-responsive">

                                    <!-- 📋 TABLA DE PRODUCTOS -->
                                    <table class="table kaiu-table table-hover">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Nombre</th>
                                                <th>Colores</th>
                                                <th>Precio / Medidas</th>
                                                <th>Cantidad</th>
                                                <th>Disponibilidad</th>
                                            </tr>
                                        </thead>
                                        <tbody>

                                            <?php $i = 1; ?> <!-- 🔥 contador visual -->

                                        <?php endif; ?>

                                        <!-- 🔁 FILA DE PRODUCTO -->
                                        <tr class="fila-producto">

                                            <!-- 🔢 ID visual -->
                                            <td>
                                                <span class="badge-mantenimiento">
                                                    #<?= $i++ ?>
                                                </span>
                                            </td>

                                            <!-- 🏷️ Nombre -->
                                            <td class="nombre">
                                                <strong><?= $p['Nombre'] ?></strong>
                                            </td>

                                            <!-- 🎨 COLORES -->
                                            <td class="color-text">
                                                <?php
                                                // Si el producto tiene colores
                                                if (!empty($p['Color'])) {

                                                    // Divide colores por coma
                                                    $colores = explode(",", $p['Color']);

                                                    foreach ($colores as $color) {

                                                        $color = trim($color);
                                                        if ($color == "") continue;

                                                        // Separa nombre y color HEX
                                                        if (strpos($color, "|") !== false) {
                                                            list($nombre, $hex) = explode("|", $color);
                                                        } else {
                                                            $nombre = $color;
                                                            $hex = "#cccccc";
                                                        }

                                                        $nombre = trim($nombre);
                                                        $hex = trim($hex);

                                                        if ($hex == "" || $hex == "#") {
                                                            $hex = "#cccccc";
                                                        }
                                                ?>
                                                        <span class="color-circle"
                                                            data-color="<?= htmlspecialchars($nombre) ?>"
                                                            style="background-color: <?= htmlspecialchars($hex) ?>;">
                                                        </span>
                                                <?php
                                                    }
                                                } else {
                                                    echo '<span class="text-muted">Sin color</span>';
                                                }
                                                ?>
                                            </td>

                                            <!-- 💰 PRECIOS POR MEDIDA -->
                                            <td>
                                                <?php if (!empty($p['variaciones'])): ?>

                                                    <?php foreach ($p['variaciones'] as $v): ?>
                                                        <div>
                                                            <strong><?= $v['medida'] ?></strong> :
                                                            <span class="text-success fw-bold">
                                                                $<?= number_format($v['precio'], 0, ',', '.') ?>
                                                            </span>
                                                        </div>
                                                    <?php endforeach; ?>

                                                <?php else: ?>

                                                    <div>
                                                        <span class="text-success fw-bold">
                                                            $<?= number_format($p['Precio_Venta'], 0, ',', '.') ?>
                                                        </span>
                                                    </div>

                                                <?php endif; ?>
                                            </td>

                                            <!-- 📦 STOCK POR MEDIDA -->
                                            <td>
                                                <?php if (!empty($p['variaciones'])): ?>

                                                    <?php foreach ($p['variaciones'] as $v): ?>

                                                        <?php if ($v['stock'] > 0): ?>
                                                            <div class="badge-disponible" style="margin-bottom:5px;">
                                                                <?= $v['medida'] ?> → <?= $v['stock'] ?> disponibles
                                                            </div>
                                                        <?php else: ?>
                                                            <div class="badge-inactivo" style="margin-bottom:5px;">
                                                                <?= $v['medida'] ?> → Agotado
                                                            </div>
                                                        <?php endif; ?>

                                                    <?php endforeach; ?>

                                                <?php else: ?>

                                                    <?php if ($p['Cantidad'] > 0): ?>
                                                        <span class="badge-disponible">
                                                            <?= $p['Cantidad'] ?> disponibles
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge-inactivo">
                                                            Agotado
                                                        </span>
                                                    <?php endif; ?>

                                                <?php endif; ?>
                                            </td>

                                            <!-- 📊 DISPONIBILIDAD GLOBAL -->
                                            <td>
                                                <?php
                                                $totalStock = 0;

                                                if (!empty($p['variaciones'])) {
                                                    foreach ($p['variaciones'] as $v) {
                                                        $totalStock += $v['stock'];
                                                    }
                                                } else {
                                                    $totalStock = $p['Cantidad'];
                                                }

                                                if ($totalStock > 3):
                                                ?>
                                                    <span class="badge-disponible">Disponible</span>
                                                <?php elseif ($totalStock > 0): ?>
                                                    <span class="badge-stock-bajo">Stock bajo</span>
                                                <?php else: ?>
                                                    <span class="badge-inactivo">Agotado</span>
                                                <?php endif; ?>
                                            </td>

                                        </tr>

                                    <?php endforeach; ?>

                                        </tbody>
                                    </table>

                                </div>
                            </div>
                        </div>

            </div>

            <!-- 🔙 BOTÓN VOLVER -->
            <div class="text-center mt-4">
                <a href="../DASHBOARD/dashboard.php" class="btn btn-kaiu">⬅ Volver</a>
            </div>

        </div>
    </div>

    <script>
        // 🔎 Evento que se ejecuta cada vez que el usuario escribe en el buscador
        document.getElementById("buscador").addEventListener("keyup", function() {

            // 🔡 Obtiene el texto escrito, lo pasa a minúsculas y elimina espacios
            let filtro = this.value.toLowerCase().trim();

            // 📦 Selecciona todas las filas de productos
            let filas = document.querySelectorAll(".fila-producto");

            // 📚 Selecciona todos los acordeones (categorías)
            let acordeones = document.querySelectorAll(".accordion-item");

            // 🔎 Variable para saber si encontró coincidencias
            let encontrado = false;

            // 🔁 Recorre cada fila (producto)
            filas.forEach(fila => {

                // 📛 Obtiene el nombre del producto
                let nombre = fila.querySelector(".nombre").innerText.toLowerCase();

                // 🎨 Obtiene los colores del producto
                let colores = fila.querySelector(".color-text").innerText.toLowerCase();

                // 🔄 Si el buscador está vacío
                if (filtro === "") {
                    fila.style.display = ""; // muestra la fila
                    fila.classList.remove("resaltado"); // quita resaltado
                }
                // 🔍 Si coincide con nombre o color
                else if (nombre.includes(filtro) || colores.includes(filtro)) {
                    fila.style.display = ""; // muestra la fila
                    fila.classList.add("resaltado"); // resalta la coincidencia
                    encontrado = true; // marca que sí encontró algo
                }
                // ❌ Si no coincide
                else {
                    fila.style.display = "none"; // oculta la fila
                    fila.classList.remove("resaltado"); // quita resaltado
                }

            });

            /* 🔥 CONTROL DE ACORDEONES (categorías) */
            acordeones.forEach(item => {

                // 📦 Obtiene el contenedor desplegable
                let collapse = item.querySelector(".accordion-collapse");

                // 🔘 Botón del acordeón
                let button = item.querySelector(".accordion-button");

                // 👀 Filas visibles dentro de ese acordeón
                let visibles = item.querySelectorAll(".fila-producto:not([style*='display: none'])");

                // 🔄 Si el buscador está vacío
                if (filtro === "") {
                    item.style.display = ""; // muestra el acordeón
                    collapse.classList.remove("show"); // lo cierra
                    button.classList.add("collapsed"); // deja el botón cerrado
                }
                // 🔍 Si hay resultados en ese acordeón
                else if (visibles.length > 0) {
                    item.style.display = ""; // muestra la categoría
                    collapse.classList.add("show"); // abre el acordeón
                    button.classList.remove("collapsed"); // activa el botón
                }
                // ❌ Si no hay resultados
                else {
                    item.style.display = "none"; // oculta la categoría completa
                }

            });

            /* ❌ MENSAJE CUANDO NO HAY RESULTADOS */
            let mensaje = document.getElementById("mensajeNoEncontrado");

            // Si hay texto y no encontró nada
            if (filtro !== "" && !encontrado) {
                mensaje.classList.remove("d-none"); // muestra mensaje
            } else {
                mensaje.classList.add("d-none"); // oculta mensaje
            }

        });
    </script>

    <!-- 📦 JS de Bootstrap para funcionamiento de acordeones -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>
<?php
/**
 * API del módulo de Ventas — FASE 1.
 * Equivalente en PHP + PDO de FVentasa.pas / FM_SelVenta.pas / FConcentra.pas
 * (solo el camino de venta de CONTADO / "Facturado" F12 por ahora).
 *
 * Endpoints:
 *   GET  api/ventas.php?action=folio                                    -> siguiente número de venta
 *   GET  api/ventas.php?action=buscar_codbarra&codbarra=205001305        -> datos del artículo (para escaneo)
 *   GET  api/ventas.php?action=buscar_manual&marcano=&modelono=&talla=   -> datos del artículo (búsqueda manual)
 *   GET  api/ventas.php?action=modelos_marca&marcano=                    -> modelos+corrida de una marca (autocompletar)
 *   POST api/ventas.php?action=guardar_contado  (body JSON: carrito, pago)  -> guarda la venta de contado
 *   POST api/ventas.php?action=devolucion (body JSON: codbarra)             -> registra una devolución
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

header('Content-Type: application/json; charset=utf-8');
requerirLoginApi();

$metodo = $_SERVER['REQUEST_METHOD'];
$accion = $_GET['action'] ?? '';

if ($metodo === 'GET') {
    switch ($accion) {
        case 'folio':
            siguienteFolio($pdo);
            break;
        case 'buscar_codbarra':
            buscarPorCodBarra($pdo, $_GET['codbarra'] ?? '');
            break;
        case 'buscar_manual':
            buscarManual($pdo, $_GET['marcano'] ?? '', $_GET['modelono'] ?? '', $_GET['talla'] ?? '');
            break;
        case 'modelos_marca':
            modelosPorMarca($pdo, $_GET['marcano'] ?? '');
            break;
        default:
            responder(false, 'Acción no reconocida.', null, 400);
    }
} elseif ($metodo === 'POST' && $accion === 'guardar_contado') {
    guardarVentaContado($pdo);
} elseif ($metodo === 'POST' && $accion === 'devolucion') {
    registrarDevolucion($pdo);
} else {
    responder(false, 'Método no permitido.', null, 405);
}

/* ------------------------------------------------------------------ */
/* Funciones auxiliares                                               */
/* ------------------------------------------------------------------ */

function responder($success, $message = '', $data = null, $code = 200)
{
    http_response_code($code);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data'    => $data,
    ]);
    exit;
}

function siguienteFolio($pdo)
{
    $stmt = $pdo->query('SELECT MAX(VENTANO) AS maximo FROM ventas');
    $maximo = $stmt->fetch()['maximo'];
    responder(true, '', ['folio' => $maximo ? ((int) $maximo + 1) : 1]);
}

/**
 * Equivalente a Agrega_Detalle() en FVentasa.pas: localiza el artículo
 * por CODBARRA y arma la fila que se agregará al carrito.
 */
function buscarPorCodBarra($pdo, $codbarra)
{
    $codbarra = trim($codbarra);
    if ($codbarra === '') {
        responder(false, 'Código de barras vacío.', null, 400);
    }

    $sql = 'SELECT a.MARCANO, a.MODELONO, a.TALLA, a.EXISTENCIA, a.CODBARRA,
                   ma.MARCADES,
                   mo.MODELODES, mo.COLOR, mo.MATERIAL, mo.SUELA, mo.PRECIOVE, mo.PRECIOCO
            FROM articulos a
            JOIN marcas ma  ON ma.MARCANO = a.MARCANO
            JOIN modelos mo ON mo.MARCANO = a.MARCANO AND mo.MODELONO = a.MODELONO
            WHERE a.CODBARRA = :codbarra
            LIMIT 1';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':codbarra' => $codbarra]);
    $articulo = $stmt->fetch();

    if (!$articulo) {
        responder(false, 'El código de artículo no existe.', null, 404);
    }

    responder(true, '', formatearArticuloParaCarrito($articulo));
}

/**
 * Equivalente a FM_SelVenta (búsqueda manual por marca/modelo/talla).
 */
function buscarManual($pdo, $marcano, $modelono, $talla)
{
    if ($marcano === '' || $modelono === '' || $talla === '') {
        responder(false, 'Marca, modelo y talla son requeridos.', null, 400);
    }

    $sql = 'SELECT a.MARCANO, a.MODELONO, a.TALLA, a.EXISTENCIA, a.CODBARRA,
                   ma.MARCADES,
                   mo.MODELODES, mo.COLOR, mo.MATERIAL, mo.SUELA, mo.PRECIOVE, mo.PRECIOCO
            FROM articulos a
            JOIN marcas ma  ON ma.MARCANO = a.MARCANO
            JOIN modelos mo ON mo.MARCANO = a.MARCANO AND mo.MODELONO = a.MODELONO
            WHERE a.MARCANO = :marcano AND a.MODELONO = :modelono AND a.TALLA = :talla
            LIMIT 1';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':marcano' => $marcano, ':modelono' => $modelono, ':talla' => $talla]);
    $articulo = $stmt->fetch();

    if (!$articulo) {
        responder(false, 'No hay existencia para esa combinación de marca, modelo y talla.', null, 404);
    }
    if ((int) $articulo['EXISTENCIA'] <= 0) {
        responder(false, 'La talla no tiene existencia disponible.', null, 409);
    }

    responder(true, '', formatearArticuloParaCarrito($articulo));
}

function formatearArticuloParaCarrito($articulo)
{
    return [
        'codbarra'   => $articulo['CODBARRA'],
        'marcano'    => (int) $articulo['MARCANO'],
        'marcades'   => $articulo['MARCADES'],
        'modelono'   => (int) $articulo['MODELONO'],
        'modelodes'  => $articulo['MODELODES'],
        'color'      => $articulo['COLOR'],
        'material'   => $articulo['MATERIAL'],
        'suela'      => $articulo['SUELA'],
        'talla'      => (int) $articulo['TALLA'],
        'precio'     => (float) $articulo['PRECIOVE'],
        'preciovale' => (float) $articulo['PRECIOCO'], // igual que EPrecioVale en el original
        'existencia' => (int) $articulo['EXISTENCIA'],
    ];
}

/**
 * Modelos + rango de tallas (vía vmodecorr) de una marca, para
 * apoyar la búsqueda manual desde el navegador (autocompletar modelo).
 */
function modelosPorMarca($pdo, $marcano)
{
    if ($marcano === '') {
        responder(false, 'MARCANO es requerido.', null, 400);
    }

    $stmt = $pdo->prepare(
        'SELECT MODELONO, MODELODES, COLOR, MATERIAL, SUELA, PRECIOVE, TALLAINI, TALLAFIN, CLASIFIDES
         FROM vmodecorr WHERE MARCANO = :marcano ORDER BY MODELODES ASC'
    );
    $stmt->execute([':marcano' => $marcano]);

    responder(true, '', $stmt->fetchAll());
}

/**
 * Equivalente a BAceptarClick + Imprime_Ticket (parcial) + Guarda_Venta
 * + Actualiza_Inventario(True) + Actualiza_Inventario_General, para el
 * camino de CONTADO (F12 / "Facturado").
 */
function guardarVentaContado($pdo)
{
    $input   = json_decode(file_get_contents('php://input'), true) ?? [];
    $carrito = $input['carrito'] ?? [];
    $pagado  = isset($input['pagado']) ? (float) $input['pagado'] : 0;

    if (empty($carrito) || !is_array($carrito)) {
        responder(false, 'El carrito está vacío.', null, 400);
    }

    // Total esperado (se recalcula en servidor, nunca se confía en el total del cliente)
    $total = 0;
    foreach ($carrito as $item) {
        $total += (float) ($item['precio'] ?? 0);
    }

    if ($pagado < $total) {
        responder(false, 'El monto pagado es menor al importe total.', null, 422);
    }

    $pdo->beginTransaction();
    try {
        // 1) Folio de venta
        $stmt = $pdo->query('SELECT MAX(VENTANO) AS maximo FROM ventas');
        $maximo = $stmt->fetch()['maximo'];
        $folio  = $maximo ? ((int) $maximo + 1) : 1;

        // 2) Costeo PEPS + desglose de IVA por artículo (igual que Actualiza_Inventario(True) + Imprime_Ticket)
        $subtotal    = 0;
        $totalIva    = 0;
        $detalle     = [];
        $numeroLinea = 1;

        foreach ($carrito as $item) {
            $marcano  = (int) $item['marcano'];
            $modelono = (int) $item['modelono'];
            $talla    = (int) $item['talla'];
            $codbarra = $item['codbarra'];
            $precio   = (float) $item['precio'];

            // Costo PEPS: la existencia más antigua con EXISTENCIA > 0
            $stmt = $pdo->prepare(
                'SELECT FECHAENT, PRECIOCO FROM peps
                 WHERE MARCANO = :marcano AND MODELONO = :modelono AND TALLA = :talla AND EXISTENCIA > 0
                 ORDER BY FECHAENT ASC LIMIT 1'
            );
            $stmt->execute([':marcano' => $marcano, ':modelono' => $modelono, ':talla' => $talla]);
            $registroPeps = $stmt->fetch();

            $costoPeps = 0;
            if ($registroPeps) {
                $costoPeps = (float) $registroPeps['PRECIOCO'];
                // Descuenta 1 pieza de esa entrada PEPS específica
                $stmtUpd = $pdo->prepare(
                    'UPDATE peps SET EXISTENCIA = EXISTENCIA - 1
                     WHERE MARCANO = :marcano AND MODELONO = :modelono AND TALLA = :talla
                       AND FECHAENT = :fechaent AND PRECIOCO = :precioco AND EXISTENCIA > 0
                     LIMIT 1'
                );
                $stmtUpd->execute([
                    ':marcano'   => $marcano,
                    ':modelono'  => $modelono,
                    ':talla'     => $talla,
                    ':fechaent'  => $registroPeps['FECHAENT'],
                    ':precioco'  => $registroPeps['PRECIOCO'],
                ]);
            }

            // Desglose de IVA (16%), igual que en Imprime_Ticket
            $importeSinIva = round($precio / 1.16, 2);
            $iva           = $precio - $importeSinIva;
            $subtotal     += $importeSinIva;
            $totalIva     += $iva;

            // Descuenta 1 pieza del inventario general (Actualiza_Inventario_General)
            $stmt = $pdo->prepare(
                'UPDATE articulos SET EXISTENCIA = EXISTENCIA - 1 WHERE CODBARRA = :codbarra AND EXISTENCIA > 0 LIMIT 1'
            );
            $stmt->execute([':codbarra' => $codbarra]);

            // Existencia restante, para mostrarla en el resumen (igual que informaba FConcentra)
            $stmt = $pdo->prepare('SELECT EXISTENCIA FROM articulos WHERE CODBARRA = :codbarra');
            $stmt->execute([':codbarra' => $codbarra]);
            $existenciaRestante = (int) ($stmt->fetchColumn() ?: 0);

            $detalle[] = [
                'detalleno'  => $numeroLinea,
                'codbarra'   => $codbarra,
                'marcano'    => $marcano,
                'modelono'   => $modelono,
                'talla'      => $talla,
                'marcades'   => $item['marcades']  ?? '',
                'modelodes'  => $item['modelodes'] ?? '',
                'color'      => $item['color']     ?? '',
                'material'   => $item['material']  ?? '',
                'suela'      => $item['suela']      ?? '',
                'precio'     => $precio,
                'preciocopeps' => $costoPeps,
                'existencia_restante' => $existenciaRestante,
            ];

            $numeroLinea++;
        }

        // 3) Actualiza contadores diarios/acumulados en `empresa`
        $stmt = $pdo->query('SELECT * FROM empresa LIMIT 1');
        $empresa = $stmt->fetch();
        if ($empresa) {
            $stmt = $pdo->prepare(
                'UPDATE empresa SET
                    RECFISCAL   = RECFISCAL + 1,
                    GTSUBTOT    = GTSUBTOT + :subtotal,
                    GTVTA       = GTVTA + :total,
                    GTIVA       = GTIVA + :iva,
                    RECIBOSDIA  = RECIBOSDIA + 1,
                    SUBTOTAL    = SUBTOTAL + :subtotal2,
                    VENTADIA    = VENTADIA + :total2,
                    IVADIA      = IVADIA + :iva2
                 WHERE CODIGONO = :codigono'
            );
            $stmt->execute([
                ':subtotal'  => $subtotal,
                ':total'     => $total,
                ':iva'       => $totalIva,
                ':subtotal2' => $subtotal,
                ':total2'    => $total,
                ':iva2'      => $totalIva,
                ':codigono'  => $empresa['CODIGONO'],
            ]);
        }

        // 4) Inserta encabezado en `ventas`
        $stmt = $pdo->prepare(
            'INSERT INTO ventas (VENTANO, FECHAVEN, IMPORTEVEN, ESTADOVEN, VALENO, VENDENO)
             VALUES (:ventano, CURDATE(), :importe, NULL, 0, 0)'
        );
        $stmt->execute([':ventano' => $folio, ':importe' => $total]);

        // 5) Inserta detalle en `detventas`
        $stmtDet = $pdo->prepare(
            'INSERT INTO detventas (VENTANO, DETALLENO, MARCANO, MODELONO, TALLA, CANTIDAD, PRECIOVE, IMPORTEART, SITUACION, CODBARRA, PRECIOCO)
             VALUES (:ventano, :detalleno, :marcano, :modelono, :talla, 1, :precio, :precio2, NULL, :codbarra, :preciocopeps)'
        );
        foreach ($detalle as $linea) {
            $stmtDet->execute([
                ':ventano'      => $folio,
                ':detalleno'    => $linea['detalleno'],
                ':marcano'      => $linea['marcano'],
                ':modelono'     => $linea['modelono'],
                ':talla'        => $linea['talla'],
                ':precio'       => $linea['precio'],
                ':precio2'      => $linea['precio'],
                ':codbarra'     => $linea['codbarra'],
                ':preciocopeps' => $linea['preciocopeps'],
            ]);
        }

        $pdo->commit();

        responder(true, 'Venta registrada correctamente.', [
            'folio'    => $folio,
            'fecha'    => date('Y-m-d'),
            'subtotal' => round($subtotal, 2),
            'iva'      => round($totalIva, 2),
            'total'    => round($total, 2),
            'pagado'   => round($pagado, 2),
            'cambio'   => round($pagado - $total, 2),
            'detalle'  => $detalle,
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        responder(false, 'Error al guardar la venta: ' . $e->getMessage(), null, 500);
    }
}

/**
 * Equivalente a Agrega_Entrada() en FVentasa.pas (código escaneado con
 * prefijo 'D'): regresa 1 pieza a la existencia.
 */
function registrarDevolucion($pdo)
{
    $input    = json_decode(file_get_contents('php://input'), true) ?? [];
    $codbarra = trim($input['codbarra'] ?? '');

    if ($codbarra === '') {
        responder(false, 'Código de barras requerido.', null, 400);
    }

    $stmt = $pdo->prepare('SELECT EXISTENCIA FROM articulos WHERE CODBARRA = :codbarra');
    $stmt->execute([':codbarra' => $codbarra]);
    $articulo = $stmt->fetch();

    if (!$articulo) {
        responder(false, 'El código no existe.', null, 404);
    }

    $stmt = $pdo->prepare('UPDATE articulos SET EXISTENCIA = EXISTENCIA + 1 WHERE CODBARRA = :codbarra');
    $stmt->execute([':codbarra' => $codbarra]);

    responder(true, 'Devolución registrada con éxito.', ['codbarra' => $codbarra]);
}

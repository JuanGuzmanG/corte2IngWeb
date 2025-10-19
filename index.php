<?php
session_start();

// Función para calcular rangos de probabilidad de demanda
function calcularRangosDemanda($probabilidades) {
    $rangos = [];
    $acumulado = 0;
    foreach ($probabilidades as $key => $prob) {
        $minimo = $acumulado;
        $acumulado += $prob / 100;
        $rangos[$key] = [
            'minimo' => $minimo,
            'maximo' => $acumulado
        ];
    }
    return $rangos;
}

// Función para calcular rangos de tiempo de entrega
function calcularRangosTiempo($probabilidades) {
    $rangos = [];
    $acumulado = 0;
    foreach ($probabilidades as $key => $prob) {
        $minimo = $acumulado;
        $acumulado += $prob / 100;
        $rangos[$key] = [
            'minimo' => $minimo,
            'maximo' => $acumulado
        ];
    }
    return $rangos;
}

// Función para generar números aleatorios usando método congruencial
function generarNumerosAleatorios($a, $x0, $b, $n, $cantidad) {
    $numeros = [];
    $x = $x0;

    for ($i = 0; $i <= $cantidad; $i++) {
        $x = ($a * $x + $b) % $n;
        $numeros[] = [
            'x' => $x,
            'valor' => $x / $n
        ];
    }

    return $numeros;
}

// Función para buscar valor según rango de probabilidad
function buscarValor($azar, $unidades, $rangos) {
    $keys = array_keys($unidades);
    $ultimaKey = end($keys);

    foreach ($rangos as $key => $rango) {
        if ($key === $ultimaKey) {
            if ($azar >= $rango['minimo'] && $azar <= $rango['maximo']) {
                return $unidades[$key];
            }
        } else {
            if ($azar >= $rango['minimo'] && $azar < $rango['maximo']) {
                return $unidades[$key];
            }
        }
    }
    return 0;
}

// Función para formatear dinero
function formatoDinero($valor) {
    if ($valor === '' || $valor === null || $valor === 0) return '$0.00';
    return '$' . number_format($valor, 2, '.', ',');
}

// Procesar formulario
$datosCompletos = null;
$azares1 = null;
$azares2 = null;
$resumen = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {

    if ($_POST['accion'] === 'generar_azar') {
        // Generar números aleatorios
        $a = floatval($_POST['a']);
        $x0 = floatval($_POST['x0']);
        $b = floatval($_POST['b']);
        $n = floatval($_POST['n']);

        $a2 = floatval($_POST['a2']);
        $x0_2 = floatval($_POST['x0_2']);
        $b2 = floatval($_POST['b2']);
        $n2 = floatval($_POST['n2']);

        $azares1 = generarNumerosAleatorios($a, $x0, $b, $n, 1000);
        $azares2 = generarNumerosAleatorios($a2, $x0_2, $b2, $n2, 1000);

        $_SESSION['azares1'] = $azares1;
        $_SESSION['azares2'] = $azares2;

    } elseif ($_POST['accion'] === 'calcular_datos') {

        if (!isset($_SESSION['azares1']) || !isset($_SESSION['azares2'])) {
            $error = "Primero debe generar los números aleatorios";
        } else {
            $azares1 = $_SESSION['azares1'];
            $azares2 = $_SESSION['azares2'];

            // Parámetros del inventario
            $r = floatval($_POST['r']);
            $q = floatval($_POST['q']);
            $invIni = floatval($_POST['inventario_inicial']);
            $cAlm = floatval($_POST['costo_almacenamiento']);
            $cPed = floatval($_POST['costo_pedido']);
            $cPer = floatval($_POST['costo_perdida']);

            // Unidades de demanda
            $unidades = [
                0 => floatval($_POST['unidades'][0]),
                1 => floatval($_POST['unidades'][1]),
                2 => floatval($_POST['unidades'][2]),
                3 => floatval($_POST['unidades'][3]),
                4 => floatval($_POST['unidades'][4])
            ];

            // Probabilidades de demanda
            $probDemanda = [
                0 => floatval($_POST['probabilidad'][0]),
                1 => floatval($_POST['probabilidad'][1]),
                2 => floatval($_POST['probabilidad'][2]),
                3 => floatval($_POST['probabilidad'][3]),
                4 => floatval($_POST['probabilidad'][4])
            ];

            // Días de entrega
            $dias = [
                0 => floatval($_POST['dias'][0]),
                1 => floatval($_POST['dias'][1]),
                2 => floatval($_POST['dias'][2]),
                3 => floatval($_POST['dias'][3]),
                4 => floatval($_POST['dias'][4])
            ];

            // Probabilidades de tiempo de entrega
            $probEntrega = [
                0 => floatval($_POST['prob_entrega'][0]),
                1 => floatval($_POST['prob_entrega'][1]),
                2 => floatval($_POST['prob_entrega'][2]),
                3 => floatval($_POST['prob_entrega'][3]),
                4 => floatval($_POST['prob_entrega'][4])
            ];

            // Calcular rangos
            $rangosDemanda = calcularRangosDemanda($probDemanda);
            $rangosTiempo = calcularRangosTiempo($probEntrega);

            // Inicializar datos
            $datosCompletos = [];
            $totalCI = 0;
            $totalCO = 0;
            $totalCP = 0;

            // Día 0
            $datosCompletos[0] = [
                'dia' => 0,
                'azar1' => '',
                'demanda' => '',
                'inventario' => $invIni,
                'costoInv' => 0,
                'costoOrd' => 0,
                'azar2' => '',
                'llegaPedido' => 0,
                'cuentaAtras' => 0,
                'costoPres' => 0
            ];

            // Calcular días 1 a 1000
            for ($i = 1; $i <= 1000; $i++) {
                // Obtener datos del día anterior
                $datosAnt = $datosCompletos[$i - 1];
                $invAnt = $datosAnt['inventario'];
                $cueAnt = $datosAnt['cuentaAtras'];

                // 1. Decrementar cuenta atrás si existe
                $cue = ($cueAnt > 0) ? $cueAnt - 1 : 0;

                // 2. Verificar si llega pedido HOY (cuando cuenta atrás llega a 0)
                $llegaHoy = 0;
                if ($cueAnt > 0 && $cue == 0) {
                    $llegaHoy = $q;
                }

                // 3. Actualizar inventario con llegada de pedido
                $invConLlegada = $invAnt + $llegaHoy;

                // 4. Generar demanda
                $az1 = $azares1[$i]['valor'];
                $dem = buscarValor($az1, $unidades, $rangosDemanda);

                // 5. Satisfacer demanda
                $inv = $invConLlegada - $dem;

                // 6. Verificar si se debe hacer un nuevo pedido
                $costoOrd = 0;
                $az2Val = '';
                $lle = 0;

                // Se hace pedido si: inventario <= R Y NO hay pedido pendiente
                if ($inv <= $r && $cue == 0) {
                    $costoOrd = $cPed;
                    $az2Val = number_format($azares2[$i]['valor'], 3, '.', '');
                    $lle = buscarValor($azares2[$i]['valor'], $dias, $rangosTiempo);
                    $cue = $lle; // Iniciar nueva cuenta atrás
                } else {
                    $az2Val = '';
                }

                // 7. Calcular costos
                // Costo de inventario (solo si hay inventario positivo)
                $costoInv = ($inv > 0) ? $inv * $cAlm : 0;
                $totalCI += $costoInv;

                // Costo de ordenar
                $totalCO += $costoOrd;

                // Costo de pérdida de prestigio (solo si inventario es negativo)
                $costoPres = ($inv < 0) ? abs($inv) * $cPer : 0;
                $totalCP += $costoPres;

                $datosCompletos[$i] = [
                    'dia' => $i,
                    'azar1' => number_format($az1, 8, '.', ''),
                    'demanda' => $dem,
                    'inventario' => $inv,
                    'costoInv' => $costoInv,
                    'costoOrd' => $costoOrd,
                    'azar2' => $az2Val,
                    'llegaPedido' => $lle,
                    'cuentaAtras' => $cue,
                    'costoPres' => $costoPres
                ];
            }

            $resumen = [
                'costoTotal' => $totalCI + $totalCO + $totalCP,
                'totalCostoInv' => $totalCI,
                'totalCostoOrd' => $totalCO,
                'totalCostoPres' => $totalCP
            ];

            $_SESSION['datosCompletos'] = $datosCompletos;
            $_SESSION['resumen'] = $resumen;
        }
    }
}

// Recuperar datos de sesión si existen
if (isset($_SESSION['azares1'])) {
    $azares1 = $_SESSION['azares1'];
}
if (isset($_SESSION['azares2'])) {
    $azares2 = $_SESSION['azares2'];
}
if (isset($_SESSION['datosCompletos'])) {
    $datosCompletos = $_SESSION['datosCompletos'];
}
if (isset($_SESSION['resumen'])) {
    $resumen = $_SESSION['resumen'];
}

// Valores por defecto
$valores = [
    'r' => $_POST['r'] ?? 81,
    'q' => $_POST['q'] ?? 106,
    'inventario_inicial' => $_POST['inventario_inicial'] ?? 570,
    'costo_perdida' => $_POST['costo_perdida'] ?? 400,
    'costo_pedido' => $_POST['costo_pedido'] ?? 7000,
    'costo_almacenamiento' => $_POST['costo_almacenamiento'] ?? 125,
    'a' => $_POST['a'] ?? 2678917,
    'x0' => $_POST['x0'] ?? 4579991,
    'b' => $_POST['b'] ?? 1317513,
    'n' => $_POST['n'] ?? 9824217,
    'a2' => $_POST['a2'] ?? 7921083,
    'x0_2' => $_POST['x0_2'] ?? 6731297,
    'b2' => $_POST['b2'] ?? 9021531,
    'n2' => $_POST['n2'] ?? 9420811
];

$unidades_default = $_POST['unidades'] ?? [25, 26, 27, 28, 29];
$probabilidad_default = $_POST['probabilidad'] ?? [10, 20, 30, 25, 15];
$dias_default = $_POST['dias'] ?? [3, 4, 5, 6, 7];
$prob_entrega_default = $_POST['prob_entrega'] ?? [20, 30, 35, 10, 5];

// Calcular rangos para mostrar
$rangosDemandaVista = calcularRangosDemanda($probabilidad_default);
$rangosTiempoVista = calcularRangosTiempo($prob_entrega_default);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Gestión de Inventario</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 20px; }
        .container { max-width: 1400px; margin: 0 auto; background: white; border-radius: 15px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); overflow: hidden; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; }
        .header h1 { font-size: 2em; margin-bottom: 10px; }
        .content { padding: 40px; }
        .table-wrapper { overflow-x: auto; margin-bottom: 30px; }
        table { width: 100%; border-collapse: collapse; background: white; box-shadow: 0 5px 15px rgba(0,0,0,0.1); border-radius: 10px; overflow: hidden; }
        thead { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        th { padding: 15px; text-align: left; font-weight: 600; font-size: 0.9em; text-transform: uppercase; letter-spacing: 0.5px; }
        td { padding: 15px; border-bottom: 1px solid #e0e0e0; }
        tbody tr:hover { background-color: #f5f5f5; transition: background-color 0.3s ease; }
        input[type="number"], input[type="text"] { width: 100%; padding: 10px; border: 2px solid #e0e0e0; border-radius: 5px; font-size: 1em; }
        input[readonly] { background-color: #f8f9fa; cursor: not-allowed; }
        .btn-container { display: flex; gap: 15px; justify-content: center; margin-top: 30px; }
        button { padding: 12px 30px; font-size: 1em; border: none; border-radius: 5px; cursor: pointer; transition: all 0.3s ease; font-weight: 600; }
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4); }
        .label-cell { font-weight: 600; color: #495057; }
        .alert { padding: 15px; margin: 20px 0; border-radius: 5px; }
        .alert-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>📊 Sistema de Gestión de Inventario</h1>
        <p>Administración de parámetros de inventario - PHP</p>
    </div>
    <div class="content">

        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="table-wrapper">
                <table>
                    <thead><tr><th>Parámetro</th><th>Valor</th></tr></thead>
                    <tbody>
                    <tr><td class="label-cell">R (Punto de reorden)</td><td><input type="number" name="r" value="<?php echo $valores['r']; ?>" required></td></tr>
                    <tr><td class="label-cell">Q (Cantidad de pedido)</td><td><input type="number" name="q" value="<?php echo $valores['q']; ?>" required></td></tr>
                    <tr><td class="label-cell">Inventario Inicial</td><td><input type="number" name="inventario_inicial" value="<?php echo $valores['inventario_inicial']; ?>" required></td></tr>
                    <tr><td class="label-cell">Costo pérdida prestigio ($/unidad)</td><td><input type="number" name="costo_perdida" value="<?php echo $valores['costo_perdida']; ?>" required></td></tr>
                    <tr><td class="label-cell">Costo hacer pedido ($/pedido)</td><td><input type="number" name="costo_pedido" value="<?php echo $valores['costo_pedido']; ?>" required></td></tr>
                    <tr><td class="label-cell">Costo almacenar ($/unidad/día)</td><td><input type="number" name="costo_almacenamiento" value="<?php echo $valores['costo_almacenamiento']; ?>" required></td></tr>
                    </tbody>
                </table>
            </div>

            <h2 style="margin-top: 50px; margin-bottom: 20px; color: #495057;">📈 Distribución de Probabilidad de Demanda</h2>
            <div class="table-wrapper">
                <table>
                    <thead><tr><th>Unidades</th><th>Probabilidad (%)</th><th>Mínimo</th><th>Máximo</th></tr></thead>
                    <tbody>
                    <?php for ($i = 0; $i < 5; $i++): ?>
                        <tr>
                            <td><input type="number" name="unidades[]" value="<?php echo $unidades_default[$i]; ?>"></td>
                            <td><input type="number" name="probabilidad[]" value="<?php echo $probabilidad_default[$i]; ?>"></td>
                            <td><input type="text" value="<?php echo number_format($rangosDemandaVista[$i]['minimo'], 4); ?>" readonly></td>
                            <td><input type="text" value="<?php echo number_format($rangosDemandaVista[$i]['maximo'], 4); ?>" readonly></td>
                        </tr>
                    <?php endfor; ?>
                    <tr style="background-color: #f8f9fa;">
                        <td>Total:</td>
                        <td><input type="text" value="<?php echo array_sum($probabilidad_default); ?>%" readonly style="background-color: #e9ecef; color: <?php echo (array_sum($probabilidad_default) == 100) ? 'green' : 'red'; ?>;"></td>
                        <td colspan="2"></td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <h2 style="margin-top: 50px; margin-bottom: 20px; color: #495057;">⏱️ Tiempo de Entrega del Proveedor</h2>
            <div class="table-wrapper">
                <table>
                    <thead><tr><th>Días</th><th>Probabilidad (%)</th><th>Mínimo</th><th>Máximo</th></tr></thead>
                    <tbody>
                    <?php for ($i = 0; $i < 5; $i++): ?>
                        <tr>
                            <td><input type="number" name="dias[]" value="<?php echo $dias_default[$i]; ?>"></td>
                            <td><input type="number" name="prob_entrega[]" value="<?php echo $prob_entrega_default[$i]; ?>"></td>
                            <td><input type="text" value="<?php echo number_format($rangosTiempoVista[$i]['minimo'], 4); ?>" readonly></td>
                            <td><input type="text" value="<?php echo number_format($rangosTiempoVista[$i]['maximo'], 4); ?>" readonly></td>
                        </tr>
                    <?php endfor; ?>
                    <tr style="background-color: #f8f9fa;">
                        <td>Total:</td>
                        <td><input type="text" value="<?php echo array_sum($prob_entrega_default); ?>%" readonly style="background-color: #e9ecef; color: <?php echo (array_sum($prob_entrega_default) == 100) ? 'green' : 'red'; ?>;"></td>
                        <td colspan="2"></td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <h2 style="margin-top: 50px; margin-bottom: 20px; color: #495057;">🔢 Números #1</h2>
            <div class="table-wrapper">
                <table>
                    <thead><tr><th>A</th><th>X0</th><th>B</th><th>N</th></tr></thead>
                    <tbody>
                    <tr>
                        <td><input type="number" name="a" value="<?php echo $valores['a']; ?>"></td>
                        <td><input type="number" name="x0" value="<?php echo $valores['x0']; ?>"></td>
                        <td><input type="number" name="b" value="<?php echo $valores['b']; ?>"></td>
                        <td><input type="number" name="n" value="<?php echo $valores['n']; ?>"></td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <h2 style="margin-top: 50px; margin-bottom: 20px; color: #495057;">🔢 Números #2</h2>
            <div class="table-wrapper">
                <table>
                    <thead><tr><th>A</th><th>X0</th><th>B</th><th>N</th></tr></thead>
                    <tbody>
                    <tr>
                        <td><input type="number" name="a2" value="<?php echo $valores['a2']; ?>"></td>
                        <td><input type="number" name="x0_2" value="<?php echo $valores['x0_2']; ?>"></td>
                        <td><input type="number" name="b2" value="<?php echo $valores['b2']; ?>"></td>
                        <td><input type="number" name="n2" value="<?php echo $valores['n2']; ?>"></td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <h2 style="margin-top: 50px; margin-bottom: 20px; color: #495057;">🎲 Generación de Números Aleatorios</h2>
            <div class="btn-container" style="margin-bottom: 20px;">
                <button type="submit" name="accion" value="generar_azar" class="btn-primary">🔄 Generar Números Aleatorios</button>
            </div>

            <?php if ($azares1 && $azares2): ?>
                <div class="table-wrapper">
                    <table>
                        <thead><tr><th>Fila</th><th>AZAR #1 X</th><th>AZAR #1</th><th>AZAR #2 X</th><th>AZAR #2</th></tr></thead>
                        <tbody>
                        <?php for ($i = 0; $i < 10; $i++): ?>
                            <tr>
                                <td class="label-cell"><?php echo $i; ?></td>
                                <td><input type="text" value="<?php echo floor($azares1[$i]['x']); ?>" readonly></td>
                                <td><input type="text" value="<?php echo number_format($azares1[$i]['valor'], 8, '.', ''); ?>" readonly></td>
                                <td><input type="text" value="<?php echo floor($azares2[$i]['x']); ?>" readonly></td>
                                <td><input type="text" value="<?php echo number_format($azares2[$i]['valor'], 5, '.', ''); ?>" readonly></td>
                            </tr>
                        <?php endfor; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <?php if ($resumen): ?>
                <h2 style="margin-top: 50px; margin-bottom: 20px; color: #495057;">💰 Resumen de Costos Totales</h2>
                <div class="table-wrapper">
                    <table>
                        <thead>
                        <tr>
                            <th>Costo Total</th>
                            <th>Total Costo Inventario</th>
                            <th>Total Costo Ordenar</th>
                            <th>Total Costo Pérdida Prestigio</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td><input type="text" value="<?php echo formatoDinero($resumen['costoTotal']); ?>" readonly style="background-color: #fff3cd; font-weight: bold; font-size: 1.1em;"></td>
                            <td><input type="text" value="<?php echo formatoDinero($resumen['totalCostoInv']); ?>" readonly style="background-color: #d1ecf1;"></td>
                            <td><input type="text" value="<?php echo formatoDinero($resumen['totalCostoOrd']); ?>" readonly style="background-color: #d1ecf1;"></td>
                            <td><input type="text" value="<?php echo formatoDinero($resumen['totalCostoPres']); ?>" readonly style="background-color: #d1ecf1;"></td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <h2 style="margin-top: 50px; margin-bottom: 20px; color: #495057;">📊 Tabla de Datos</h2>
            <div class="btn-container" style="margin-bottom: 20px;">
                <button type="submit" name="accion" value="calcular_datos" class="btn-primary">📈 Calcular Datos (1000 filas)</button>
            </div>

            <?php if ($datosCompletos): ?>
                <p style="text-align: center; margin-bottom: 10px; color: #6c757d;">Mostrando las primeras 50 filas de 1000 calculadas</p>
                <div class="table-wrapper">
                    <table style="font-size: 0.85em;">
                        <thead><tr><th>Día</th><th>Azar 1</th><th>Demanda</th><th>Inventario</th><th>Costo Inv</th><th>Costo Ord</th><th>Azar 2</th><th>Llega Pedido</th><th>Cuenta Atrás</th><th>Costo Prestigio</th></tr></thead>
                        <tbody>
                        <?php for ($i = 0; $i < min(50, count($datosCompletos)); $i++):
                            $d = $datosCompletos[$i];
                            ?>
                            <tr>
                                <td class="label-cell"><?php echo $d['dia']; ?></td>
                                <td><input type="text" value="<?php echo $d['azar1']; ?>" readonly></td>
                                <td><input type="text" value="<?php echo $d['demanda']; ?>" readonly></td>
                                <td><input type="text" value="<?php echo number_format($d['inventario'], 2); ?>" readonly></td>
                                <td><input type="text" value="<?php echo formatoDinero($d['costoInv']); ?>" readonly></td>
                                <td><input type="text" value="<?php echo formatoDinero($d['costoOrd']); ?>" readonly></td>
                                <td><input type="text" value="<?php echo $d['azar2']; ?>" readonly></td>
                                <td><input type="text" value="<?php echo $d['llegaPedido']; ?>" readonly></td>
                                <td><input type="text" value="<?php echo $d['cuentaAtras']; ?>" readonly></td>
                                <td><input type="text" value="<?php echo formatoDinero($d['costoPres']); ?>" readonly></td>
                            </tr>
                        <?php endfor; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>
</body>
</html>
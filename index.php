<?php
session_start();

// Función para calcular rangos
function calcularRangos($probabilidades) {
    $rangos = [];
    $acumulado = 0;
    foreach ($probabilidades as $prob) {
        $min = $acumulado;
        $acumulado += $prob / 100;
        $max = $acumulado;
        $rangos[] = ['min' => $min, 'max' => $max];
    }
    return $rangos;
}

// Función para buscar valor en rango
function buscarValorEnRango($azar, $valores, $rangos) {
    for ($i = 0; $i < count($valores); $i++) {
        $min = $rangos[$i]['min'];
        $max = $rangos[$i]['max'];

        if ($i === count($valores) - 1) {
            if ($azar >= $min && $azar <= $max) return $valores[$i];
        } else {
            if ($azar >= $min && $azar < $max) return $valores[$i];
        }
    }
    return 0;
}

// Función para generar números aleatorios
function generarNumerosAleatorios($a, $x0, $b, $n, $cantidad = 1000) {
    $numeros = [];
    $vx = $x0;

    for ($i = 0; $i < $cantidad; $i++) {
        $nx = fmod(($a * $vx + $b), $n);
        $numeros[] = [
            'x' => floor($nx),
            'valor' => $nx / $n
        ];
        $vx = $nx;
    }

    return $numeros;
}

// Formato dinero
function formatoDinero($valor) {
    if ($valor == 0) return '$0.00';
    return '$' . number_format($valor, 2, '.', ',');
}

// Procesar formulario
$datos = null;
$azares1 = null;
$azares2 = null;
$datosCompletos = null;
$totales = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener datos del formulario
    $r = floatval($_POST['r']);
    $q = floatval($_POST['q']);
    $inventarioInicial = floatval($_POST['inventario_inicial']);
    $costoPerdida = floatval($_POST['costo_perdida']);
    $costoPedido = floatval($_POST['costo_pedido']);
    $costoAlmacenamiento = floatval($_POST['costo_almacenamiento']);

    $unidades = [
        floatval($_POST['unidades_1']),
        floatval($_POST['unidades_2']),
        floatval($_POST['unidades_3']),
        floatval($_POST['unidades_4']),
        floatval($_POST['unidades_5'])
    ];

    $probDemanda = [
        floatval($_POST['prob_1']),
        floatval($_POST['prob_2']),
        floatval($_POST['prob_3']),
        floatval($_POST['prob_4']),
        floatval($_POST['prob_5'])
    ];

    $dias = [
        floatval($_POST['dias_1']),
        floatval($_POST['dias_2']),
        floatval($_POST['dias_3']),
        floatval($_POST['dias_4']),
        floatval($_POST['dias_5'])
    ];

    $probEntrega = [
        floatval($_POST['prob_ent_1']),
        floatval($_POST['prob_ent_2']),
        floatval($_POST['prob_ent_3']),
        floatval($_POST['prob_ent_4']),
        floatval($_POST['prob_ent_5'])
    ];

    $a = floatval($_POST['a']);
    $x0 = floatval($_POST['x0']);
    $b = floatval($_POST['b']);
    $n = floatval($_POST['n']);

    $a2 = floatval($_POST['a2']);
    $x0_2 = floatval($_POST['x0_2']);
    $b2 = floatval($_POST['b2']);
    $n2 = floatval($_POST['n2']);

    // Calcular rangos
    $rangosDemanda = calcularRangos($probDemanda);
    $rangosEntrega = calcularRangos($probEntrega);

    // Generar números aleatorios
    $azares1 = generarNumerosAleatorios($a, $x0, $b, $n, 1000);
    $azares2 = generarNumerosAleatorios($a2, $x0_2, $b2, $n2, 1000);

    // Calcular datos
    $datosCompletos = [];
    $totalCI = 0;
    $totalCO = 0;
    $totalCP = 0;

    // Fila 0
    $datosCompletos[] = [
        'dia' => 0,
        'azar1' => '',
        'demanda' => '',
        'inventario' => $inventarioInicial,
        'costoInv' => 0,
        'costoOrd' => 0,
        'azar2' => '',
        'llegaPedido' => 0,
        'cuentaAtras' => -1,
        'costoPres' => 0
    ];

    // Filas 1 a 999
    for ($i = 1; $i < 1000; $i++) {
        $az1 = $azares1[$i]['valor'];
        $demanda = buscarValorEnRango($az1, $unidades, $rangosDemanda);

        $datosAnt = $datosCompletos[$i - 1];
        $invAnt = $datosAnt['inventario'];
        $cueAnt = $datosAnt['cuentaAtras'];
        $lleAnt = $datosAnt['llegaPedido'];

        // Primero calculamos Cuenta Atrás porque se necesita para Inventario
        // Cuenta atrás: =SI(H76>0;H76;SI(I76>0;I76-1;-1))
        // H76 = llegaPedidoAnterior, I76 = cuentaAtrasAnterior
        if ($lleAnt > 0) {
            $cuentaAtras = $lleAnt;
        } else if ($cueAnt > 0) {
            $cuentaAtras = $cueAnt - 1;
        } else {
            $cuentaAtras = -1;
        }

        // Inventario: =SI(D76>0;D76;0)-C77+SI(I77=0;$B$6;0)
        // D76 = inventarioAnterior, C77 = demandaActual, I77 = cuentaAtrasActual, B6 = Q
        $invParaCalculo = $invAnt > 0 ? $invAnt : 0;
        $addQ = ($cuentaAtras === 0) ? $q : 0;
        $inventario = $invParaCalculo - $demanda + $addQ;

        // Costo inventario: =SI(D78>0;D78*$B$10;0)
        // D78 = inventarioActual, B10 = costoAlmacenamiento
        $costoInv = ($inventario > 0) ? ($inventario * $costoAlmacenamiento) : 0;
        $totalCI += $costoInv;

        // Costo ordenar: =SI(Y(D77<=R;I77<=0);$B$8;0)
        // D77 = inventarioActual, I77 = cuentaAtrasActual, B8 = costoPedido
        $costoOrd = 0;
        if ($inventario <= $r && $cuentaAtras <= 0) {
            $costoOrd = $costoPedido;
        }
        $totalCO += $costoOrd;

        // Azar 2: =SI(F77>0;AZAR2;-1)
        // F77 = costoOrdenar
        $az2Val = '';
        $az2Num = -1;
        if ($costoOrd > 0) {
            $az2Val = number_format($azares2[$i]['valor'], 3, '.', '');
            $az2Num = $azares2[$i]['valor'];
        } else {
            $az2Val = '-1';
        }

        // Llega pedido: busca en la tabla de tiempo de entrega usando azar2
        $llegaPedido = 0;
        if ($az2Num > 0) {
            $llegaPedido = buscarValorEnRango($az2Num, $dias, $rangosEntrega);
        }

        // Costo pérdida prestigio: =SI(D77<0;D77*$B$9*-1;"")
        // D77 = inventarioActual, B9 = costoPerdida
        $costoPres = ($inventario < 0) ? ($inventario * $costoPerdida * -1) : 0;
        $totalCP += $costoPres;

        $datosCompletos[] = [
            'dia' => $i,
            'azar1' => number_format($az1, 8, '.', ''),
            'demanda' => $demanda,
            'inventario' => $inventario,
            'costoInv' => $costoInv,
            'costoOrd' => $costoOrd,
            'azar2' => $az2Val,
            'llegaPedido' => $llegaPedido,
            'cuentaAtras' => $cuentaAtras,
            'costoPres' => $costoPres
        ];
    }

    $totales = [
        'total' => $totalCI + $totalCO + $totalCP,
        'totalCI' => $totalCI,
        'totalCO' => $totalCO,
        'totalCP' => $totalCP
    ];

    $datos = [
        'r' => $r,
        'q' => $q,
        'inventarioInicial' => $inventarioInicial,
        'costoPerdida' => $costoPerdida,
        'costoPedido' => $costoPedido,
        'costoAlmacenamiento' => $costoAlmacenamiento,
        'unidades' => $unidades,
        'probDemanda' => $probDemanda,
        'dias' => $dias,
        'probEntrega' => $probEntrega,
        'rangosDemanda' => $rangosDemanda,
        'rangosEntrega' => $rangosEntrega
    ];
} else {
    // Valores por defecto
    $datos = [
        'r' => 81,
        'q' => 106,
        'inventarioInicial' => 570,
        'costoPerdida' => 400,
        'costoPedido' => 7000,
        'costoAlmacenamiento' => 125,
        'unidades' => [25, 26, 27, 28, 29],
        'probDemanda' => [10, 20, 30, 25, 15],
        'dias' => [3, 4, 5, 6, 7],
        'probEntrega' => [20, 30, 35, 10, 5]
    ];
    $datos['rangosDemanda'] = calcularRangos($datos['probDemanda']);
    $datos['rangosEntrega'] = calcularRangos($datos['probEntrega']);
}
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
        .highlight-yellow { background-color: #fff3cd !important; font-weight: bold; font-size: 1.1em; }
        .highlight-blue { background-color: #d1ecf1 !important; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>📊 Sistema de Gestión de Inventario</h1>
        <p>Administración de parámetros de inventario - PHP</p>
    </div>
    <div class="content">
        <form method="POST">
            <div class="table-wrapper">
                <table>
                    <thead><tr><th>Parámetro</th><th>Valor</th></tr></thead>
                    <tbody>
                    <tr><td class="label-cell">R (Punto de reorden)</td><td><input type="number" name="r" value="<?= $datos['r'] ?>" required></td></tr>
                    <tr><td class="label-cell">Q (Cantidad de pedido)</td><td><input type="number" name="q" value="<?= $datos['q'] ?>" required></td></tr>
                    <tr><td class="label-cell">Inventario Inicial</td><td><input type="number" name="inventario_inicial" value="<?= $datos['inventarioInicial'] ?>" required></td></tr>
                    <tr><td class="label-cell">Costo pérdida prestigio ($/unidad)</td><td><input type="number" name="costo_perdida" value="<?= $datos['costoPerdida'] ?>" required></td></tr>
                    <tr><td class="label-cell">Costo hacer pedido ($/pedido)</td><td><input type="number" name="costo_pedido" value="<?= $datos['costoPedido'] ?>" required></td></tr>
                    <tr><td class="label-cell">Costo almacenar ($/unidad/día)</td><td><input type="number" name="costo_almacenamiento" value="<?= $datos['costoAlmacenamiento'] ?>" required></td></tr>
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
                            <td><input type="number" name="unidades_<?= $i+1 ?>" value="<?= $datos['unidades'][$i] ?>"></td>
                            <td><input type="number" name="prob_<?= $i+1 ?>" value="<?= $datos['probDemanda'][$i] ?>"></td>
                            <td><input type="text" value="<?= number_format($datos['rangosDemanda'][$i]['min'], 4) ?>" readonly></td>
                            <td><input type="text" value="<?= number_format($datos['rangosDemanda'][$i]['max'], 4) ?>" readonly></td>
                        </tr>
                    <?php endfor; ?>
                    <tr style="background-color: #f8f9fa;">
                        <td>Total:</td>
                        <td><input type="text" value="<?= array_sum($datos['probDemanda']) ?>%" readonly style="background-color: #e9ecef; color: <?= abs(array_sum($datos['probDemanda']) - 100) > 0.01 ? 'red' : 'green' ?>;"></td>
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
                            <td><input type="number" name="dias_<?= $i+1 ?>" value="<?= $datos['dias'][$i] ?>"></td>
                            <td><input type="number" name="prob_ent_<?= $i+1 ?>" value="<?= $datos['probEntrega'][$i] ?>"></td>
                            <td><input type="text" value="<?= number_format($datos['rangosEntrega'][$i]['min'], 4) ?>" readonly></td>
                            <td><input type="text" value="<?= number_format($datos['rangosEntrega'][$i]['max'], 4) ?>" readonly></td>
                        </tr>
                    <?php endfor; ?>
                    <tr style="background-color: #f8f9fa;">
                        <td>Total:</td>
                        <td><input type="text" value="<?= array_sum($datos['probEntrega']) ?>%" readonly style="background-color: #e9ecef; color: <?= abs(array_sum($datos['probEntrega']) - 100) > 0.01 ? 'red' : 'green' ?>;"></td>
                        <td colspan="2"></td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <h2 style="margin-top: 50px; margin-bottom: 20px; color: #495057;">🔢 Números #1</h2>
            <div class="table-wrapper">
                <table>
                    <thead><tr><th>A</th><th>X0</th><th>B</th><th>N</th></tr></thead>
                    <tbody><tr>
                        <td><input type="number" name="a" value="2678917"></td>
                        <td><input type="number" name="x0" value="4579991"></td>
                        <td><input type="number" name="b" value="1317513"></td>
                        <td><input type="number" name="n" value="9824217"></td>
                    </tr></tbody>
                </table>
            </div>

            <h2 style="margin-top: 50px; margin-bottom: 20px; color: #495057;">🔢 Números #2</h2>
            <div class="table-wrapper">
                <table>
                    <thead><tr><th>A</th><th>X0</th><th>B</th><th>N</th></tr></thead>
                    <tbody><tr>
                        <td><input type="number" name="a2" value="7921083"></td>
                        <td><input type="number" name="x0_2" value="6731297"></td>
                        <td><input type="number" name="b2" value="9021531"></td>
                        <td><input type="number" name="n2" value="9420811"></td>
                    </tr></tbody>
                </table>
            </div>

            <div class="btn-container">
                <button type="submit" class="btn-primary">📈 Calcular Datos (1000 filas)</button>
            </div>
        </form>

        <?php if ($azares1 && $azares2): ?>
            <h2 style="margin-top: 50px; margin-bottom: 20px; color: #495057;">🎲 Números Aleatorios (Primeros 10)</h2>
            <div class="table-wrapper">
                <table>
                    <thead><tr><th>Fila</th><th>AZAR #1 X</th><th>AZAR #1</th><th>AZAR #2 Y</th><th>AZAR #2</th></tr></thead>
                    <tbody>
                    <?php for ($i = 0; $i < 10; $i++): ?>
                        <tr>
                            <td class="label-cell"><?= $i ?></td>
                            <td><input type="text" value="<?= $azares1[$i]['x'] ?>" readonly></td>
                            <td><input type="text" value="<?= number_format($azares1[$i]['valor'], 8, '.', '') ?>" readonly></td>
                            <td><input type="text" value="<?= $azares2[$i]['x'] ?>" readonly></td>
                            <td><input type="text" value="<?= number_format($azares2[$i]['valor'], 5, '.', '') ?>" readonly></td>
                        </tr>
                    <?php endfor; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <?php if ($totales): ?>
            <h2 style="margin-top: 50px; margin-bottom: 20px; color: #495057;">💰 Resumen de Costos Totales</h2>
            <div class="table-wrapper">
                <table>
                    <thead><tr><th>Costo Total</th><th>Total Costo Inventario</th><th>Total Costo Ordenar</th><th>Total Costo Pérdida Prestigio</th></tr></thead>
                    <tbody>
                    <tr>
                        <td><input type="text" value="<?= formatoDinero($totales['total']) ?>" readonly class="highlight-yellow"></td>
                        <td><input type="text" value="<?= formatoDinero($totales['totalCI']) ?>" readonly class="highlight-blue"></td>
                        <td><input type="text" value="<?= formatoDinero($totales['totalCO']) ?>" readonly class="highlight-blue"></td>
                        <td><input type="text" value="<?= formatoDinero($totales['totalCP']) ?>" readonly class="highlight-blue"></td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <h2 style="margin-top: 50px; margin-bottom: 20px; color: #495057;">📊 Tabla de Datos</h2>
            <p style="text-align: center; margin-bottom: 10px; color: #6c757d;">Mostrando las primeras 50 filas de 1000 calculadas</p>
            <div class="table-wrapper">
                <table style="font-size: 0.85em;">
                    <thead><tr><th>Día</th><th>Azar 1</th><th>Demanda</th><th>Inventario</th><th>Costo Inv</th><th>Costo Ord</th><th>Azar 2</th><th>Llega Pedido</th><th>Cuenta Atrás</th><th>Costo Prestigio</th></tr></thead>
                    <tbody>
                    <?php for ($i = 0; $i < min(50, count($datosCompletos)); $i++):
                        $d = $datosCompletos[$i];
                        ?>
                        <tr>
                            <td class="label-cell"><?= $d['dia'] ?></td>
                            <td><input type="text" value="<?= $d['azar1'] ?>" readonly></td>
                            <td><input type="text" value="<?= $d['demanda'] ?>" readonly></td>
                            <td><input type="text" value="<?= number_format($d['inventario'], 2, '.', ',') ?>" readonly></td>
                            <td><input type="text" value="<?= formatoDinero($d['costoInv']) ?>" readonly></td>
                            <td><input type="text" value="<?= formatoDinero($d['costoOrd']) ?>" readonly></td>
                            <td><input type="text" value="<?= $d['azar2'] ?>" readonly></td>
                            <td><input type="text" value="<?= $d['llegaPedido'] ?>" readonly></td>
                            <td><input type="text" value="<?= $d['cuentaAtras'] ?>" readonly></td>
                            <td><input type="text" value="<?= formatoDinero($d['costoPres']) ?>" readonly></td>
                        </tr>
                    <?php endfor; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
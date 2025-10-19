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
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>📊 Sistema de Gestión de Inventario</h1>
        <p>Administración de parámetros de inventario</p>
    </div>
    <div class="content">
        <form id="inventoryForm">
            <div class="table-wrapper">
                <table>
                    <thead><tr><th>Parámetro</th><th>Valor</th></tr></thead>
                    <tbody>
                    <tr><td class="label-cell">R (Punto de reorden)</td><td><input type="number" id="r" value="81" required onchange="limpiarResultados()"></td></tr>
                    <tr><td class="label-cell">Q (Cantidad de pedido)</td><td><input type="number" id="q" value="106" required onchange="limpiarResultados()"></td></tr>
                    <tr><td class="label-cell">Inventario Inicial</td><td><input type="number" id="inventario_inicial" value="570" required onchange="limpiarResultados()"></td></tr>
                    <tr><td class="label-cell">Costo pérdida prestigio ($/unidad)</td><td><input type="number" id="costo_perdida" value="400" required onchange="limpiarResultados()"></td></tr>
                    <tr><td class="label-cell">Costo hacer pedido ($/pedido)</td><td><input type="number" id="costo_pedido" value="7000" required onchange="limpiarResultados()"></td></tr>
                    <tr><td class="label-cell">Costo almacenar ($/unidad/día)</td><td><input type="number" id="costo_almacenamiento" value="125" required onchange="limpiarResultados()"></td></tr>
                    </tbody>
                </table>
            </div>
        </form>

        <h2 style="margin-top: 50px; margin-bottom: 20px; color: #495057;">📈 Distribución de Probabilidad de Demanda</h2>
        <div class="table-wrapper">
            <table>
                <thead><tr><th>Unidades</th><th>Probabilidad (%)</th><th>Mínimo</th><th>Máximo</th></tr></thead>
                <tbody>
                <tr><td><input type="number" class="unidades-input" value="25" onchange="limpiarResultados()"></td><td><input type="number" class="probabilidad-input" value="10" onchange="calcularRangos(); limpiarResultados();"></td><td><input type="text" class="minimo-output" readonly></td><td><input type="text" class="maximo-output" readonly></td></tr>
                <tr><td><input type="number" class="unidades-input" value="26" onchange="limpiarResultados()"></td><td><input type="number" class="probabilidad-input" value="20" onchange="calcularRangos(); limpiarResultados();"></td><td><input type="text" class="minimo-output" readonly></td><td><input type="text" class="maximo-output" readonly></td></tr>
                <tr><td><input type="number" class="unidades-input" value="27" onchange="limpiarResultados()"></td><td><input type="number" class="probabilidad-input" value="30" onchange="calcularRangos(); limpiarResultados();"></td><td><input type="text" class="minimo-output" readonly></td><td><input type="text" class="maximo-output" readonly></td></tr>
                <tr><td><input type="number" class="unidades-input" value="28" onchange="limpiarResultados()"></td><td><input type="number" class="probabilidad-input" value="25" onchange="calcularRangos(); limpiarResultados();"></td><td><input type="text" class="minimo-output" readonly></td><td><input type="text" class="maximo-output" readonly></td></tr>
                <tr><td><input type="number" class="unidades-input" value="29" onchange="limpiarResultados()"></td><td><input type="number" class="probabilidad-input" value="15" onchange="calcularRangos(); limpiarResultados();"></td><td><input type="text" class="minimo-output" readonly></td><td><input type="text" class="maximo-output" readonly></td></tr>
                <tr style="background-color: #f8f9fa;"><td>Total:</td><td><input type="text" id="totalProbabilidad" readonly style="background-color: #e9ecef;"></td><td colspan="2"></td></tr>
                </tbody>
            </table>
        </div>

        <h2 style="margin-top: 50px; margin-bottom: 20px; color: #495057;">⏱️ Tiempo de Entrega del Proveedor</h2>
        <div class="table-wrapper">
            <table>
                <thead><tr><th>Días</th><th>Probabilidad (%)</th><th>Mínimo</th><th>Máximo</th></tr></thead>
                <tbody>
                <tr><td><input type="number" class="dias-input" value="3" onchange="limpiarResultados()"></td><td><input type="number" class="prob-entrega-input" value="20" onchange="calcularRangosTiempo(); limpiarResultados();"></td><td><input type="text" class="min-entrega-output" readonly></td><td><input type="text" class="max-entrega-output" readonly></td></tr>
                <tr><td><input type="number" class="dias-input" value="4" onchange="limpiarResultados()"></td><td><input type="number" class="prob-entrega-input" value="30" onchange="calcularRangosTiempo(); limpiarResultados();"></td><td><input type="text" class="min-entrega-output" readonly></td><td><input type="text" class="max-entrega-output" readonly></td></tr>
                <tr><td><input type="number" class="dias-input" value="5" onchange="limpiarResultados()"></td><td><input type="number" class="prob-entrega-input" value="35" onchange="calcularRangosTiempo(); limpiarResultados();"></td><td><input type="text" class="min-entrega-output" readonly></td><td><input type="text" class="max-entrega-output" readonly></td></tr>
                <tr><td><input type="number" class="dias-input" value="6" onchange="limpiarResultados()"></td><td><input type="number" class="prob-entrega-input" value="10" onchange="calcularRangosTiempo(); limpiarResultados();"></td><td><input type="text" class="min-entrega-output" readonly></td><td><input type="text" class="max-entrega-output" readonly></td></tr>
                <tr><td><input type="number" class="dias-input" value="7" onchange="limpiarResultados()"></td><td><input type="number" class="prob-entrega-input" value="5" onchange="calcularRangosTiempo(); limpiarResultados();"></td><td><input type="text" class="min-entrega-output" readonly></td><td><input type="text" class="max-entrega-output" readonly></td></tr>
                <tr style="background-color: #f8f9fa;"><td>Total:</td><td><input type="text" id="totalProbTiempo" readonly style="background-color: #e9ecef;"></td><td colspan="2"></td></tr>
                </tbody>
            </table>
        </div>

        <h2 style="margin-top: 50px; margin-bottom: 20px; color: #495057;">🔢 Números #1</h2>
        <div class="table-wrapper">
            <table>
                <thead><tr><th>A</th><th>X0</th><th>B</th><th>N</th></tr></thead>
                <tbody><tr><td><input type="number" id="a" value="2678917" onchange="limpiarResultados()"></td><td><input type="number" id="x0" value="4579991" onchange="limpiarResultados()"></td><td><input type="number" id="b" value="1317513" onchange="limpiarResultados()"></td><td><input type="number" id="n" value="9824217" onchange="limpiarResultados()"></td></tr></tbody>
            </table>
        </div>

        <h2 style="margin-top: 50px; margin-bottom: 20px; color: #495057;">🔢 Números #2</h2>
        <div class="table-wrapper">
            <table>
                <thead><tr><th>A</th><th>X0</th><th>B</th><th>N</th></tr></thead>
                <tbody><tr><td><input type="number" id="a2" value="7921083" onchange="limpiarResultados()"></td><td><input type="number" id="x0_2" value="6731297" onchange="limpiarResultados()"></td><td><input type="number" id="b2" value="9021531" onchange="limpiarResultados()"></td><td><input type="number" id="n2" value="9420811" onchange="limpiarResultados()"></td></tr></tbody>
            </table>
        </div>

        <h2 style="margin-top: 50px; margin-bottom: 20px; color: #495057;">🎲 Generación de Números Aleatorios</h2>
        <div class="btn-container" style="margin-bottom: 20px;">
            <button type="button" class="btn-primary" onclick="generarAzar()">🔄 Generar Números Aleatorios</button>
        </div>
        <div class="table-wrapper">
            <table>
                <thead><tr><th>Fila</th><th>AZAR #1 X</th><th>AZAR #1</th><th>AZAR #2 Y</th><th>AZAR #2</th></tr></thead>
                <tbody>
                <tr><td class="label-cell">0</td><td><input type="text" class="azar1-x" readonly></td><td><input type="text" class="azar1" readonly></td><td><input type="text" class="azar2-y" readonly></td><td><input type="text" class="azar2" readonly></td></tr>
                <tr><td class="label-cell">1</td><td><input type="text" class="azar1-x" readonly></td><td><input type="text" class="azar1" readonly></td><td><input type="text" class="azar2-y" readonly></td><td><input type="text" class="azar2" readonly></td></tr>
                <tr><td class="label-cell">2</td><td><input type="text" class="azar1-x" readonly></td><td><input type="text" class="azar1" readonly></td><td><input type="text" class="azar2-y" readonly></td><td><input type="text" class="azar2" readonly></td></tr>
                <tr><td class="label-cell">3</td><td><input type="text" class="azar1-x" readonly></td><td><input type="text" class="azar1" readonly></td><td><input type="text" class="azar2-y" readonly></td><td><input type="text" class="azar2" readonly></td></tr>
                <tr><td class="label-cell">4</td><td><input type="text" class="azar1-x" readonly></td><td><input type="text" class="azar1" readonly></td><td><input type="text" class="azar2-y" readonly></td><td><input type="text" class="azar2" readonly></td></tr>
                <tr><td class="label-cell">5</td><td><input type="text" class="azar1-x" readonly></td><td><input type="text" class="azar1" readonly></td><td><input type="text" class="azar2-y" readonly></td><td><input type="text" class="azar2" readonly></td></tr>
                <tr><td class="label-cell">6</td><td><input type="text" class="azar1-x" readonly></td><td><input type="text" class="azar1" readonly></td><td><input type="text" class="azar2-y" readonly></td><td><input type="text" class="azar2" readonly></td></tr>
                <tr><td class="label-cell">7</td><td><input type="text" class="azar1-x" readonly></td><td><input type="text" class="azar1" readonly></td><td><input type="text" class="azar2-y" readonly></td><td><input type="text" class="azar2" readonly></td></tr>
                <tr><td class="label-cell">8</td><td><input type="text" class="azar1-x" readonly></td><td><input type="text" class="azar1" readonly></td><td><input type="text" class="azar2-y" readonly></td><td><input type="text" class="azar2" readonly></td></tr>
                <tr><td class="label-cell">9</td><td><input type="text" class="azar1-x" readonly></td><td><input type="text" class="azar1" readonly></td><td><input type="text" class="azar2-y" readonly></td><td><input type="text" class="azar2" readonly></td></tr>
                </tbody>
            </table>
        </div>

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
                    <td><input type="text" id="costoTotal" readonly style="background-color: #fff3cd; font-weight: bold; font-size: 1.1em;"></td>
                    <td><input type="text" id="totalCostoInv" readonly style="background-color: #d1ecf1;"></td>
                    <td><input type="text" id="totalCostoOrd" readonly style="background-color: #d1ecf1;"></td>
                    <td><input type="text" id="totalCostoPres" readonly style="background-color: #d1ecf1;"></td>
                </tr>
                </tbody>
            </table>
        </div>

        <h2 style="margin-top: 50px; margin-bottom: 20px; color: #495057;">📊 Tabla de Datos</h2>
        <div class="btn-container" style="margin-bottom: 20px;">
            <button type="button" class="btn-primary" onclick="calcularDatos()">📈 Calcular Datos (1000 filas)</button>
        </div>
        <p style="text-align: center; margin-bottom: 10px; color: #6c757d;">Mostrando las primeras 50 filas de 1000 calculadas</p>
        <div class="table-wrapper">
            <table style="font-size: 0.85em;">
                <thead><tr><th>Día</th><th>Azar 1</th><th>Demanda</th><th>Inventario</th><th>Costo Inv</th><th>Costo Ord</th><th>Azar 2</th><th>Llega Pedido</th><th>Cuenta Atrás</th><th>Costo Prestigio</th></tr></thead>
                <tbody id="tablaDatosBody">
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function calcularRangos() {
        const p = document.querySelectorAll('.probabilidad-input');
        const mi = document.querySelectorAll('.minimo-output');
        const ma = document.querySelectorAll('.maximo-output');
        let a = 0, t = 0;
        for (let i = 0; i < p.length; i++) {
            const v = parseFloat(p[i].value) || 0;
            t += v;
            mi[i].value = a.toFixed(4);
            a += v / 100;
            ma[i].value = a.toFixed(4);
        }
        document.getElementById('totalProbabilidad').value = t.toFixed(2) + '%';
        document.getElementById('totalProbabilidad').style.color = Math.abs(t - 100) > 0.01 ? 'red' : 'green';
    }

    function calcularRangosTiempo() {
        const p = document.querySelectorAll('.prob-entrega-input');
        const mi = document.querySelectorAll('.min-entrega-output');
        const ma = document.querySelectorAll('.max-entrega-output');
        let a = 0, t = 0;
        for (let i = 0; i < p.length; i++) {
            const v = parseFloat(p[i].value) || 0;
            t += v;
            mi[i].value = a.toFixed(4);
            a += v / 100;
            ma[i].value = a.toFixed(4);
        }
        document.getElementById('totalProbTiempo').value = t.toFixed(2) + '%';
        document.getElementById('totalProbTiempo').style.color = Math.abs(t - 100) > 0.01 ? 'red' : 'green';
    }

    function generarAzar() {
        const a = parseFloat(document.getElementById('a').value);
        const x0 = parseFloat(document.getElementById('x0').value);
        const b = parseFloat(document.getElementById('b').value);
        const n = parseFloat(document.getElementById('n').value);
        const a2 = parseFloat(document.getElementById('a2').value);
        const y0 = parseFloat(document.getElementById('x0_2').value);
        const b2 = parseFloat(document.getElementById('b2').value);
        const n2 = parseFloat(document.getElementById('n2').value);

        if (isNaN(a) || isNaN(x0) || isNaN(b) || isNaN(n) || isNaN(a2) || isNaN(y0) || isNaN(b2) || isNaN(n2)) {
            alert('Complete campos Números #1 y #2');
            return;
        }

        const ax = document.querySelectorAll('.azar1-x');
        const a1 = document.querySelectorAll('.azar1');
        const ay = document.querySelectorAll('.azar2-y');
        const a22 = document.querySelectorAll('.azar2');

        let vx = x0, vy = y0;

        window.azares1 = [];
        window.azares2 = [];

        for (let i = 0; i < 1000; i++) {
            const nx = (a * vx + b) % n;
            const ny = (a2 * vy + b2) % n2;

            window.azares1.push({
                x: nx,
                valor: nx / n
            });

            window.azares2.push({
                y: ny,
                valor: ny / n2
            });

            if (i < 10) {
                ax[i].value = Math.floor(nx);
                a1[i].value = (nx / n).toFixed(8);
                ay[i].value = Math.floor(ny);
                a22[i].value = (ny / n2).toFixed(5);
            }

            vx = nx;
            vy = ny;
        }

        console.log('Generados 1000 números aleatorios');
    }

    function formatoDinero(valor) {
        if (valor === '' || valor === null || valor === undefined || valor === 0) return '$0.00';
        return '$' + parseFloat(valor).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    function buscarValor(az, uni, mi, ma) {
        for (let i = 0; i < uni.length; i++) {
            const min = parseFloat(mi[i].value);
            const max = parseFloat(ma[i].value);
            const u = parseFloat(uni[i].value);

            if (i === uni.length - 1) {
                if (az >= min && az <= max) return u;
            } else {
                if (az >= min && az < max) return u;
            }
        }
        return 0;
    }

    function calcularDatos() {
        if (!window.azares1 || !window.azares1.length) {
            alert('Genere primero los números aleatorios');
            return;
        }

        const r = parseFloat(document.getElementById('r').value);
        const q = parseFloat(document.getElementById('q').value);
        const invIni = parseFloat(document.getElementById('inventario_inicial').value);
        const cAlm = parseFloat(document.getElementById('costo_almacenamiento').value);
        const cPed = parseFloat(document.getElementById('costo_pedido').value);
        const cPer = parseFloat(document.getElementById('costo_perdida').value);

        const uni = document.querySelectorAll('.unidades-input');
        const mi = document.querySelectorAll('.minimo-output');
        const ma = document.querySelectorAll('.maximo-output');

        const dia = document.querySelectorAll('.dias-input');
        const miE = document.querySelectorAll('.min-entrega-output');
        const maE = document.querySelectorAll('.max-entrega-output');

        window.datosCompletos = [];

        let totalCI = 0, totalCO = 0, totalCP = 0;

        let datosActuales = {
            dia: 0,
            azar1: '',
            demanda: '',
            inventario: invIni,
            costoInv: 0,
            costoOrd: 0,
            azar2: '',
            llegaPedido: 0,
            cuentaAtras: -1,
            costoPres: 0
        };
        window.datosCompletos.push(datosActuales);

        for (let i = 1; i < 1000; i++) {
            const az1 = window.azares1[i].valor;
            const dem = buscarValor(az1, uni, mi, ma);

            const datosAnt = window.datosCompletos[i-1];
            const invAnt = datosAnt.inventario;
            const cueAnt = datosAnt.cuentaAtras;
            const lleAnt = datosAnt.llegaPedido;

            let cue;
            if (lleAnt > 0) {
                cue = lleAnt;
            } else if (cueAnt > 0) {
                cue = cueAnt - 1;
            } else {
                cue = -1;
            }

            const invPos = invAnt > 0 ? invAnt : 0;
            const addQ = cue === 0 ? q : 0;
            const inv = invPos - dem + addQ;

            const costoInv = inv > 0 ? inv * cAlm : 0;
            totalCI += costoInv;

            let costoOrd = 0;
            if (inv <= r && cue <= 0) {
                costoOrd = cPed;
            }
            totalCO += costoOrd;

            let az2Val = '';
            let az2Num = -1;
            if (costoOrd > 0) {
                az2Val = window.azares2[i].valor.toFixed(3);
                az2Num = window.azares2[i].valor;
            } else {
                az2Val = '-1';
            }

            let lle = 0;
            if (az2Num > 0) {
                lle = buscarValor(az2Num, dia, miE, maE);
            }

            const costoPres = inv < 0 ? inv * cPer * -1 : 0;
            totalCP += costoPres;

            window.datosCompletos.push({
                dia: i,
                azar1: az1.toFixed(8),
                demanda: dem,
                inventario: inv,
                costoInv: costoInv,
                costoOrd: costoOrd,
                azar2: az2Val,
                llegaPedido: lle,
                cuentaAtras: cue,
                costoPres: costoPres
            });
        }

        const costoTotal = totalCI + totalCO + totalCP;
        document.getElementById('costoTotal').value = formatoDinero(costoTotal);
        document.getElementById('totalCostoInv').value = formatoDinero(totalCI);
        document.getElementById('totalCostoOrd').value = formatoDinero(totalCO);
        document.getElementById('totalCostoPres').value = formatoDinero(totalCP);

        mostrarFilas();

        console.log('Calculadas 1000 filas de datos');
    }

    function mostrarFilas() {
        const tbody = document.getElementById('tablaDatosBody');
        tbody.innerHTML = '';

        for (let i = 0; i < Math.min(50, window.datosCompletos.length); i++) {
            const d = window.datosCompletos[i];
            const tr = document.createElement('tr');
            tr.innerHTML = '<td class="label-cell">' + d.dia + '</td>' +
                '<td><input type="text" value="' + d.azar1 + '" readonly></td>' +
                '<td><input type="text" value="' + d.demanda + '" readonly></td>' +
                '<td><input type="text" value="' + d.inventario.toFixed(2) + '" readonly></td>' +
                '<td><input type="text" value="' + formatoDinero(d.costoInv) + '" readonly></td>' +
                '<td><input type="text" value="' + formatoDinero(d.costoOrd) + '" readonly></td>' +
                '<td><input type="text" value="' + d.azar2 + '" readonly></td>' +
                '<td><input type="text" value="' + d.llegaPedido + '" readonly></td>' +
                '<td><input type="text" value="' + d.cuentaAtras + '" readonly></td>' +
                '<td><input type="text" value="' + formatoDinero(d.costoPres) + '" readonly></td>';
            tbody.appendChild(tr);
        }
    }

    function limpiarResultados() {
        window.azares1 = null;
        window.azares2 = null;
        window.datosCompletos = null;

        const ax = document.querySelectorAll('.azar1-x');
        const a1 = document.querySelectorAll('.azar1');
        const ay = document.querySelectorAll('.azar2-y');
        const a22 = document.querySelectorAll('.azar2');

        ax.forEach(input => input.value = '');
        a1.forEach(input => input.value = '');
        ay.forEach(input => input.value = '');
        a22.forEach(input => input.value = '');

        document.getElementById('tablaDatosBody').innerHTML = '';
        document.getElementById('costoTotal').value = '';
        document.getElementById('totalCostoInv').value = '';
        document.getElementById('totalCostoOrd').value = '';
        document.getElementById('totalCostoPres').value = '';
    }

    window.addEventListener('load', function() {
        calcularRangos();
        calcularRangosTiempo();
    });
</script>
</body>
</html>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos = array(
        'r' => isset($_POST['r']) ? floatval($_POST['r']) : 0,
        'q' => isset($_POST['q']) ? floatval($_POST['q']) : 0,
        'inventario_inicial' => isset($_POST['inventario_inicial']) ? floatval($_POST['inventario_inicial']) : 0,
        'costo_perdida' => isset($_POST['costo_perdida']) ? floatval($_POST['costo_perdida']) : 0,
        'costo_pedido' => isset($_POST['costo_pedido']) ? floatval($_POST['costo_pedido']) : 0,
        'costo_almacenamiento' => isset($_POST['costo_almacenamiento']) ? floatval($_POST['costo_almacenamiento']) : 0
    );
}
?>
/**
 * INVENTARIO — Google Apps Script
 * ─────────────────────────────────────────────────────────────────────────
 * Pegá este código en tu Google Sheet:
 *   Extensiones → Apps Script → borrá el contenido → pegá esto → guardá
 *
 * Luego desplegá:
 *   Implementar → Nueva implementación → Aplicación web
 *   Ejecutar como: Yo mismo
 *   Quién tiene acceso: Cualquier persona
 *   → Implementar → copiá la URL y pegala en el sistema de inventario
 *
 * IMPORTANTE: cada vez que modifiques el script tenés que crear una
 * NUEVA implementación (no "actualizar" la existente) para que los
 * cambios se apliquen.
 * ─────────────────────────────────────────────────────────────────────────
 */

const SHEET_NAME = 'Inventario';

const HEADERS = [
  'ID', 'Nombre', 'Categoría', 'Marca', 'Modelo', 'Clasificación',
  'Cantidad', 'Código', 'Costo (USD)', 'Precio Venta (USD)',
  'Precio Reventa (USD)', 'Tipo', 'Ubicación', 'Notas'
];

/* ═══════════════════ GET — leer inventario ════════════════════════════════
   Soporta JSONP: si viene ?callback=xxx responde con xxx([...datos...])
   Esto bypasea CORS desde cualquier dominio.
════════════════════════════════════════════════════════════════════════════ */
function doGet(e) {
  try {
    const sheet    = getOrCreateSheet();
    const data     = sheet.getDataRange().getValues();
    const callback = e && e.parameter && e.parameter.callback;

    if (data.length <= 1) {
      return respond([], callback);
    }

    const headers = data[0].map(h => String(h).trim());
    const rows    = data.slice(1).map(row => {
      const obj = {};
      headers.forEach((h, i) => {
        obj[h] = (row[i] === '' || row[i] === null || row[i] === undefined) ? '' : row[i];
      });
      return obj;
    });

    return respond(rows, callback);

  } catch (err) {
    const callback = e && e.parameter && e.parameter.callback;
    return respond({ error: err.message }, callback);
  }
}

/* ═══════════════════ POST — escribir inventario ═══════════════════════════
   Acepta dos formatos:
   1. URLSearchParams: action=set&data=[...]  (recomendado, sin preflight CORS)
   2. JSON body:       {"action":"set","data":[...]}
════════════════════════════════════════════════════════════════════════════ */
function doPost(e) {
  try {
    let action, rows;

    // Formato 1: URLSearchParams (application/x-www-form-urlencoded)
    if (e.parameter && e.parameter.action) {
      action = e.parameter.action;
      rows   = JSON.parse(e.parameter.data || '[]');

    // Formato 2: JSON body (text/plain o application/json)
    } else if (e.postData && e.postData.contents) {
      const body = JSON.parse(e.postData.contents);
      action     = body.action || 'set';
      rows       = body.data  || [];

    } else {
      return ContentService.createTextOutput('ERROR: no se recibieron datos');
    }

    if (action !== 'set') {
      return ContentService.createTextOutput('OK - acción ignorada');
    }

    const sheet = getOrCreateSheet();
    sheet.clearContents();
    sheet.getRange(1, 1, 1, HEADERS.length).setValues([HEADERS]);

    // Formato encabezado
    const hr = sheet.getRange(1, 1, 1, HEADERS.length);
    hr.setBackground('#303030');
    hr.setFontColor('#F5F1E8');
    hr.setFontWeight('bold');
    hr.setFontFamily('Arial');

    if (rows.length > 0) {
      const values = rows.map(row =>
        HEADERS.map(h => {
          const val = row[h];
          return (val === undefined || val === null) ? '' : val;
        })
      );
      sheet.getRange(2, 1, values.length, HEADERS.length).setValues(values);

      // Formato filas
      for (let i = 0; i < values.length; i++) {
        const color = i % 2 === 0 ? '#F5F1E8' : '#E8E4D8';
        sheet.getRange(i + 2, 1, 1, HEADERS.length).setBackground(color);
      }

      sheet.getRange(2, 1, values.length, HEADERS.length)
        .setFontFamily('Arial').setFontSize(10);
    }

    // Anchos de columna
    [40,320,160,130,160,90,70,70,80,110,120,90,230,220].forEach((w, i) => {
      sheet.setColumnWidth(i + 1, w);
    });
    sheet.setFrozenRows(1);

    // Hojas de resumen
    updateSummarySheets_(SpreadsheetApp.getActiveSpreadsheet(), rows);

    return ContentService.createTextOutput('OK');

  } catch (err) {
    return ContentService.createTextOutput('ERROR: ' + err.message);
  }
}

/* ═══════════════════ Helpers ══════════════════════════════════════════════ */

function respond(data, callback) {
  const json = JSON.stringify(data);
  if (callback) {
    // JSONP — bypasea CORS
    return ContentService
      .createTextOutput(callback + '(' + json + ')')
      .setMimeType(ContentService.MimeType.JAVASCRIPT);
  }
  return ContentService
    .createTextOutput(json)
    .setMimeType(ContentService.MimeType.JSON);
}

function getOrCreateSheet() {
  const ss    = SpreadsheetApp.getActiveSpreadsheet();
  let   sheet = ss.getSheetByName(SHEET_NAME);
  if (!sheet) {
    sheet = ss.insertSheet(SHEET_NAME, 0);
    sheet.getRange(1, 1, 1, HEADERS.length).setValues([HEADERS]);
    const hr = sheet.getRange(1, 1, 1, HEADERS.length);
    hr.setBackground('#303030');
    hr.setFontColor('#F5F1E8');
    hr.setFontWeight('bold');
    sheet.setFrozenRows(1);
  }
  return sheet;
}

function updateSummarySheets_(ss, rows) {
  updateSummarySheet_(ss, 'Por Categoría', rows, 'Categoría');
  updateSummarySheet_(ss, 'Por Ubicación', rows, 'Ubicación');
}

function updateSummarySheet_(ss, name, rows, groupKey) {
  let sheet = ss.getSheetByName(name);
  if (!sheet) sheet = ss.insertSheet(name);
  sheet.clearContents();

  const map = {};
  rows.forEach(r => {
    const key = r[groupKey] || '(Sin ' + groupKey.toLowerCase() + ')';
    if (!map[key]) map[key] = { refs: 0, units: 0 };
    map[key].refs++;
    const qty = Number(r['Cantidad']);
    if (!isNaN(qty)) map[key].units += qty;
  });

  sheet.getRange(1, 1, 1, 3).setValues([[groupKey, 'Nro. Referencias', 'Unidades Totales']]);
  const hr = sheet.getRange(1, 1, 1, 3);
  hr.setBackground('#303030'); hr.setFontColor('#F5F1E8'); hr.setFontWeight('bold');

  const data = Object.entries(map)
    .sort((a, b) => a[0].localeCompare(b[0], 'es'))
    .map(([k, v]) => [k, v.refs, v.units]);

  if (data.length) sheet.getRange(2, 1, data.length, 3).setValues(data);

  sheet.setColumnWidth(1, 280);
  sheet.setColumnWidth(2, 140);
  sheet.setColumnWidth(3, 140);
  sheet.setFrozenRows(1);
}


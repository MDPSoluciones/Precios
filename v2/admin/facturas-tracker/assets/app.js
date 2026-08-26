let compras = [];

const tbody = document.getElementById('tbodyCompras');
const sinResultados = document.getElementById('sinResultados');
const resumen = document.getElementById('resumen');
const buscarInput = document.getElementById('buscar');
const filtroFactura = document.getElementById('filtroFactura');

const modalOverlay = document.getElementById('modalOverlay');
const modalTitulo = document.getElementById('modalTitulo');
const form = document.getElementById('formCompra');

function fmtMoneda(n) {
  return '$' + Number(n).toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function fmtFecha(f) {
  if (!f) return '-';
  const [y, m, d] = f.split('-');
  return `${d}/${m}/${y}`;
}

async function cargarCompras() {
  const res = await fetch('api.php');
  if (res.status === 401) {
    window.location.href = 'login.php';
    return;
  }
  const data = await res.json();
  compras = data.compras || [];
  compras.sort((a, b) => (b.fecha || '').localeCompare(a.fecha || ''));
  render();
}

function render() {
  const texto = buscarInput.value.trim().toLowerCase();
  const filtro = filtroFactura.value;

  const filtradas = compras.filter(c => {
    const coincideTexto = !texto || [c.sitio, c.id_externo, c.vendedor, c.producto].join(' ').toLowerCase().includes(texto);
    const coincideFactura = !filtro || (filtro === 'si' ? c.tiene_factura : !c.tiene_factura);
    return coincideTexto && coincideFactura;
  });

  tbody.innerHTML = '';
  filtradas.forEach(c => {
    const tr = document.createElement('tr');
    if (!c.tiene_factura) tr.classList.add('sin-factura');

    tr.innerHTML = `
      <td>${c.id}</td>
      <td>${fmtFecha(c.fecha)}</td>
      <td>${escapeHtml(c.sitio)}</td>
      <td>${escapeHtml(c.id_externo || '-')}</td>
      <td>${escapeHtml(c.vendedor || '-')}</td>
      <td class="producto-col">${escapeHtml(c.producto)}</td>
      <td>${fmtMoneda(c.monto)}</td>
      <td>
        <span class="badge ${c.tiene_factura ? 'badge-si' : 'badge-no'}" style="cursor:pointer" data-toggle="${c.id}" title="Click para cambiar">
          ${c.tiene_factura ? 'Sí' : 'No'}
        </span>
      </td>
      <td>${c.link_pdf ? `<a href="${escapeAttr(c.link_pdf)}" target="_blank" rel="noopener">Ver PDF</a>` : '-'}</td>
      <td>${escapeHtml(c.registrado_por || '-')}</td>
      <td>
        <button class="btn-mini btn-secundario" data-editar="${c.id}">Editar</button>
        <button class="btn-mini btn-danger" data-eliminar="${c.id}">Borrar</button>
      </td>
    `;
    tbody.appendChild(tr);
  });

  sinResultados.style.display = filtradas.length ? 'none' : 'block';

  const sinFactura = compras.filter(c => !c.tiene_factura).length;
  resumen.innerHTML = `${compras.length} compras total ${sinFactura > 0 ? `· <span class="alerta">${sinFactura} sin factura</span>` : ''}`;
}

function escapeHtml(s) {
  return (s || '').toString().replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
}
function escapeAttr(s) { return escapeHtml(s); }

// Delegación de eventos en la tabla
tbody.addEventListener('click', async (e) => {
  const idToggle = e.target.dataset.toggle;
  const idEditar = e.target.dataset.editar;
  const idEliminar = e.target.dataset.eliminar;

  if (idToggle) {
    await fetch('api.php?accion=toggle_factura', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id: idToggle })
    });
    cargarCompras();
  }

  if (idEditar) abrirModal(compras.find(c => c.id === idEditar));

  if (idEliminar) {
    if (confirm('¿Seguro que querés eliminar este registro?')) {
      await fetch('api.php?accion=eliminar', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: idEliminar })
      });
      cargarCompras();
    }
  }
});

buscarInput.addEventListener('input', render);
filtroFactura.addEventListener('change', render);

document.getElementById('btnNueva').addEventListener('click', () => abrirModal(null));
document.getElementById('btnCancelar').addEventListener('click', cerrarModal);
modalOverlay.addEventListener('click', (e) => { if (e.target === modalOverlay) cerrarModal(); });

function abrirModal(compra) {
  form.reset();
  document.getElementById('compraId').value = '';

  if (compra) {
    modalTitulo.textContent = 'Editar compra';
    document.getElementById('compraId').value = compra.id;
    document.getElementById('fecha').value = compra.fecha || '';
    document.getElementById('sitio').value = compra.sitio || '';
    document.getElementById('idExterno').value = compra.id_externo || '';
    document.getElementById('vendedor').value = compra.vendedor || '';
    document.getElementById('producto').value = compra.producto || '';
    document.getElementById('monto').value = compra.monto || '';
    document.getElementById('tieneFactura').checked = !!compra.tiene_factura;
    document.getElementById('linkPdf').value = compra.link_pdf || '';
    document.getElementById('notas').value = compra.notas || '';
  } else {
    modalTitulo.textContent = 'Nueva compra';
    document.getElementById('fecha').value = new Date().toISOString().slice(0, 10);
  }

  modalOverlay.style.display = 'flex';
}

function cerrarModal() {
  modalOverlay.style.display = 'none';
}

form.addEventListener('submit', async (e) => {
  e.preventDefault();

  const payload = {
    id: document.getElementById('compraId').value || null,
    fecha: document.getElementById('fecha').value,
    sitio: document.getElementById('sitio').value,
    id_externo: document.getElementById('idExterno').value,
    vendedor: document.getElementById('vendedor').value,
    producto: document.getElementById('producto').value,
    monto: document.getElementById('monto').value,
    tiene_factura: document.getElementById('tieneFactura').checked,
    link_pdf: document.getElementById('linkPdf').value,
    notas: document.getElementById('notas').value,
  };

  await fetch('api.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  });

  cerrarModal();
  cargarCompras();
});

cargarCompras();

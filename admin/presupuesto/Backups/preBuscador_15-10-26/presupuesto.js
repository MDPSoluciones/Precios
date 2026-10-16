// ─── Estado global ────────────────────────────────────────────────────────────
let productos = [];
let historial = [];
let clientesGuardados = [];
let ultimoPresupuesto = null;
let indiceProdEditando = null;

// ─── Inicialización ───────────────────────────────────────────────────────────
document.addEventListener("DOMContentLoaded", () => {
    cargarHistorial();
    cargarClientes();
    iniciarAutocompletado();
    const hoy = new Date().toISOString().split("T")[0];
    document.getElementById("fecha").value = hoy;
});

// ─── #6 localStorage — sincronización ─────────────────────────────────────────
const LS_HISTORIAL = "presupuesto_historial_v2";
const LS_CLIENTES  = "presupuesto_clientes_v2";

function guardarEnCache(clave, datos) {
    try { localStorage.setItem(clave, JSON.stringify(datos)); } catch (_) {}
}

function leerDeCache(clave) {
    try {
        const raw = localStorage.getItem(clave);
        return raw ? JSON.parse(raw) : null;
    } catch (_) { return null; }
}

// ─── Empresa ──────────────────────────────────────────────────────────────────
function cambiarEmpresa() {
    const toggle = document.getElementById("empresa_toggle");
    if (toggle.checked) {
        document.getElementById("header_nombre").innerText = "MDP SOLUCIONES";
        document.getElementById("empresa_nombre").value = "MDP SOLUCIONES";
        document.getElementById("empresa_cuit").value = "20-35023798-5";
        document.getElementById("empresa_direccion").value = "Sarmiento 24";
        document.getElementById("empresa_mail").value = "";
        document.getElementById("empresa_tel").value = "";
    } else {
        document.getElementById("header_nombre").innerText = "AXENTIA";
        document.getElementById("empresa_nombre").value = "AXENTIA S.R.L.";
        document.getElementById("empresa_cuit").value = "30-71902891-4";
        document.getElementById("empresa_direccion").value = "Marinero Sosa 854";
        document.getElementById("empresa_mail").value = "adm.axentia@gmail.com";
        document.getElementById("empresa_tel").value = "+54 9 3364 24-9663";
    }
}

// ─── Clientes ─────────────────────────────────────────────────────────────────
function cargarClientes() {
    fetch("obtener_clientes.php")
        .then(res => res.json())
        .then(data => {
            clientesGuardados = data;
            guardarEnCache(LS_CLIENTES, data); // #6
        })
        .catch(() => {
            // #6 Fallback a localStorage
            const cache = leerDeCache(LS_CLIENTES);
            if (cache) {
                clientesGuardados = cache;
                console.warn("Clientes cargados desde caché local.");
            }
        });
}

function guardarClienteServidor(cliente) {
    fetch("guardar_cliente.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(cliente)
    })
        .then(res => res.json())
        .then(data => { if (data.status === "ok") cargarClientes(); })
        .catch(err => console.error("Error guardando cliente:", err));
}

function iniciarAutocompletado() {
    const inputCliente = document.getElementById("cliente_nombre");
    const box = document.getElementById("sugerencias_clientes");

    inputCliente.addEventListener("input", function () {
        const texto = this.value.toLowerCase();
        if (texto.length < 2) { box.style.display = "none"; return; }
        const resultados = clientesGuardados.filter(c =>
            c.nombre && c.nombre.toLowerCase().includes(texto)
        );
        mostrarSugerencias(resultados);
    });

    document.addEventListener("click", e => {
        if (!e.target.closest("#cliente_nombre")) box.style.display = "none";
    });
}

function mostrarSugerencias(lista) {
    const box = document.getElementById("sugerencias_clientes");
    box.innerHTML = "";
    if (lista.length === 0) { box.style.display = "none"; return; }
    lista.forEach(c => {
        const div = document.createElement("div");
        div.innerHTML = `<strong>${c.nombre}</strong><br><small>${c.cuit || ""}</small>`;
        div.onclick = () => seleccionarCliente(c);
        box.appendChild(div);
    });
    box.style.display = "block";
}

function seleccionarCliente(c) {
    document.getElementById("cliente_nombre").value   = c.nombre    || "";
    document.getElementById("cliente_cuit").value     = c.cuit      || "";
    document.getElementById("cliente_direccion").value = c.direccion || "";
    document.getElementById("cliente_mail").value     = c.mail      || "";
    document.getElementById("sugerencias_clientes").style.display = "none";
}

// ─── #4 Gestión de clientes (modal) ──────────────────────────────────────────
function abrirModalClientes() {
    renderTablaClientes();
    document.getElementById("modal-clientes").style.display = "flex";
    document.body.style.overflow = "hidden";
}

function cerrarModalClientes() {
    document.getElementById("modal-clientes").style.display = "none";
    document.body.style.overflow = "";
}

function cerrarModalSiOverlay(e) {
    if (e.target === document.getElementById("modal-clientes")) cerrarModalClientes();
}

function renderTablaClientes(indiceEditando = null) {
    const cont = document.getElementById("modal-clientes-contenido");

    if (clientesGuardados.length === 0) {
        cont.innerHTML = '<p class="modal-vacio">No hay clientes guardados.</p>';
        return;
    }

    let html = `<table class="tabla-clientes-modal">
        <thead><tr><th>Nombre</th><th>CUIT</th><th>Dirección</th><th>Email</th><th></th></tr></thead>
        <tbody>`;

    clientesGuardados.forEach((c, i) => {
        if (i === indiceEditando) {
            // Fila en modo edición inline
            html += `<tr class="fila-editando">
                <td><input type="text"  id="ce_nombre"    value="${esc(c.nombre)}"    placeholder="Nombre"></td>
                <td><input type="text"  id="ce_cuit"      value="${esc(c.cuit)}"      placeholder="CUIT"></td>
                <td><input type="text"  id="ce_dir"       value="${esc(c.direccion)}" placeholder="Dirección"></td>
                <td><input type="email" id="ce_mail"      value="${esc(c.mail)}"      placeholder="Email"></td>
                <td class="acciones-cliente">
                    <button class="btn-guardar-cliente" onclick="confirmarEdicionCliente(${i}, '${esc(c.nombre)}')">✔ Guardar</button>
                    <button class="btn-secundario"      onclick="renderTablaClientes()">✕</button>
                </td>
            </tr>`;
        } else {
            html += `<tr>
                <td>${esc(c.nombre)}</td>
                <td>${esc(c.cuit)}</td>
                <td>${esc(c.direccion)}</td>
                <td>${esc(c.mail)}</td>
                <td class="acciones-cliente">
                    <button class="btn-editar" onclick="renderTablaClientes(${i})">✏️</button>
                    <button class="btn-eliminar" onclick="confirmarEliminarCliente(${i}, '${esc(c.nombre)}')">🗑</button>
                </td>
            </tr>`;
        }
    });

    html += `</tbody></table>`;
    cont.innerHTML = html;
}

// Escapa comillas simples para atributos onclick
function esc(str) {
    return (str || "").replace(/'/g, "\\'").replace(/"/g, "&quot;");
}

function confirmarEdicionCliente(i, nombreOriginal) {
    const nuevosDatos = {
        nombreOriginal,
        nombre:    document.getElementById("ce_nombre").value.trim(),
        cuit:      document.getElementById("ce_cuit").value.trim(),
        direccion: document.getElementById("ce_dir").value.trim(),
        mail:      document.getElementById("ce_mail").value.trim(),
    };

    if (!nuevosDatos.nombre) { alert("El nombre no puede estar vacío."); return; }

    fetch("actualizar_cliente.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(nuevosDatos)
    })
        .then(r => r.json())
        .then(d => {
            if (d.status === "ok") {
                cargarClientes();
                // Actualizar en memoria para que la tabla refleje el cambio
                clientesGuardados[i] = {
                    nombre:    nuevosDatos.nombre,
                    cuit:      nuevosDatos.cuit,
                    direccion: nuevosDatos.direccion,
                    mail:      nuevosDatos.mail
                };
                guardarEnCache(LS_CLIENTES, clientesGuardados);
                renderTablaClientes();
            } else {
                alert("Error: " + (d.mensaje || "desconocido"));
            }
        })
        .catch(err => console.error(err));
}

function confirmarEliminarCliente(i, nombre) {
    if (!confirm(`¿Eliminar el cliente "${nombre}"?`)) return;

    fetch("eliminar_cliente.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ nombre })
    })
        .then(r => r.json())
        .then(d => {
            if (d.status === "ok") {
                clientesGuardados.splice(i, 1);
                guardarEnCache(LS_CLIENTES, clientesGuardados);
                renderTablaClientes();
            } else {
                alert("Error: " + (d.mensaje || "desconocido"));
            }
        })
        .catch(err => console.error(err));
}

// ─── Productos ────────────────────────────────────────────────────────────────
function agregarProducto() {
    const desc    = document.getElementById("producto_desc").value.trim();
    const cant    = parseInt(document.getElementById("producto_cant").value);
    const precio  = parseFloat(document.getElementById("producto_precio").value);
    const ivaPorc = parseFloat(document.getElementById("producto_iva").value) || 0;
    const moneda  = document.getElementById("moneda").value;

    if (!desc || isNaN(cant) || isNaN(precio)) {
        alert("Completá descripción, cantidad y precio.");
        return;
    }

    const subTotalBase = cant * precio;
    const totalIVA     = subTotalBase * ivaPorc / 100;
    const item         = { desc, cant, precio, subTotalBase, ivaPorc, totalIVA };

    if (indiceProdEditando !== null) {
        productos[indiceProdEditando] = item;
        indiceProdEditando = null;
        document.getElementById("aviso_edicion").style.display = "none";
        document.getElementById("form_productos").classList.remove("modo-edicion");
        document.getElementById("btn_agregar_prod").textContent = "Agregar producto";
        document.getElementById("btn_cancelar_edicion").style.display = "none";
    } else {
        productos.push(item);
    }

    limpiarFormProducto();
    actualizarTabla(moneda);
}

function editarProducto(i) {
    const p = productos[i];
    document.getElementById("producto_desc").value   = p.desc;
    document.getElementById("producto_cant").value   = p.cant;
    document.getElementById("producto_precio").value = p.precio;
    document.getElementById("producto_iva").value    = p.ivaPorc;

    indiceProdEditando = i;
    document.getElementById("aviso_edicion").style.display = "inline-block";
    document.getElementById("aviso_edicion").textContent = `✏️ Editando ítem ${i + 1}: "${p.desc}"`;
    document.getElementById("form_productos").classList.add("modo-edicion");
    document.getElementById("btn_agregar_prod").textContent = "Guardar cambios";
    document.getElementById("btn_cancelar_edicion").style.display = "inline-block";
    document.getElementById("form_productos").scrollIntoView({ behavior: "smooth", block: "center" });
}

function cancelarEdicion() {
    indiceProdEditando = null;
    limpiarFormProducto();
    document.getElementById("aviso_edicion").style.display = "none";
    document.getElementById("form_productos").classList.remove("modo-edicion");
    document.getElementById("btn_agregar_prod").textContent = "Agregar producto";
    document.getElementById("btn_cancelar_edicion").style.display = "none";
}

function eliminarProducto(i) {
    productos.splice(i, 1);
    if (indiceProdEditando !== null && indiceProdEditando >= i) cancelarEdicion();
    actualizarTabla(document.getElementById("moneda").value);
}

function limpiarFormProducto() {
    document.getElementById("producto_desc").value   = "";
    document.getElementById("producto_cant").value   = "";
    document.getElementById("producto_precio").value = "";
    document.getElementById("producto_iva").value    = "10.5";
}

function actualizarTabla(moneda) {
    const tbody = document.querySelector("#tabla_productos tbody");
    tbody.innerHTML = "";
    let total = 0, totalIVA = 0;

    productos.forEach((p, i) => {
        total    += p.subTotalBase;
        totalIVA += p.totalIVA;

        const tr = document.createElement("tr");
        tr.innerHTML = `
            <td>${p.desc}</td>
            <td>${p.cant}</td>
            <td>${formatearNumero(p.precio)} ${moneda}</td>
            <td>${formatearNumero(p.subTotalBase)} ${moneda}</td>
            <td>${p.ivaPorc}%</td>
            <td>${formatearNumero(p.totalIVA)} ${moneda}</td>
            <td>
                <button class="btn-editar" onclick="editarProducto(${i})" title="Editar">✏️</button>
                <button class="btn-eliminar-prod" onclick="eliminarProducto(${i})" title="Eliminar">🗑</button>
            </td>`;
        tbody.appendChild(tr);
    });

    const totalFinal = total + totalIVA;
    document.getElementById("total").innerText      = formatearNumero(total);
    document.getElementById("iva_total").innerText  = formatearNumero(totalIVA);
    document.getElementById("total_final").innerText = formatearNumero(totalFinal);
    document.querySelectorAll(".simbolo_moneda").forEach(el => el.innerText = moneda);
}

// ─── #8 Nuevo presupuesto ─────────────────────────────────────────────────────
function nuevoPresupuesto() {
    if (productos.length > 0 || document.getElementById("cliente_nombre").value.trim()) {
        if (!confirm("¿Limpiar el formulario y empezar un presupuesto nuevo?")) return;
    }

    // Limpiar productos
    productos = [];
    cancelarEdicion();
    actualizarTabla(document.getElementById("moneda").value);

    // Limpiar campos cliente
    ["cliente_nombre", "cliente_cuit", "cliente_direccion", "cliente_mail"].forEach(id => {
        document.getElementById(id).value = "";
    });

    // Limpiar campos presupuesto
    const hoy = new Date().toISOString().split("T")[0];
    document.getElementById("fecha").value   = hoy;
    document.getElementById("validez").value = "5";
    document.getElementById("notas").value   = "";
    document.getElementById("moneda").value  = "ARS";
    document.getElementById("pago").selectedIndex = 0;

    // Limpiar resultado
    document.getElementById("resultado").innerHTML = "";

    ultimoPresupuesto = null;
    window.scrollTo({ top: 0, behavior: "smooth" });
}

// ─── Presupuesto ──────────────────────────────────────────────────────────────
function generarPresupuesto() {
    if (productos.length === 0) { alert("Agregá al menos un producto."); return; }

    const empresa = {
        nombre:    document.getElementById("empresa_nombre").value,
        cuit:      document.getElementById("empresa_cuit").value,
        direccion: document.getElementById("empresa_direccion").value,
        mail:      document.getElementById("empresa_mail").value,
        tel:       document.getElementById("empresa_tel").value
    };
    const cliente = {
        nombre:    document.getElementById("cliente_nombre").value,
        cuit:      document.getElementById("cliente_cuit").value,
        direccion: document.getElementById("cliente_direccion").value,
        mail:      document.getElementById("cliente_mail").value
    };
    const presupuesto = {
        moneda:   document.getElementById("moneda").value,
        fecha:    document.getElementById("fecha").value,
        validez:  document.getElementById("validez").value,
        pago:     document.getElementById("pago").value,
        notas:    document.getElementById("notas").value.trim(), // #9
        productos: [...productos],
        totales: {
            neto:  document.getElementById("total").innerText,
            iva:   document.getElementById("iva_total").innerText,
            final: document.getElementById("total_final").innerText
        }
    };

    guardarClienteServidor(cliente);

    fetch("guardar_presupuesto.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ empresa, cliente, presupuesto })
    })
        .then(res => res.json())
        .then(data => {
            if (data.codigo) presupuesto.codigo = data.codigo;

            ultimoPresupuesto = { empresa, cliente, presupuesto };
            historial.push({ empresa, cliente, presupuesto });
            guardarEnCache(LS_HISTORIAL, historial); // #6
            mostrarHistorial();
            verPresupuesto(historial.length - 1);
        })
        .catch(err => console.error("Error al guardar presupuesto:", err));
}

function verPresupuesto(i) {
    const { empresa, cliente, presupuesto } = historial[i];

    let html = `
        <h2>Presupuesto — ${presupuesto.codigo || ""}</h2>
        <h3>Empresa</h3>
        <p><strong>${empresa.nombre}</strong> (CUIT: ${empresa.cuit})<br>
        ${empresa.direccion}<br>${empresa.mail} — ${empresa.tel}</p>
        <h3>Cliente</h3>
        <p><strong>${cliente.nombre}</strong> (CUIT: ${cliente.cuit})<br>
        ${cliente.direccion}<br>${cliente.mail}</p>
        <p><strong>Fecha:</strong> ${presupuesto.fecha} &nbsp;|&nbsp;
           <strong>Validez:</strong> ${presupuesto.validez} días &nbsp;|&nbsp;
           <strong>Forma de pago:</strong> ${presupuesto.pago}</p>`;

    // #9 Notas
    if (presupuesto.notas) {
        html += `<p><strong>Notas:</strong> ${presupuesto.notas}</p>`;
    }

    html += `<h3>Productos</h3>
        <table border="1" cellspacing="0" cellpadding="5">
        <tr><th>Descripción</th><th>Cant</th><th>Precio Base</th><th>Sub-Tot Base</th><th>IVA (%)</th><th>Total IVA</th></tr>`;

    presupuesto.productos.forEach(p => {
        html += `<tr>
            <td>${p.desc}</td><td>${p.cant}</td>
            <td>${p.precio.toFixed(2)} ${presupuesto.moneda}</td>
            <td>${p.subTotalBase.toFixed(2)} ${presupuesto.moneda}</td>
            <td>${p.ivaPorc}%</td>
            <td>${p.totalIVA.toFixed(2)} ${presupuesto.moneda}</td>
        </tr>`;
    });

    html += `</table>
        <div class="totales">
            <p><strong>Total Neto:</strong> ${presupuesto.totales.neto} ${presupuesto.moneda}</p>
            <p><strong>Total IVA:</strong> ${presupuesto.totales.iva} ${presupuesto.moneda}</p>
            <p><strong>Total Final:</strong> ${presupuesto.totales.final} ${presupuesto.moneda}</p>
        </div>`;

    document.getElementById("resultado").innerHTML = html;
    document.getElementById("resultado").scrollIntoView({ behavior: "smooth", block: "start" });
}

// ─── #7 Duplicar presupuesto ──────────────────────────────────────────────────
function duplicarPresupuesto(i) {
    const { cliente, presupuesto } = historial[i];

    if (productos.length > 0 || document.getElementById("cliente_nombre").value.trim()) {
        if (!confirm("Hay datos en el formulario. ¿Reemplazarlos con este presupuesto?")) return;
    }

    // Cargar cliente
    document.getElementById("cliente_nombre").value    = cliente.nombre    || "";
    document.getElementById("cliente_cuit").value      = cliente.cuit      || "";
    document.getElementById("cliente_direccion").value = cliente.direccion || "";
    document.getElementById("cliente_mail").value      = cliente.mail      || "";

    // Cargar datos del presupuesto (fecha = hoy, sin código)
    const hoy = new Date().toISOString().split("T")[0];
    document.getElementById("fecha").value   = hoy;
    document.getElementById("validez").value = presupuesto.validez || "5";
    document.getElementById("notas").value   = presupuesto.notas   || "";
    document.getElementById("moneda").value  = presupuesto.moneda  || "ARS";

    // Seleccionar forma de pago
    const pagoSelect = document.getElementById("pago");
    for (let opt of pagoSelect.options) {
        if (opt.value === presupuesto.pago || opt.text === presupuesto.pago) {
            opt.selected = true;
            break;
        }
    }

    // Cargar productos (copia profunda)
    productos = presupuesto.productos.map(p => ({ ...p }));
    cancelarEdicion();
    actualizarTabla(presupuesto.moneda || "ARS");

    ultimoPresupuesto = null;
    document.getElementById("resultado").innerHTML = "";

    window.scrollTo({ top: 0, behavior: "smooth" });
}

// ─── Historial ────────────────────────────────────────────────────────────────
function cargarHistorial() {
    fetch("obtener_historial.php")
        .then(res => res.json())
        .then(data => {
            historial = data;
            guardarEnCache(LS_HISTORIAL, data); // #6
            mostrarHistorial();
        })
        .catch(() => {
            // #6 Fallback a localStorage
            const cache = leerDeCache(LS_HISTORIAL);
            if (cache) {
                historial = cache;
                console.warn("Historial cargado desde caché local.");
                mostrarHistorial();
            }
        });
}

function mostrarHistorial() {
    const filtro = document.getElementById("filtro_cliente").value.toLowerCase();
    const lista  = document.getElementById("lista_historial");
    lista.innerHTML = "";

    const indexados = historial
        .map((h, i) => ({ h, i }))
        .filter(({ h }) =>
            h.cliente.nombre.toLowerCase().includes(filtro) ||
            (h.presupuesto.codigo || "").toLowerCase().includes(filtro)
        )
        .sort((a, b) => {
            const fechaDiff = new Date(b.h.presupuesto.fecha) - new Date(a.h.presupuesto.fecha);
            if (fechaDiff !== 0) return fechaDiff;
            const numA = parseInt((a.h.presupuesto.codigo || "").replace(/\D/g, "")) || 0;
            const numB = parseInt((b.h.presupuesto.codigo || "").replace(/\D/g, "")) || 0;
            return numB - numA;
        });

    if (indexados.length === 0) {
        lista.innerHTML = '<li class="historial-vacio">No hay presupuestos que coincidan.</li>';
        return;
    }

    indexados.forEach(({ h, i }) => {
        const li = document.createElement("li");

        const info = document.createElement("span");
        info.className = "historial-info";
        info.innerHTML =
            `<span class="historial-codigo">${h.presupuesto.codigo || "—"}</span> ` +
            `<span class="historial-cliente">${h.cliente.nombre}</span> ` +
            `<span class="historial-fecha">${formatearFecha(h.presupuesto.fecha)}</span>`;

        const acciones = document.createElement("span");
        acciones.className = "historial-acciones";

        const verBtn = document.createElement("button");
        verBtn.innerHTML = "👁 Ver";
        verBtn.className = "btn-ver";
        verBtn.onclick = () => verPresupuesto(i);

        const copiarBtn = document.createElement("button"); // #7
        copiarBtn.innerHTML = "📋 Copiar";
        copiarBtn.className = "btn-copiar";
        copiarBtn.title = "Duplicar como nuevo presupuesto";
        copiarBtn.onclick = () => duplicarPresupuesto(i);

        const pdfBtn = document.createElement("button");
        pdfBtn.innerHTML = "⬇ PDF";
        pdfBtn.className = "btn-pdf";
        pdfBtn.onclick = () => descargarPDF(i);

        const remitoBtn = document.createElement("button");
        remitoBtn.innerHTML = "📄 Remito";
        remitoBtn.className = "btn-remito";
        remitoBtn.title = "Descargar remito sin precios";
        remitoBtn.onclick = () => descargarRemito(i);

        const delBtn = document.createElement("button");
        delBtn.innerHTML = "🗑";
        delBtn.className = "btn-eliminar";
        delBtn.title = "Eliminar presupuesto";
        delBtn.onclick = () => eliminarPresupuesto(i, h.presupuesto.codigo);

        acciones.append(verBtn, copiarBtn, pdfBtn, remitoBtn, delBtn);
        li.append(info, acciones);
        lista.appendChild(li);
    });
}

function formatearFecha(fechaStr) {
    if (!fechaStr) return "—";
    const [y, m, d] = fechaStr.split("-");
    return `${d}/${m}/${y}`;
}

function eliminarPresupuesto(i, codigo) {
    if (!confirm(`¿Eliminar el presupuesto ${codigo || "seleccionado"}? Esta acción no se puede deshacer.`)) return;

    fetch("eliminar_presupuesto.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ codigo })
    })
        .then(res => res.json())
        .then(data => {
            if (data.status === "ok") {
                historial.splice(i, 1);
                guardarEnCache(LS_HISTORIAL, historial); // #6
                mostrarHistorial();
                const resultado = document.getElementById("resultado");
                if (resultado.querySelector("h2")?.textContent.includes(codigo)) {
                    resultado.innerHTML = "";
                }
            } else {
                alert("Error al eliminar: " + (data.mensaje || "desconocido"));
            }
        })
        .catch(err => console.error("Error eliminando presupuesto:", err));
}

// ─── PDF ──────────────────────────────────────────────────────────────────────
function descargarPDF(i = null) {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();
    const pageWidth = doc.internal.pageSize.width;

    let empresa, cliente, presupuesto;
    if (i === null) {
        if (!ultimoPresupuesto) { alert("Primero generá un presupuesto."); return; }
        ({ empresa, cliente, presupuesto } = ultimoPresupuesto);
    } else {
        ({ empresa, cliente, presupuesto } = historial[i]);
    }

    doc.setFontSize(10);
    doc.text(`N°: ${presupuesto.codigo || "—"}`, pageWidth - 20, 20, { align: "right" });
    doc.setFontSize(16);
    doc.text("Presupuesto", pageWidth / 2, 15, { align: "center" });
    doc.setFontSize(12);
    doc.text(`Empresa: ${empresa.nombre} (CUIT: ${empresa.cuit})`, 14, 30);
    doc.text(`${empresa.direccion}`, 14, 36);
    doc.text(`${empresa.mail} - ${empresa.tel}`, 14, 42);
    doc.text(`Cliente: ${cliente.nombre} (CUIT: ${cliente.cuit})`, 14, 54);
    doc.text(`${cliente.direccion}`, 14, 60);
    doc.text(`${cliente.mail}`, 14, 66);
    doc.text(`Fecha: ${presupuesto.fecha}`, 14, 78);
    doc.text(`Validez: ${presupuesto.validez} días`, 14, 84);
    doc.text(`Forma de pago: ${presupuesto.pago}`, 14, 90);

    // #9 Notas en el PDF
    let startY = 100;
    if (presupuesto.notas) {
        doc.setFontSize(11);
        const lineas = doc.splitTextToSize(`Notas: ${presupuesto.notas}`, pageWidth - 28);
        doc.text(lineas, 14, 97);
        startY = 97 + lineas.length * 6 + 4;
    }

    doc.autoTable({
        startY,
        head: [["Descripción", "Cant", "Precio Base", "Sub-Tot Base", "IVA (%)", "Total IVA"]],
        body: presupuesto.productos.map(p => [
            p.desc, p.cant,
            formatearNumero(p.precio)      + " " + presupuesto.moneda,
            formatearNumero(p.subTotalBase) + " " + presupuesto.moneda,
            p.ivaPorc + "%",
            formatearNumero(p.totalIVA)    + " " + presupuesto.moneda
        ]),
        theme: "grid",
        headStyles:         { fillColor: [48, 48, 48], textColor: [245, 241, 232] },
        alternateRowStyles: { fillColor: [249, 249, 249] }
    });

    const totales = [
        ["Total Neto:",  presupuesto.totales.neto   + " " + presupuesto.moneda],
        ["Total IVA:",   presupuesto.totales.iva    + " " + presupuesto.moneda],
        ["Total Final:", presupuesto.totales.final  + " " + presupuesto.moneda]
    ];
    const tableWidth = Math.max(...totales.map(r => doc.getTextWidth(r[0] + " " + r[1]))) + 20;

    doc.autoTable({
        body: totales,
        startY: doc.lastAutoTable.finalY + 10,
        theme: "grid",
        tableWidth: "auto",
        margin: { left: pageWidth - tableWidth - 14 },
        styles:     { halign: "right", cellPadding: 3 },
        bodyStyles: { fillColor: [245, 241, 232], textColor: [48, 48, 48] },
        didParseCell(data) {
            if (data.row.index === 2) {
                data.cell.styles.fillColor  = [190, 161, 103];
                data.cell.styles.textColor  = [48, 48, 48];
                data.cell.styles.fontStyle  = "bold";
            }
        }
    });

    const codigo       = presupuesto.codigo || "SIN-CODIGO";
    const nombreCliente = (cliente.nombre || "Cliente").replace(/[^a-z0-9]/gi, "_").toUpperCase();
    doc.save(`${codigo}_${nombreCliente}.pdf`);
}

// ─── Remito ───────────────────────────────────────────────────────────────────
function descargarRemito(i = null) {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();
    const pageWidth  = doc.internal.pageSize.width;
    const pageHeight = doc.internal.pageSize.height;
    const margen = 14;

    let empresa, cliente, presupuesto;
    if (i === null) {
        if (!ultimoPresupuesto) { alert("Primero generá un presupuesto."); return; }
        ({ empresa, cliente, presupuesto } = ultimoPresupuesto);
    } else {
        ({ empresa, cliente, presupuesto } = historial[i]);
    }

    const codigo        = presupuesto.codigo || "SIN-CODIGO";
    const nombreCliente = (cliente.nombre || "Cliente").replace(/[^a-z0-9]/gi, "_").toUpperCase();

    // ── Función auxiliar que dibuja una copia completa ──────────────────────
    function dibujarCopia(etiqueta) {
        // Badge de copia — ancho dinámico según el texto
        doc.setFontSize(8);
        const badgeText = etiqueta.toUpperCase();
        const badgeW = doc.getTextWidth(badgeText) + 10;
        doc.setFillColor(48, 48, 48);
        doc.roundedRect(margen, 8, badgeW, 8, 2, 2, "F");
        doc.setTextColor(245, 241, 232);
        doc.text(badgeText, margen + badgeW / 2, 13.5, { align: "center" });
        doc.setTextColor(48, 48, 48);

        // Título y número
        doc.setFontSize(16);
        doc.setFont(undefined, "bold");
        doc.text("Remito", pageWidth / 2, 15, { align: "center" });
        doc.setFont(undefined, "normal");

        // Datos empresa
        doc.setFontSize(11);
        doc.setFont(undefined, "bold");
        doc.text(empresa.nombre, margen, 30);
        doc.setFont(undefined, "normal");
        doc.setFontSize(10);
        doc.text(`CUIT: ${empresa.cuit}`, margen, 36);
        doc.text(empresa.direccion, margen, 42);
        if (empresa.mail || empresa.tel)
            doc.text(`${empresa.mail}  ${empresa.tel}`.trim(), margen, 48);

        // Línea divisoria
        doc.setDrawColor(190, 161, 103);
        doc.setLineWidth(0.5);
        doc.line(margen, 53, pageWidth - margen, 53);
        doc.setDrawColor(48, 48, 48);

        // Datos cliente
        doc.setFontSize(10);
        doc.setFont(undefined, "bold");
        doc.text("Cliente:", margen, 60);
        doc.setFont(undefined, "normal");
        doc.text(`${cliente.nombre}  —  CUIT: ${cliente.cuit}`, margen + 18, 60);
        doc.text(cliente.direccion, margen, 66);
        if (cliente.mail) doc.text(cliente.mail, margen, 72);

        // Fecha y pago
        doc.text(`Fecha: ${formatearFecha(presupuesto.fecha)}`, margen, 80);
        doc.text(`Forma de pago: ${presupuesto.pago}`, margen + 60, 80);

        // Notas
        let startY = 90;
        if (presupuesto.notas) {
            doc.setFontSize(10);
            const lineas = doc.splitTextToSize(`Notas: ${presupuesto.notas}`, pageWidth - margen * 2);
            doc.text(lineas, margen, 88);
            startY = 88 + lineas.length * 5 + 4;
        }

        // Tabla de productos (sin precios)
        doc.autoTable({
            startY,
            head: [["Descripción", "Cantidad"]],
            body: presupuesto.productos.map(p => [p.desc, p.cant]),
            theme: "grid",
            headStyles:         { fillColor: [48, 48, 48], textColor: [245, 241, 232], fontSize: 10 },
            alternateRowStyles: { fillColor: [249, 249, 249] },
            columnStyles:       { 1: { halign: "center", cellWidth: 30 } },
            styles:             { fontSize: 10 }
        });

        // ── Espacio de firma ─────────────────────────────────────────────────
        const firmaY = Math.max(doc.lastAutoTable.finalY + 20, pageHeight - 50);

        doc.setDrawColor(48, 48, 48);
        doc.setLineWidth(0.3);

        // Línea firma
        doc.line(margen, firmaY, margen + 70, firmaY);
        doc.setFontSize(9);
        doc.text("Firma y aclaración", margen, firmaY + 5);

        // Línea fecha recepción
        doc.line(pageWidth - margen - 50, firmaY, pageWidth - margen, firmaY);
        doc.text("Fecha de recepción", pageWidth - margen - 50, firmaY + 5);
    }

    // ── Página 1: Copia Cliente ──────────────────────────────────────────────
    dibujarCopia("Copia Cliente");

    // ── Página 2: Copia Proveedor / Transportista ────────────────────────────
    doc.addPage();
    dibujarCopia("Copia Proveedor / Transportista");

    doc.save(`REMITO_${codigo}_${nombreCliente}.pdf`);
}

// ─── Utilidades ───────────────────────────────────────────────────────────────
function formatearNumero(num) {
    return Number(num).toLocaleString("es-AR", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

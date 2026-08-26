document.addEventListener("DOMContentLoaded", () => {
  const tableBody = document.querySelector("#stockTable tbody");
  const modal = document.getElementById("editModal");
  const overlay = document.getElementById("overlay");
  const form = document.getElementById("editForm");

  // ===== CARGAR DATOS PRINCIPALES =====
  fetch("fetch_data.php")
    .then(res => res.json())
    .then(data => {
      tableBody.innerHTML = data.map(row => `
        <tr>
          <td>${row.brand}</td>
          <td>${row.model}</td>
          <td>${row.color} ${row.storage}GB</td>
          <td>${row.quantity}</td>
          <td><button class="editBtn" data-id="${row.id_stock}" 
              data-brand="${row.id_brand}" data-model="${row.id_model}" 
              data-variant="${row.id_variant}" data-qty="${row.quantity}">Editar</button></td>
        </tr>
      `).join("");

      document.querySelectorAll(".editBtn").forEach(btn => {
        btn.addEventListener("click", e => openModal(e.target.dataset));
      });
    });

  // ===== ABRIR MODAL =====
  async function openModal(data) {
    overlay.classList.add("visible");
    modal.classList.add("show");

    // Rellenar campos base
    form.id_stock.value = data.id;
    form.quantity.value = data.qty;

    // Cargar marcas
    const brands = await fetch("fetch_options.php?type=brands").then(r => r.json());
    form.brand_id.innerHTML = brands
      .map(b => `<option value="${b.id_brand}" ${b.id_brand == data.brand ? "selected" : ""}>${b.name}</option>`)
      .join("");

    await loadModels(data.brand, data.model);
    await loadVariants(data.model, data.variant);
  }

  // ===== CARGAR MODELOS SEGÚN MARCA =====
  async function loadModels(brandId, selectedModel = null) {
    const res = await fetch(`fetch_options.php?type=models&brand_id=${brandId}`);
    const models = await res.json();
    form.model_id.innerHTML = models
      .map(m => `<option value="${m.id_model}" ${m.id_model == selectedModel ? "selected" : ""}>${m.name}</option>`)
      .join("");
  }

  // ===== CARGAR VARIANTES SEGÚN MODELO =====
  async function loadVariants(modelId, selectedVariant = null) {
    const res = await fetch(`fetch_options.php?type=variants&model_id=${modelId}`);
    const variants = await res.json();
    form.variant_id.innerHTML = variants
      .map(v => `<option value="${v.id_variant}" ${v.id_variant == selectedVariant ? "selected" : ""}>${v.name}</option>`)
      .join("");
  }

  // ===== CAMBIO DE SELECTS DINÁMICOS =====
  form.brand_id.addEventListener("change", async e => {
    await loadModels(e.target.value);
    form.variant_id.innerHTML = "";
  });

  form.model_id.addEventListener("change", async e => {
    await loadVariants(e.target.value);
  });

  // ===== CERRAR MODAL =====
  document.getElementById("cancelBtn").addEventListener("click", closeModal);
  overlay.addEventListener("click", closeModal);

  function closeModal() {
    modal.classList.remove("show");
    overlay.classList.remove("visible");
  }

  // ===== GUARDAR CAMBIOS (AJAX) =====
  form.addEventListener("submit", e => {
    e.preventDefault();
    const formData = new FormData(form);

    fetch("update_stock.php", {
      method: "POST",
      body: formData
    })
      .then(res => res.json())
      .then(resp => {
        if (resp.success) {
          showToast("✅ Cambios guardados correctamente");
          setTimeout(() => {
            closeModal();
            location.reload();
          }, 800);
        } else {
          showToast("⚠️ Error: " + (resp.error || "No se pudo guardar"));
        }
      })
      .catch(() => showToast("❌ Error al enviar datos"));
  });

  // ===== TOAST (MENSAJE FLOTANTE) =====
  function showToast(msg) {
    const toast = document.createElement("div");
    toast.className = "toast";
    toast.textContent = msg;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
  }
});

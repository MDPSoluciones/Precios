function calcularCaja() {
  const n1 = parseFloat(document.getElementById("num1").value);
  const n2 = parseFloat(document.getElementById("num2").value);
  let res;

  if (isNaN(n1) || isNaN(n2)) {
    res = "Por favor, ingresá dos números válidos.";
  } else {
    res = n2 !== 0 ? (n1 / n2) : "No se puede dividir por 0";
    if (!isNaN(res)) res = parseFloat(res.toFixed(2));
  }

  document.getElementById("cajaResultado").textContent = `Resultado: ${res}`;

  if (!isNaN(res)) {
    navigator.clipboard.writeText(res.toString())
      .then(() => { document.getElementById("cajaClipboard").textContent = "Copiado al portapapeles."; })
      .catch(() => { document.getElementById("cajaClipboard").textContent = ""; });
  } else {
    document.getElementById("cajaClipboard").textContent = "";
  }
}

document.getElementById("calculatorModal").addEventListener("click", function (event) {
  if (event.target === this) this.classList.remove("abierto");
});

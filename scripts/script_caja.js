const VALID_USER = "admin";
const VALID_PASS = "1234";

function login() {
  const user = document.getElementById("username").value;
  const pass = document.getElementById("password").value;

  if (user === VALID_USER && pass === VALID_PASS) {
    const expiration = new Date().getTime() + 24 * 60 * 60 * 1000; // 24 hs
    localStorage.setItem("session", JSON.stringify({ user, expiration }));
    showPrivateContent(user);
  } else {
    document.getElementById("error").textContent = "Usuario o contraseña incorrectos.";
  }
}

function logout() {
  localStorage.removeItem("session");
  location.reload();
}

function checkSession() {
  const session = JSON.parse(localStorage.getItem("session") || "{}");
  if (session.user && session.expiration > new Date().getTime()) {
    showPrivateContent(session.user);
  } else {
    localStorage.removeItem("session");
  }
}

function showPrivateContent(user) {
  document.getElementById("login-form").style.display = "none";
  document.getElementById("private-content").style.display = "block";
  document.getElementById("user-info").textContent = `Bienvenido ${user}`;
}

window.addEventListener("DOMContentLoaded", checkSession);


function openCalculator() {
  document.getElementById("calculator-modal").style.display = "block";
}

function closeCalculator() {
  document.getElementById("calculator-modal").style.display = "none";
}

function calcular() {
  const n1 = parseFloat(document.getElementById("num1").value);
  const n2 = parseFloat(document.getElementById("num2").value);
//   const op = document.getElementById("operation").value;
  let res;

if (isNaN(n1) || isNaN(n2)) {
    res = "Por favor, ingresa dos números válidos.";
  } else {
    
        res = n2 !== 0 ? (n1 / n2) : "No se puede dividir por 0";
        if (!isNaN(res)) {
            res = parseFloat(res.toFixed(2));
        }   
        
    


  /* if (isNaN(n1) || isNaN(n2)) {
    res = "Por favor, ingresa dos números válidos.";
  } else {
    switch(op) {
      case "sumar":
        res = n1 + n2;
        break;
      case "restar":
        res = n1 - n2;
        break;
      case "multiplicar":
        res = n1 * n2;
        break;
      case "dividir":
        res = n2 !== 0 ? (n1 / n2) : "No se puede dividir por 0";
        break;
    } */
  }

    
    // Mostrar resultado en el modal
   const texto = `Resultado: ${res}`;
  document.getElementById("resultado").textContent = texto;

  // Copiar al portapapeles
  navigator.clipboard.writeText(res.toString()).then(() => {
    console.log("Resultado copiado al portapapeles");
    document.getElementById("clipboard").textContent = `Copiado al portapapeles.`;
  }).catch(err => {
    console.error("Error al copiar:", err);
  });

//   document.getElementById("resultado").textContent = `Resultado: ${res}`;
}

// Cerrar modal si el usuario hace clic fuera del contenido
window.onclick = function(event) {
  const modal = document.getElementById("calculator-modal");
  if (event.target === modal) {
    closeCalculator();
  }
}


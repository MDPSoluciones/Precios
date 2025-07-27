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

document.addEventListener("DOMContentLoaded", () => {
    const logueado = localStorage.getItem("logueado");

    if (!logueado) {
        window.location.href = "login.html";
        return;
    }
    const nombre = localStorage.getItem("nombre");
    const rol = localStorage.getItem("rol");

    document.getElementById("nombreUsuario").textContent = nombre;
    if (rol === "admin") {
        document.getElementById("tarjetasAdministrador").classList.remove("d-none");
        document.getElementById("tarjetasAsociados").classList.add("d-none");
    } else {
        document.getElementById("tarjetasAsociados").classList.remove("d-none");
        document.getElementById("tarjetasAdministrador").classList.add("d-none");
    }

    const cerrarSesionBtn = document.getElementById("cerrarSesion");
    cerrarSesionBtn.addEventListener("click", () => {
        localStorage.clear();
        window.location.href = "login.html";
    });
});
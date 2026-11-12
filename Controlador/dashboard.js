document.addEventListener("DOMContentLoaded", () => {
    const logueado = localStorage.getItem("logueado");

    if (!logueado) {
        window.location.href = "../Vista/login.html";
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

    async function cerrarSesion() {
        try{
            const respuesta = await fetch("../Modelo/cerrarsesion.php", {
                method: "POST"
            });

            if (!respuesta.ok) {
                throw new Error("Error al cerrar sesión");
            }
            localStorage.clear();
            window.location.href = "../Vista/login.html";
        }catch(error){
            console.error("Error al cerrar sesión:", error);
        }
    }

    document.getElementById("cerrarSesion").addEventListener("click", cerrarSesion);
});
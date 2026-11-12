document.addEventListener("DOMContentLoaded", () => {
    if (!localStorage.getItem("logueado")) {
        window.location.href = "../Vista/login.html";
        return;
    }
    document.getElementById("nombreUsuario").textContent = localStorage.getItem("nombre");

    document.getElementById("cerrarSesion").addEventListener("click", async () => {
        try {
            const respuesta = await fetch("../Modelo/cerrarsesion.php", { method: "POST" });
            if (!respuesta.ok) {
                throw new Error("Error al cerrar sesión");
            }
            localStorage.clear();
            window.location.href = "../Vista/login.html";
        } catch (error) {
            console.error("Error al cerrar sesión:", error);
        }
    });
});

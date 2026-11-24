const formulario = document.getElementById("formPrestamos");

formulario.addEventListener("submit", async (evento) => {
    evento.preventDefault();
    const boton = formulario.querySelector("button[type='submit']");
    // Solo se envían los datos que la tabla prestamos guarda; el asociado y la tasa se definen en el servidor
    const datos = {
        valorPrestamo: Number(formulario.valorPrestamo.value),
        numeroCuotas: Number(formulario.plazoMeses.value),
        fechaPrestamo: formulario.fechaSolicitud.value
    };
    boton.disabled = true;
    try {
        const respuesta = await fetch("../Modelo/gestionarprestamos.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(datos)
        });
        const resultado = await respuesta.json();

        if (respuesta.status === 401) {
            window.location.href = "../Vista/login.html";
            return;
        }
        if (!resultado.success) {
            throw new Error(resultado.message || "Error en la solicitud");
        }
        alert(resultado.message);
        window.location.href = "Prestamos.html";
    } catch (error) {
        alert("Error al solicitar el préstamo: " + error.message);
    } finally {
        boton.disabled = false;
    }
});

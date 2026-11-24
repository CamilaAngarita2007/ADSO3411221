const formulario = document.getElementById("formPago");
const idPrestamo = Number(new URLSearchParams(window.location.search).get("idPrestamo"));
let deudaActual = 0;

function formatearPesos(valor) {
    return Number(valor).toLocaleString("es-CO", { style: "currency", currency: "COP" });
}

async function cargarPrestamo() {
    if (!Number.isInteger(idPrestamo) || idPrestamo < 1) {
        alert("No se indicó el préstamo a abonar");
        window.location.href = "pagos.html";
        return;
    }
    try {
        const respuesta = await fetch("../Modelo/gestionarpagos.php");
        const resultado = await respuesta.json();
        if (respuesta.status === 401) {
            window.location.href = "../Vista/login.html";
            return;
        }
        if (!resultado.success) {
            throw new Error(resultado.message || "Error al cargar el préstamo");
        }

        const prestamo = resultado.prestamos.find((p) => Number(p.idPrestamo) === idPrestamo);
        if (!prestamo) {
            alert("Este préstamo no tiene deuda pendiente");
            window.location.href = "pagos.html";
            return;
        }

        deudaActual = Number(prestamo.valorPrestamo);
        document.getElementById("deudaActual").textContent = formatearPesos(deudaActual);
        // No se puede abonar más que la deuda actual
        document.getElementById("valorAbono").max = deudaActual.toFixed(2);
    } catch (error) {
        alert("Error al cargar el préstamo: " + error.message);
    }
}

formulario.addEventListener("submit", async (evento) => {
    evento.preventDefault();
    const boton = document.getElementById("botonAbonar");
    const valorCuota = Number(document.getElementById("valorAbono").value);

    if (valorCuota > deudaActual) {
        alert("El abono no puede superar la deuda actual de " + formatearPesos(deudaActual));
        return;
    }

    boton.disabled = true;
    try {
        const respuesta = await fetch("../Modelo/gestionarpagos.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ idPrestamo: idPrestamo, valorCuota: valorCuota })
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
        window.location.href = "pagos.html";
    } catch (error) {
        alert("Error al registrar el abono: " + error.message);
    } finally {
        boton.disabled = false;
    }
});

cargarPrestamo();

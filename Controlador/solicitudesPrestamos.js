const listaSolicitudes = document.getElementById("listaSolicitudes");

async function solicitarApi(metodo, datos) {
    const opciones = {
        method: metodo,
        headers: {
            "Content-Type": "application/json"
        }
    };
    if (datos) {
        opciones.body = JSON.stringify(datos);
    }
    const respuesta = await fetch("../Modelo/gestionarsolicitudesprestamo.php", opciones);
    const resultado = await respuesta.json();

    if (respuesta.status === 401) {
        window.location.href = "../Vista/login.html";
        throw new Error("Sesión no iniciada");
    }
    if (respuesta.status === 403) {
        window.location.href = "Prestamos.html";
        throw new Error(resultado.message || "Sin permisos");
    }
    if (!resultado.success) {
        throw new Error(resultado.message || "Error en la solicitud");
    }
    return resultado;
}

async function cargarSolicitudes() {
    try {
        const resultado = await solicitarApi("GET");
        mostrarSolicitudes(resultado.solicitudes);
        document.getElementById("contenedorTabla").classList.remove("d-none");
    } catch (error) {
        alert("Error al cargar las solicitudes: " + error.message);
    } finally {
        document.getElementById("cargandoSolicitudes").classList.add("d-none");
    }
}

function mostrarSolicitudes(solicitudes) {
    listaSolicitudes.replaceChildren();
    if (solicitudes.length === 0) {
        const fila = document.createElement("tr");
        const celda = document.createElement("td");
        celda.colSpan = 6;
        celda.className = "text-center py-4 text-muted";
        celda.textContent = "No hay solicitudes pendientes";
        fila.append(celda);
        listaSolicitudes.append(fila);
        return;
    }
    solicitudes.forEach((solicitud) => {
        const fila = document.createElement("tr");
        fila.append(
            crearCelda(solicitud.nombreAsociado),
            crearCelda(Number(solicitud.valorPrestamo).toLocaleString("es-CO", { style: "currency", currency: "COP" })),
            crearCelda(`${Number(solicitud.tasaInteres).toFixed(2)} %`),
            crearCelda(solicitud.numeroCuotas),
            crearCelda(solicitud.fechaPrestamo)
        );

        const acciones = document.createElement("td");
        acciones.className = "text-end text-nowrap";

        const botonAceptar = document.createElement("button");
        botonAceptar.type = "button";
        botonAceptar.className = "btn btn-sm btn-outline-success me-2";
        botonAceptar.innerHTML = `<i class="bi bi-check-circle"></i> Aceptar`;
        botonAceptar.addEventListener("click", () => aceptarSolicitud(solicitud.idSolicitud));

        const botonRechazar = document.createElement("button");
        botonRechazar.type = "button";
        botonRechazar.className = "btn btn-sm btn-outline-danger";
        botonRechazar.innerHTML = `<i class="bi bi-x-circle"></i> Rechazar`;
        botonRechazar.addEventListener("click", () => rechazarSolicitud(solicitud.idSolicitud));

        acciones.append(botonAceptar, botonRechazar);
        fila.append(acciones);
        listaSolicitudes.append(fila);
    });
}

function crearCelda(texto) {
    const celda = document.createElement("td");
    celda.textContent = texto;
    return celda;
}

async function aceptarSolicitud(idSolicitud) {
    if (!confirm("¿Aceptar esta solicitud y registrarla como préstamo?")) {
        return;
    }
    try {
        const resultado = await solicitarApi("POST", { idSolicitud: idSolicitud });
        alert(resultado.message);
        await cargarSolicitudes();
    } catch (error) {
        alert("Error al aceptar la solicitud: " + error.message);
    }
}

async function rechazarSolicitud(idSolicitud) {
    if (!confirm("¿Rechazar esta solicitud? Se eliminará sin guardarse como préstamo.")) {
        return;
    }
    try {
        const resultado = await solicitarApi("DELETE", { idSolicitud: idSolicitud });
        alert(resultado.message);
        await cargarSolicitudes();
    } catch (error) {
        alert("Error al rechazar la solicitud: " + error.message);
    }
}

cargarSolicitudes();

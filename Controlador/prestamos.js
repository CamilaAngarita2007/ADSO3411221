const listaPrestamos = document.getElementById("listaPrestamos");
let esAdministrador = false;
let prestamos = [];

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
    const respuesta = await fetch("../Modelo/gestionarprestamos.php", opciones);
    const resultado = await respuesta.json();

    if (respuesta.status === 401) {
        window.location.href = "../Vista/login.html";
        throw new Error("Sesión no iniciada");
    }
    if (!resultado.success) {
        throw new Error(resultado.message || "Error en la solicitud");
    }
    return resultado;
}

async function cargarPrestamos() {
    try {
        const resultado = await solicitarApi("GET");
        esAdministrador = resultado.rol === "admin";
        document.getElementById("thAsociado").classList.toggle("d-none", !esAdministrador);
        document.getElementById("thAcciones").classList.toggle("d-none", !esAdministrador);
        // Solo los asociados pueden solicitar préstamos
        document.getElementById("btnNuevoPrestamo").classList.toggle("d-none", esAdministrador);
        // Solo el administrador aprueba o rechaza solicitudes
        document.getElementById("btnSolicitudes").classList.toggle("d-none", !esAdministrador);

        prestamos = resultado.prestamos;
        mostrarPrestamos();
        document.getElementById("contenedorTabla").classList.remove("d-none");
    } catch (error) {
        alert("Error al cargar los préstamos: " + error.message);
    } finally {
        document.getElementById("cargandoPrestamos").classList.add("d-none");
    }
}

function mostrarPrestamos() {
    listaPrestamos.replaceChildren();
    if (prestamos.length === 0) {
        const fila = document.createElement("tr");
        const celda = document.createElement("td");
        celda.colSpan = esAdministrador ? 6 : 4;
        celda.className = "text-center py-4 text-muted";
        celda.textContent = "No hay préstamos registrados";
        fila.append(celda);
        listaPrestamos.append(fila);
        return;
    }
    prestamos.forEach((prestamo) => {
        const fila = document.createElement("tr");
        if (esAdministrador) {
            fila.append(crearCelda(prestamo.nombreAsociado));
        }
        fila.append(
            crearCelda(Number(prestamo.valorPrestamo).toLocaleString("es-CO", { style: "currency", currency: "COP" })),
            crearCelda(`${Number(prestamo.tasaInteres).toFixed(2)} %`),
            crearCelda(prestamo.numeroCuotas),
            crearCelda(prestamo.fechaPrestamo)
        );
        if (esAdministrador) {
            const acciones = document.createElement("td");
            acciones.className = "text-end text-nowrap";

            const botonEditar = document.createElement("button");
            botonEditar.type = "button";
            botonEditar.className = "btn btn-sm btn-outline-primary me-2";
            botonEditar.innerHTML = `<i class="bi bi-pencil-square"></i>`;
            botonEditar.addEventListener("click", () => abrirEdicion(prestamo));

            const botonEliminar = document.createElement("button");
            botonEliminar.type = "button";
            botonEliminar.className = "btn btn-sm btn-outline-danger";
            botonEliminar.innerHTML = `<i class="bi bi-trash"></i>`;
            botonEliminar.addEventListener("click", () => eliminarPrestamo(prestamo.idPrestamo));

            acciones.append(botonEditar, botonEliminar);
            fila.append(acciones);
        }
        listaPrestamos.append(fila);
    });
}

function crearCelda(texto) {
    const celda = document.createElement("td");
    celda.textContent = texto;
    return celda;
}

async function eliminarPrestamo(idPrestamo) {
    if (!confirm("¿Seguro que deseas eliminar este préstamo? También se eliminarán sus pagos de cuotas.")) {
        return;
    }
    try {
        await solicitarApi("DELETE", { idPrestamo: idPrestamo });
        await cargarPrestamos();
    } catch (error) {
        alert("Error al eliminar el préstamo: " + error.message);
    }
}

function abrirEdicion(prestamo) {
    const formulario = document.getElementById("formEdicion");
    formulario.idPrestamo.value = prestamo.idPrestamo;
    formulario.valorPrestamo.value = prestamo.valorPrestamo;
    formulario.numeroCuotas.value = prestamo.numeroCuotas;
    formulario.fechaPrestamo.value = (prestamo.fechaPrestamo ?? "").substring(0, 10);
    bootstrap.Modal.getOrCreateInstance(document.getElementById("modalEdicion")).show();
}

document.getElementById("formEdicion").addEventListener("submit", async (evento) => {
    evento.preventDefault();
    const formulario = evento.target;
    const datos = {
        idPrestamo: Number(formulario.idPrestamo.value),
        valorPrestamo: Number(formulario.valorPrestamo.value),
        numeroCuotas: Number(formulario.numeroCuotas.value),
        fechaPrestamo: formulario.fechaPrestamo.value
    };
    try {
        await solicitarApi("PUT", datos);
        bootstrap.Modal.getInstance(document.getElementById("modalEdicion")).hide();
        await cargarPrestamos();
    } catch (error) {
        alert("Error al actualizar el préstamo: " + error.message);
    }
});

cargarPrestamos();

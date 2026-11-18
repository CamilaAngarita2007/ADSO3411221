const listaActividades = document.getElementById("listaActividades");
let esAdministrador = false;
let actividades = [];

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
    const respuesta = await fetch("../Modelo/gestionarActividades.php", opciones);
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

async function cargarActividades() {
    try {
        const resultado = await solicitarApi("GET");
        esAdministrador = resultado.rol === "admin";
        document.getElementById("thAcciones").classList.toggle("d-none", !esAdministrador);

        actividades = resultado.actividades;
        mostrarActividades();
        document.getElementById("contenedorTabla").classList.remove("d-none");
    } catch (error) {
        alert("Error al cargar las actividades: " + error.message);
    } finally {
        document.getElementById("cargandoActividades").classList.add("d-none");
    }
}

function mostrarActividades() {
    listaActividades.replaceChildren();
    if (actividades.length === 0) {
        const fila = document.createElement("tr");
        const celda = document.createElement("td");
        celda.colSpan = esAdministrador ? 5 : 4;
        celda.className = "text-center py-4 text-muted";
        celda.textContent = "No hay actividades disponibles";
        fila.append(celda);
        listaActividades.append(fila);
        return;
    }
    actividades.forEach((actividad) => {
        const fila = document.createElement("tr");
        fila.append(
            crearCelda(actividad.nombreActividad),
            crearCelda(actividad.fechaEvento),
            crearCelda(actividad.municipio),
            crearCelda(actividad.totalRecaudado === null ? "pendiente" : Number(actividad.totalRecaudado).toLocaleString("es-CO", { style: "currency", currency: "COP" }))
        );
        if (esAdministrador) {
            const acciones = document.createElement("td");
            acciones.className = "text-end text-nowrap";

            const botonEditar = document.createElement("button");
            botonEditar.type = "button";
            botonEditar.className = "btn btn-sm btn-outline-primary me-2";
            botonEditar.innerHTML = `<i class="bi bi-pencil-square"></i>`;
            botonEditar.addEventListener("click", () => abrirEdicion(actividad));

            const botonEliminar = document.createElement("button");
            botonEliminar.type = "button";
            botonEliminar.className = "btn btn-sm btn-outline-danger";
            botonEliminar.innerHTML = `<i class="bi bi-trash"></i>`;
            botonEliminar.addEventListener("click", () => eliminarActividad(actividad.idActividad));

            acciones.append(botonEditar, botonEliminar);
            fila.append(acciones);
        }
        listaActividades.append(fila);
    });
}

function crearCelda(texto) {
    const celda = document.createElement("td");
    celda.textContent = texto;
    return celda;
}

async function eliminarActividad(idActividad) {
    if (!confirm("¿Seguro que deseas eliminar esta actividad?")) {
        return;
    }
    try {
        await solicitarApi("DELETE", { idActividad: idActividad });
        await cargarActividades();
    } catch (error) {
        alert("Error al eliminar la actividad: " + error.message);
    }
}

function abrirEdicion(actividad) {
    const formulario = document.getElementById("formEdicion");
    formulario.idActividad.value = actividad.idActividad;
    formulario.nombreActividad.value = actividad.nombreActividad;
    formulario.fechaEvento.value = (actividad.fechaEvento ?? "").substring(0, 10);
    formulario.municipio.value = actividad.municipio;
    formulario.totalRecaudado.value = actividad.totalRecaudado ?? "";
    bootstrap.Modal.getOrCreateInstance(document.getElementById("modalEdicion")).show();
}

document.getElementById("formEdicion").addEventListener("submit", async (evento) => {
    evento.preventDefault();
    const formulario = evento.target;
    const datos = {
        idActividad: Number(formulario.idActividad.value),
        nombreActividad: formulario.nombreActividad.value.trim(),
        fechaEvento: formulario.fechaEvento.value,
        municipio: formulario.municipio.value.trim(),
        totalRecaudado: formulario.totalRecaudado.value === "" ? null : Number(formulario.totalRecaudado.value)
    };
    try {
        await solicitarApi("PUT", datos);
        bootstrap.Modal.getInstance(document.getElementById("modalEdicion")).hide();
        await cargarActividades();
    } catch (error) {
        alert("Error al actualizar la actividad: " + error.message);
    }
});

cargarActividades();

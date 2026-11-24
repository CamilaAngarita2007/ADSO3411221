const listaAsociados = document.getElementById("listaAsociados");
let asociados = [];

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
    const respuesta = await fetch("../Modelo/gestionarasociados.php", opciones);
    const resultado = await respuesta.json();

    if (respuesta.status === 401) {
        window.location.href = "../Vista/login.html";
        throw new Error("Sesión no iniciada");
    }
    if (respuesta.status === 403) {
        window.location.href = "dashboard.html";
        throw new Error(resultado.message || "Sin permisos");
    }
    if (!resultado.success) {
        throw new Error(resultado.message || "Error en la solicitud");
    }
    return resultado;
}

function formatearFecha(fecha) {
    return fecha ? fecha.substring(0, 10) : "";
}

async function cargarAsociados() {
    try {
        const resultado = await solicitarApi("GET");
        asociados = resultado.asociados;
        mostrarAsociados();
        document.getElementById("contenedorTabla").classList.remove("d-none");
    } catch (error) {
        alert("Error al cargar los asociados: " + error.message);
    } finally {
        document.getElementById("cargandoAsociados").classList.add("d-none");
    }
}

function mostrarAsociados() {
    listaAsociados.replaceChildren();
    if (asociados.length === 0) {
        const fila = document.createElement("tr");
        const celda = document.createElement("td");
        celda.colSpan = 7;
        celda.className = "text-center py-4 text-muted";
        celda.textContent = "No hay asociados registrados";
        fila.append(celda);
        listaAsociados.append(fila);
        return;
    }
    asociados.forEach((asociado) => {
        const fila = document.createElement("tr");

        const acciones = document.createElement("td");
        acciones.className = "text-end text-nowrap";

        const botonEditar = document.createElement("button");
        botonEditar.type = "button";
        botonEditar.className = "btn btn-sm btn-outline-primary me-2";
        botonEditar.innerHTML = `<i class="bi bi-pencil-square"></i>`;
        botonEditar.addEventListener("click", () => abrirEdicion(asociado));

        const botonEliminar = document.createElement("button");
        botonEliminar.type = "button";
        botonEliminar.className = "btn btn-sm btn-outline-danger";
        botonEliminar.innerHTML = `<i class="bi bi-trash"></i>`;
        botonEliminar.addEventListener("click", () => eliminarAsociado(asociado));

        acciones.append(botonEditar, botonEliminar);
        fila.append(
            crearCelda(asociado.documento),
            crearCelda(`${asociado.nombres} ${asociado.apellidos}`),
            crearCelda(asociado.ciudad),
            crearCelda(asociado.telefono),
            crearCelda(asociado.email),
            crearCelda(asociado.rol),
            acciones
        );
        listaAsociados.append(fila);
    });
}

function crearCelda(texto) {
    const celda = document.createElement("td");
    celda.textContent = texto;
    return celda;
}

async function eliminarAsociado(asociado) {
    if (!confirm(`¿Seguro que deseas eliminar a ${asociado.nombres} ${asociado.apellidos}?\nTambién se borrarán sus préstamos, abonos y solicitudes.`)) {
        return;
    }
    try {
        await solicitarApi("DELETE", { idAsociado: asociado.idAsociado });
        await cargarAsociados();
    } catch (error) {
        alert("Error al eliminar el asociado: " + error.message);
    }
}

function abrirEdicion(asociado) {
    const formulario = document.getElementById("formEdicion");
    formulario.idAsociado.value = asociado.idAsociado;
    document.getElementById("editDocumento").value = asociado.documento;
    formulario.rol.value = asociado.rol;
    formulario.nombres.value = asociado.nombres;
    formulario.apellidos.value = asociado.apellidos;
    formulario.fechaNacimiento.value = formatearFecha(asociado.fechaNacimiento);
    formulario.genero.value = asociado.genero;
    formulario.direccionResidencia.value = asociado.direccionResidencia;
    formulario.telefono.value = asociado.telefono;
    formulario.email.value = asociado.email;
    formulario.nuevaPassword.value = "";
    formulario.confirmarPassword.value = "";

    // Si la ciudad guardada no está en la lista, se agrega para no perder el dato
    const selectCiudad = formulario.ciudad;
    const existe = [...selectCiudad.options].some((opcion) => opcion.value === asociado.ciudad);
    if (!existe) {
        selectCiudad.append(new Option(asociado.ciudad, asociado.ciudad));
    }
    selectCiudad.value = asociado.ciudad;

    bootstrap.Modal.getOrCreateInstance(document.getElementById("modalEdicion")).show();
}

document.getElementById("formEdicion").addEventListener("submit", async (evento) => {
    evento.preventDefault();
    const formulario = evento.target;

    if (formulario.nuevaPassword.value !== formulario.confirmarPassword.value) {
        alert("Las contraseñas no coinciden");
        return;
    }

    const datos = {
        idAsociado: Number(formulario.idAsociado.value),
        rol: formulario.rol.value,
        nombres: formulario.nombres.value.trim(),
        apellidos: formulario.apellidos.value.trim(),
        fechaNacimiento: formulario.fechaNacimiento.value,
        genero: formulario.genero.value,
        direccionResidencia: formulario.direccionResidencia.value.trim(),
        ciudad: formulario.ciudad.value,
        telefono: formulario.telefono.value.trim(),
        email: formulario.email.value.trim(),
        nuevaPassword: formulario.nuevaPassword.value
    };

    try {
        await solicitarApi("PUT", datos);
        bootstrap.Modal.getInstance(document.getElementById("modalEdicion")).hide();
        await cargarAsociados();
    } catch (error) {
        alert("Error al actualizar el asociado: " + error.message);
    }
});

cargarAsociados();

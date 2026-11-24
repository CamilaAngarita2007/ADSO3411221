const listaPrestamosConSaldo = document.getElementById("listaPrestamosConSaldo");
const listaPagos = document.getElementById("listaPagos");
let esAdministrador = false;
let prestamosConSaldo = [];
let pagos = [];

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
    const respuesta = await fetch("../Modelo/gestionarpagos.php", opciones);
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

function formatearPesos(valor) {
    return Number(valor).toLocaleString("es-CO", { style: "currency", currency: "COP" });
}

async function cargarPagos() {
    try {
        const resultado = await solicitarApi("GET");
        esAdministrador = resultado.rol === "admin";

        // Mostrar la vista que corresponde al rol
        document.getElementById("tablaAsociado").classList.toggle("d-none", esAdministrador);
        document.getElementById("tablaAdministrador").classList.toggle("d-none", !esAdministrador);
        if (esAdministrador) {
            document.getElementById("tituloPagina").textContent = "Abonos registrados";
            document.getElementById("descripcionPagina").textContent = "Consulta, edita o elimina los abonos de los asociados.";
            pagos = resultado.pagos;
            mostrarPagos();
        } else {
            prestamosConSaldo = resultado.prestamos;
            mostrarPrestamosConSaldo();
        }
        document.getElementById("contenedorTabla").classList.remove("d-none");
    } catch (error) {
        alert("Error al cargar los pagos: " + error.message);
    } finally {
        document.getElementById("cargandoPagos").classList.add("d-none");
    }
}

function mostrarPrestamosConSaldo() {
    listaPrestamosConSaldo.replaceChildren();
    if (prestamosConSaldo.length === 0) {
        listaPrestamosConSaldo.append(filaVacia(3, "No tienes préstamos con deuda pendiente"));
        return;
    }
    prestamosConSaldo.forEach((prestamo) => {
        const fila = document.createElement("tr");
        const acciones = document.createElement("td");
        acciones.className = "text-end text-nowrap";

        const botonAbonar = document.createElement("a");
        botonAbonar.className = "btn btn-sm btn-outline-success";
        botonAbonar.href = `registroPago.html?idPrestamo=${prestamo.idPrestamo}`;
        botonAbonar.innerHTML = `<i class="bi bi-cash-coin"></i> Abonar`;

        acciones.append(botonAbonar);
        fila.append(
            crearCelda(prestamo.fechaPrestamo),
            crearCelda(formatearPesos(prestamo.valorPrestamo)),
            acciones
        );
        listaPrestamosConSaldo.append(fila);
    });
}

function mostrarPagos() {
    listaPagos.replaceChildren();
    if (pagos.length === 0) {
        listaPagos.append(filaVacia(5, "No hay abonos registrados"));
        return;
    }
    pagos.forEach((pago) => {
        const fila = document.createElement("tr");
        const acciones = document.createElement("td");
        acciones.className = "text-end text-nowrap";

        const botonEditar = document.createElement("button");
        botonEditar.type = "button";
        botonEditar.className = "btn btn-sm btn-outline-primary me-2";
        botonEditar.innerHTML = `<i class="bi bi-pencil-square"></i>`;
        botonEditar.addEventListener("click", () => abrirEdicion(pago));

        const botonEliminar = document.createElement("button");
        botonEliminar.type = "button";
        botonEliminar.className = "btn btn-sm btn-outline-danger";
        botonEliminar.innerHTML = `<i class="bi bi-trash"></i>`;
        botonEliminar.addEventListener("click", () => eliminarPago(pago.idPago));

        acciones.append(botonEditar, botonEliminar);
        fila.append(
            crearCelda(pago.nombreAsociado),
            crearCelda("#" + pago.idPrestamo),
            crearCelda(pago.fechaPago),
            crearCelda(formatearPesos(pago.valorCuota)),
            acciones
        );
        listaPagos.append(fila);
    });
}

function filaVacia(columnas, texto) {
    const fila = document.createElement("tr");
    const celda = document.createElement("td");
    celda.colSpan = columnas;
    celda.className = "text-center py-4 text-muted";
    celda.textContent = texto;
    fila.append(celda);
    return fila;
}

function crearCelda(contenido) {
    const celda = document.createElement("td");
    if (contenido instanceof Node) {
        celda.append(contenido);
    } else {
        celda.textContent = contenido;
    }
    return celda;
}

async function eliminarPago(idPago) {
    if (!confirm("¿Seguro que deseas eliminar este abono? El saldo del préstamo volverá a subir.")) {
        return;
    }
    try {
        await solicitarApi("DELETE", { idPago: idPago });
        await cargarPagos();
    } catch (error) {
        alert("Error al eliminar el abono: " + error.message);
    }
}

function abrirEdicion(pago) {
    const formulario = document.getElementById("formEdicion");
    formulario.idPago.value = pago.idPago;
    formulario.valorCuota.value = pago.valorCuota;
    formulario.fechaPago.value = (pago.fechaPago ?? "").substring(0, 10);
    bootstrap.Modal.getOrCreateInstance(document.getElementById("modalEdicion")).show();
}

document.getElementById("formEdicion").addEventListener("submit", async (evento) => {
    evento.preventDefault();
    const formulario = evento.target;
    const datos = {
        idPago: Number(formulario.idPago.value),
        valorCuota: Number(formulario.valorCuota.value),
        fechaPago: formulario.fechaPago.value
    };
    try {
        await solicitarApi("PUT", datos);
        bootstrap.Modal.getInstance(document.getElementById("modalEdicion")).hide();
        await cargarPagos();
    } catch (error) {
        alert("Error al actualizar el abono: " + error.message);
    }
});

cargarPagos();

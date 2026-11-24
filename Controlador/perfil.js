let perfil = null;

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
    const respuesta = await fetch("../Modelo/gestionarperfil.php", opciones);
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

function formatearFecha(fecha) {
    return fecha ? fecha.substring(0, 10) : "";
}

function mostrarPerfil() {
    document.getElementById("verDocumento").textContent = perfil.documento;
    document.getElementById("verNombres").textContent = perfil.nombres;
    document.getElementById("verApellidos").textContent = perfil.apellidos;
    document.getElementById("verFechaNacimiento").textContent = formatearFecha(perfil.fechaNacimiento);
    document.getElementById("verGenero").textContent = perfil.genero;
    document.getElementById("verDireccion").textContent = perfil.direccionResidencia;
    document.getElementById("verCiudad").textContent = perfil.ciudad;
    document.getElementById("verTelefono").textContent = perfil.telefono;
    document.getElementById("verEmail").textContent = perfil.email;
    document.getElementById("verRol").textContent = perfil.rol;
}

async function cargarPerfil() {
    try {
        const resultado = await solicitarApi("GET");
        perfil = resultado.perfil;
        mostrarPerfil();
        document.getElementById("contenedorPerfil").classList.remove("d-none");
        document.getElementById("btnEditarPerfil").classList.remove("d-none");
    } catch (error) {
        alert("Error al cargar el perfil: " + error.message);
    } finally {
        document.getElementById("cargandoPerfil").classList.add("d-none");
    }
}

function abrirEdicion() {
    const formulario = document.getElementById("formEdicion");
    formulario.nombres.value = perfil.nombres;
    formulario.apellidos.value = perfil.apellidos;
    formulario.fechaNacimiento.value = formatearFecha(perfil.fechaNacimiento);
    formulario.genero.value = perfil.genero;
    formulario.direccionResidencia.value = perfil.direccionResidencia;
    formulario.telefono.value = perfil.telefono;
    formulario.email.value = perfil.email;
    formulario.nuevaPassword.value = "";
    formulario.confirmarPassword.value = "";

    // Si la ciudad guardada no está en la lista, se agrega para no perder el dato
    const selectCiudad = formulario.ciudad;
    const existe = [...selectCiudad.options].some((opcion) => opcion.value === perfil.ciudad);
    if (!existe) {
        selectCiudad.append(new Option(perfil.ciudad, perfil.ciudad));
    }
    selectCiudad.value = perfil.ciudad;

    bootstrap.Modal.getOrCreateInstance(document.getElementById("modalEdicion")).show();
}

document.getElementById("btnEditarPerfil").addEventListener("click", abrirEdicion);

document.getElementById("formEdicion").addEventListener("submit", async (evento) => {
    evento.preventDefault();
    const formulario = evento.target;

    if (formulario.nuevaPassword.value !== formulario.confirmarPassword.value) {
        alert("Las contraseñas no coinciden");
        return;
    }

    const datos = {
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
        const resultado = await solicitarApi("PUT", datos);
        // Actualiza el nombre que se muestra en el encabezado de todas las vistas
        localStorage.setItem("nombre", `${datos.nombres} ${datos.apellidos}`);
        bootstrap.Modal.getInstance(document.getElementById("modalEdicion")).hide();
        alert(resultado.message);
        await cargarPerfil();
    } catch (error) {
        alert("Error al actualizar el perfil: " + error.message);
    }
});

cargarPerfil();

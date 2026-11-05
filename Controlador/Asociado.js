const formulario = document.getElementById("formPerfil");
formulario.addEventListener("submit", async function (event) {
    event.preventDefault();
    const BnGuardar = formulario.querySelector("button[type='submit']");
    //Lee los campos de los datos y los convierte en objetos
    const perfil = Object.fromEntries(new FormData(formulario).entries());
    BnGuardar.disabled = true; //deshabilitar el boton mientras se procesa la solicitud
    document.getElementById("mensajeRegistro").textContent = "Guardando...";
    document.getElementById("mensajeRegistro").dataset.tipo = '';
    try {
        const respuesta = await fetch(formulario.action, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(perfil) });
        //Pasar la respuesta JSON a un objeto de Javascript 
        const resultado = await respuesta.json();
        document.getElementById("mensajeRegistro").textContent = resultado.message;
        if (respuesta.ok && resultado.success) {
            formulario.reset();
        }
    } catch (error) {
        console.error('Error al enviar el formulario:', error);
        document.getElementById("mensajeRegistro").textContent = "Error al registrar el perfil. Por favor, inténtelo de nuevo.";
        document.getElementById("mensajeRegistro").dataset.tipo = 'error';
    } finally {
        BnGuardar.disabled = false; //habilitar el boton después de procesar la solicitud
    }
});

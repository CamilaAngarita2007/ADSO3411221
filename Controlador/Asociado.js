const formulario = document.getElementById("formPerfil");
formulario.addEventListener("submit", async function (event) {
    event.preventDefault();
    const mensaje = document.getElementById("mensajeRegistro");
    const BnGuardar = formulario.querySelector("button[type='submit']");
    //Lee los campos de los datos y los convierte en objetos
    const perfil = Object.fromEntries(new FormData(formulario).entries());

    //Validar que las contraseñas coincidan antes de enviar
    if (perfil.passwordHash !== perfil.confirmarPassword) {
        mensaje.textContent = "Las contraseñas no coinciden.";
        mensaje.dataset.tipo = 'error';
        return;
    }
    delete perfil.confirmarPassword;

    BnGuardar.disabled = true; //deshabilitar el boton mientras se procesa la solicitud
    mensaje.textContent = "Guardando...";
    mensaje.dataset.tipo = '';
    try {
        const respuesta = await fetch(formulario.action, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(perfil) });
        //Pasar la respuesta JSON a un objeto de Javascript
        const resultado = await respuesta.json();
        mensaje.textContent = resultado.message;
        if (respuesta.ok && resultado.success) {
            formulario.reset();
        } else {
            mensaje.dataset.tipo = 'error';
        }
    } catch (error) {
        console.error('Error al enviar el formulario:', error);
        mensaje.textContent = "Error al registrar el perfil. Por favor, inténtelo de nuevo.";
        mensaje.dataset.tipo = 'error';
    } finally {
        BnGuardar.disabled = false; //habilitar el boton después de procesar la solicitud
    }
});

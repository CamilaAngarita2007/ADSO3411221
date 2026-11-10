const formulario = document.getElementById("formLogin");
formulario.addEventListener("submit", async function (event) {
    event.preventDefault();
    const boton = formulario.querySelector("button[type='submit']");
    const mensaje = formulario.getElementById("mensajeLogin");
    boton.disabled = true;

    try {
        const respuesta = await fetch(formulario.action, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(Object.fromEntries(new FormData(formulario).entries()))
        });
        const resultado = await respuesta.json()
        if(!resultado.succes){
            mensaje.textContent = "No fue posible iniciar sesion. Por favor, verifica tus credenciales"
            boton.disabled= false;
            return;
        }
        localStorage.setItem('logueado', 'true');
        localStorage.setItem('rol', resultado.rol);
        localStorage.setItem('nombre', resultado.nombre);

        window.location.href = "dashboard.html";

    } catch (error){
        mensaje.textContent = "Error al iniciar sesión. Por favor, inténtelo de nuevo.";
     } finally {
        boton.disabled= false;
     }
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

    } catch { }




    const emailInput = document.getElementById("email").value;
    const passwordInput = document.getElementById("password").value;

    const usuarioencontrado = usuarios.find(
        u => u.email === emailInput && u.password === passwordInput);

    if (usuarioencontrado) {
        localStorage.setItem('logueado', 'true');
        localStorage.setItem('rol', usuarioencontrado.rol);
        localStorage.setItem('nombre', usuarioencontrado.nombre);
        window.location.href = "dashboard.html";
    } else {
        alert("Usuario o contraseña incorrectas");
    }
});
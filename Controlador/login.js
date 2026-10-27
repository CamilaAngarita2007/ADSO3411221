const usuarios = [
    { email: "admin@gmail.com", password: "1234", rol: "admin", nombre: "Camila" },
    { email: "user1@gmail.com", password: "1234", rol: "asociado", nombre: "Juan" }
];

document.getElementById("formLogin").addEventListener("submit", function (event) {
    event.preventDefault();
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
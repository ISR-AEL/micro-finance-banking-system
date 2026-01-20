document.addEventListener("DOMContentLoaded", function() {
    const togglePwd = document.getElementById("togglePwd");
    const password = document.getElementById("password");

    togglePwd.addEventListener("click", function() {
        if (password.type === "password") {
            password.type = "text";
            togglePwd.innerHTML = '<i class="fa-regular fa-eye-slash"></i>';
        } else {
            password.type = "password";
            togglePwd.innerHTML = '<i class="fa-regular fa-eye"></i>';
        }
    });
});

document.addEventListener("DOMContentLoaded", () => {

    const form = document.getElementById("cpLoginForm");

    if (!form) return;

    form.addEventListener("submit", () => {

        document.getElementById("loginBtn").style.display = "none";

        document.getElementById("loginLoading").style.display = "block";

    });

});
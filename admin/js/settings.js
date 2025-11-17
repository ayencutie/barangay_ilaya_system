document.addEventListener("DOMContentLoaded", () => {

    // DARK MODE
    const darkToggle = document.getElementById("darkToggle");

    darkToggle.addEventListener("change", () => {
        const mode = darkToggle.checked ? 1 : 0;

        document.body.classList.toggle("dark", mode === 1);

        fetch("update_darkmode.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `dark_mode=${mode}`
        });
    });

    // CHANGE PASSWORD
    const form = document.getElementById("changePassForm");

    form.addEventListener("submit", e => {
        e.preventDefault();

        let cur = document.getElementById("currentPass").value;
        let newp = document.getElementById("newPass").value;

        fetch("update_password.php", {
            method: "POST",
            headers: {"Content-Type": "application/x-www-form-urlencoded"},
            body: `current=${cur}&new=${newp}`
        })
        .then(r => r.text())
        .then(msg => {
            document.getElementById("passMessage").textContent = msg;
        });
    });
});

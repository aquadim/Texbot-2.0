const formSettings = document.getElementById("settings");
const inpDateStart = document.getElementById("dateStart");
const inpDateEnd = document.getElementById("dateEnd");

formSettings.addEventListener('submit', (e) => {
    const fd = new FormData(formSettings);
    fetch("/api/functions.php", {
        method: 'POST',
        body: fd
    });
    e.preventDefault();
});
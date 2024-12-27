const formSettings = document.getElementById("settings");
const dataArea = document.getElementById("dataArea");

let currentGroupId;

// Названия
// {id: название}
const functionNames = new Map();
const groupNames = new Map();

formSettings.addEventListener('submit', (e) => {
    const fd = new FormData(formSettings);
    currentGroupId = fd.get("groupId");
    fetch("/api/functions.php", {
        method: 'POST',
        body: fd
    }).then(async function(r) {
        const data = await r.json();
        updateChart(data);
    });
    e.preventDefault();
});

function updateChart(data) {
    const allTraces = [];
    const layout = {
        title: {
            text: "Использование функций группой " + groupNames.get(currentGroupId)
        }
    };

    const config = {
        scrollZoom: true,
        locale: 'ru',
        responsive: true
    }

    // Проход по всем функциям
    Object.keys(data).forEach(fnId => {
        const info = data[fnId];
        
        const functionName = functionNames.get(fnId);
        const xValues = [];
        const yValues = [];

        // Проход по всем датам
        Object.keys(info).forEach(dateKey => {
            const dateObj = new Date(dateKey);
            const count = info[dateKey];

            xValues.push(dateObj);
            yValues.push(count);
        });

        const trace = {
            type: 'scatter',
            x: xValues,
            y: yValues,
            mode: 'lines+markers',
            name: functionName
        };

        allTraces.push(trace);
    });

    Plotly.newPlot(dataArea, allTraces, layout, config);
}

// Сбор названий функций
fetch("/api/function-types.php", {
    method: "POST"
}).then(async function(r) {
    const response = await r.json();
    response.forEach(i => {
       functionNames.set(i.id.toString(), i.name);
    });
});

// Сбор всех групп
fetch("/api/all-groups.php", {
    method: "POST"
}).then(async function(r) {
    const data = await r.json();
    const groupSelect = document.getElementById("groupId");
    data.forEach(g => {
        groupNames.set(g.id.toString(), g.name);
        
        const opt = document.createElement("option");
        opt.textContent = g.name;
        opt.value = g.id;
        groupSelect.append(opt);
    });
});

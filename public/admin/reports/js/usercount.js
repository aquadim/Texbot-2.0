fetch("/api/usercount.php").then(r =>
{
    r.json().then(data =>
    {
        const table = document.createElement("table");
        table.classList.add("table");

        // Заголовки
        const thead = document.createElement("thead");
        const theadRow = document.createElement("tr");
        const columnNames = [
            "Группа",
            "Зарегистрировано в vk",
            "Зарегистрировано в telegram",
            "Всего"
        ];
        columnNames.forEach(colName => {
            const col = document.createElement("th");
            col.scope = "col";
            col.textContent = colName;
            theadRow.append(col);
        });
        thead.append(theadRow);

        // Строки
        const tbody = document.createElement("tbody");
        data.forEach(dataRow =>
        {
            const row = document.createElement("tr");

            // Название группы
            let td1 = document.createElement("td");
            td1.textContent = dataRow.group_name;
            row.append(td1);
            
            // ВК
            let td2 = document.createElement("td");
            td2.textContent = dataRow.vkcom;
            row.append(td2);
            
            // Telegram
            let td3 = document.createElement("td");
            td3.textContent = dataRow.telegramorg;
            row.append(td3);
            
            // Всего
            let td4 = document.createElement("td");
            td4.textContent = dataRow.vkcom + dataRow.telegramorg;
            row.append(td4);

            tbody.append(row);
        });

        table.append(thead);
        table.append(tbody);
        document.getElementById("tableArea").append(table);
    });
});


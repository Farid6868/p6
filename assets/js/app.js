function advancedLiveSearch(inputId, tableId, modeSelectId = null) {
    const input = document.getElementById(inputId);
    const table = document.getElementById(tableId);
    const rows = table.querySelectorAll("tbody tr");
    const modeSelect = modeSelectId ? document.getElementById(modeSelectId) : null;

    input.addEventListener("keyup", filter);
    if (modeSelect) modeSelect.addEventListener("change", filter);

    function filter() {
        const value = input.value.toLowerCase();
        const mode = modeSelect ? modeSelect.value : "all";

        rows.forEach(row => {
            let text = "";

            if (mode === "all") {
                text = row.innerText.toLowerCase();
            } else {
                const cell = row.querySelector(`[data-field="${mode}"]`);
                text = cell ? cell.innerText.toLowerCase() : "";
            }

            row.style.display = text.includes(value) ? "" : "none";
        });
    }
}

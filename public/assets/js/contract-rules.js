function initContractDateValidation(selector) {
    const input = document.querySelector(selector);
    if (!input) return;

    function isSunday(date) {
        return date.getDay() === 0;
    }

    function getLastWorkingDays(year, month, count = 4) {
        let date = new Date(year, month + 1, 0);
        const blocked = [];

        while (blocked.length < count) {
            if (!isSunday(date)) {
                blocked.push(formatDate(date));
            }
            date.setDate(date.getDate() - 1);
        }

        return blocked;
    }

    function formatDate(date) {
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const d = String(date.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    }

    function validateDate() {

        if (!input.value) return;

        const selected = new Date(input.value + "T00:00:00");

        const today = new Date();
        today.setHours(0,0,0,0);

        // Rule 1: Only last 2 days allowed in past
        const minAllowed = new Date(today);
        minAllowed.setDate(today.getDate() - 2);

        if (selected < minAllowed) {
            alert("You can select only up to last 2 days in past.");
            input.value = '';
            return;
        }

        // Rule 2: Block last 4 working days
        const blockedDates = getLastWorkingDays(
            selected.getFullYear(),
            selected.getMonth()
        );

        const selectedFormatted = formatDate(selected);

        if (blockedDates.includes(selectedFormatted)) {
            alert("Last 4 working days of the month are not allowed.");
            input.value = '';
            return;
        }
    }

    // Trigger validation
    input.addEventListener("change", validateDate);
    input.addEventListener("input", validateDate);
}
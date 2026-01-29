function initDOJValidation(selector) {
    const dojInput = document.querySelector(selector);
    if (!dojInput) return;

    // Only Sunday is off
    function isSunday(date) {
        return date.getDay() === 0;
    }

    // Get last 4 working days (Saturday working)
    function getLastWorkingDays(year, month, count = 4) {
        let date = new Date(year, month + 1, 0);
        const blocked = [];

        while (blocked.length < count) {
            if (!isSunday(date)) {
                const y = date.getFullYear();
                const m = String(date.getMonth() + 1).padStart(2, '0');
                const d = String(date.getDate()).padStart(2, '0');
                blocked.push(`${y}-${m}-${d}`);
            }
            date.setDate(date.getDate() - 1);
        }

        return blocked;
    }

    dojInput.addEventListener("change", function () {
        if (!this.value) return;

        const selected = new Date(this.value + "T00:00:00");
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        // Rule 1: Only last 2 days allowed in past
        const minAllowed = new Date(today);
        minAllowed.setDate(today.getDate() - 2);

        if (selected < minAllowed) {
            alert("You can select only up to last 2 days in past.");
            this.value = '';
            return;
        }

        // Rule 2: Block last 4 working days
        const blockedDates = getLastWorkingDays(
            selected.getFullYear(),
            selected.getMonth()
        );

        const formatted =
            selected.getFullYear() + '-' +
            String(selected.getMonth() + 1).padStart(2, '0') + '-' +
            String(selected.getDate()).padStart(2, '0');

        if (blockedDates.includes(formatted)) {
            alert("Last 4 working days of the month are not allowed.");
            this.value = '';
            return;
        }
    });
}
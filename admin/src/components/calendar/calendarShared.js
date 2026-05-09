export const DAY_MODE_START_HOUR = 0;
export const DAY_MODE_END_HOUR = 23;

export function getHourValue(time = "00:00") {
    const [hour = "0"] = String(time).split(":");

    return Number(hour);
}

export function getEventSpanHours(event) {
    const startHour = getHourValue(event.startTime);
    const endHour = getHourValue(event.endTime || event.startTime);

    return Math.max(1, endHour - startHour);
}

export function buildDayModeCells(events, getKey = (event) => event.id) {
    const cells = [];
    const sortedEvents = [...events].sort((left, right) =>
        left.startTime.localeCompare(right.startTime),
    );
    let currentHour = DAY_MODE_START_HOUR;

    sortedEvents.forEach((event, index) => {
        const startHour = getHourValue(event.startTime);
        const spanHours = getEventSpanHours(event);

        if (startHour > currentHour) {
            cells.push({
                key: `empty-${currentHour}-${index}`,
                colspan: startHour - currentHour,
                event: null,
            });
        }

        cells.push({
            key: `event-${getKey(event)}`,
            colspan: spanHours,
            event,
        });

        currentHour = startHour + spanHours;
    });

    if (currentHour <= DAY_MODE_END_HOUR) {
        cells.push({
            key: `empty-${currentHour}-end`,
            colspan: DAY_MODE_END_HOUR + 1 - currentHour,
            event: null,
        });
    }

    return cells;
}

export function formatTimeRange(event) {
    return `${event.startTime} - ${event.endTime || event.startTime}`;
}

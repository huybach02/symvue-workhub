import i18n from "@/plugins/i18n";

export const DAY_MODE_START_HOUR = 0;
export const DAY_MODE_END_HOUR = 23;

export function getHourValue(time = "00:00") {
    const [hour = "0"] = String(time).split(":");

    return Number(hour);
}

export function isOvernightRange(startTime, endTime) {
    if (!startTime || !endTime) {
        return false;
    }

    if (!/^\d{2}:\d{2}/.test(startTime) || !/^\d{2}:\d{2}/.test(endTime)) {
        return false;
    }

    return endTime <= startTime;
}

export function isOvernightEvent(event) {
    return isOvernightRange(event?.startTime, event?.endTime);
}

function addDays(dateValue, amount) {
    const date = new Date(`${dateValue}T00:00:00`);

    if (Number.isNaN(date.getTime())) {
        return "";
    }

    date.setDate(date.getDate() + amount);

    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, "0");
    const day = String(date.getDate()).padStart(2, "0");

    return `${year}-${month}-${day}`;
}

export function isEventContinuationOnDate(event, dateValue) {
    return Boolean(
        event?.date &&
            dateValue &&
            isOvernightEvent(event) &&
            addDays(event.date, 1) === dateValue,
    );
}

export function isEventVisibleOnDate(event, dateValue) {
    return event?.date === dateValue || isEventContinuationOnDate(event, dateValue);
}

export function buildCalendarEventForDate(event, dateValue) {
    if (!event || !dateValue) {
        return event;
    }

    if (isEventContinuationOnDate(event, dateValue)) {
        return {
            ...event,
            calendarDate: dateValue,
            calendarStartTime: "00:00",
            calendarEndTime: event.endTime,
            calendarSegment: "continuation",
        };
    }

    if (event.date === dateValue) {
        return {
            ...event,
            calendarDate: dateValue,
            calendarStartTime: event.startTime,
            calendarEndTime: isOvernightEvent(event) ? "24:00" : event.endTime,
            calendarSegment: isOvernightEvent(event) ? "start" : "full",
        };
    }

    return event;
}

export function getCalendarEventsForDate(events, dateValue) {
    return events
        .filter((event) => isEventVisibleOnDate(event, dateValue))
        .map((event) => buildCalendarEventForDate(event, dateValue));
}

function getCellStartTime(event) {
    return event?.calendarStartTime || event?.startTime || "00:00";
}

function getCellEndTime(event) {
    return event?.calendarEndTime || event?.endTime || event?.startTime || "00:00";
}

export function getEventSpanHours(event) {
    const startHour = getHourValue(getCellStartTime(event));
    const endHour = getHourValue(getCellEndTime(event));

    return Math.max(1, endHour - startHour);
}

export function buildDayModeCells(events, getKey = (event) => event.id) {
    const cells = [];
    const sortedEvents = [...events].sort((left, right) =>
        getCellStartTime(left).localeCompare(getCellStartTime(right)),
    );
    let currentHour = DAY_MODE_START_HOUR;

    sortedEvents.forEach((event, index) => {
        const startHour = getHourValue(getCellStartTime(event));
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

export function formatTimeRangeFromValues(startTime, endTime) {
    const resolvedStartTime = startTime || "--:--";
    const resolvedEndTime = endTime || resolvedStartTime;
    const nextDayLabel = isOvernightRange(resolvedStartTime, resolvedEndTime)
        ? ` (${i18n.global.t("thoi_gian_lam_viec.overnight_next_day_suffix")})`
        : "";

    return `${resolvedStartTime} - ${resolvedEndTime}${nextDayLabel}`;
}

export function formatTimeRange(event) {
    return formatTimeRangeFromValues(event?.startTime, event?.endTime);
}

export function formatCalendarEventTitle(event) {
    if (event?.calendarSegment === "continuation") {
        return i18n.global.t(
            "thoi_gian_lam_viec.overnight_continuation_until",
            {
                time: event.endTime || "--:--",
            },
        );
    }

    return formatTimeRange(event);
}

export function getCalendarSegmentClass(event) {
    if (event?.calendarSegment === "continuation") {
        return "calendar-chip-continuation";
    }

    if (event?.calendarSegment === "start") {
        return "calendar-chip-overnight-start";
    }

    return "";
}

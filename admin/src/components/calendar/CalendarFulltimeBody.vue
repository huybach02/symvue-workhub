<template>
    <tbody v-if="loading">
        <tr>
            <td
                :colspan="calendarColumns.length + 1"
                class="calendar-cell calendar-loading-cell"
            >
                <div class="calendar-loading-wrap">
                    <v-progress-circular
                        indeterminate
                        color="primary"
                        size="36"
                    />
                </div>
            </td>
        </tr>
    </tbody>

    <tbody v-else-if="currentMode !== 'day'">
        <tr v-for="user in users" :key="user.id">
            <td class="sticky-col-left user-cell">
                <div class="d-flex align-center justify-space-between">
                    <v-list-item :title="user.name" class="px-0 flex-grow-1">
                        <template #prepend>
                            <v-avatar v-if="user.image" :image="user.image" />
                            <v-avatar v-else icon="mdi-account" />
                        </template>
                    </v-list-item>
                    <v-menu
                        v-if="hasUserFixedSchedule(user)"
                        :close-on-content-click="true"
                        location="top"
                    >
                        <template #activator="{ props }">
                            <v-btn
                                v-bind="props"
                                icon="mdi-dots-vertical"
                                variant="text"
                                density="compact"
                            />
                        </template>
                        <v-list density="compact">
                            <v-list-item
                                prepend-icon="mdi-calendar-plus"
                                :title="$t('work_schedule.add_override_schedule')"
                                @click="$emit('addOverride', user)"
                            />
                            <v-list-item
                                prepend-icon="mdi-calendar-remove"
                                :title="$t('work_schedule.delete_override_schedule')"
                                class="text-error"
                                @click="$emit('clearSchedule', user)"
                            />
                        </v-list>
                    </v-menu>
                </div>
            </td>

            <td
                v-for="col in calendarColumns"
                :key="col.value"
                :title="col.holidayName || null"
                class="calendar-cell text-center"
                :class="{
                    'holiday-cell': col.isHoliday,
                    'weekend-cell': col.isWeekend && !col.isHoliday,
                    'today-cell': col.isToday,
                }"
            >
                <div class="events-container">
                    <v-chip
                        v-if="col.isHoliday"
                        size="x-small"
                        color="red"
                        text-color="red-darken-3"
                        class="ma-1 font-weight-bold holiday-tag"
                        variant="flat"
                    >
                        {{ col.holidayName || "Nghỉ lễ" }}
                    </v-chip>

                    <v-chip
                        v-for="event in getEvents(user.id, col.value)"
                        :key="getEventKey(event)"
                        :color="event.color"
                        size="small"
                        :class="[
                            'ma-1 w-100 justify-center font-weight-bold calendar-segment-chip',
                            getCalendarSegmentClass(event),
                        ]"
                        variant="flat"
                    >
                        <v-icon v-if="isOvernightEvent(event)" size="x-small" class="mr-1">mdi-weather-night</v-icon>
                        {{ formatEventTitle(event) }}
                    </v-chip>
                </div>
            </td>
        </tr>
    </tbody>

    <tbody v-else>
        <tr v-for="user in users" :key="user.id">
            <td class="sticky-col-left user-cell">
                <div class="d-flex align-center justify-space-between">
                    <v-list-item :title="user.name" class="px-0 flex-grow-1">
                        <template #prepend>
                            <v-avatar v-if="user.image" :image="user.image" />
                            <v-avatar v-else icon="mdi-account" />
                        </template>
                    </v-list-item>
                    <v-menu
                        v-if="hasUserFixedSchedule(user)"
                        :close-on-content-click="true"
                        location="top"
                    >
                        <template #activator="{ props }">
                            <v-btn
                                v-bind="props"
                                icon="mdi-dots-vertical"
                                variant="text"
                                density="compact"
                            />
                        </template>
                        <v-list density="compact">
                            <v-list-item
                                prepend-icon="mdi-calendar-plus"
                                :title="$t('work_schedule.add_override_schedule')"
                                @click="$emit('addOverride', user)"
                            />
                            <v-list-item
                                prepend-icon="mdi-calendar-remove"
                                :title="$t('work_schedule.delete_override_schedule')"
                                class="text-error"
                                @click="$emit('clearSchedule', user)"
                            />
                        </v-list>
                    </v-menu>
                </div>
            </td>

            <td
                v-for="cell in getDayModeRowCells(user.id)"
                :key="cell.key"
                class="calendar-cell text-center day-mode-cell"
                :colspan="cell.colspan"
            >
                <div v-if="cell.event" class="day-mode-event-wrapper">
                    <v-chip
                        :color="cell.event.color"
                        size="small"
                        :class="[
                            'w-100 justify-center font-weight-bold day-mode-chip calendar-segment-chip',
                            getCalendarSegmentClass(cell.event),
                        ]"
                        variant="flat"
                    >
                        <v-icon v-if="isOvernightEvent(cell.event)" size="x-small" class="mr-1">mdi-weather-night</v-icon>
                        {{ formatEventTitle(cell.event) }}
                    </v-chip>
                </div>
            </td>
        </tr>
    </tbody>
</template>

<script>
import {
    buildDayModeCells,
    formatCalendarEventTitle,
    getCalendarSegmentClass,
    getCalendarEventsForDate,
    isOvernightEvent,
} from "./calendarShared";

export default {
    name: "CalendarFulltimeBody",
    props: {
        users: {
            type: Array,
            default: () => [],
        },
        events: {
            type: Array,
            default: () => [],
        },
        calendarColumns: {
            type: Array,
            default: () => [],
        },
        currentMode: {
            type: String,
            default: "week",
        },
        baseDate: {
            type: Object,
            required: true,
        },
    },
    emits: ["addOverride", "clearSchedule"],
    computed: {
        loading() {
            return this.$store.getters["workSchedule/fulltimeLoading"];
        },
    },
    methods: {
        hasUserFixedSchedule(user) {
            return this.events.some(
                (event) =>
                    event.source === "fixed_schedule" &&
                    event.user_id === user.id,
            );
        },
        getDayModeEvents(userId) {
            return getCalendarEventsForDate(
                this.events.filter(
                    (event) =>
                        event.user_id === userId &&
                        event.startTime &&
                        event.endTime,
                ),
                this.baseDate.format("YYYY-MM-DD"),
            );
        },
        getDayModeRowCells(userId) {
            return buildDayModeCells(
                this.getDayModeEvents(userId),
                (event) => `${userId}-${this.getEventKey(event)}`,
            );
        },
        getEvents(userId, colValue) {
            return getCalendarEventsForDate(
                this.events.filter((event) => event.user_id === userId),
                colValue,
            );
        },
        getEventKey(event) {
            return `${event.id}-${event.calendarDate || event.date}-${event.calendarSegment || "event"}`;
        },
        formatEventTitle(event) {
            if (event.startTime && event.endTime) {
                return formatCalendarEventTitle(event);
            }

            return event.title || "";
        },
        getCalendarSegmentClass,
        isOvernightEvent,
    },
};
</script>

<style scoped>
.calendar-loading-cell {
    padding: 32px 16px !important;
}

.calendar-loading-wrap {
    min-height: 160px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.calendar-segment-chip {
    position: relative;
    overflow: hidden;
}

.calendar-chip-overnight-start {
    border-top-right-radius: 6px !important;
    border-bottom-right-radius: 6px !important;
}

.calendar-chip-continuation {
    border-top-left-radius: 6px !important;
    border-bottom-left-radius: 6px !important;
}

.calendar-chip-overnight-start::after,
.calendar-chip-continuation::before {
    content: "";
    position: absolute;
    top: 0;
    bottom: 0;
    width: 5px;
    background: rgba(255, 255, 255, 0.75);
}

.calendar-chip-overnight-start::after {
    right: 0;
    box-shadow: -6px 0 12px rgba(255, 255, 255, 0.2);
}

.calendar-chip-continuation::before {
    left: 0;
    box-shadow: 6px 0 12px rgba(255, 255, 255, 0.2);
}
</style>

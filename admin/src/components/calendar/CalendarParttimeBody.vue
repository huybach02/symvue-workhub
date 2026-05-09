<template>
    <tbody v-if="currentMode !== 'day'">
        <tr>
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
                <div class="events-container parttime-events-container">
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
                        v-for="shift in getShiftsByDate(col.value)"
                        :key="shift.id"
                        :color="shift.color || DEFAULT_SHIFT_COLOR"
                        size="small"
                        class="ma-1 w-100 justify-center font-weight-bold parttime-chip"
                        variant="flat"
                        @click="$emit('shiftSelected', shift)"
                    >
                        {{ formatShiftLabel(shift) }}
                    </v-chip>
                </div>
            </td>
        </tr>
    </tbody>

    <tbody v-else-if="dayModeShifts.length">
        <tr v-for="shift in dayModeShifts" :key="shift.id">
            <td
                v-for="cell in getDayModeRowCells(shift)"
                :key="cell.key"
                class="calendar-cell text-center day-mode-cell"
                :colspan="cell.colspan"
            >
                <div v-if="cell.event" class="day-mode-event-wrapper">
                    <v-chip
                        :color="cell.event.color || DEFAULT_SHIFT_COLOR"
                        size="small"
                        class="w-100 justify-center font-weight-bold day-mode-chip day-mode-shift-chip"
                        variant="flat"
                        @click="$emit('shiftSelected', cell.event)"
                    >
                        {{ formatShiftLabel(cell.event) }}
                    </v-chip>
                </div>
            </td>
        </tr>
    </tbody>

    <tbody v-else>
        <tr>
            <td
                :colspan="calendarColumns.length"
                class="calendar-cell text-center empty-parttime-cell"
            >
                Chưa có ca làm việc trong ngày này
            </td>
        </tr>
    </tbody>
</template>

<script>
import {
    buildDayModeCells,
    formatTimeRange,
} from "./calendarShared";

const DEFAULT_SHIFT_COLOR = "#2e7d32";

export default {
    name: "CalendarParttimeBody",
    props: {
        shifts: {
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
    emits: ["shiftSelected"],
    computed: {
        dayModeShifts() {
            return this.getShiftsByDate(this.baseDate.format("YYYY-MM-DD"));
        },
    },
    methods: {
        formatShiftLabel(shift) {
            return `${shift.title}: ${formatTimeRange(shift)}`;
        },
        getShiftsByDate(date) {
            return this.shifts
                .filter((shift) => shift.date === date)
                .sort((left, right) =>
                    left.startTime.localeCompare(right.startTime),
                );
        },
        getDayModeRowCells(shift) {
            return buildDayModeCells([shift], (event) => event.id);
        },
    },
    data() {
        return {
            DEFAULT_SHIFT_COLOR,
        };
    },
};
</script>

<style scoped>
.parttime-events-container {
    align-items: stretch;
}

.parttime-chip,
.day-mode-shift-chip {
    cursor: pointer;
}

.empty-parttime-cell {
    padding-block: 32px !important;
    color: #757575;
}
</style>

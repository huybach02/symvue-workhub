<template>
    <tbody v-if="currentMode !== 'day'">
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
                                title="Thêm lịch thay thế"
                                @click="$emit('addOverride', user)"
                            />
                            <v-list-item
                                prepend-icon="mdi-calendar-remove"
                                title="Xóa lịch làm việc"
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
                        :key="event.id"
                        :color="event.color"
                        size="small"
                        class="ma-1 w-100 justify-center font-weight-bold"
                        variant="flat"
                    >
                        {{ event.title }}
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
                                title="Thêm lịch thay thế"
                                @click="$emit('addOverride', user)"
                            />
                            <v-list-item
                                prepend-icon="mdi-calendar-remove"
                                title="Xóa lịch làm việc"
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
                        class="w-100 justify-center font-weight-bold day-mode-chip"
                        variant="flat"
                    >
                        {{ cell.event.title }}
                    </v-chip>
                </div>
            </td>
        </tr>
    </tbody>
</template>

<script>
import { buildDayModeCells } from "./calendarShared";

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
    methods: {
        hasUserFixedSchedule(user) {
            return this.events.some(
                (event) =>
                    event.source === "fixed_schedule" &&
                    event.user_id === user.id,
            );
        },
        getDayModeEvents(userId) {
            return this.events.filter(
                (event) =>
                    event.user_id === userId &&
                    event.date === this.baseDate.format("YYYY-MM-DD"),
            );
        },
        getDayModeRowCells(userId) {
            return buildDayModeCells(
                this.getDayModeEvents(userId),
                (event) => `${userId}-${event.id}`,
            );
        },
        getEvents(userId, colValue) {
            return this.events.filter(
                (event) => event.user_id === userId && event.date === colValue,
            );
        },
    },
};
</script>

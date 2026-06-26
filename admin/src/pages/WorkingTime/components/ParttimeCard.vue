<template>
    <v-card color="primary" variant="outlined" class="mx-auto">
        <v-card-item>
            <div>
                <div class="d-flex justify-space-between">
                    <div class="text-h6 mb-1">{{ thoiGianLamViec.thu }}</div>
                    <v-tooltip text="Thêm ca làm việc" location="top">
                        <template v-slot:activator="{ props }">
                            <v-btn
                                v-if="permission.create"
                                v-bind="props"
                                icon="mdi-plus"
                                size="small"
                                variant="tonal"
                                color="primary"
                                @click="
                                    $emit('open-dialog', { thoiGianLamViec })
                                "
                            />
                        </template>
                    </v-tooltip>
                </div>
                <v-list>
                    <v-list-item
                        v-for="(caLamViec, index) in caLamViecList"
                        :key="caLamViec.id"
                        :prepend-icon="
                            isOvernight(caLamViec)
                                ? 'mdi-weather-night'
                                : 'mdi-clock-outline'
                        "
                    >
                        <template v-slot:title>
                            <span
                                class="text-wrap font-weight-medium text-body-2"
                            >
                                Ca {{ index + 1 }}:
                                {{ formatShiftTimeRange(caLamViec) }}
                            </span>
                        </template>
                        <template v-slot:subtitle v-if="caLamViec.ghiChu">
                            <span class="text-wrap text-caption">
                                {{ caLamViec.ghiChu }}
                            </span>
                        </template>

                        <template v-slot:append>
                            <v-btn
                                v-if="permission.edit"
                                icon="mdi-pencil-outline"
                                size="x-small"
                                variant="outlined"
                                color="warning"
                                class="mr-1"
                                @click="
                                    $emit('open-dialog', {
                                        thoiGianLamViec,
                                        caLamViec,
                                    })
                                "
                            />
                            <v-switch
                                v-if="permission.edit"
                                :model-value="Boolean(caLamViec.status)"
                                color="success"
                                hide-details
                                density="compact"
                                inset
                                @update:model-value="
                                    (value) =>
                                        toggleShiftStatus(caLamViec, value)
                                "
                            />
                        </template>
                    </v-list-item>
                </v-list>
            </div>
        </v-card-item>
    </v-card>
</template>

<script>
import {
    formatTimeRangeFromValues,
    isOvernightRange,
} from "@/components/calendar/calendarShared";
import { mapActions, mapGetters } from "vuex";

export default {
    props: {
        thoiGianLamViec: {
            type: Object,
            required: true,
        },
        permission: {
            type: Object,
            default: () => ({}),
        },
    },
    emits: ["open-dialog"],
    computed: {
        ...mapGetters("workingTime", ["parttimeShiftsByWorkingTime"]),
        caLamViecList() {
            return this.parttimeShiftsByWorkingTime(this.thoiGianLamViec.id);
        },
    },
    created() {
        this.fetchCaLamViecList();
    },
    methods: {
        ...mapActions("workingTime", [
            "fetchParttimeShifts",
            "updateParttimeShiftStatus",
        ]),
        async fetchCaLamViecList() {
            await this.fetchParttimeShifts({
                workingTimeId: this.thoiGianLamViec.id,
            });
        },
        async toggleShiftStatus(caLamViec, value) {
            await this.updateParttimeShiftStatus({
                id: caLamViec.id,
                workingTimeId: this.thoiGianLamViec.id,
                status: value,
            });
        },
        isOvernight(caLamViec) {
            return isOvernightRange(caLamViec.gioBatDau, caLamViec.gioKetThuc);
        },
        formatShiftTimeRange(caLamViec) {
            return formatTimeRangeFromValues(
                caLamViec.gioBatDau,
                caLamViec.gioKetThuc,
            );
        },
    },
};
</script>

<style></style>

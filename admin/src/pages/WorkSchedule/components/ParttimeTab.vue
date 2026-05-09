<template>
    <div>
        <Calendar
            :data-calendar="dataCalendar"
            type="parttime"
            @shift-selected="handleShiftSelected"
        />

        <v-dialog v-model="dialog" max-width="720">
            <v-card>
                <v-card-title class="font-weight-bold">
                    Thiết lập nhân sự cho ca làm việc
                </v-card-title>

                <v-card-text v-if="selectedShift" class="pt-4">
                    <div class="mb-4">
                        <div class="text-subtitle-1 font-weight-medium">
                            {{ selectedShift.title }}
                        </div>
                        <div class="text-body-2 text-medium-emphasis">
                            {{ selectedShift.date }} | {{ shiftTimeRange }}
                        </div>
                    </div>

                    <v-autocomplete
                        v-model="selectedMemberIds"
                        :items="members"
                        item-title="name"
                        item-value="id"
                        label="Chọn nhân sự cho ca"
                        variant="outlined"
                        multiple
                        chips
                        closable-chips
                        clearable
                        hide-details="auto"
                    />

                    <div class="mt-4">
                        <div class="text-subtitle-2 mb-2">
                            Nhân sự đã chọn
                        </div>

                        <div
                            v-if="selectedMembers.length"
                            class="d-flex flex-wrap ga-2"
                        >
                            <v-chip
                                v-for="member in selectedMembers"
                                :key="member.id"
                                color="primary"
                                variant="tonal"
                                size="small"
                            >
                                {{ member.name }}
                            </v-chip>
                        </div>

                        <div v-else class="text-body-2 text-medium-emphasis">
                            Chưa chọn nhân sự nào cho ca này.
                        </div>
                    </div>
                </v-card-text>

                <v-card-actions>
                    <v-spacer />
                    <v-btn variant="text" @click="dialog = false">Đóng</v-btn>
                    <v-btn color="primary" @click="handleSaveAssignment">
                        Lưu tạm
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </div>
</template>

<script>
import dayjs from "dayjs";
import Calendar from "@/components/Calendar.vue";

export default {
    components: {
        Calendar,
    },
    props: {
        members: {
            type: Array,
            default: () => [],
        },
    },
    data() {
        return {
            dataCalendar: {
                shifts: [],
            },
            dialog: false,
            selectedShift: null,
            selectedMemberIds: [],
        };
    },
    computed: {
        selectedMembers() {
            return this.members.filter((member) =>
                this.selectedMemberIds.includes(member.id),
            );
        },
        shiftTimeRange() {
            if (!this.selectedShift) {
                return "";
            }

            return `${this.selectedShift.startTime} - ${this.selectedShift.endTime}`;
        },
    },
    watch: {
        members: {
            handler() {
                this.setupMockCalendar();
            },
            immediate: true,
        },
    },
    methods: {
        setupMockCalendar() {
            this.dataCalendar = {
                shifts: this.buildMockShifts(),
            };
        },
        buildMockShifts() {
            const today = dayjs();
            const startOfMonth = today.startOf("month");
            const endOfMonth = today.endOf("month");
            const shifts = [];
            const defaultAssignments = this.members
                .slice(0, 3)
                .map((member) => member.id);

            for (
                let currentDate = startOfMonth;
                currentDate.isBefore(endOfMonth) ||
                currentDate.isSame(endOfMonth, "day");
                currentDate = currentDate.add(1, "day")
            ) {
                const dateValue = currentDate.format("YYYY-MM-DD");
                const isWeekend =
                    currentDate.day() === 0 || currentDate.day() === 6;

                shifts.push(
                    {
                        id: `${dateValue}-shift-1`,
                        title: "Ca 1",
                        date: dateValue,
                        startTime: "08:00",
                        endTime: "12:00",
                        color: "#2e7d32",
                        assignedUserIds: defaultAssignments.slice(0, 2),
                    },
                    {
                        id: `${dateValue}-shift-2`,
                        title: "Ca 2",
                        date: dateValue,
                        startTime: "13:00",
                        endTime: "17:00",
                        color: "#388e3c",
                        assignedUserIds: defaultAssignments.slice(1, 3),
                    },
                );

                if (!isWeekend) {
                    shifts.push({
                        id: `${dateValue}-shift-3`,
                        title: "Ca 3",
                        date: dateValue,
                        startTime: "18:00",
                        endTime: "21:00",
                        color: "#43a047",
                        assignedUserIds: defaultAssignments.slice(0, 1),
                    });
                }
            }

            return shifts;
        },
        handleShiftSelected(shift) {
            this.selectedShift = shift;
            this.selectedMemberIds = [...(shift.assignedUserIds ?? [])];
            this.dialog = true;
        },
        handleSaveAssignment() {
            if (!this.selectedShift) {
                return;
            }

            this.dataCalendar = {
                ...this.dataCalendar,
                shifts: this.dataCalendar.shifts.map((shift) =>
                    shift.id === this.selectedShift.id
                        ? {
                              ...shift,
                              assignedUserIds: [...this.selectedMemberIds],
                          }
                        : shift,
                ),
            };

            this.selectedShift = this.dataCalendar.shifts.find(
                (shift) => shift.id === this.selectedShift.id,
            );
            this.dialog = false;
        },
    },
};
</script>

<template>
    <div>
        <div>
            <div class="flex justify-between align-center">
                <div class="d-flex ga-3">
                    <v-chip color="blue" variant="flat" size="small">
                        {{ $t('work_schedule.fixed_schedule') }}
                    </v-chip>
                    <v-chip color="orange" variant="flat" size="small">
                        {{ $t('work_schedule.override_schedule') }}
                    </v-chip>
                    <v-chip color="red" variant="flat" size="small">
                        {{ $t('work_schedule.holiday_schedule') }}
                    </v-chip>
                </div>
                <FulltimeTabDialogCreate
                    ref="fulltimeTabDialogCreate"
                    :members="members"
                    @create="handleCreate"
                />
            </div>
            <Calendar
                :data-calendar="dataCalendar"
                :type="tab"
                @add-override="dialog = true"
                @clear-schedule="showConfirmDelete = true"
                @user-selected="userSelected = $event"
            />

            <v-dialog v-model="dialog" max-width="1000" scrollable persistent>
                <v-card
                    :title="$t('work_schedule.add_override_schedule')"
                    :prepend-icon="`mdi-plus`"
                    class="position-relative"
                >
                    <v-btn
                        icon="mdi-close"
                        variant="text"
                        size="small"
                        class="close-btn"
                        @click="dialog = false"
                    />

                    <v-card-text>
                        <FulltimeOverrideForm
                            :user-selected="userSelected"
                            @cancel="handleCancelOverride"
                        />
                    </v-card-text>
                </v-card>
            </v-dialog>
        </div>

        <ConfirmDialog
            v-model="showConfirmDelete"
            :message="$t('work_schedule.confirm_delete')"
            :loading="isDeleting"
            @confirm="handleDelete"
            @cancel="showConfirmDelete = false"
        />
    </div>
</template>

<script>
import Calendar from "@/components/Calendar.vue";
import FulltimeTabDialogCreate from "./FulltimeTabDialogCreate.vue";
import { toast } from "@/main";
import { postData } from "@/services/bases/postData";
import { API_ROUTES_CONFIG } from "@/configs/apiRouteConfig";
import { getListData } from "@/services/bases/getData";
import ConfirmDialog from "@/components/ConfirmDialog.vue";
import FulltimeOverrideForm from "./FulltimeOverrideForm.vue";

export default {
    components: {
        Calendar,
        FulltimeTabDialogCreate,
        ConfirmDialog,
        FulltimeOverrideForm,
    },
    props: {
        tab: {
            type: String,
            default: "fulltime",
        },
        departmentId: {
            type: String,
            default: "",
        },
    },
    data() {
        return {
            dataCalendar: {},
            showConfirmDelete: false,
            isDeleting: false,
            userSelected: null,
            dialog: false,
        };
    },
    computed: {
        members() {
            return this.dataCalendar.users || [];
        },
    },
    watch: {
        departmentId: {
            handler(newVal, oldVal) {
                if (newVal !== oldVal) {
                    this.getFulltime();
                }
            },
            immediate: true,
        },
        tab: {
            handler(newVal, oldVal) {
                if (newVal !== oldVal) {
                    this.getFulltime();
                }
            },
            immediate: true,
        },
    },
    created() {
        this.getFulltime();
    },
    methods: {
        async getFulltime() {
            if (!this.departmentId) {
                this.dataCalendar = {};
                this.$store.commit("workSchedule/SET_FULLTIME_LOADING", false);
                return;
            }

            this.$store.commit("workSchedule/SET_FULLTIME_LOADING", true);

            try {
                const res = await getListData(
                    `${API_ROUTES_CONFIG.workSchedule}/fulltime/${this.departmentId}`,
                );

                this.dataCalendar = res;
            } finally {
                this.$store.commit("workSchedule/SET_FULLTIME_LOADING", false);
            }
        },

        async handleCreate(startDate, endDate, selectedMemberIds) {
            if (!startDate || !endDate || selectedMemberIds.length === 0) {
                toast.error(this.$t('work_schedule.please_select_time_and_members'));
            }

            this.$refs.fulltimeTabDialogCreate.isLoading = true;

            await postData(API_ROUTES_CONFIG.workSchedule + "/fulltime", {
                startDate,
                endDate,
                userIds: selectedMemberIds,
            });

            await this.getFulltime();

            this.$refs.fulltimeTabDialogCreate.isLoading = false;
            this.$refs.fulltimeTabDialogCreate.dialog = false;
        },
        async handleDelete() {
            this.isDeleting = true;
            await postData(API_ROUTES_CONFIG.workSchedule + "/fulltime/clear", {
                departmentId: this.departmentId,
                userId: this.userSelected.id,
            });
            this.isDeleting = false;
            await this.getFulltime();
            this.showConfirmDelete = false;
        },
        handleCancelOverride() {
            this.dialog = false;
            this.userSelected = null;
            this.getFulltime();
        },
    },
};
</script>

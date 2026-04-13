<template>
    <div>
        <FulltimeTabDialogCreate
            ref="fulltimeTabDialogCreate"
            :members="members"
            @create="handleCreate"
        />

        <Calendar :data-calendar="dataCalendar" />
    </div>
</template>

<script>
import Calendar from "@/components/Calendar.vue";
import FulltimeTabDialogCreate from "./FulltimeTabDialogCreate.vue";
import { toast } from "@/main";
import { postData } from "@/services/bases/postData";
import { API_ROUTES_CONFIG } from "@/configs/apiRouteConfig";
import { getListData } from "@/services/bases/getData";

export default {
    components: {
        Calendar,
        FulltimeTabDialogCreate,
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
            dataCalendar: [],
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
                this.dataCalendar = [];
                return;
            }

            const res = await getListData(
                `${API_ROUTES_CONFIG.workSchedule}/fulltime/${this.departmentId}`,
            );

            this.dataCalendar = res;
        },

        async handleCreate(startDate, endDate, selectedMemberIds) {
            if (!startDate || !endDate || selectedMemberIds.length === 0) {
                toast.error("Vui lòng chọn thời gian và thành viên");
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
    },
};
</script>

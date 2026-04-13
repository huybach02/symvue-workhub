<template>
    <div class="work-schedule-page">
        <v-row class="ma-0">
            <v-col cols="12" md="5" class="pa-0">
                <div class="d-flex ga-4 align-center department-filter">
                    <div class="department-label">
                        <b>{{ $t("field.bo_phan") }}</b>
                        <span class="text-red"> * </span>
                    </div>
                    <v-autocomplete
                        v-model="departmentId"
                        :items="departments"
                        item-title="label"
                        item-value="value"
                        variant="outlined"
                        clearable
                        :loading="departmentsLoading"
                        :placeholder="`${$t('base.enter')} ${$t('field.bo_phan')}`"
                        @update:model-value="handleDepartmentChange"
                    />
                </div>
            </v-col>
        </v-row>

        <v-row v-if="departmentId" class="ma-0">
            <v-col cols="12" class="pa-0">
                <v-tabs v-model="tab" color="primary" class="schedule-tabs">
                    <v-tab
                        v-for="item in tabs"
                        :key="item.value"
                        :value="item.value"
                    >
                        {{ item.title }}
                    </v-tab>
                </v-tabs>

                <div class="schedule-content">
                    <FulltimeTab
                        v-if="tab === 'fulltime'"
                        :tab="tab"
                        :department-id="departmentId"
                    />

                    <ParttimeTab
                        v-else-if="tab === 'parttime'"
                        :members="parttimeMembers"
                    />
                </div>
            </v-col>
        </v-row>
    </div>
</template>

<script>
import { API_ROUTES_CONFIG } from "@/configs/apiRouteConfig";
import { getDataById, getDataSelect } from "@/services/bases/getData";
import { usePermission } from "@/hooks/usePermission";
import FulltimeTab from "./components/FulltimeTab.vue";
import ParttimeTab from "./components/ParttimeTab.vue";

export default {
    name: "WorkSchedule",
    components: {
        FulltimeTab,
        ParttimeTab,
    },
    data() {
        return {
            path: API_ROUTES_CONFIG.workSchedule,
            items: [],
            totalItems: 0,
            loading: false,
            tab: "fulltime",
            tabs: [
                {
                    title: "Thời gian cố định",
                    value: "fulltime",
                },
                {
                    title: "Làm việc theo ca",
                    value: "parttime",
                },
            ],
            departments: [],
            departmentsLoading: false,
            departmentId: null,
            members: [],
            membersLoading: false,
        };
    },
    computed: {
        permission() {
            return usePermission(this.path);
        },
        fulltimeMembers() {
            return this.members.filter(
                (member) => Number(member.hinhThucLamViec) === 1,
            );
        },
        parttimeMembers() {
            return this.members.filter(
                (member) => Number(member.hinhThucLamViec) === 2,
            );
        },
    },
    watch: {
        departmentId: {
            handler(newVal, oldVal) {
                if (newVal !== oldVal) {
                    this.getMembersByDepartment();
                }
            },
            immediate: true,
        },
    },
    created() {
        this.getDepartments();
    },
    methods: {
        async getDepartments() {
            this.departmentsLoading = true;

            try {
                this.departments =
                    (await getDataSelect(API_ROUTES_CONFIG.boPhan)) ?? [];
            } finally {
                this.departmentsLoading = false;
            }
        },
        handleDepartmentChange(value) {
            this.departmentId = value;
        },
        async getMembersByDepartment() {
            if (!this.departmentId) {
                this.members = [];
                return;
            }

            this.members = await getDataById(
                API_ROUTES_CONFIG.boPhan,
                this.departmentId,
                "member",
            );
        },
    },
};
</script>

<style scoped>
.work-schedule-page {
    padding-top: 8px;
}

.department-filter {
    margin-bottom: 8px;
}

.department-label {
    min-width: 170px;
}

.schedule-tabs {
    margin-top: 4px;
}

.schedule-content {
    margin-top: 12px;
}
</style>

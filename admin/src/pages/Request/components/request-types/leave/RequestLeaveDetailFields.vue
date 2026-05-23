<template>
    <v-card-text class="pa-4">
        <v-row dense>
            <v-col cols="12" md="12">
                <v-sheet
                    rounded="lg"
                    border
                    color="grey-lighten-5"
                    class="pa-4 h-100"
                >
                    <div class="d-flex align-center ga-2 mb-2">
                        <v-icon
                            icon="mdi-tag-outline"
                            size="18"
                            color="primary"
                        />
                        <div class="text-caption text-medium-emphasis">
                            {{ $t("field.leave_type") }}
                        </div>
                    </div>

                    <div class="text-body-1 font-weight-bold">
                        {{ item?.payload?.leaveType || "--" }}
                    </div>
                </v-sheet>
            </v-col>

            <v-col cols="12" md="6">
                <v-sheet
                    rounded="lg"
                    border
                    color="grey-lighten-5"
                    class="pa-4 h-100"
                >
                    <div class="d-flex align-center ga-2 mb-2">
                        <v-icon
                            icon="mdi-calendar-start-outline"
                            size="18"
                            color="success"
                        />
                        <div class="text-caption text-medium-emphasis">
                            {{ $t("field.ngay_bat_dau") }}
                        </div>
                    </div>

                    <div class="text-body-1 font-weight-bold">
                        {{ item?.payload?.startDate || "--" }}
                    </div>
                </v-sheet>
            </v-col>

            <v-col cols="12" md="6">
                <v-sheet
                    rounded="lg"
                    border
                    color="grey-lighten-5"
                    class="pa-4 h-100"
                >
                    <div class="d-flex align-center ga-2 mb-2">
                        <v-icon
                            icon="mdi-calendar-end-outline"
                            size="18"
                            color="error"
                        />
                        <div class="text-caption text-medium-emphasis">
                            {{ $t("field.ngay_ket_thuc") }}
                        </div>
                    </div>

                    <div class="text-body-1 font-weight-bold">
                        {{ item?.payload?.endDate || "--" }}
                    </div>
                </v-sheet>
            </v-col>

            <v-col cols="12">
                <v-sheet
                    rounded="lg"
                    border
                    color="grey-lighten-5"
                    class="pa-4"
                >
                    <div class="d-flex align-center ga-2 mb-2">
                        <v-icon
                            icon="mdi-text-box-outline"
                            size="18"
                            color="warning"
                        />
                        <div class="text-caption text-medium-emphasis">
                            {{ $t("field.request_reason") }}
                        </div>
                    </div>

                    <div class="text-body-1">
                        {{ item?.payload?.reason || "--" }}
                    </div>
                </v-sheet>
            </v-col>

            <v-col cols="12">
                <v-sheet
                    rounded="lg"
                    border
                    color="grey-lighten-5"
                    class="pa-4"
                >
                    <div class="d-flex align-center ga-2 mb-2">
                        <v-icon
                            icon="mdi-account-switch-outline"
                            size="18"
                            color="info"
                        />
                        <div class="text-caption text-medium-emphasis">
                            {{ $t("field.handover_note") }}
                        </div>
                    </div>

                    <div class="text-body-1">
                        {{ item?.payload?.handoverNote || "--" }}
                    </div>
                </v-sheet>
            </v-col>
        </v-row>
    </v-card-text>
</template>

<script>
export default {
    name: "RequestLeaveDetailFields",
    props: {
        item: {
            type: Object,
            default: null,
        },
    },
};
</script>

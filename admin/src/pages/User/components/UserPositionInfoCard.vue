<template>
    <v-card variant="outlined">
        <v-card-item>
            <v-card-title class="text-wrap">
                {{ position.name || "--" }}
            </v-card-title>
            <v-card-subtitle>
                {{ position.code || "--" }}
            </v-card-subtitle>
        </v-card-item>

        <v-divider />

        <v-card-text>
            <v-row>
                <v-col cols="12" md="6">
                    <div class="text-caption text-medium-emphasis mb-1">
                        {{ $t("field.hinh_thuc_lam_viec") }}
                    </div>
                    <div>{{ employmentTypeLabel }}</div>
                </v-col>

                <v-col cols="12" md="6">
                    <div class="text-caption text-medium-emphasis mb-1">
                        {{ $t("field.range_luong") }}
                    </div>
                    <div>
                        {{
                            formatSalaryRange(
                                position.minSalary,
                                position.maxSalary,
                            )
                        }}
                    </div>
                </v-col>

                <v-col cols="12" md="6">
                    <div class="text-caption text-medium-emphasis mb-1">
                        {{ $t("field.so_thang_thu_viec") }}
                    </div>
                    <div>{{ position.probationMonths ?? "--" }}</div>
                </v-col>

                <v-col cols="12" md="6">
                    <div class="text-caption text-medium-emphasis mb-1">
                        {{ $t("field.ty_le_luong_thu_viec") }}
                    </div>
                    <div>{{ position.probationSalaryRate ?? "--" }}%</div>
                </v-col>

                <v-col cols="12" md="6">
                    <div class="text-caption text-medium-emphasis mb-1">
                        {{ $t("field.so_ngay_nghi_phep_nam") }}
                    </div>
                    <div>{{ position.annualLeaveDays ?? "--" }}</div>
                </v-col>

                <v-col cols="12" md="6">
                    <div class="text-caption text-medium-emphasis mb-1">
                        {{ $t("field.chu_ky_danh_gia_thang") }}
                    </div>
                    <div>{{ position.reviewCycleMonths ?? "--" }}</div>
                </v-col>

                <v-col cols="12" md="6">
                    <div class="text-caption text-medium-emphasis mb-1">
                        {{ $t("field.so_ngay_bao_truoc") }}
                    </div>
                    <div>{{ position.noticePeriodDays ?? "--" }}</div>
                </v-col>

                <v-col cols="12" md="6">
                    <div class="text-caption text-medium-emphasis mb-1">
                        {{ $t("field.trang_thai") }}
                    </div>
                    <v-chip
                        :color="
                            Number(position.status) === 1 ? 'success' : 'grey'
                        "
                        size="small"
                        variant="tonal"
                    >
                        {{
                            Number(position.status) === 1
                                ? $t("status_values.active")
                                : $t("status_values.inactive")
                        }}
                    </v-chip>
                </v-col>

                <v-col cols="12" v-if="position.description">
                    <div class="text-caption text-medium-emphasis mb-1">
                        {{ $t("field.mo_ta") }}
                    </div>
                    <div>{{ position.description }}</div>
                </v-col>

                <v-col cols="12">
                    <div class="text-caption text-medium-emphasis mb-2">
                        {{ $t("field.phu_cap") }}
                    </div>

                    <div
                        v-if="
                            Array.isArray(position.allowances) &&
                            position.allowances.length
                        "
                        class="d-flex flex-wrap ga-2"
                    >
                        <v-chip
                            v-for="(allowance, index) in position.allowances"
                            :key="`position-allowance-${index}`"
                            size="small"
                            variant="tonal"
                            color="primary"
                        >
                            {{ formatAllowance(allowance) }}
                        </v-chip>
                    </div>

                    <div v-else class="text-medium-emphasis">--</div>
                </v-col>
            </v-row>
        </v-card-text>
    </v-card>
</template>

<script>
import { functionHelper } from "@/helpers/functionHelper";
import { constant } from "@/utils/constants/constant";

export default {
    props: {
        position: {
            type: Object,
            default: null,
        },
    },
    computed: {
        employmentTypeLabel() {
            return (
                constant.EMPLOYMENT_TYPE_OPTIONS.find(
                    (item) => item.value === this.position?.employmentType,
                )?.text || "--"
            );
        },
    },
    methods: {
        formatNumber(value) {
            return functionHelper.formatNumber(value);
        },
        formatSalaryRange(minSalary, maxSalary) {
            const currency = this.position?.currency || "";
            const min = this.formatNumber(minSalary);
            const max = this.formatNumber(maxSalary);

            if (min === "--" && max === "--") {
                return "--";
            }

            return `${min} - ${max} ${currency}`.trim();
        },
        formatAllowance(allowance) {
            if (typeof allowance === "string") {
                return allowance;
            }

            const name = allowance?.name || "--";
            const amount =
                allowance?.amount === null ||
                allowance?.amount === undefined ||
                allowance?.amount === ""
                    ? ""
                    : `: ${this.formatNumber(allowance.amount)} ${
                          this.position?.currency || ""
                      }`;

            return `${name}${amount}`.trim();
        },
    },
};
</script>

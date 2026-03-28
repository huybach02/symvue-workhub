<template>
    <div>
        <div
            class="d-flex flex-column flex-md-row align-start align-md-center justify-space-between ga-4 mb-2"
        >
            <div class="text-h6 mb-2 font-weight-bold">
                {{ $t("bo_phan.text.positionList") }}
            </div>

            <v-btn
                color="primary"
                prepend-icon="mdi-plus"
                @click="$emit('create')"
            >
                {{ $t("bo_phan.button.addPosition") }}
            </v-btn>
        </div>
        <div variant="outlined">
            <div v-if="loading" class="d-flex justify-center py-8">
                <v-progress-circular indeterminate color="primary" />
            </div>

            <v-alert
                v-else-if="items.length === 0"
                type="warning"
                variant="tonal"
            >
                {{ $t("bo_phan.text.noPositions") }}
            </v-alert>

            <v-table v-else striped="even">
                <thead class="header-custom">
                    <tr>
                        <th class="text-left">{{ $t("field.ma_chuc_vu") }}</th>
                        <th class="text-left">{{ $t("field.ten_chuc_vu") }}</th>
                        <th class="text-left"></th>
                        <th class="text-left">
                            {{ $t("field.hinh_thuc_lam_viec") }}
                        </th>
                        <th class="text-left">{{ $t("field.range_luong") }}</th>
                        <th class="text-left">{{ $t("field.trang_thai") }}</th>
                        <th class="text-left">{{ $t("base.created_at") }}</th>
                        <th class="text-left">{{ $t("base.updated_at") }}</th>
                        <th class="text-left"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="position in items" :key="position.id">
                        <td>{{ position.code || "--" }}</td>
                        <td>
                            <div class="font-weight-medium">
                                {{ position.name }}
                            </div>
                            <div
                                v-if="position.description"
                                class="text-caption text-medium-emphasis"
                            >
                                {{ position.description }}
                            </div>
                        </td>
                        <td>
                            <v-chip
                                v-if="Number(position.isManager) === 1"
                                color="success"
                                size="small"
                                variant="outlined"
                                prepend-icon="mdi-shield-crown-outline"
                            >
                                {{ $t("bo_phan.text.manager") }}
                            </v-chip>
                        </td>
                        <td>
                            {{
                                getEmploymentTypeLabel(position.employmentType)
                            }}
                        </td>
                        <td>
                            {{ formatNumber(position.minSalary) }} -
                            {{ formatNumber(position.maxSalary) }}
                            {{ position.currency || "--" }}
                        </td>
                        <td>
                            <v-chip
                                :color="
                                    Number(position.status) === 1
                                        ? 'success'
                                        : 'error'
                                "
                                size="small"
                            >
                                {{
                                    Number(position.status) === 1
                                        ? $t("status_values.active")
                                        : $t("status_values.inactive")
                                }}
                            </v-chip>
                        </td>
                        <td>{{ position.createdAt }}</td>
                        <td>{{ position.updatedAt }}</td>
                        <td>
                            <div class="d-flex ga-2">
                                <v-btn
                                    size="small"
                                    variant="outlined"
                                    color="warning"
                                    icon="mdi-pencil"
                                    @click="$emit('edit', position)"
                                />
                                <v-btn
                                    size="small"
                                    variant="outlined"
                                    color="error"
                                    icon="mdi-delete"
                                    @click="$emit('delete', position)"
                                />
                            </div>
                        </td>
                    </tr>
                </tbody>
            </v-table>
        </div>
    </div>
</template>

<script>
import { functionHelper } from "@/helpers/functionHelper";

export default {
    props: {
        items: {
            type: Array,
            default: () => [],
        },
        loading: {
            type: Boolean,
            default: false,
        },
    },
    emits: ["create", "edit", "delete"],
    methods: {
        getEmploymentTypeLabel(value) {
            const map = {
                FULL_TIME: this.$t("bo_phan.employmentType.fullTime"),
                PART_TIME: this.$t("bo_phan.employmentType.partTime"),
                INTERN: this.$t("bo_phan.employmentType.intern"),
                CONTRACTOR: this.$t("bo_phan.employmentType.contractor"),
            };

            return map[value] || value || "--";
        },
        formatNumber(value) {
            return functionHelper.formatNumber(value);
        },
    },
};
</script>

<template>
    <div>
        <div class="text-right">
            <v-btn color="primary" @click="dialog = true">
                {{ $t('work_schedule.add_fulltime_schedule') }}
            </v-btn>
        </div>

        <v-dialog v-model="dialog" max-width="1100" scrollable persistent>
            <v-card
                :title="$t('work_schedule.add_fulltime_schedule')"
                :prepend-icon="'mdi-plus-circle-outline'"
                class="position-relative"
            >
                <v-btn
                    icon="mdi-close"
                    variant="text"
                    size="small"
                    class="position-absolute"
                    style="top: 8px; right: 8px"
                    @click="dialog = false"
                />

                <v-card-text class="pt-4">
                    <v-row>
                        <v-col cols="12" md="6">
                            <div class="mb-2">
                                {{ $t('field.ngay_bat_dau') }}
                                <span class="text-red"> * </span>
                            </div>
                            <DatePicker
                                :model-value="form.startDate"
                                :placeholder="$t('field.chon_ngay')"
                                @update:model-value="form.startDate = $event"
                            />
                        </v-col>

                        <v-col cols="12" md="6">
                            <div class="mb-2">
                                {{ $t('field.ngay_ket_thuc') }}
                                <span class="text-red"> * </span>
                            </div>
                            <DatePicker
                                :model-value="form.endDate"
                                :placeholder="$t('field.chon_ngay')"
                                @update:model-value="form.endDate = $event"
                            />
                        </v-col>
                    </v-row>

                    <div class="mt-4">
                        <div
                            class="d-flex flex-wrap justify-space-between align-center ga-3 mb-3"
                        >
                            <div>
                                <div class="text-h6 font-weight-bold">
                                    {{ $t('work_schedule.danh_sach_nhan_su') }}
                                </div>
                                <div class="text-medium-emphasis text-body-2">
                                    {{ $t('work_schedule.chon_nhan_su_ap_dung') }}
                                </div>
                            </div>

                            <div
                                class="d-flex align-center justify-end ga-2 w-100 w-sm-auto"
                            >
                                <div class="text-body-2 text-medium-emphasis">
                                    {{ $t('base.selected') }}: {{ selectedMemberIds.length }}
                                </div>

                                <v-btn
                                    variant="outlined"
                                    color="primary"
                                    @click="toggleSelectAllMembers"
                                >
                                    {{
                                        isAllMembersSelected
                                            ? $t('base.deselect_all')
                                            : $t('base.select_all')
                                    }}
                                </v-btn>
                            </div>
                        </div>

                        <v-row v-if="members.length">
                            <v-col
                                v-for="member in members"
                                :key="member.id"
                                cols="12"
                                md="6"
                                lg="4"
                            >
                                <v-card
                                    variant="outlined"
                                    class="member-card"
                                    :class="{
                                        'member-card-selected':
                                            selectedMemberIds.includes(
                                                member.id,
                                            ),
                                    }"
                                    @click="toggleMember(member.id)"
                                >
                                    <v-card-text
                                        class="d-flex align-center justify-space-between ga-3"
                                    >
                                        <div class="d-flex align-center ga-3">
                                            <v-avatar
                                                size="44"
                                                :image="member.image"
                                            />

                                            <div>
                                                <div
                                                    class="font-weight-bold text-body-1"
                                                >
                                                    {{ member.name }}
                                                </div>
                                                <div
                                                    class="text-body-2 text-medium-emphasis"
                                                >
                                                    {{
                                                        member.email ||
                                                        member.maNhanVien ||
                                                        $t('base.not_available')
                                                    }}
                                                </div>
                                            </div>
                                        </div>

                                        <v-checkbox-btn
                                            :model-value="
                                                selectedMemberIds.includes(
                                                    member.id,
                                                )
                                            "
                                            color="primary"
                                            @click.stop="
                                                toggleMember(member.id)
                                            "
                                        />
                                    </v-card-text>
                                </v-card>
                            </v-col>
                        </v-row>

                        <v-empty-state
                            v-else
                            icon="mdi-account-group-outline"
                            :text="$t('base.no_data')"
                            :title="$t('base.empty_state')"
                        />
                    </div>
                </v-card-text>

                <v-card-actions class="px-6 pb-4">
                    <v-btn
                        variant="text"
                        color="primary"
                        class="confirm-action-btn"
                        :loading="isLoading"
                        @click="handleCreate"
                    >
                        {{ $t('base.confirm') }}
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </div>
</template>

<script>
import DatePicker from "@/components/DatePicker.vue";

export default {
    components: {
        DatePicker,
    },
    props: {
        members: {
            type: Array,
            default: () => [],
        },
    },
    emits: ["create"],
    data() {
        return {
            dialog: false,
            form: {
                startDate: "",
                endDate: "",
            },
            selectedMemberIds: [],
            isLoading: false,
        };
    },
    computed: {
        isAllMembersSelected() {
            return (
                this.members.length > 0 &&
                this.selectedMemberIds.length === this.members.length
            );
        },
    },
    watch: {
        dialog(isOpen) {
            if (!isOpen) {
                this.resetForm();
            }
        },
    },
    methods: {
        toggleSelectAllMembers() {
            if (this.isAllMembersSelected) {
                this.selectedMemberIds = [];
                return;
            }

            this.selectedMemberIds = this.members.map((member) => member.id);
        },
        toggleMember(memberId) {
            if (this.selectedMemberIds.includes(memberId)) {
                this.selectedMemberIds = this.selectedMemberIds.filter(
                    (id) => id !== memberId,
                );
                return;
            }

            this.selectedMemberIds = [...this.selectedMemberIds, memberId];
        },
        handleCloseDialog() {
            this.dialog = false;
        },
        resetForm() {
            this.form = {
                startDate: "",
                endDate: "",
            };
            this.selectedMemberIds = [];
        },
        handleCreate() {
            this.$emit(
                "create",
                this.form.startDate,
                this.form.endDate,
                this.selectedMemberIds,
            );
        },
    },
};
</script>

<style scoped>
.member-card {
    cursor: pointer;
    transition:
        border-color 0.2s ease,
        background-color 0.2s ease,
        box-shadow 0.2s ease;
}

.member-card:hover {
    border-color: rgb(var(--v-theme-primary));
    box-shadow: 0 8px 20px rgba(24, 103, 192, 0.08);
}

.member-card-selected {
    border-color: rgb(var(--v-theme-primary));
    background-color: rgba(var(--v-theme-primary), 0.06);
}

.confirm-action-btn {
    font-weight: 600;
    letter-spacing: 0.04em;
}
</style>

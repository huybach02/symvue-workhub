<template>
    <div>
        <v-row>
            <v-col cols="12" md="12" class="text-right">
                <v-tooltip
                    :text="$t('bo_phan.text.viewMembers')"
                    location="top"
                >
                    <template #activator="{ props: tooltipProps }">
                        <v-btn
                            v-bind="tooltipProps"
                            icon
                            size="small"
                            variant="outlined"
                            color="primary"
                            @click="dialog = true"
                        >
                            <v-icon>mdi-account-group</v-icon>
                        </v-btn>
                    </template>
                </v-tooltip>
            </v-col>
        </v-row>

        <!-- Dialog danh sách thành viên -->
        <v-dialog v-model="dialog" max-width="1000" scrollable persistent>
            <v-card
                :title="
                    $t('bo_phan.text.userList', { tenBoPhan: item.tenBoPhan })
                "
                prepend-icon="mdi-account-group"
                class="position-relative"
            >
                <v-btn
                    icon="mdi-close"
                    variant="text"
                    size="small"
                    class="close-btn"
                    @click="dialog = false"
                />

                <v-card-text class="">
                    <!-- Select autocomple để add thành viên vào bộ phận -->
                    <v-row align="end" class="mb-2">
                        <v-col cols="12" md="10">
                            <div class="mb-2">
                                {{ $t("field.thanh_vien_moi") }}
                                <span class="text-red"> * </span>
                            </div>
                            <v-autocomplete
                                v-model="nguoiDungSelected"
                                name="boPhanId"
                                :items="danhSachNguoiDung"
                                item-title="label"
                                item-value="value"
                                variant="outlined"
                                clearable
                                hide-details
                                :placeholder="`${$t('base.enter')} ${$t('field.thanh_vien_moi')}`"
                            />
                        </v-col>
                        <v-col cols="12" md="2">
                            <v-btn
                                color="primary"
                                prepend-icon="mdi-plus"
                                size="large"
                                :loading="adding"
                                @click="addMember"
                            >
                                {{ $t("button.create") }}
                            </v-btn>
                        </v-col>
                    </v-row>

                    <div
                        v-if="loading"
                        class="d-flex justify-center align-center pa-6"
                    >
                        <v-progress-circular indeterminate color="primary" />
                    </div>

                    <div
                        v-else-if="members.length === 0"
                        class="d-flex flex-column align-center justify-center pa-8 text-medium-emphasis"
                    >
                        <v-icon size="48" class="mb-3">
                            mdi-account-off-outline
                        </v-icon>
                        <span>{{ $t("bo_phan.text.noMembers") }}</span>
                    </div>

                    <v-list v-else lines="two" class="py-0">
                        <v-list-item
                            v-for="member in members"
                            :key="member.id"
                            class="px-4 py-3"
                        >
                            <template #prepend>
                                <div class="py-2">
                                    <v-avatar v-if="member.image" size="50">
                                        <v-img
                                            :src="member.image"
                                            :alt="member.name"
                                            cover
                                        />
                                    </v-avatar>
                                    <v-avatar
                                        v-else
                                        color="grey-lighten-2"
                                        size="50"
                                    >
                                        <v-icon
                                            icon="mdi-account"
                                            color="grey-darken-1"
                                        />
                                    </v-avatar>
                                </div>
                            </template>

                            <template #title>
                                <div
                                    class="d-flex align-center gap-2 flex-wrap"
                                >
                                    <span class="font-weight-medium">{{
                                        member.name
                                    }}</span>
                                    <v-chip
                                        v-if="member.isManager"
                                        color="primary"
                                        size="x-small"
                                        variant="flat"
                                        prepend-icon="mdi-shield-crown-outline"
                                        class="ms-5"
                                    >
                                        {{ $t("bo_phan.text.manager") }}
                                    </v-chip>
                                </div>
                            </template>

                            <!-- Email & phone -->
                            <template #subtitle>
                                <div class="d-flex flex-column">
                                    <span class="mb-1">{{ member.email }}</span>
                                    <span v-if="member.phone">{{
                                        member.phone
                                    }}</span>
                                </div>
                            </template>

                            <template #append>
                                <v-btn
                                    v-if="
                                        !member.isManager && permission?.delete
                                    "
                                    size="small"
                                    variant="tonal"
                                    color="error"
                                    @click="openRemoveConfirm(member)"
                                >
                                    {{ $t("button.remove") }}
                                </v-btn>
                            </template>
                        </v-list-item>
                    </v-list>
                </v-card-text>

                <v-card-actions class="px-4 pb-3 pt-2">
                    <v-spacer />
                    <span class="text-caption text-medium-emphasis">
                        {{
                            $t("bo_phan.text.totalMembers", {
                                count: members.length,
                            })
                        }}
                    </span>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <ConfirmDialog
            v-model="confirmDialog"
            :message="
                $t('bo_phan.text.removeMemberConfirm', {
                    name: selectedMember ? selectedMember.name : '',
                    tenBoPhan: item.tenBoPhan,
                })
            "
            :loading="removing"
            icon="mdi-account-remove"
            @confirm="onConfirmRemove"
            @cancel="selectedMember = null"
        />
    </div>
</template>

<script>
import { getDataById, getDataSelect } from "@/services/bases/getData";
import { postData } from "@/services/bases/postData";
import { deleteData } from "@/services/bases/deleteData";
import ConfirmDialog from "@/components/ConfirmDialog.vue";
import { API_ROUTES_CONFIG } from "@/configs/apiRouteConfig";
import { toast } from "@/main";

export default {
    components: { ConfirmDialog },
    props: {
        item: {
            type: Object,
            default: null,
        },
        path: {
            type: String,
            default: "",
        },
        permission: {
            type: Object,
            default: null,
        },
    },
    emits: ["reload"],
    data() {
        return {
            dialog: false,
            loading: false,
            members: [],
            confirmDialog: false,
            selectedMember: null,
            removing: false,
            nguoiDungData: [],
            nguoiDungSelected: null,
            adding: false,
        };
    },
    computed: {
        danhSachNguoiDung() {
            return this.nguoiDungData.filter((item) => {
                return !this.members.some((member) => member.id === item.value);
            });
        },
    },
    watch: {
        async dialog(isOpen) {
            if (isOpen) {
                // Load song song danh sách thành viên và danh sách người dùng cho autocomplete
                await Promise.all([this.fetchMembers(), this.getNguoiDung()]);
            } else {
                this.members = [];
                this.nguoiDungSelected = null;
            }
        },
    },
    methods: {
        async fetchMembers() {
            this.loading = true;
            try {
                const data = await getDataById(
                    this.path,
                    this.item.id,
                    "thanh-vien",
                );
                this.members = data ?? [];
            } finally {
                this.loading = false;
            }
        },

        openRemoveConfirm(member) {
            this.selectedMember = member;
            this.confirmDialog = true;
        },

        async onConfirmRemove() {
            if (!this.selectedMember) return;
            this.removing = true;
            try {
                await deleteData(
                    `${this.path}/${this.item.id}/thanh-vien`,
                    this.selectedMember.id,
                );
                this.confirmDialog = false;
                this.selectedMember = null;
                await this.fetchMembers();
                this.$emit("reload");
            } finally {
                this.removing = false;
            }
        },
        async getNguoiDung() {
            const res = await getDataSelect(API_ROUTES_CONFIG.user);
            this.nguoiDungData = res ?? [];
        },

        async addMember() {
            if (!this.nguoiDungSelected) {
                toast.error(this.$t("bo_phan.text.selectUserRequired"));
                return;
            }

            this.adding = true;
            try {
                const res = await postData(
                    `${this.path}/${this.item.id}/thanh-vien`,
                    { user_id: this.nguoiDungSelected },
                );
                if (res) {
                    this.nguoiDungSelected = null;
                    await this.fetchMembers();
                    this.$emit("reload");
                }
            } finally {
                this.adding = false;
            }
        },
    },
};
</script>

<style scoped>
.close-btn {
    position: absolute;
    top: 8px;
    right: 8px;
}
</style>

<template>
    <v-dialog v-model="dialog" max-width="600">
        <v-card
            prepend-icon="mdi-clock-outline"
            :title="$t('thoi_gian_lam_viec.edit')"
        >
            <v-divider />

            <v-card-text>
                <VeeForm
                    v-if="dataLoaded"
                    as="form"
                    :validation-schema="thoiGianLamViecSchema"
                    :initial-values="initialValues"
                    @submit="onSubmit"
                >
                    <v-row>
                        <v-col cols="12">
                            <VeeField
                                v-slot="{ field, errorMessage }"
                                name="thu"
                            >
                                <v-text-field
                                    v-bind="field"
                                    :error-messages="errorMessage"
                                    type="text"
                                    readonly
                                    variant="outlined"
                                    persistent-placeholder
                                >
                                    <template #label>
                                        {{ $t("thoi_gian_lam_viec.thu") }}
                                    </template>
                                </v-text-field>
                            </VeeField>
                        </v-col>
                        <v-col cols="12" md="6">
                            <VeeField
                                v-slot="{ field, errorMessage }"
                                name="gioBatDau"
                            >
                                <v-text-field
                                    v-bind="field"
                                    :error-messages="errorMessage"
                                    variant="outlined"
                                    persistent-placeholder
                                >
                                    <template #label>
                                        {{
                                            $t("thoi_gian_lam_viec.gio_bat_dau")
                                        }}
                                    </template>
                                    <v-menu
                                        v-model="showMenuGioBatDau"
                                        :close-on-content-click="false"
                                        activator="parent"
                                        min-width="0"
                                    >
                                        <v-time-picker
                                            :model-value="field.value"
                                            @update:model-value="
                                                (val) => updateTime(field, val)
                                            "
                                        />
                                    </v-menu>
                                </v-text-field>
                            </VeeField>
                        </v-col>
                        <v-col cols="12" md="6">
                            <VeeField
                                v-slot="{ field, errorMessage }"
                                name="gioKetThuc"
                            >
                                <v-text-field
                                    v-bind="field"
                                    :error-messages="errorMessage"
                                    variant="outlined"
                                    persistent-placeholder
                                >
                                    <template #label>
                                        {{
                                            $t(
                                                "thoi_gian_lam_viec.gio_ket_thuc",
                                            )
                                        }}
                                    </template>
                                    <v-menu
                                        v-model="showMenuGioKetThuc"
                                        :close-on-content-click="false"
                                        activator="parent"
                                        min-width="0"
                                    >
                                        <v-time-picker
                                            :model-value="field.value"
                                            @update:model-value="
                                                (val) => updateTime(field, val)
                                            "
                                        />
                                    </v-menu>
                                </v-text-field>
                            </VeeField>
                        </v-col>
                        <v-col cols="12">
                            <VeeField
                                v-slot="{ field, errorMessage }"
                                name="ghiChu"
                            >
                                <v-text-field
                                    v-bind="field"
                                    :error-messages="errorMessage"
                                    type="text"
                                    variant="outlined"
                                    persistent-placeholder
                                >
                                    <template #label>
                                        {{ $t("thoi_gian_lam_viec.ghi_chu") }}
                                    </template>
                                </v-text-field>
                            </VeeField>
                        </v-col>
                    </v-row>
                    <v-row>
                        <v-col cols="12 d-flex justify-end">
                            <v-btn
                                :text="$t('thoi_gian_lam_viec.cancel_button')"
                                variant="plain"
                                @click="dialog = false"
                            />

                            <v-btn
                                color="primary"
                                :text="$t('thoi_gian_lam_viec.save_button')"
                                type="submit"
                                :loading="this.$store.state.isLoading"
                            />
                        </v-col>
                    </v-row>
                </VeeForm>
            </v-card-text>
        </v-card>
    </v-dialog>
</template>

<script>
import { API_ROUTES_CONFIG } from "@/configs/apiRouteConfig";
import { functionHelper } from "@/helpers/functionHelper";
import { getDataById } from "@/services/bases/getData";
import { putData } from "@/services/bases/updateData";
import { Form as VeeForm, Field as VeeField } from "vee-validate";

export default {
    components: {
        VeeForm,
        VeeField,
    },
    props: {
        isOpen: {
            type: Boolean,
            default: false,
        },
        itemEdit: {
            type: Object,
            default: null,
        },
    },
    emits: ["close", "update"],
    data() {
        return {
            initialValues: {
                thu: "",
                gioBatDau: "",
                gioKetThuc: "",
                ghiChu: "",
            },
            dataLoaded: false,
            showMenuGioBatDau: false,
            showMenuGioKetThuc: false,
            time: null,
        };
    },
    computed: {
        dialog: {
            get() {
                return this.isOpen;
            },
            set(value) {
                if (!value) {
                    this.$emit("close");
                }
            },
        },
    },
    watch: {
        itemEdit: {
            handler() {
                this.dataLoaded = false;
                if (this.isOpen) {
                    this.fetchItemById();
                }
            },
            deep: true,
        },
    },
    methods: {
        async fetchItemById() {
            if (this.itemEdit) {
                this.dataLoaded = false;
                this.$store.commit("setIsLoading");
                const response = await getDataById(
                    API_ROUTES_CONFIG.thoiGianLamViec.fulltime,
                    this.itemEdit.id,
                );
                if (response) {
                    this.initialValues = response;
                    this.dataLoaded = true;
                    this.$store.commit("unsetIsLoading");
                }
            }
        },
        async onSubmit(values) {
            this.$store.commit("setIsLoading");
            const response = await putData(
                API_ROUTES_CONFIG.thoiGianLamViec.fulltime,
                this.itemEdit.id,
                values,
            );
            if (response) {
                this.$store.commit("unsetIsLoading");
                this.dialog = false;
                this.$emit("update");
            }
        },
        updateTime(field, value) {
            functionHelper.updateTime(field, value);
        },
    },
};
</script>

<style></style>

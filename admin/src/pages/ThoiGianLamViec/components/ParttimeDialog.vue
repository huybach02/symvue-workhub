<template>
    <v-dialog v-model="dialog" max-width="600">
        <v-card
            prepend-icon="mdi-clock-outline"
            :title="$t('ca_lam_viec.create')"
        >
            <v-divider />

            <v-card-text>
                <VeeForm
                    as="form"
                    :validation-schema="thoiGianLamViecSchema"
                    :initial-values="initialValues"
                    @submit="onSubmit"
                >
                    <v-row>
                        <v-col cols="12">
                            <v-text-field
                                :value="thoiGianLamViec?.thu"
                                type="text"
                                readonly
                                variant="outlined"
                                persistent-placeholder
                            >
                                <template #label>
                                    {{ $t("thoi_gian_lam_viec.thu") }}
                                </template>
                            </v-text-field>
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
                                        {{ $t("ca_lam_viec.gio_bat_dau") }}
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
                                        {{ $t("ca_lam_viec.gio_ket_thuc") }}
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
                                        {{ $t("ca_lam_viec.ghi_chu") }}
                                    </template>
                                </v-text-field>
                            </VeeField>
                        </v-col>
                    </v-row>
                    <v-row>
                        <v-col cols="12 d-flex justify-end">
                            <v-btn
                                :text="$t('ca_lam_viec.cancel_button')"
                                variant="plain"
                                @click="dialog = false"
                            />

                            <v-btn
                                color="primary"
                                :text="$t('ca_lam_viec.save_button')"
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
import { functionHelper } from "@/helpers/functionHelper";
import { thoiGianLamViecService } from "@/services/thoiGianLamViecService";
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
        thoiGianLamViec: {
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
    methods: {
        onSubmit(values) {
            this.$store.commit("setIsLoading");
            values.thoiGianLamViecId = this.thoiGianLamViec.id;
            thoiGianLamViecService.createParttime(values).then((response) => {
                if (response) {
                    this.dialog = false;
                    this.$emit("update");
                }
            });
            this.$store.commit("unsetIsLoading");
        },
        updateTime(field, value) {
            functionHelper.updateTime(field, value);
        },
    },
};
</script>

<style></style>

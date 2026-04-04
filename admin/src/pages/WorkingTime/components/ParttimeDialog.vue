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
                                    {{ $t("field.thu") }}
                                </template>
                            </v-text-field>
                        </v-col>
                        <v-col cols="12" md="6">
                            <VeeField
                                v-slot="{
                                    field,
                                    errorMessage,
                                    handleChange,
                                    handleBlur,
                                }"
                                name="gioBatDau"
                            >
                                <TimePicker
                                    :model-value="field.value"
                                    :error-messages="errorMessage"
                                    :placeholder="`${$t('base.enter')} ${$t('field.gio_bat_dau')}`"
                                    @update:model-value="
                                        (value) => {
                                            handleChange(value);
                                            handleBlur();
                                        }
                                    "
                                    @blur="handleBlur"
                                />
                            </VeeField>
                        </v-col>
                        <v-col cols="12" md="6">
                            <VeeField
                                v-slot="{
                                    field,
                                    errorMessage,
                                    handleChange,
                                    handleBlur,
                                }"
                                name="gioKetThuc"
                            >
                                <TimePicker
                                    :model-value="field.value"
                                    :error-messages="errorMessage"
                                    :placeholder="`${$t('base.enter')} ${$t('field.gio_ket_thuc')}`"
                                    @update:model-value="
                                        (value) => {
                                            handleChange(value);
                                            handleBlur();
                                        }
                                    "
                                    @blur="handleBlur"
                                />
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
                                        {{ $t("field.ghi_chu") }}
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
import { API_ROUTES_CONFIG } from "@/configs/apiRouteConfig";
import TimePicker from "@/components/TimePicker.vue";
import { postData } from "@/services/bases/postData";
import { Form as VeeForm, Field as VeeField } from "vee-validate";

export default {
    components: {
        TimePicker,
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
        async onSubmit(values) {
            this.$store.commit("setIsLoading");
            values.thoiGianLamViecId = this.thoiGianLamViec.id;
            const response = await postData(
                API_ROUTES_CONFIG.thoiGianLamViec,
                values,
            );
            if (response) {
                this.dialog = false;
                this.$emit("update");
            }
            this.$store.commit("unsetIsLoading");
        },
    },
};
</script>

<style></style>

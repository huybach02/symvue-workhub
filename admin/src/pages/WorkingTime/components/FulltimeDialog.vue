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
                                        {{ $t("field.thu") }}
                                    </template>
                                </v-text-field>
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
                                v-slot="{ field, errorMessage, handleChange }"
                                name="applyToExistingSchedules"
                            >
                                <div>
                                    <v-label class="mb-2 font-weight-bold">
                                        {{
                                            $t("thoi_gian_lam_viec.apply_scope")
                                        }}
                                    </v-label>

                                    <v-radio-group
                                        :model-value="field.value"
                                        hide-details
                                        @update:model-value="
                                            onApplyScopeChange(
                                                handleChange,
                                                $event,
                                            )
                                        "
                                    >
                                        <v-card
                                            class="mb-3"
                                            :color="
                                                field.value === true
                                                    ? 'primary'
                                                    : undefined
                                            "
                                            variant="tonal"
                                            @click="handleChange(true)"
                                        >
                                            <v-card-text class="pa-3">
                                                <v-radio
                                                    :label="
                                                        $t(
                                                            'thoi_gian_lam_viec.apply_existing_schedules',
                                                        )
                                                    "
                                                    :value="true"
                                                    color="primary"
                                                    hide-details
                                                />
                                            </v-card-text>
                                        </v-card>

                                        <v-card
                                            :color="
                                                field.value === false
                                                    ? 'primary'
                                                    : undefined
                                            "
                                            variant="tonal"
                                            @click="handleChange(false)"
                                        >
                                            <v-card-text class="pa-3">
                                                <v-radio
                                                    :label="
                                                        $t(
                                                            'thoi_gian_lam_viec.apply_new_schedules_only',
                                                        )
                                                    "
                                                    :value="false"
                                                    color="primary"
                                                    hide-details
                                                />
                                            </v-card-text>
                                        </v-card>
                                    </v-radio-group>

                                    <v-messages
                                        v-if="errorMessage"
                                        color="error"
                                        :messages="[errorMessage]"
                                    />
                                </div>
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
                                :text="$t('thoi_gian_lam_viec.cancel_button')"
                                variant="plain"
                                @click="dialog = false"
                            />

                            <v-btn
                                color="primary"
                                :text="$t('thoi_gian_lam_viec.save_button')"
                                type="submit"
                                :loading="saving"
                            />
                        </v-col>
                    </v-row>
                </VeeForm>
            </v-card-text>
        </v-card>
    </v-dialog>
</template>

<script>
import TimePicker from "@/components/TimePicker.vue";
import { toast } from "@/main";
import { Form as VeeForm, Field as VeeField } from "vee-validate";
import { mapActions, mapGetters } from "vuex";

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
                applyToExistingSchedules: null,
            },
            dataLoaded: false,
        };
    },
    computed: {
        ...mapGetters("workingTime", ["fulltimeById", "saving"]),
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
        ...mapActions("workingTime", ["fetchFulltimeDetail", "updateFulltime"]),
        async fetchItemById() {
            if (this.itemEdit) {
                this.dataLoaded = false;
                this.initialValues = this.fulltimeById(this.itemEdit.id) ?? {
                    ...this.initialValues,
                };

                const response = await this.fetchFulltimeDetail({
                    id: this.itemEdit.id,
                    force: true,
                });

                if (response) {
                    this.initialValues = {
                        ...response,
                        applyToExistingSchedules: null,
                    };
                    this.dataLoaded = true;
                }
            }
        },
        onApplyScopeChange(handleChange, value) {
            handleChange(value);
        },
        async onSubmit(values) {
            if (
                values.applyToExistingSchedules !== true &&
                values.applyToExistingSchedules !== false
            ) {
                toast.error(this.$t("thoi_gian_lam_viec.apply_scope_required"));
                return;
            }

            const response = await this.updateFulltime({
                id: this.itemEdit.id,
                values,
            });

            if (response) {
                this.dialog = false;
                this.$emit("update");
            }
        },
    },
};
</script>

<style></style>

<template>
    <v-dialog v-model="dialog" max-width="600">
        <v-card
            prepend-icon="mdi-clock-outline"
            :title="
                itemEdit ? $t('ca_lam_viec.edit') : $t('ca_lam_viec.create')
            "
        >
            <v-divider />

            <v-card-text>
                <VeeForm
                    v-slot="{ values }"
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
                        <v-col v-if="isOvernightValues(values)" cols="12">
                            <v-alert
                                type="info"
                                variant="tonal"
                                density="compact"
                                icon="mdi-weather-night"
                                class="overnight-alert"
                            >
                                {{
                                    $t(
                                        "thoi_gian_lam_viec.overnight_notice",
                                    )
                                }}
                                <br />
                                <strong>
                                    ({{ formatOvernightRange(values) }})
                                </strong>
                            </v-alert>
                        </v-col>
                        <v-col v-if="itemEdit" cols="12">
                            <VeeField
                                v-slot="{ field, handleChange }"
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
                                :text="$t('ca_lam_viec.cancel_button')"
                                variant="plain"
                                @click="dialog = false"
                            />

                            <v-btn
                                color="primary"
                                :text="$t('ca_lam_viec.save_button')"
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
import {
    formatTimeRangeFromValues,
    isOvernightRange,
} from "@/components/calendar/calendarShared";
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
        thoiGianLamViec: {
            type: Object,
            default: null,
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
        };
    },
    computed: {
        ...mapGetters("workingTime", ["saving"]),
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
                this.initialValues = this.itemEdit
                    ? {
                          ...this.itemEdit,
                          applyToExistingSchedules: null,
                      }
                    : {
                          thu: "",
                          gioBatDau: "",
                          gioKetThuc: "",
                          ghiChu: "",
                          applyToExistingSchedules: null,
                      };
            },
            immediate: true,
        },
        isOpen(value) {
            if (!value) {
                return;
            }

            this.initialValues = this.itemEdit
                ? {
                      ...this.itemEdit,
                      applyToExistingSchedules: null,
                  }
                : {
                      thu: "",
                      gioBatDau: "",
                      gioKetThuc: "",
                      ghiChu: "",
                      applyToExistingSchedules: null,
                  };
        },
    },
    methods: {
        ...mapActions("workingTime", [
            "createParttimeShift",
            "updateParttimeShift",
        ]),
        onApplyScopeChange(handleChange, value) {
            handleChange(value);
        },
        isOvernightValues(values) {
            return isOvernightRange(values?.gioBatDau, values?.gioKetThuc);
        },
        formatOvernightRange(values) {
            return formatTimeRangeFromValues(
                values?.gioBatDau,
                values?.gioKetThuc,
            );
        },
        async onSubmit(values) {
            if (
                this.itemEdit &&
                values.applyToExistingSchedules !== true &&
                values.applyToExistingSchedules !== false
            ) {
                toast.error(this.$t("thoi_gian_lam_viec.apply_scope_required"));
                return;
            }

            const payload = {
                ...values,
                thoiGianLamViecId: this.thoiGianLamViec.id,
                applyToExistingSchedules:
                    this.itemEdit === null
                        ? false
                        : values.applyToExistingSchedules,
            };
            const response = this.itemEdit
                ? await this.updateParttimeShift({
                      id: this.itemEdit.id,
                      values: payload,
                  })
                : await this.createParttimeShift(payload);

            if (response) {
                this.dialog = false;
                this.$emit("update");
            }
        },
    },
};
</script>

<style scoped>
.overnight-alert {
    margin-top: -8px;
}
</style>

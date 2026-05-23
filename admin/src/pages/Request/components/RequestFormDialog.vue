<template>
    <v-dialog :model-value="modelValue" max-width="900" persistent scrollable @update:model-value="$emit('update:modelValue', $event)">
        <v-card>
            <v-card-title class="d-flex align-center justify-space-between">
                <div>
                    {{ mode === "create" ? $t("request.create_title") : $t("request.edit_title") }}
                </div>
                <v-btn icon="mdi-close" variant="text" @click="$emit('update:modelValue', false)" />
            </v-card-title>

            <v-card-text>
                <VeeForm
                    ref="formRef"
                    as="form"
                    :validation-schema="validationSchema"
                    :initial-values="initialValues"
                    @submit="handleSubmit"
                >
                    <component
                        :is="activeFormComponent"
                        v-if="activeFormComponent"
                    />

                    <v-alert
                        v-else
                        type="warning"
                        variant="tonal"
                    >
                        {{ $t("request.unsupported_type") }}
                    </v-alert>

                    <div class="d-flex justify-end ga-2 mt-4">
                        <v-btn color="grey" @click="$emit('update:modelValue', false)">
                            {{ $t("button.cancel") }}
                        </v-btn>
                        <v-btn color="primary" type="submit" :loading="$store.state.isLoading">
                            {{ mode === "create" ? $t("button.create") : $t("button.update") }}
                        </v-btn>
                    </div>
                </VeeForm>
            </v-card-text>
        </v-card>
    </v-dialog>
</template>

<script>
import { Form as VeeForm } from "vee-validate";
import { mapActions } from "vuex";
import { getRequestTypeComponentConfig } from "./request-types/requestTypeComponentRegistry";

export default {
    name: "RequestFormDialog",
    components: {
        VeeForm,
    },
    props: {
        modelValue: {
            type: Boolean,
            default: false,
        },
        selectedType: {
            type: Object,
            default: null,
        },
        mode: {
            type: String,
            default: "create",
        },
        item: {
            type: Object,
            default: null,
        },
    },
    emits: ["update:modelValue", "saved"],
    data() {
        return {
            initialValues: {},
        };
    },
    computed: {
        requestTypeCode() {
            return this.selectedType?.code || this.item?.type || null;
        },
        requestTypeConfig() {
            return getRequestTypeComponentConfig(this.requestTypeCode);
        },
        validationSchema() {
            return this.requestTypeConfig?.validationSchema ?? null;
        },
        activeFormComponent() {
            return this.requestTypeConfig?.formComponent ?? null;
        },
        defaultValues() {
            return { ...(this.requestTypeConfig?.initialValues ?? {}) };
        },
        currentFormValues() {
            if (this.mode === "edit" && this.item?.payload) {
                return this.requestTypeConfig?.mapPayloadToForm?.(this.item.payload) ?? this.item.payload;
            }

            return this.defaultValues;
        },
    },
    watch: {
        modelValue(isOpen) {
            if (!isOpen) {
                return;
            }

            this.$nextTick(() => {
                if (!this.$refs.formRef) {
                    return;
                }

                this.$refs.formRef.resetForm({
                    values: this.currentFormValues,
                });
            });
        },
    },
    methods: {
        ...mapActions("request", ["createRequest", "updateRequest"]),
        async handleSubmit(values) {
            if (!this.requestTypeCode) {
                return;
            }

            this.$store.commit("setIsLoading");

            try {
                if (this.mode === "create") {
                    await this.createRequest({
                        type: this.requestTypeCode,
                        payload: values,
                    });
                } else {
                    await this.updateRequest({
                        requestId: this.item.id,
                        values: {
                            payload: values,
                        },
                    });
                }

                this.$emit("saved");
            } finally {
                this.$store.commit("unsetIsLoading");
            }
        },
    },
};
</script>

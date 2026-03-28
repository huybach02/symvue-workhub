<template>
    <div>
        <v-dialog v-model="dialogModel" max-width="1100" scrollable persistent>
            <v-card
                :title="dialogTitle"
                :prepend-icon="
                    mode === 'create' ? 'mdi-plus-circle-outline' : 'mdi-pencil'
                "
                class="position-relative"
            >
                <v-btn
                    icon="mdi-close"
                    variant="text"
                    size="small"
                    class="position-absolute"
                    style="top: 8px; right: 8px"
                    @click="dialogModel = false"
                />

                <v-card-text>
                    <DepartmentPositionForm
                        :item="item"
                        :mode="mode"
                        :loading="loading"
                        :department="department"
                        :submit-button-text="
                            mode === 'create'
                                ? $t('button.create')
                                : $t('button.update')
                        "
                        @submit="$emit('submit', $event)"
                        @cancel="dialogModel = false"
                    />
                </v-card-text>
            </v-card>
        </v-dialog>
    </div>
</template>

<script>
import DepartmentPositionForm from "./DepartmentPositionForm.vue";

export default {
    components: {
        DepartmentPositionForm,
    },
    props: {
        modelValue: {
            type: Boolean,
            default: false,
        },
        item: {
            type: Object,
            default: null,
        },
        mode: {
            type: String,
            default: "create",
        },
        loading: {
            type: Boolean,
            default: false,
        },
        department: {
            type: Object,
            default: null,
        },
    },
    emits: ["update:modelValue", "submit"],
    computed: {
        dialogModel: {
            get() {
                return this.modelValue;
            },
            set(value) {
                this.$emit("update:modelValue", value);
            },
        },
        dialogTitle() {
            return `${this.$t(
                this.mode === "create" ? "title.create" : "title.update",
            )} ${this.$t("field.chuc_vu")}`;
        },
    },
};
</script>

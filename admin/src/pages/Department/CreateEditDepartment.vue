<template>
    <div>
        <v-row>
            <v-col cols="12" md="12" class="text-right">
                <v-btn
                    v-if="mode === 'create'"
                    color="primary"
                    prepend-icon="mdi-plus"
                    @click="dialog = true"
                >
                    {{ titleCreate }}
                </v-btn>
                <v-btn
                    v-if="mode === 'update'"
                    icon
                    size="small"
                    variant="outlined"
                    color="warning"
                    @click="dialog = true"
                >
                    <v-icon>mdi-pencil</v-icon>
                </v-btn>
            </v-col>
        </v-row>
        <v-dialog v-model="dialog" max-width="1400" scrollable persistent>
            <v-card
                :title="mode === 'create' ? titleCreate : titleUpdate"
                :prepend-icon="
                    mode === 'create' ? 'mdi-plus' : 'mdi-pencil-outline'
                "
                class="position-relative"
            >
                <v-btn
                    icon="mdi-close"
                    variant="text"
                    size="small"
                    class="close-btn"
                    @click="dialog = false"
                />

                <v-card-text>
                    <FormDepartment
                        :submit-button-text="
                            mode === 'create'
                                ? $t('button.create')
                                : $t('button.update')
                        "
                        :item="dataItem"
                        :mode="mode"
                        @submit="onSubmit"
                        @cancel="dialog = false"
                    />
                </v-card-text>
            </v-card>
        </v-dialog>
    </div>
</template>

<script>
import FormDepartment from "./FormDepartment.vue";
import { mapActions, mapGetters } from "vuex";

export default {
    components: {
        FormDepartment,
    },
    props: {
        mode: {
            type: String,
            default: "create",
        },
        item: {
            type: Object,
            default: null,
        },
        path: {
            type: String,
            default: "",
        },
    },
    emits: ["reload"],
    data() {
        return {
            dialog: false,
            dataItem: null,
        };
    },
    computed: {
        ...mapGetters("department", ["departmentDetailById"]),
        titleCreate() {
            return this.$t("title.create") + " " + this.$t("bo_phan.title");
        },
        titleUpdate() {
            return this.$t("title.update") + " " + this.$t("bo_phan.title");
        },
    },
    watch: {
        async dialog(isOpen) {
            if (isOpen && this.mode === "update") {
                this.dataItem = this.departmentDetailById(this.item.id);
                this.dataItem = await this.fetchDepartmentDetail({
                    departmentId: this.item.id,
                    force: true,
                });
            }
        },
    },
    methods: {
        ...mapActions("department", [
            "createDepartment",
            "fetchDepartmentDetail",
            "updateDepartment",
        ]),
        async onSubmit(values) {
            this.$store.commit("setIsLoading");

            try {
                if (this.mode === "create") {
                    await this.createDepartment(values);
                } else {
                    await this.updateDepartment({
                        departmentId: this.item.id,
                        values,
                    });
                }

                this.dialog = false;
                this.$emit("reload");
            } finally {
                this.$store.commit("unsetIsLoading");
            }
        },
    },
};
</script>

<style></style>

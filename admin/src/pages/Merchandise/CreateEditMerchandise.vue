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
                prepend-icon="mdi-plus"
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
                    <component
                        :is="formComponent"
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
import FormIngredient from "./FormIngredient.vue";
import FormFinishedProduct from "./FormFinishedProduct.vue";
import { mapActions, mapGetters } from "vuex";

export default {
    components: {
        FormIngredient,
        FormFinishedProduct,
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
        type: {
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
        ...mapGetters("merchandise", ["itemById"]),
        formComponent() {
            const currentType = this.item?.type || this.type;
            if (currentType === "finished_product") {
                return "FormFinishedProduct";
            }
            return "FormIngredient";
        },
        titleCreate() {
            const typeKey = this.type || "title";
            return (
                this.$t("title.create") + " " + this.$t(`merchandise.${typeKey}`)
            );
        },
        titleUpdate() {
            const typeKey = this.item?.type || this.type || "title";
            return (
                this.$t("title.update") + " " + this.$t(`merchandise.${typeKey}`)
            );
        },
    },
    watch: {
        async dialog(isOpen) {
            if (isOpen && this.mode === "update") {
                this.dataItem = this.itemById(this.item.id);
                this.dataItem = await this.fetchItemDetail({
                    id: this.item.id,
                    force: true,
                });
            }
        },
    },
    methods: {
        ...mapActions("merchandise", [
            "createItem",
            "fetchItemDetail",
            "updateItem",
        ]),
        async onSubmit(values) {
            this.$store.commit("setIsLoading");

            try {
                const payload = {
                    ...values,
                    type:
                        this.mode === "create"
                            ? this.type
                            : this.item?.type || this.type,
                    stockAlertQuantity: (values.stockAlertQuantity !== null && values.stockAlertQuantity !== undefined && values.stockAlertQuantity !== "")
                        ? String(values.stockAlertQuantity)
                        : null,
                    profit: (values.profit !== null && values.profit !== undefined && values.profit !== "")
                        ? String(values.profit)
                        : null,
                };
                if (this.mode === "create") {
                    await this.createItem(payload);
                } else {
                    await this.updateItem({
                        id: this.item.id,
                        values: payload,
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

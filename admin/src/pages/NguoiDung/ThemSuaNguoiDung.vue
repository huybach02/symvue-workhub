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
                    <FormNguoiDung
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
import { postData } from "@/services/bases/postData";
import FormNguoiDung from "./FormNguoiDung.vue";
import { putData } from "@/services/bases/updateData";
import { getDataById } from "@/services/bases/getData";

export default {
    components: {
        FormNguoiDung,
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
        titleCreate() {
            return this.$t("title.create") + " " + this.$t("user.title");
        },
        titleUpdate() {
            return this.$t("title.update") + " " + this.$t("user.title");
        },
    },
    watch: {
        async dialog(isOpen) {
            if (isOpen && this.mode === "update") {
                const res = await getDataById(this.path, this.item.id);
                this.dataItem = res;
            }
        },
    },
    methods: {
        async onSubmit(values) {
            if (this.mode === "create") {
                this.$store.commit("setIsLoading");
                await postData(this.path, values, () => {
                    this.dialog = false;
                    this.$emit("reload");
                });
                this.$store.commit("unsetIsLoading");
            } else {
                this.$store.commit("setIsLoading");
                await putData(this.path, this.item.id, values, () => {
                    this.dialog = false;
                    this.$emit("reload");
                });
                this.$store.commit("unsetIsLoading");
            }
        },
    },
};
</script>

<style></style>

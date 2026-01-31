<template>
    <div class="text-center">
        <v-dialog v-model="dialog" max-width="1400" scrollable>
            <v-card
                prepend-icon="mdi-image-multiple"
                :title="$t('media_library.title')"
                class="position-relative"
            >
                <v-btn
                    icon="mdi-close"
                    variant="text"
                    size="small"
                    class="close-btn"
                    @click="dialog = false"
                />

                <div class="px-7 pb-6">
                    <v-tabs v-model="tab" align-tabs="start" color="primary">
                        <v-tab :value="1">
                            <v-icon start> mdi-image-multiple </v-icon>
                            {{ $t("media_library.tab_library") }}
                        </v-tab>
                        <v-tab :value="2">
                            <v-icon start color="error"> mdi-delete </v-icon>
                            <p class="text-error">
                                {{ $t("media_library.tab_trash") }}
                            </p>
                        </v-tab>
                    </v-tabs>

                    <v-tabs-window v-model="tab">
                        <!-- Danh sách ảnh -->
                        <v-tabs-window-item :value="1">
                            <MediaLibaryImageList
                                :is-multiple="isMultiple"
                                :active-tab="tab"
                                @close="dialog = false"
                            />
                        </v-tabs-window-item>

                        <!-- Thùng rác -->
                        <v-tabs-window-item :value="2">
                            <MediaLibraryTrash :active-tab="tab" />
                        </v-tabs-window-item>
                    </v-tabs-window>
                </div>
            </v-card>
        </v-dialog>
    </div>
</template>

<script>
import MediaLibaryImageList from "./MediaLibaryImageList.vue";
import MediaLibraryTrash from "./MediaLibraryTrash.vue";

export default {
    components: {
        MediaLibaryImageList,
        MediaLibraryTrash,
    },
    props: {
        modelValue: Boolean,
        isMultiple: {
            type: Boolean,
            default: false,
        },
    },
    emits: ["update:modelValue"],
    data() {
        return {
            dialog: this.modelValue,
            tab: 1,
        };
    },
    computed: {},
    watch: {
        modelValue(val) {
            this.dialog = val;
        },
        dialog(val) {
            this.$emit("update:modelValue", val);
        },
    },
};
</script>

<style scoped>
.close-btn {
    position: absolute;
    top: 8px;
    right: 8px;
    z-index: 1;
}
</style>

<template>
    <div>
        <div v-if="label" class="mb-2">
            {{ label }}
            <span v-if="required" class="text-red"> * </span>
        </div>

        <v-card
            variant="outlined"
            color="grey"
            :class="{ 'error-border': errorMessage }"
        >
            <v-card-item>
                <v-btn
                    color="primary"
                    variant="tonal"
                    prepend-icon="mdi-image-multiple"
                    @click="showModal = true"
                >
                    {{ $t("media_library.select_from_library") }}
                </v-btn>

                <MediaLibraryModal
                    v-model="showModal"
                    :is-multiple="isMultiple"
                />

                <div v-if="displayImages.length > 0" class="mt-4">
                    <div class="d-flex justify-space-between align-center mb-3">
                        <h3 class="text-subtitle-1 font-weight-medium">
                            {{ $t("media_library.selected_images") }} ({{
                                displayImages.length
                            }})
                        </h3>
                    </div>
                    <v-row>
                        <v-col
                            v-for="(image, index) in displayImages"
                            :key="index"
                            cols="12"
                            md="6"
                        >
                            <v-img
                                :src="image"
                                :aspect-ratio="1"
                                cover
                                class="rounded elevation-2"
                            />
                        </v-col>
                    </v-row>
                </div>
            </v-card-item>
        </v-card>
        <div
            v-if="errorMessage"
            class="text-error text-caption mt-2 ml-4"
            style="color: rgb(var(--v-theme-error))"
        >
            {{ errorMessage }}
        </div>
    </div>
</template>

<script>
import MediaLibraryModal from "./MediaLibraryModal.vue";

export default {
    components: { MediaLibraryModal },
    props: {
        label: {
            type: String,
            default: "",
        },
        required: {
            type: Boolean,
            default: false,
        },
        isMultiple: {
            type: Boolean,
            default: false,
        },
        errorMessage: {
            type: String,
            default: "",
        },
        modelValue: {
            type: [String, Array, Object],
            default: null,
        },
    },
    emits: ["selected"],
    data() {
        return {
            showModal: false,
        };
    },
    computed: {
        selectedMedia() {
            return this.$store.getters["media/selectedMedia"];
        },
        displayImages() {
            // Nếu có ảnh mới chọn từ modal, hiển thị ảnh đó
            if (this.selectedMedia.length > 0) {
                return this.selectedMedia.map((media) => media.path);
            }

            // Nếu không, hiển thị ảnh từ modelValue (giá trị form)
            if (this.modelValue) {
                if (Array.isArray(this.modelValue)) {
                    return this.modelValue;
                }
                if (typeof this.modelValue === "string") {
                    return [this.modelValue];
                }
                if (this.modelValue.path) {
                    return [this.modelValue.path];
                }
            }

            return [];
        },
    },
    watch: {
        selectedMedia(newVal) {
            if (this.isMultiple && newVal.length > 0) {
                this.$emit("selected", newVal);
            } else if (!this.isMultiple && newVal.length > 0) {
                this.$emit("selected", newVal[0]);
            } else {
                this.$emit("selected", null);
            }
        },
    },
    unmounted() {
        this.$store.dispatch("media/clearSelectedMedia");
    },
};
</script>

<style scoped>
.error-border {
    border: 1px solid rgb(var(--v-theme-error)) !important;
}
</style>

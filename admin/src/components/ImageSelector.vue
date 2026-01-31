<template>
    <v-card variant="tonal" color="primary">
        <v-card-item>
            <v-btn
                color="primary"
                variant="tonal"
                prepend-icon="mdi-image-multiple"
                @click="showModal = true"
            >
                {{ $t("media_library.select_from_library") }}
            </v-btn>

            <MediaLibraryModal v-model="showModal" :is-multiple="isMultiple" />

            <!-- Hiển thị ảnh đã chọn -->
            <div v-if="selectedMedia.length > 0" class="mt-4">
                <div class="d-flex justify-space-between align-center mb-3">
                    <h3 class="text-subtitle-1 font-weight-medium">
                        {{ $t("media_library.selected_images") }} ({{
                            selectedMedia.length
                        }})
                    </h3>
                </div>
                <v-row>
                    <v-col
                        v-for="media in selectedMedia"
                        :key="media.id"
                        cols="6"
                        md="1   "
                    >
                        <v-img
                            :src="media.path"
                            :aspect-ratio="1"
                            cover
                            class="rounded elevation-2"
                        />
                    </v-col>
                </v-row>
            </div>
        </v-card-item>
    </v-card>
</template>

<script>
import MediaLibraryModal from "./MediaLibraryModal.vue";

export default {
    components: { MediaLibraryModal },
    props: {
        isMultiple: {
            type: Boolean,
            default: false,
        },
    },
    data() {
        return {
            showModal: false,
        };
    },
    computed: {
        selectedMedia() {
            return this.$store.getters["media/selectedMedia"];
        },
    },
    unmounted() {
        this.$store.dispatch("media/clearSelectedMedia");
    },
};
</script>

<style></style>

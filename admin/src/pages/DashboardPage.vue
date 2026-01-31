<template>
    <div>
        Dashboard Content

        <ImageSelector
            v-model="form.avatar"
            label="Ảnh đại diện"
            :is-multiple="false"
        />
    </div>
</template>

<script>
import ImageSelector from "../components/ImageSelector.vue";

export default {
    components: { ImageSelector },
    data() {
        return {
            form: {
                avatar: null,
            },
        };
    },
};
</script>

<style></style>

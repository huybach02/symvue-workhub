<template>
    <div
        class="rich-editor-input w-100"
        :class="{
            'rich-editor-error': errorMessages && errorMessages.length > 0,
        }"
    >
        <div class="rich-editor-container w-100">
            <div v-if="label" class="mb-2 rich-editor-label">
                {{ label }}
            </div>
            <QuillEditor
                theme="snow"
                toolbar="full"
                :placeholder="placeholder"
                :content="content"
                content-type="html"
                @update:content="onContentChange"
                @blur="$emit('blur')"
            />
        </div>
        <div class="mt-2 px-4">
            <v-messages
                :active="errorMessages && errorMessages.length > 0"
                :messages="errorMessages"
                color="error"
            />
        </div>
    </div>
</template>

<script>
import { QuillEditor } from "@vueup/vue-quill";
import "@vueup/vue-quill/dist/vue-quill.snow.css";

export default {
    name: "RichEditor",
    components: {
        QuillEditor,
    },
    props: {
        modelValue: {
            type: String,
            default: "",
        },
        label: {
            type: String,
            default: "",
        },
        placeholder: {
            type: String,
            default: "",
        },
        errorMessages: {
            type: [String, Array],
            default: "",
        },
    },
    emits: ["update:modelValue", "blur"],
    computed: {
        content() {
            return this.modelValue;
        },
    },
    methods: {
        onContentChange(htmlContent) {
            const isEmpty =
                htmlContent === "<p><br></p>" || htmlContent.trim() === "";
            this.$emit("update:modelValue", isEmpty ? "" : htmlContent);
        },
    },
};
</script>

<style scoped>
.rich-editor-input :deep(.ql-container) {
    min-height: 200px;
    border-bottom-left-radius: 4px;
    border-bottom-right-radius: 4px;
    font-family: inherit;
    font-size: 16px;
}

.rich-editor-input :deep(.ql-toolbar) {
    border-top-left-radius: 4px;
    border-top-right-radius: 4px;
}

.rich-editor-input.rich-editor-error :deep(.ql-container),
.rich-editor-input.rich-editor-error :deep(.ql-toolbar) {
    border-color: rgb(var(--v-theme-error)) !important;
}

.rich-editor-input :deep(.v-messages) {
    color: rgb(var(--v-theme-error)) !important;
    opacity: 1 !important;
}
</style>

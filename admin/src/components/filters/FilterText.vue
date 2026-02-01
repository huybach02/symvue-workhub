<template>
    <v-text-field
        density="compact"
        variant="plain"
        :label="title"
        hide-details
        @input="update($event.target.value)"
    />
</template>

<script>
import { useDebounceVue } from "@/hooks/useDebounce";

export default {
    name: "FilterText",
    props: {
        title: {
            type: String,
            default: "",
        },
        delay: {
            type: Number,
            default: 500,
        },
    },
    emits: ["update"],
    created() {
        this.debouncedUpdate = useDebounceVue(this.handleUpdate, this.delay);
    },
    methods: {
        update(val) {
            this.debouncedUpdate(val);
        },
        handleUpdate(val) {
            this.$emit("update", {
                fieldType: "text",
                type: "contain",
                value: val,
            });
        },
    },
};
</script>

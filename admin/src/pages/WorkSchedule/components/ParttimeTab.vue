<template>
    <div>
        <Calendar :users="members" />
    </div>
</template>

<script>
import Calendar from "@/components/Calendar.vue";

export default {
    components: {
        Calendar,
    },
    props: {
        members: {
            type: Array,
            default: () => [],
        },
    },
};
</script>

<style></style>

<template>
    <v-row>
        <v-col v-for="item in items" :key="item.code" cols="12">
            <v-card class="pa-1" @click="$emit('select', item)">
                <v-list-item
                    class="px-2"
                    :title="item.title"
                    :subtitle="item.description"
                >
                    <template #prepend>
                        <v-icon :icon="item.icon" size="32" />
                    </template>
                </v-list-item>
            </v-card>
        </v-col>
    </v-row>
</template>

<script>
export default {
    name: "RequestTypeCards",
    props: {
        items: {
            type: Array,
            default: () => [],
        },
    },
    emits: ["select"],
};
</script>

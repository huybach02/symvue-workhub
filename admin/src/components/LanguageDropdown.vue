<template>
    <div>
        <!-- Inline mode: render as list items -->
        <template v-if="inline">
            <v-list-item
                v-for="(item, index) in items"
                :key="index"
                :value="index"
                :class="{ 'bg-primary-lighten-5': item.value === $i18n.locale }"
                @click="changeLanguage(item.value)"
            >
                <template #prepend>
                    <v-icon>mdi-translate</v-icon>
                </template>
                <v-list-item-title>{{ item.title }}</v-list-item-title>
            </v-list-item>
        </template>

        <!-- Standalone mode: render as menu button -->
        <v-menu v-else>
            <template #activator="{ props }">
                <v-btn color="primary" v-bind="props" variant="tonal">
                    <template #prepend>
                        <v-icon>mdi-translate</v-icon>
                    </template>
                    {{ currentLanguage }}
                </v-btn>
            </template>
            <v-list>
                <v-list-item
                    v-for="(item, index) in items"
                    :key="index"
                    :value="index"
                    @click="changeLanguage(item.value)"
                >
                    <template #prepend>
                        <v-icon>mdi-translate</v-icon>
                    </template>
                    <v-list-item-title>{{ item.title }}</v-list-item-title>
                </v-list-item>
            </v-list>
        </v-menu>
    </div>
</template>

<script>
export default {
    props: {
        inline: {
            type: Boolean,
            default: false,
        },
    },
    computed: {
        items() {
            return [
                { title: this.$t("language.en"), value: "en" },
                { title: this.$t("language.vi"), value: "vi" },
            ];
        },
        currentLanguage() {
            return this.items.find((item) => item.value === this.$i18n.locale)
                .title;
        },
    },
    methods: {
        changeLanguage(lang) {
            localStorage.setItem("lang", lang);
            // Reload lại trang để đảm bảo UI cập nhật ngay lập tức
            location.reload();
        },
    },
};
</script>

<style></style>

<template>
    <div>
        <router-view />
    </div>
</template>

<script>
export default {
    name: "AdminApp",
};
</script>

<style>
.v-input--disabled .v-field__append-inner {
    display: none !important;
}

.sticky-actions-bar {
    position: sticky;
    bottom: -24px;
    background: rgb(var(--v-theme-surface));
    margin: 16px -24px -24px -24px;
    padding: 16px 24px;
    z-index: 2;
    border-top: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}
</style>

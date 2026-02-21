<template>
    <div>
        <v-defaults-provider :defaults="{ VTab: { ripple: false } }">
            <v-tabs slider-transition="grow">
                <v-tab
                    value="one"
                    :class="{ 'text-primary font-weight-bold': tab === 'one' }"
                    @click="changeTab('one')"
                >
                    {{ $t("thoi_gian_lam_viec.fulltime") }}
                </v-tab>
                <v-tab
                    value="two"
                    :class="{ 'text-primary font-weight-bold': tab === 'two' }"
                    @click="changeTab('two')"
                >
                    {{ $t("thoi_gian_lam_viec.parttime") }}
                </v-tab>
            </v-tabs>

            <v-tabs-window v-model="tab" class="mt-5">
                <v-tabs-window-item value="one">
                    <Fulltime :permission="permission" />
                </v-tabs-window-item>
                <v-tabs-window-item value="two">
                    <Parttime :permission="permission" />
                </v-tabs-window-item>
            </v-tabs-window>
        </v-defaults-provider>
    </div>
</template>

<script>
import { usePermission } from "@/hooks/usePermission";
import Fulltime from "./components/Fulltime.vue";
import Parttime from "./components/Parttime.vue";

export default {
    components: {
        Fulltime,
        Parttime,
    },
    data() {
        return {
            tab: "one",
        };
    },
    computed: {
        permission() {
            return usePermission("/thoi-gian-lam-viec");
        },
    },
    methods: {
        changeTab(tab) {
            this.tab = tab;
        },
    },
};
</script>

<style></style>

<template>
    <div>
        <v-btn
            icon
            size="small"
            variant="outlined"
            color="primary"
            @click="dialog = true"
        >
            <v-icon>mdi-briefcase-account-outline</v-icon>
        </v-btn>

        <v-dialog v-model="dialog" max-width="1100" scrollable persistent>
            <v-card
                :title="
                    $t('position.position_and_contract', { name: item.name })
                "
                prepend-icon="mdi-briefcase-account-outline"
                class="position-relative"
            >
                <v-btn
                    icon="mdi-close"
                    variant="text"
                    size="small"
                    class="position-absolute"
                    style="top: 8px; right: 8px"
                    @click="dialog = false"
                />

                <v-card-text>
                    <v-tabs v-model="tab" color="primary">
                        <v-tab value="position">
                            {{ $t("position.position") }}
                        </v-tab>
                        <v-tab value="contract">
                            {{ $t("position.contract") }}
                        </v-tab>
                    </v-tabs>

                    <v-tabs-window v-model="tab" class="mt-4" :touch="false">
                        <v-tabs-window-item value="position">
                            <UserPositionAssignmentTab
                                :item="item"
                                :path="path"
                                :active="dialog && tab === 'position'"
                                @reload="$emit('reload')"
                            />
                        </v-tabs-window-item>

                        <v-tabs-window-item value="contract">
                            <UserPositionContractTab
                                :item="item"
                                :path="path"
                                :active="dialog && tab === 'contract'"
                                @reload="$emit('reload')"
                            />
                        </v-tabs-window-item>
                    </v-tabs-window>
                </v-card-text>
            </v-card>
        </v-dialog>
    </div>
</template>

<script>
import UserPositionContractTab from "./components/UserPositionContractTab.vue";
import UserPositionAssignmentTab from "./components/UserPositionAssignmentTab.vue";

export default {
    components: {
        UserPositionContractTab,
        UserPositionAssignmentTab,
    },
    props: {
        path: {
            type: String,
            default: "",
        },
        item: {
            type: Object,
            default: null,
        },
    },
    emits: ["reload"],
    data() {
        return {
            dialog: false,
            tab: "position",
        };
    },
    watch: {
        dialog(isOpen) {
            if (!isOpen) {
                this.tab = "position";
            }
        },
    },
};
</script>

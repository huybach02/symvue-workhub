<template>
    <div>
        <v-row>
            <v-col cols="12">
                <SaleOrderList
                    v-if="permission?.index"
                    :path="path"
                    :permission="permission"
                    @reload="getDanhSach"
                />
            </v-col>
        </v-row>
    </div>
</template>

<script>
import { API_ROUTES_CONFIG } from "@/configs/apiRouteConfig";
import SaleOrderList from "./SaleOrderList.vue";
import { usePermission } from "@/hooks/usePermission";
import { mapActions } from "vuex";

export default {
    name: "SaleOrder",
    components: {
        SaleOrderList,
    },
    data() {
        return {
            path: API_ROUTES_CONFIG.saleOrder,
            lastParams: null,
        };
    },
    computed: {
        permission() {
            return usePermission(this.path);
        },
    },
    methods: {
        ...mapActions("saleOrder", ["fetchItems"]),
        async getDanhSach(params) {
            if (
                params &&
                typeof params === "object" &&
                !(params instanceof Event)
            ) {
                this.lastParams = params;
            }
            await this.fetchItems(this.lastParams || params);
        },
    },
};
</script>

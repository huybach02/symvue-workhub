import { leaveRequestSchema } from "@/utils/schemas/request/leaveRequest";
import { stockInRequestSchema } from "@/utils/schemas/request/stockInRequest";
import RequestLeaveDetailFields from "./leave/RequestLeaveDetailFields.vue";
import RequestLeaveFormFields from "./leave/RequestLeaveFormFields.vue";
import RequestStockInDetailFields from "./stock-in/RequestStockInDetailFields.vue";
import RequestStockInFormFields from "./stock-in/RequestStockInFormFields.vue";

export const REQUEST_TYPE_COMPONENT_REGISTRY = {
    leave: {
        formComponent: RequestLeaveFormFields,
        detailComponent: RequestLeaveDetailFields,
        validationSchema: leaveRequestSchema,
        initialValues: {
            leaveType: "annual_leave",
            startDate: "",
            endDate: "",
            reason: "",
            handoverNote: "",
        },
        mapPayloadToForm(payload = {}) {
            return {
                leaveType: payload.leaveType ?? "annual_leave",
                startDate: payload.startDate ?? "",
                endDate: payload.endDate ?? "",
                reason: payload.reason ?? "",
                handoverNote: payload.handoverNote ?? "",
            };
        },
    },
    "stock:stock-in": {
        formComponent: RequestStockInFormFields,
        detailComponent: RequestStockInDetailFields,
        validationSchema: stockInRequestSchema,
        initialValues: {
            providers: [],
        },
        mapPayloadToForm(payload = {}) {
            return {
                providers: Array.isArray(payload.providers)
                    ? payload.providers.map((providerGroup) => ({
                          providerId: providerGroup.providerId ?? null,
                          items: Array.isArray(providerGroup.items)
                              ? providerGroup.items.map((item) => ({
                                    merchandiseId: item.merchandiseId ?? null,
                                    quantity:
                                        item.quantity === null ||
                                        item.quantity === undefined
                                            ? null
                                            : Number(item.quantity),
                                    unitId: item.unitId ?? null,
                                    price:
                                        item.price === null ||
                                        item.price === undefined
                                            ? null
                                            : Number(item.price),
                                    currency: item.currency ?? "VND",
                                    factorToBase: item.factorToBase ?? null,
                                    note: item.note ?? "",
                                }))
                              : [],
                      }))
                    : [],
            };
        },
    },
};

export function getRequestTypeComponentConfig(type) {
    return REQUEST_TYPE_COMPONENT_REGISTRY[type] ?? null;
}

import { leaveRequestSchema } from "@/utils/schemas/request/leaveRequest";
import { stockInRequestSchema } from "@/utils/schemas/request/stockInRequest";
import { productionRequestSchema } from "@/utils/schemas/request/productionRequest";
import RequestLeaveDetailFields from "./leave/RequestLeaveDetailFields.vue";
import RequestLeaveFormFields from "./leave/RequestLeaveFormFields.vue";
import RequestProductionDetailFields from "./production/RequestProductionDetailFields.vue";
import RequestProductionFormFields from "./production/RequestProductionFormFields.vue";
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
                                    lineId: item.lineId ?? crypto.randomUUID(),
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
    "stock:production": {
        formComponent: RequestProductionFormFields,
        detailComponent: RequestProductionDetailFields,
        validationSchema: productionRequestSchema,
        initialValues: {
            items: [],
        },
        mapPayloadToForm(payload = {}) {
            return {
                items: Array.isArray(payload.items)
                    ? payload.items.map((item) => ({
                          lineId: item.lineId ?? crypto.randomUUID(),
                          finishedProductId: item.finishedProductId ?? null,
                          finishedProductName: item.finishedProductName ?? "",
                          quantity:
                              item.quantity === null ||
                              item.quantity === undefined
                                  ? null
                                  : Number(item.quantity),
                          outputUnitId: item.outputUnitId ?? null,
                          outputUnitName: item.outputUnitName ?? "",
                          expectedWastePercent:
                              item.expectedWastePercent === null ||
                              item.expectedWastePercent === undefined
                                  ? null
                                  : Number(item.expectedWastePercent),
                          materials: Array.isArray(item.materials)
                              ? item.materials.map((material) => ({
                                    ingredientId:
                                        material.ingredientId ?? null,
                                    ingredientName:
                                        material.ingredientName ?? "",
                                    quantity:
                                        material.quantity === null ||
                                        material.quantity === undefined
                                            ? null
                                            : Number(material.quantity),
                                    unitId: material.unitId ?? null,
                                    unitName: material.unitName ?? "",
                                    wasteRate:
                                        material.wasteRate === null ||
                                        material.wasteRate === undefined
                                            ? null
                                            : Number(material.wasteRate),
                                    note: material.note ?? "",
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

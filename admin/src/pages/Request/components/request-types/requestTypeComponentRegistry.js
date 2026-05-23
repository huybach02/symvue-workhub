import { leaveRequestSchema } from "@/utils/schemas/request/leaveRequest";
import RequestLeaveDetailFields from "./leave/RequestLeaveDetailFields.vue";
import RequestLeaveFormFields from "./leave/RequestLeaveFormFields.vue";

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
};

export function getRequestTypeComponentConfig(type) {
    return REQUEST_TYPE_COMPONENT_REGISTRY[type] ?? null;
}

import * as yup from "yup";
import { buildDateRule, buildStringRule } from "../../validationBuilder";
import { i18n } from "@/plugins/i18n";

const t = (key, params) => i18n.global.t(key, params);

export const leaveRequestSchema = yup.object({
    leaveType: buildStringRule(t("field.leave_type"), {
        required: true,
    }),
    startDate: buildDateRule(t("field.ngay_bat_dau"), {
        required: true,
    }),
    endDate: buildDateRule(t("field.ngay_ket_thuc"), {
        required: true,
    }).test(
        "end-after-start",
        "Ngày kết thúc phải lớn hơn hoặc bằng ngày bắt đầu",
        function validateEndDate(value) {
            const { startDate } = this.parent;

            if (!startDate || !value) {
                return true;
            }

            return new Date(value) >= new Date(startDate);
        },
    ),
    reason: buildStringRule(t("field.request_reason"), {
        required: true,
        max: 1000,
    }),
    handoverNote: buildStringRule(t("field.handover_note"), {
        required: false,
        max: 1000,
    }),
});

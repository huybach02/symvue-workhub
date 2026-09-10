import * as yup from "yup";
import { i18n } from "@/plugins/i18n";

export const diningTableRangeSchema = yup.object({
    from: yup
        .number()
        .typeError(() => i18n.global.t("dining_table.validation.from_required"))
        .required(() => i18n.global.t("dining_table.validation.from_required"))
        .integer(() => i18n.global.t("dining_table.validation.from_positive"))
        .min(1, () => i18n.global.t("dining_table.validation.from_positive")),
    to: yup
        .number()
        .typeError(() => i18n.global.t("dining_table.validation.to_required"))
        .required(() => i18n.global.t("dining_table.validation.to_required"))
        .integer(() => i18n.global.t("dining_table.validation.to_positive"))
        .min(1, () => i18n.global.t("dining_table.validation.to_positive"))
        .min(yup.ref("from"), () => i18n.global.t("dining_table.validation.to_gte_from")),
});

import * as yup from "yup";
import { buildStringRule } from "../validationBuilder";
import { i18n } from "@/plugins/i18n";

export const ROOT_PARENT_VALUE = "__root__";

const t = (key) => i18n.global.t(key);

export const categorySchema = yup.object({
    name: buildStringRule(t("field.name"), {
        required: true,
        min: 3,
        max: 255,
    }),

    parentId: yup
        .mixed()
        .required("Danh mục cha là bắt buộc"),
});

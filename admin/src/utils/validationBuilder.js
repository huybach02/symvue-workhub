import * as yup from "yup";
import { i18n } from "@/plugins/i18n";

const t = (key, values) => i18n.global.t(key, values);

const applyRequired = (rule, required = true) => {
    if (required) return rule.required();
    return rule.nullable().notRequired();
};

export const buildStringRule = (label, options = {}) => {
    const { required = true, min = null, max = null, trim = true } = options;
    let rule = yup.string().label(label);

    if (trim) rule = rule.trim();
    if (min !== null) rule = rule.min(min);
    if (max !== null) rule = rule.max(max);

    return applyRequired(rule, required);
};

export const buildEmailRule = (label, options = {}) => {
    const { required = true } = options;
    let rule = yup.string().label(label).email().max(255);
    return applyRequired(rule, required);
};

export const buildNumberRule = (label, options = {}) => {
    const {
        required = true,
        min = null,
        max = null,
        integer = false,
    } = options;

    let rule = yup
        .number()
        .label(label)
        .typeError(({ label }) =>
            t("validation.number.type_error", { field: label }),
        );

    if (integer) rule = rule.integer();
    if (min !== null) rule = rule.min(min);
    if (max !== null) rule = rule.max(max);

    rule = rule.transform((value, originalValue) => {
        return String(originalValue).trim() === "" ? null : value;
    });

    return applyRequired(rule, required);
};

export const buildBooleanRule = (label, options = {}) => {
    const { required = true } = options;
    let rule = yup.boolean().label(label);
    return applyRequired(rule, required);
};

export const buildPasswordRule = (label, options = {}) => {
    const { required = true, min = 8, strong = false } = options;
    let rule = yup.string().label(label).min(min);

    if (strong) {
        rule = rule
            .matches(/[a-z]/, ({ label }) =>
                t("validation.password.lowercase", { field: label }),
            )
            .matches(/[A-Z]/, ({ label }) =>
                t("validation.password.uppercase", { field: label }),
            )
            .matches(/[0-9]/, ({ label }) =>
                t("validation.password.number", { field: label }),
            )
            .matches(/[^a-zA-Z0-9]/, ({ label }) =>
                t("validation.password.special", { field: label }),
            );
    }
    return applyRequired(rule, required);
};

export const buildConfirmPasswordRule = (
    label,
    targetFieldName = "password",
) => {
    return yup
        .string()
        .label(label)
        .required()
        .oneOf([yup.ref(targetFieldName)], ({ label }) =>
            t("validation.mixed.one_of", { field: label }),
        );
};

export const buildPhoneRule = (label, options = {}) => {
    const { required = true } = options;
    const phoneRegex = /(84|0[3|5|7|8|9])+([0-9]{8})\b/;

    let rule = yup.string().label(label);

    rule = rule.test(
        "is-phone",
        ({ label }) => t("validation.phone.invalid", { field: label }),
        (value) => {
            if (!value) return !required;
            return phoneRegex.test(value);
        },
    );
    return applyRequired(rule, required);
};

export const buildFileRule = (label, options = {}) => {
    const {
        required = true,
        maxSizeMB = 2,
        formats = ["image/jpeg", "image/png", "image/jpg"],
    } = options;
    let rule = yup.mixed().label(label);

    rule = rule.test(
        "fileSize",
        ({ label }) =>
            t("validation.file.max_size", { field: label, max: maxSizeMB }),
        (value) => {
            if (!value) return !required;
            return value && value.size <= maxSizeMB * 1024 * 1024;
        },
    );

    rule = rule.test(
        "fileType",
        ({ label }) => t("validation.file.invalid_type", { field: label }),
        (value) => {
            if (!value) return !required;
            return value && formats.includes(value.type);
        },
    );
    return applyRequired(rule, required);
};

export const buildOtpRule = (label, options = {}) => {
    const { required = true } = options;
    let rule = yup
        .string()
        .label(label)
        .length(6)
        .matches(/^[0-9]+$/);
    return applyRequired(rule, required);
};

// Validate URL
export const buildUrlRule = (label, options = {}) => {
    const { required = true } = options;
    let rule = yup
        .string()
        .label(label)
        .url(({ label }) => t("validation.url.invalid", { field: label }));
    return applyRequired(rule, required);
};

// Validate ngày tháng
export const buildDateRule = (label, options = {}) => {
    const { required = true, min = null, max = null } = options;
    let rule = yup
        .date()
        .label(label)
        .typeError(({ label }) =>
            t("validation.date.type_error", { field: label }),
        );

    if (min !== null) {
        rule = rule.min(min, ({ label, min }) =>
            t("validation.date.min", { field: label, min }),
        );
    }
    if (max !== null) {
        rule = rule.max(max, ({ label, max }) =>
            t("validation.date.max", { field: label, max }),
        );
    }

    return applyRequired(rule, required);
};

// Validate mảng
export const buildArrayRule = (label, options = {}) => {
    const { required = true, min = null, max = null, of = null } = options;
    let rule = yup.array().label(label);

    if (min !== null) {
        rule = rule.min(min, ({ label, min }) =>
            t("validation.array.min", { field: label, min }),
        );
    }
    if (max !== null) {
        rule = rule.max(max, ({ label, max }) =>
            t("validation.array.max", { field: label, max }),
        );
    }
    if (of !== null) {
        rule = rule.of(of);
    }

    return applyRequired(rule, required);
};

// Validate select/dropdown (oneOf)
export const buildSelectRule = (label, allowedValues = [], options = {}) => {
    const { required = true } = options;
    let rule = yup
        .mixed()
        .label(label)
        .oneOf(allowedValues, ({ label }) =>
            t("validation.select.invalid", { field: label }),
        );
    return applyRequired(rule, required);
};

// Validate username
export const buildUsernameRule = (label, options = {}) => {
    const { required = true, min = 3, max = 30 } = options;
    const usernameRegex = /^[a-zA-Z0-9_]+$/;

    let rule = yup
        .string()
        .label(label)
        .min(min)
        .max(max)
        .matches(usernameRegex, ({ label }) =>
            t("validation.username.invalid", { field: label }),
        );

    return applyRequired(rule, required);
};

// Validate mã bưu điện (Việt Nam)
export const buildPostalCodeRule = (label, options = {}) => {
    const { required = true } = options;
    const postalCodeRegex = /^[0-9]{6}$/;

    let rule = yup
        .string()
        .label(label)
        .matches(postalCodeRegex, ({ label }) =>
            t("validation.postal_code.invalid", { field: label }),
        );

    return applyRequired(rule, required);
};

// Validate số thẻ tín dụng (Luhn algorithm)
export const buildCreditCardRule = (label, options = {}) => {
    const { required = true } = options;

    let rule = yup.string().label(label);

    rule = rule.test(
        "is-credit-card",
        ({ label }) => t("validation.credit_card.invalid", { field: label }),
        (value) => {
            if (!value) return !required;
            // Loại bỏ khoảng trắng và dấu gạch ngang
            const sanitized = value.replace(/[\s-]/g, "");
            // Kiểm tra chỉ chứa số và độ dài 13-19 ký tự
            if (!/^\d{13,19}$/.test(sanitized)) return false;
            // Thuật toán Luhn
            let sum = 0;
            let isEven = false;
            for (let i = sanitized.length - 1; i >= 0; i--) {
                let digit = parseInt(sanitized[i]);
                if (isEven) {
                    digit *= 2;
                    if (digit > 9) digit -= 9;
                }
                sum += digit;
                isEven = !isEven;
            }
            return sum % 10 === 0;
        },
    );

    return applyRequired(rule, required);
};

// Validate địa chỉ IP (IPv4)
export const buildIpAddressRule = (label, options = {}) => {
    const { required = true, version = 4 } = options;
    const ipv4Regex =
        /^(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/;
    const ipv6Regex =
        /^(([0-9a-fA-F]{1,4}:){7}[0-9a-fA-F]{1,4}|([0-9a-fA-F]{1,4}:){1,7}:|([0-9a-fA-F]{1,4}:){1,6}:[0-9a-fA-F]{1,4}|([0-9a-fA-F]{1,4}:){1,5}(:[0-9a-fA-F]{1,4}){1,2}|([0-9a-fA-F]{1,4}:){1,4}(:[0-9a-fA-F]{1,4}){1,3}|([0-9a-fA-F]{1,4}:){1,3}(:[0-9a-fA-F]{1,4}){1,4}|([0-9a-fA-F]{1,4}:){1,2}(:[0-9a-fA-F]{1,4}){1,5}|[0-9a-fA-F]{1,4}:((:[0-9a-fA-F]{1,4}){1,6})|:((:[0-9a-fA-F]{1,4}){1,7}|:)|fe80:(:[0-9a-fA-F]{0,4}){0,4}%[0-9a-zA-Z]{1,}|::(ffff(:0{1,4}){0,1}:){0,1}((25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])\.){3}(25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])|([0-9a-fA-F]{1,4}:){1,4}:((25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9])\.){3}(25[0-5]|(2[0-4]|1{0,1}[0-9]){0,1}[0-9]))$/;

    const regex = version === 6 ? ipv6Regex : ipv4Regex;

    let rule = yup
        .string()
        .label(label)
        .matches(regex, ({ label }) =>
            t("validation.ip_address.invalid", {
                field: label,
                version: `IPv${version}`,
            }),
        );

    return applyRequired(rule, required);
};

// Validate slug (URL-friendly string)
export const buildSlugRule = (label, options = {}) => {
    const { required = true, min = 1, max = 255 } = options;
    const slugRegex = /^[a-z0-9]+(?:-[a-z0-9]+)*$/;

    let rule = yup
        .string()
        .label(label)
        .min(min)
        .max(max)
        .matches(slugRegex, ({ label }) =>
            t("validation.slug.invalid", { field: label }),
        );

    return applyRequired(rule, required);
};

// Validate mã màu hex
export const buildColorRule = (label, options = {}) => {
    const { required = true, allowShorthand = true } = options;
    const colorRegex = allowShorthand
        ? /^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/
        : /^#[A-Fa-f0-9]{6}$/;

    let rule = yup
        .string()
        .label(label)
        .matches(colorRegex, ({ label }) =>
            t("validation.color.invalid", { field: label }),
        );

    return applyRequired(rule, required);
};

// Validate UUID (v4)
export const buildUuidRule = (label, options = {}) => {
    const { required = true, version = 4 } = options;
    const uuidRegex =
        version === 4
            ? /^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i
            : /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i;

    let rule = yup
        .string()
        .label(label)
        .matches(uuidRegex, ({ label }) =>
            t("validation.uuid.invalid", { field: label }),
        );

    return applyRequired(rule, required);
};

// Validate JSON string
export const buildJsonRule = (label, options = {}) => {
    const { required = true } = options;

    let rule = yup.string().label(label);

    rule = rule.test(
        "is-json",
        ({ label }) => t("validation.json.invalid", { field: label }),
        (value) => {
            if (!value) return !required;
            try {
                JSON.parse(value);
                return true;
            } catch {
                return false;
            }
        },
    );

    return applyRequired(rule, required);
};

// Validate thời gian (HH:mm hoặc HH:mm:ss)
export const buildTimeRule = (label, options = {}) => {
    const { required = true, includeSeconds = false } = options;
    const timeRegex = includeSeconds
        ? /^([01]\d|2[0-3]):([0-5]\d):([0-5]\d)$/
        : /^([01]\d|2[0-3]):([0-5]\d)$/;

    let rule = yup
        .string()
        .label(label)
        .matches(timeRegex, ({ label }) =>
            t("validation.time.invalid", { field: label }),
        );

    return applyRequired(rule, required);
};

// Validate tuổi
export const buildAgeRule = (label, options = {}) => {
    const { required = true, min = 0, max = 150 } = options;

    let rule = yup
        .number()
        .label(label)
        .integer()
        .typeError(({ label }) =>
            t("validation.number.type_error", { field: label }),
        );

    if (min !== null) {
        rule = rule.min(min, ({ label, min }) =>
            t("validation.age.min", { field: label, min }),
        );
    }
    if (max !== null) {
        rule = rule.max(max, ({ label, max }) =>
            t("validation.age.max", { field: label, max }),
        );
    }

    rule = rule.transform((value, originalValue) => {
        return String(originalValue).trim() === "" ? null : value;
    });

    return applyRequired(rule, required);
};

// Validate phần trăm (0-100)
export const buildPercentageRule = (label, options = {}) => {
    const { required = true, allowDecimal = true } = options;

    let rule = yup
        .number()
        .label(label)
        .min(0, ({ label }) => t("validation.percentage.min", { field: label }))
        .max(100, ({ label }) =>
            t("validation.percentage.max", { field: label }),
        )
        .typeError(({ label }) =>
            t("validation.number.type_error", { field: label }),
        );

    if (!allowDecimal) {
        rule = rule.integer();
    }

    rule = rule.transform((value, originalValue) => {
        return String(originalValue).trim() === "" ? null : value;
    });

    return applyRequired(rule, required);
};

// Validate tiền tệ (số dương)
export const buildCurrencyRule = (label, options = {}) => {
    const {
        required = true,
        min = 0,
        max = null,
        allowDecimal = true,
    } = options;

    let rule = yup
        .number()
        .label(label)
        .typeError(({ label }) =>
            t("validation.number.type_error", { field: label }),
        );

    if (!allowDecimal) {
        rule = rule.integer();
    }

    if (min !== null) {
        rule = rule.min(min, ({ label, min }) =>
            t("validation.currency.min", { field: label, min }),
        );
    }
    if (max !== null) {
        rule = rule.max(max, ({ label, max }) =>
            t("validation.currency.max", { field: label, max }),
        );
    }

    rule = rule.transform((value, originalValue) => {
        return String(originalValue).trim() === "" ? null : value;
    });

    return applyRequired(rule, required);
};

// Validate CCCD/CMND Việt Nam
export const buildCccdRule = (label, options = {}) => {
    const { required = true } = options;
    // CMND: 9 hoặc 12 số, CCCD: 12 số
    const cccdRegex = /^[0-9]{9}$|^[0-9]{12}$/;

    let rule = yup
        .string()
        .label(label)
        .matches(cccdRegex, ({ label }) =>
            t("validation.cccd.invalid", { field: label }),
        );

    return applyRequired(rule, required);
};

// Validate mã số thuế Việt Nam
export const buildTaxCodeRule = (label, options = {}) => {
    const { required = true } = options;
    // Mã số thuế: 10 hoặc 13 số (10 số + 3 số chi nhánh)
    const taxCodeRegex = /^[0-9]{10}$|^[0-9]{10}-[0-9]{3}$/;

    let rule = yup
        .string()
        .label(label)
        .matches(taxCodeRegex, ({ label }) =>
            t("validation.tax_code.invalid", { field: label }),
        );

    return applyRequired(rule, required);
};

// Validate image/avatar (URL string hoặc object)
export const buildImageRule = (label, options = {}) => {
    const { required = true } = options;

    let rule = yup
        .mixed()
        .label(label)
        .test(
            "is-valid-image",
            ({ label }) => t("validation.image.invalid", { field: label }),
            (value) => {
                if (!value) return !required;
                // Chấp nhận string (URL) hoặc object có thuộc tính path
                if (typeof value === "string") return value.trim().length > 0;
                if (typeof value === "object" && value !== null) {
                    return value.path && typeof value.path === "string";
                }
                return false;
            },
        );

    return applyRequired(rule, required);
};

// Validate có điều kiện dựa trên giá trị của một field khác trong cùng form.
export const buildConditionalRule = (label, dependsOn, when) => {
    const checkCondition =
        typeof when === "function" ? when : (val) => val === when;

    return yup
        .mixed()
        .nullable()
        .test(`${dependsOn}-${label}-conditional`, "", function (value) {
            const dependedValue = this.parent?.[dependsOn];
            if (!checkCondition(dependedValue)) return true;
            if (value !== null && value !== undefined && value !== "")
                return true;
            return this.createError({
                message: t("validation.mixed.required", { field: label }),
            });
        });
};

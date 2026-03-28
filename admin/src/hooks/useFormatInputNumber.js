import { reactive } from "vue";

const sanitizeNumberInput = (value) =>
    String(value ?? "").replace(/[^\d]/g, "");

const formatNumberWithDot = (value) => {
    const digits = sanitizeNumberInput(value);

    if (!digits) {
        return "";
    }

    return digits.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
};

export const useFormatInputNumber = () => {
    const formattedValues = reactive({});

    const syncFormattedNumber = (fieldName, value) => {
        formattedValues[fieldName] = formatNumberWithDot(value);
    };

    const getFormattedNumberValue = (fieldName, value) => {
        const sanitizedValue = sanitizeNumberInput(value);
        const currentFormatted = formattedValues[fieldName] ?? "";

        if (sanitizeNumberInput(currentFormatted) !== sanitizedValue) {
            syncFormattedNumber(fieldName, value);
        }

        return formattedValues[fieldName] ?? "";
    };

    const handleFormattedNumberInput = (
        fieldName,
        inputValue,
        handleChange,
    ) => {
        const sanitizedValue = sanitizeNumberInput(inputValue);

        formattedValues[fieldName] = formatNumberWithDot(sanitizedValue);
        handleChange(sanitizedValue);
    };

    const bindFormattedNumberField = ({ fieldName, field, handleChange }) => ({
        modelValue: getFormattedNumberValue(fieldName, field.value),
        "onUpdate:modelValue": (value) =>
            handleFormattedNumberInput(fieldName, value, handleChange),
        onBlur: field.onBlur,
    });

    const bindFormattedNumberModel = ({ fieldName, value, onChange }) => ({
        modelValue: getFormattedNumberValue(fieldName, value),
        "onUpdate:modelValue": (inputValue) =>
            handleFormattedNumberInput(fieldName, inputValue, onChange),
    });

    return {
        formatNumberWithDot,
        getFormattedNumberValue,
        handleFormattedNumberInput,
        syncFormattedNumber,
        bindFormattedNumberField,
        bindFormattedNumberModel,
    };
};

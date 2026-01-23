export const messageValidate = {
    required: "{field} là bắt buộc",
    email: "{field} không đúng định dạng",
    min: "{field} phải từ {min} ký tự",
    max: "{field} phải từ {max} ký tự",
    number: "{field} phải là số",
    boolean: "{field} phải là boolean",
    minNumber: "{field} phải lớn hơn {min}",
    maxNumber: "{field} phải nhỏ hơn {max}",
};

export const renderMessage = (message, field = "") => {
    if (field) {
        return message.replace("{field}", field);
    }
    // Viết hoa chữ cái đầu
    const messageWithoutField = message.replace("{field}", "").trim();
    return (
        messageWithoutField.charAt(0).toUpperCase() +
        messageWithoutField.slice(1)
    );
};

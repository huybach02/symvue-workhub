export const messageValidate = {
    required: "{field} là bắt buộc",
    email: "{field} không đúng định dạng",
    min: "{field} phải từ {min} ký tự",
    max: "{field} phải từ {max} ký tự",
};

export const renderMessage = (message, field) => {
    return message.replace("{field}", field);
};

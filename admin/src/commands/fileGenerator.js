import fs from "fs";
import path from "path";
import { fileURLToPath } from "url";

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

/**
 * Utility để generate files từ templates
 */
class FileGenerator {
    /**
     * Đọc nội dung từ template file
     * @param {string} templateName - Tên template (List, Form, Module, CreateEdit, StoreModule)
     * @returns {string} - Nội dung template
     */
    static readTemplate(templateName) {
        const templatePath = path.join(
            __dirname,
            "../templates",
            `${templateName}.boilerplate`,
        );

        if (!fs.existsSync(templatePath)) {
            throw new Error(
                `Template ${templateName} không tồn tại tại ${templatePath}`,
            );
        }

        return fs.readFileSync(templatePath, "utf-8");
    }

    /**
     * Thay thế các placeholder trong template
     * @param {string} content - Nội dung template
     * @param {string} moduleName - Tên module (PascalCase)
     * @returns {string} - Nội dung đã được thay thế
     */
    static replacePlaceholders(content, moduleName) {
        // Chuyển đổi tên module sang các format khác nhau
        const moduleLower = this.toCamelCase(moduleName);
        const moduleSnake = this.toSnakeCase(moduleName);

        return content
            .replace(/\{\{MODULE_NAME\}\}/g, moduleName)
            .replace(/\{\{MODULE_LOWER\}\}/g, moduleLower)
            .replace(/\{\{MODULE_SNAKE\}\}/g, moduleSnake);
    }

    /**
     * Chuyển PascalCase sang camelCase
     * @param {string} str - Chuỗi PascalCase
     * @returns {string} - Chuỗi camelCase
     */
    static toCamelCase(str) {
        return str.charAt(0).toLowerCase() + str.slice(1);
    }

    /**
     * Chuyển PascalCase sang snake_case
     * @param {string} str - Chuỗi PascalCase
     * @returns {string} - Chuỗi snake_case
     */
    static toSnakeCase(str) {
        return str.replace(/([a-z0-9])([A-Z])/g, "$1_$2").toLowerCase();
    }

    /**
     * Chuyển PascalCase sang kebab-case
     * @param {string} str - Chuỗi PascalCase
     * @returns {string} - Chuỗi kebab-case
     */
    static toKebabCase(str) {
        return str.replace(/([a-z0-9])([A-Z])/g, "$1-$2").toLowerCase();
    }

    /**
     * Tạo file từ template
     * @param {string} templateName - Tên template
     * @param {string} outputPath - Đường dẫn file output
     * @param {string} moduleName - Tên module
     */
    static generateFile(templateName, outputPath, moduleName) {
        // Đọc template
        const templateContent = this.readTemplate(templateName);

        // Thay thế placeholders
        const content = this.replacePlaceholders(templateContent, moduleName);

        // Tạo thư mục nếu chưa tồn tại
        const dir = path.dirname(outputPath);
        if (!fs.existsSync(dir)) {
            fs.mkdirSync(dir, { recursive: true });
        }

        // Ghi file
        fs.writeFileSync(outputPath, content, "utf-8");

        console.log(`✓ Đã tạo file: ${outputPath}`);
    }

    /**
     * Tạo tất cả các file cho một module
     * @param {string} moduleName - Tên module (PascalCase)
     * @param {string} pagesDir - Đường dẫn thư mục pages
     */
    static generateModuleFiles(moduleName, pagesDir) {
        const moduleDir = path.join(pagesDir, moduleName);
        const srcDir = path.dirname(pagesDir);
        const storeModulesDir = path.join(srcDir, "store", "modules");
        const moduleLower = this.toCamelCase(moduleName);

        // Tạo thư mục module
        if (!fs.existsSync(moduleDir)) {
            fs.mkdirSync(moduleDir, { recursive: true });
        }

        // Danh sách các file cần tạo
        const files = [
            { template: "List", filename: `${moduleName}List.vue` },
            { template: "Form", filename: `Form${moduleName}.vue` },
            { template: "Module", filename: `${moduleName}.vue` },
            { template: "CreateEdit", filename: `CreateEdit${moduleName}.vue` },
        ];

        // Tạo từng file
        files.forEach(({ template, filename }) => {
            const outputPath = path.join(moduleDir, filename);
            this.generateFile(template, outputPath, moduleName);
        });

        this.generateFile(
            "StoreModule",
            path.join(storeModulesDir, `${moduleLower}.js`),
            moduleName,
        );

        console.log(`\n✓ Đã tạo xong module ${moduleName} tại ${moduleDir}\n`);
    }
}

export default FileGenerator;

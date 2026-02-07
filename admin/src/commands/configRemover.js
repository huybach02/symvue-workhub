import fs from "fs";
import path from "path";

/**
 * Utility để xóa entries khỏi config files
 */
class ConfigRemover {
    /**
     * Chuyển PascalCase sang camelCase
     */
    static toCamelCase(str) {
        return str.charAt(0).toLowerCase() + str.slice(1);
    }

    /**
     * Chuyển PascalCase sang kebab-case
     */
    static toKebabCase(str) {
        return str.replace(/([a-z0-9])([A-Z])/g, "$1-$2").toLowerCase();
    }

    /**
     * Chuyển PascalCase sang snake_case
     */
    static toSnakeCase(str) {
        return str.replace(/([a-z0-9])([A-Z])/g, "$1_$2").toLowerCase();
    }

    /**
     * Xóa entry khỏi apiRouteConfig.js
     * @param {string} configPath - Đường dẫn đến apiRouteConfig.js
     * @param {string} moduleName - Tên module (PascalCase)
     */
    static removeFromApiRouteConfig(configPath, moduleName) {
        const moduleLower = this.toCamelCase(moduleName);

        let content = fs.readFileSync(configPath, "utf-8");

        // Tìm và xóa dòng chứa moduleLower
        const regex = new RegExp(`\\s*${moduleLower}:\\s*"[^"]*",?\\n`, "g");
        content = content.replace(regex, "");

        fs.writeFileSync(configPath, content, "utf-8");
        console.log(`✓ Đã xóa khỏi ${configPath}`);
    }

    /**
     * Xóa entry khỏi nameRouteConfig.js
     * @param {string} configPath - Đường dẫn đến nameRouteConfig.js
     * @param {string} moduleName - Tên module (PascalCase)
     */
    static removeFromNameRouteConfig(configPath, moduleName) {
        const moduleLower = this.toCamelCase(moduleName);

        let content = fs.readFileSync(configPath, "utf-8");

        // Tìm và xóa dòng chứa moduleLower
        const regex = new RegExp(`\\s*${moduleLower}:\\s*"[^"]*",?\\n`, "g");
        content = content.replace(regex, "");

        fs.writeFileSync(configPath, content, "utf-8");
        console.log(`✓ Đã xóa khỏi ${configPath}`);
    }

    /**
     * Xóa object khỏi file JS (dùng cho menuSidebar và routes)
     * @param {string} content - Nội dung file
     * @param {string} searchString - String để tìm trong object (ví dụ: NAME_ROUTES_CONFIG.moduleLower)
     * @returns {string} - Nội dung đã xóa object
     */
    static removeObjectContaining(content, searchString) {
        const lines = content.split("\n");
        const result = [];
        let i = 0;

        while (i < lines.length) {
            const line = lines[i];

            // Nếu dòng chứa searchString
            if (line.includes(searchString)) {
                // Tìm ngược lên để tìm dòng bắt đầu object (dòng có { mở)
                let startIndex = i;
                let braceCount = 0;

                // Đếm braces từ dòng hiện tại trở về trước
                for (let j = i; j >= 0; j--) {
                    const currentLine = lines[j];
                    // Đếm braces trong dòng
                    for (let k = currentLine.length - 1; k >= 0; k--) {
                        if (currentLine[k] === "}") braceCount++;
                        if (currentLine[k] === "{") braceCount--;
                    }

                    // Nếu braceCount < 0, nghĩa là đã tìm thấy { mở của object
                    if (braceCount < 0) {
                        startIndex = j;
                        break;
                    }
                }

                // Tìm xuống dưới để tìm dòng kết thúc object (dòng có }, đóng)
                let endIndex = i;
                braceCount = 0;

                for (let j = startIndex; j < lines.length; j++) {
                    const currentLine = lines[j];
                    // Đếm braces trong dòng
                    for (const char of currentLine) {
                        if (char === "{") braceCount++;
                        if (char === "}") braceCount--;
                    }

                    // Nếu braceCount về 0 và dòng có },
                    if (braceCount === 0 && currentLine.includes("},")) {
                        endIndex = j;
                        break;
                    }
                }

                // Xóa các dòng đã thêm vào result từ startIndex
                const linesToRemove = i - startIndex;
                for (let j = 0; j < linesToRemove; j++) {
                    result.pop();
                }

                // Bỏ qua tất cả các dòng từ startIndex đến endIndex
                i = endIndex + 1;
                continue;
            }

            result.push(line);
            i++;
        }

        return result.join("\n");
    }

    /**
     * Xóa menu item khỏi menuSidebar.js
     * @param {string} configPath - Đường dẫn đến menuSidebar.js
     * @param {string} moduleName - Tên module (PascalCase)
     */
    static removeFromMenuSidebar(configPath, moduleName) {
        const moduleLower = this.toCamelCase(moduleName);

        let content = fs.readFileSync(configPath, "utf-8");

        // Xóa object chứa NAME_ROUTES_CONFIG.moduleLower
        content = this.removeObjectContaining(
            content,
            `NAME_ROUTES_CONFIG.${moduleLower}`,
        );

        fs.writeFileSync(configPath, content, "utf-8");
        console.log(`✓ Đã xóa khỏi ${configPath}`);
    }

    /**
     * Xóa route khỏi routes.js
     * @param {string} routesPath - Đường dẫn đến routes.js
     * @param {string} moduleName - Tên module (PascalCase)
     */
    static removeFromRoutes(routesPath, moduleName) {
        const moduleLower = this.toCamelCase(moduleName);

        let content = fs.readFileSync(routesPath, "utf-8");

        // Xóa object chứa NAME_ROUTES_CONFIG.moduleLower
        content = this.removeObjectContaining(
            content,
            `NAME_ROUTES_CONFIG.${moduleLower}`,
        );

        fs.writeFileSync(routesPath, content, "utf-8");
        console.log(`✓ Đã xóa khỏi ${routesPath}`);
    }

    /**
     * Xóa sidebar entry khỏi locale base files
     * @param {string} localeBasePath - Đường dẫn đến file locale base
     * @param {string} moduleName - Tên module (PascalCase)
     */
    static removeFromLocaleBase(localeBasePath, moduleName) {
        const moduleSnake = this.toSnakeCase(moduleName);

        let content = fs.readFileSync(localeBasePath, "utf-8");
        const data = JSON.parse(content);

        // Xóa khỏi sidebar
        if (data.sidebar && data.sidebar[moduleSnake]) {
            delete data.sidebar[moduleSnake];
        }

        // Ghi lại file với format đẹp
        fs.writeFileSync(
            localeBasePath,
            JSON.stringify(data, null, 4),
            "utf-8",
        );
        console.log(`✓ Đã xóa khỏi ${localeBasePath}`);
    }

    /**
     * Xóa module object khỏi locale files
     * @param {string} localePath - Đường dẫn đến file locale
     * @param {string} moduleName - Tên module (PascalCase)
     */
    static removeFromLocale(localePath, moduleName) {
        const moduleSnake = this.toSnakeCase(moduleName);

        let content = fs.readFileSync(localePath, "utf-8");
        const data = JSON.parse(content);

        // Xóa module object
        if (data[moduleSnake]) {
            delete data[moduleSnake];
        }

        // Ghi lại file với format đẹp
        fs.writeFileSync(localePath, JSON.stringify(data, null, 4), "utf-8");
        console.log(`✓ Đã xóa khỏi ${localePath}`);
    }

    /**
     * Xóa tất cả entries khỏi config files
     * @param {string} srcDir - Đường dẫn thư mục src
     * @param {string} moduleName - Tên module (PascalCase)
     */
    static removeAllConfigs(srcDir, moduleName) {
        console.log("\n📝 Đang xóa khỏi các config files...\n");

        // Remove from apiRouteConfig.js
        this.removeFromApiRouteConfig(
            path.join(srcDir, "configs", "apiRouteConfig.js"),
            moduleName,
        );

        // Remove from nameRouteConfig.js
        this.removeFromNameRouteConfig(
            path.join(srcDir, "configs", "nameRouteConfig.js"),
            moduleName,
        );

        // Remove from menuSidebar.js
        this.removeFromMenuSidebar(
            path.join(srcDir, "configs", "menuSidebar.js"),
            moduleName,
        );

        // Remove from routes.js
        this.removeFromRoutes(
            path.join(srcDir, "router", "routes.js"),
            moduleName,
        );

        // Remove from locale base files
        this.removeFromLocaleBase(
            path.join(srcDir, "locales", "base", "base.vi.json"),
            moduleName,
        );
        this.removeFromLocaleBase(
            path.join(srcDir, "locales", "base", "base.en.json"),
            moduleName,
        );

        // Remove from locale files
        this.removeFromLocale(
            path.join(srcDir, "locales", "locale.vi.json"),
            moduleName,
        );
        this.removeFromLocale(
            path.join(srcDir, "locales", "locale.en.json"),
            moduleName,
        );

        console.log("\n✓ Đã xóa xong tất cả config files\n");
    }
}

export default ConfigRemover;

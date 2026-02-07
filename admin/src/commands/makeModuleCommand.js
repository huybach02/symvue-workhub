import path from "path";
import { fileURLToPath } from "url";
import FileGenerator from "./fileGenerator.js";
import ConfigUpdater from "./configUpdater.js";

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

/**
 * Handler cho command make:module
 */
class MakeModuleCommand {
    /**
     * Validate tên module
     * @param {string} moduleName - Tên module
     * @returns {boolean} - True nếu hợp lệ
     */
    static validateModuleName(moduleName) {
        // Kiểm tra tên module phải là PascalCase
        const pascalCaseRegex = /^[A-Z][a-zA-Z0-9]*$/;

        if (!pascalCaseRegex.test(moduleName)) {
            console.error(
                "❌ Lỗi: Tên module phải là PascalCase (ví dụ: NguoiDung, SanPham)",
            );
            return false;
        }

        return true;
    }

    /**
     * Thực thi command make:module
     * @param {string} moduleName - Tên module (PascalCase)
     */
    static execute(moduleName) {
        console.log("\n🚀 Bắt đầu tạo module:", moduleName);
        console.log("=".repeat(50));

        // Validate tên module
        if (!this.validateModuleName(moduleName)) {
            process.exit(1);
        }

        try {
            // Xác định đường dẫn
            const adminDir = path.resolve(__dirname, "../..");
            const srcDir = path.join(adminDir, "src");
            const pagesDir = path.join(srcDir, "pages");

            // Bước 1: Tạo các file Vue components
            console.log("\n📁 Bước 1: Tạo các file Vue components...\n");
            FileGenerator.generateModuleFiles(moduleName, pagesDir);

            // Bước 2: Update các config files
            console.log("📝 Bước 2: Update các config files...\n");
            ConfigUpdater.updateAllConfigs(srcDir, moduleName);

            // Hoàn thành
            console.log("=".repeat(50));
            console.log(
                "✅ Hoàn thành! Module",
                moduleName,
                "đã được tạo thành công!",
            );
            console.log("\n📋 Tóm tắt:");
            console.log("   - Đã tạo 4 file Vue components");
            console.log("   - Đã update apiRouteConfig.js");
            console.log("   - Đã update nameRouteConfig.js");
            console.log("   - Đã update menuSidebar.js");
            console.log("   - Đã update routes.js");
            console.log("   - Đã update các file locale (vi & en)");
            console.log(
                "\n💡 Bạn có thể bắt đầu custom các file trong thư mục:",
            );
            console.log("   ", path.join(pagesDir, moduleName));
            console.log("=".repeat(50) + "\n");
        } catch (error) {
            console.error("\n❌ Lỗi khi tạo module:", error.message);
            console.error(error.stack);
            process.exit(1);
        }
    }
}

export default MakeModuleCommand;

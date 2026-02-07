# Frontend CLI Tool

CLI tool để tự động tạo boilerplate code cho các module mới.

## Sử dụng

```bash
# Xem hướng dẫn
node cli.js

# Tạo module mới
node cli.js make:module [TenModule]
```

## Ví dụ

```bash
node cli.js make:module SanPham
node cli.js make:module DonHang
node cli.js make:module KhachHang
```

**Lưu ý:** Tên module phải viết theo PascalCase

## Kết quả

Tool sẽ tự động:

- ✅ Tạo 4 file Vue components (DanhSach, Form, Module, ThemSua)
- ✅ Cập nhật `apiRouteConfig.js`
- ✅ Cập nhật `nameRouteConfig.js`
- ✅ Cập nhật `menuSidebar.js`
- ✅ Cập nhật `routes.js`
- ✅ Cập nhật các file locale (vi & en)

## Tùy chỉnh Template

Các template boilerplate nằm trong `src/templates/`:

- `DanhSach.boilerplate`
- `Form.boilerplate`
- `Module.boilerplate`
- `ThemSua.boilerplate`

Bạn có thể chỉnh sửa các file này để thay đổi boilerplate mặc định.

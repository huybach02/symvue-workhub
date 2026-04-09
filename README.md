# Symfony ERP Boilerplate

> Boilerplate quản trị nội bộ gồm **Symfony 7.4 API** + **Vue 3 Admin** + **PostgreSQL** + **Redis** + **Mercure**, được tổ chức sẵn cho các bài toán quản lý người dùng, bộ phận, phân quyền, thông báo realtime, import/export Excel và sinh module nhanh.

## Mục lục

- [1. Tổng quan](#1-tổng-quan)
- [2. Điểm nổi bật](#2-điểm-nổi-bật)
- [3. Kiến trúc hệ thống](#3-kiến-trúc-hệ-thống)
- [4. Stack công nghệ](#4-stack-công-nghệ)
- [5. Cấu trúc thư mục](#5-cấu-trúc-thư-mục)
- [6. Các module nghiệp vụ hiện có](#6-các-module-nghiệp-vụ-hiện-có)
- [7. Luồng xác thực và phân quyền](#7-luồng-xác-thực-và-phân-quyền)
- [8. Realtime, queue và scheduler](#8-realtime-queue-và-scheduler)
- [9. Import/Export Excel](#9-importexport-excel)
- [10. Hướng dẫn chạy local](#10-hướng-dẫn-chạy-local)
- [11. Hướng dẫn chạy bằng Docker](#11-hướng-dẫn-chạy-bằng-docker)
- [12. Biến môi trường quan trọng](#12-biến-môi-trường-quan-trọng)
- [13. Command hữu ích](#13-command-hữu-ích)
- [14. API chính](#14-api-chính)
- [15. Frontend Admin](#15-frontend-admin)
- [16. Gợi ý quy trình phát triển](#16-gợi-ý-quy-trình-phát-triển)
- [17. Lưu ý vận hành](#17-lưu-ý-vận-hành)

## 1. Tổng quan

Đây là một boilerplate hướng đến bài toán **ERP/Admin nội bộ** với kiến trúc tách rõ:

- **Backend API** đặt ở root project, xây dựng bằng Symfony.
- **Frontend Admin** đặt trong thư mục `admin/`, xây dựng bằng Vue 3 + Vite + Vuetify.
- **Mercure hub** phục vụ thông báo realtime.
- **Redis** phục vụ cache ứng dụng, lock và Messenger transport.
- **Scheduler + Messenger worker** tách riêng để xử lý tác vụ nền và job định kỳ.

Project hiện đã có sẵn nền tảng cho các luồng:

- Đăng nhập JWT + refresh token.
- Xác minh OTP sau đăng nhập.
- Quản lý thiết bị đăng nhập.
- Quản lý người dùng, bộ phận, chức vụ, vị trí công việc.
- Phân quyền theo module/action, có cache và cơ chế merge quyền.
- Chat/conversation, presence, media library.
- Thông báo realtime qua Mercure.
- Import/export dữ liệu người dùng bằng Excel.
- Sinh nhanh module backend/frontend bằng boilerplate command.

## 2. Điểm nổi bật

| Nhóm          | Mô tả                                                                                     |
| ------------- | ----------------------------------------------------------------------------------------- |
| `Auth`        | JWT, refresh token, OTP, quên mật khẩu, đổi mật khẩu, blacklist access token khi logout   |
| `Permission`  | Quyền theo `module + action`, chặn ở request listener, hỗ trợ custom permission từng user |
| `Realtime`    | Thông báo realtime bằng Mercure, có lưu DB và publish topic theo hệ thống/cá nhân/bộ phận |
| `Async`       | Dùng Symfony Messenger để đẩy tác vụ nặng ra background                                   |
| `Scheduler`   | Tự động rebuild cache quyền, keep-alive DB, dọn cache persist hết hạn                     |
| `Excel`       | Export, import, template import có dropdown mapping                                       |
| `Admin UI`    | Vue 3 + Vuetify, i18n, router guard, refresh token flow, module hóa rõ ràng               |
| `Scaffolding` | Có command tạo/xóa module ở backend và CLI tạo module ở frontend                          |

## 3. Kiến trúc hệ thống

```text
┌────────────────────┐
│   Vue Admin (Vite) │
└─────────┬──────────┘
          │ HTTP / JWT / Mercure subscribe
          ▼
┌────────────────────┐
│ Symfony API /api   │
│ - Controllers      │
│ - Services         │
│ - Doctrine ORM     │
│ - Event Listeners  │
└──────┬─────┬───────┘
       │     │
       │     ├──────────────► PostgreSQL
       │
       ├──────────────► Redis
       │                - Cache app
       │                - Lock permission rebuild
       │                - Messenger transport
       │
       ├──────────────► Mercure Hub
       │                - Push realtime notification
       │
       └──────────────► Messenger Workers / Scheduler
                        - Xử lý async
                        - Job định kỳ
```

## 4. Stack công nghệ

### Backend

- PHP `>= 8.2`
- Symfony `7.4`
- Doctrine ORM + Doctrine Migrations
- PostgreSQL `server_version: 16`
- Redis + Predis
- Symfony Messenger
- Symfony Scheduler
- Lexik JWT Authentication Bundle
- Gesdinet JWT Refresh Token Bundle
- Symfony Mercure Bundle
- Symfony Mailer
- Symfony Translation
- PhpSpreadsheet
- Stof Doctrine Extensions + SoftDelete filter

### Frontend

- Vue `3`
- Vite `7`
- Vuetify `3`
- Vue Router
- Vuex
- Vue I18n
- Axios
- Vee Validate + Yup
- Day.js

### Hạ tầng / Deploy

- Docker + Docker Compose
- Nginx
- Nginx Proxy Manager
- Mercure container |

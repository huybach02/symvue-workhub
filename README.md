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
- Mercure container

## 5. Cấu trúc thư mục

```text
.
├─ admin/                 # Frontend admin Vue 3
├─ assets/                # Asset mapper phía Symfony
├─ bin/                   # Symfony console, phpunit
├─ boilerplates/          # Template sinh Controller/Service/DTO
├─ config/                # Cấu hình Symfony, permission, mercure, jwt
├─ docker/                # Cấu hình nginx backend
├─ docs/                  # Tài liệu kỹ thuật bổ sung
├─ mercure/               # Binary/cấu hình Mercure local cho Windows
├─ migrations/            # Doctrine migrations
├─ public/                # Front controller, uploads, province/ward json
├─ src/
│  ├─ Class/              # Helper class dùng chung
│  ├─ Command/            # Symfony console commands
│  ├─ Controller/         # API controllers
│  ├─ DTO/                # Request/response DTO
│  ├─ Entity/             # Doctrine entities
│  ├─ EventListener/      # Listener phục vụ auth, permission, cleanup
│  ├─ Message/            # Message cho Messenger
│  ├─ MessageHandler/     # Async handlers
│  ├─ Repository/         # Doctrine repositories
│  ├─ Service/            # Business logic
│  └─ Traits/             # Trait dùng chung cho entity
├─ templates/             # Twig templates cho email/base
├─ tests/                 # PHPUnit bootstrap
└─ translations/          # messages.vi / messages.en
```

## 6. Các module nghiệp vụ hiện có

### Backend API

| Nhóm                       | Mô tả                                                                                                             |
| -------------------------- | ----------------------------------------------------------------------------------------------------------------- |
| `AuthController`           | `me`, logout, verify OTP, forgot password, change password                                                        |
| `UserController`           | CRUD user, sinh mã nhân viên, import/export/template Excel, province/ward, vị trí công việc, hợp đồng, permission |
| `DepartmentController`     | CRUD bộ phận, select data, danh sách chức vụ, cập nhật quyền theo chức vụ                                         |
| `WorkingTimeController`    | Cấu hình thời gian làm việc                                                                                       |
| `GeneralSettingController` | Cấu hình chung hệ thống                                                                                           |
| `NotificationController`   | Danh sách thông báo, gửi realtime, đánh dấu đã đọc                                                                |
| `ConversationController`   | Danh sách hội thoại, tìm kiếm, tạo conversation, đánh dấu đã đọc                                                  |
| `MessageController`        | Lấy tin nhắn theo conversation, gửi tin nhắn                                                                      |
| `MediaController`          | Upload, thư viện media, trash, restore, delete permanently                                                        |
| `ImportLogController`      | Lịch sử import, xem chi tiết lỗi import                                                                           |
| `PresenceController`       | Online/offline/status cho realtime presence                                                                       |

### Entity chính

- `User`
- `Department`
- `Position`
- `UserPosition`
- `UserPermission`
- `UserHasCustomPermission`
- `Notification`
- `Conversation`
- `ConversationUser`
- `Message`
- `Media`
- `Image`
- `ImportLog`
- `GeneralSetting`
- `WorkingTime`
- `LoginDevice`
- `RefreshToken`
- `CachePersist`
- `KeepAliveDB`

### Frontend Admin

Các màn hình chính hiện có:

- Đăng nhập
- Xác minh OTP
- Quên mật khẩu
- Đổi mật khẩu
- Dashboard
- Cấu hình chung
- Thời gian làm việc
- Quản lý bộ phận
- Quản lý người dùng
- Lịch sử import
- Thông báo realtime

## 7. Luồng xác thực và phân quyền

### Xác thực

Project đang dùng:

- `LexikJWTAuthenticationBundle` cho access token
- `GesdinetJWTRefreshTokenBundle` cho refresh token
- `json_login` tại route `api/auth/login`
- Header `Device-Id` phía frontend để gắn với thiết bị đăng nhập

Luồng tổng quát:

1. User gọi API đăng nhập.
2. Hệ thống kiểm tra thông tin và số lần đăng nhập sai.
3. OTP được sinh và gửi qua email.
4. API `verify-otp` xác thực OTP, tạo bản ghi thiết bị đăng nhập.
5. Frontend lưu `token`, `refresh_token`, `device_id`.
6. Axios interceptor tự refresh token khi access token hết hạn.

### Phân quyền

Phân quyền được tổ chức theo danh sách module trong `config/permission.php`, ví dụ:

- `cau-hinh-chung`
- `thoi-gian-lam-viec`
- `nguoi-dung`
- `vai-tro`
- `bo-phan`
- `thong-bao`

Mỗi module có tập action như:

- `index`
- `create`
- `show`
- `edit`
- `delete`
- `import`
- `export`
- `showMenu`

`PermissionListener` sẽ:

- Chạy ở tầng request sau khi JWT đã được xác thực.
- Bỏ qua một số route đặc biệt như auth, media, conversation, message, presence...
- Lấy quyền đã cache của user.
- Suy ra action từ HTTP method + path.
- Trả về `403` nếu user không có quyền phù hợp.

### Permission cache

Đây là phần khá đáng giá của boilerplate:

- Quyền user được merge từ nhiều `UserPermission`.
- Có hỗ trợ quyền tạm thời theo mốc `startTemp` / `endTemp`.
- Có hỗ trợ custom permission ghi đè theo user.
- Kết quả được cache vào Redis.
- Có lock chống nhiều request rebuild cùng lúc.
- Có scheduler/job để rebuild lại cache định kỳ.

## 8. Realtime, queue và scheduler

### Realtime với Mercure

Project hỗ trợ các topic dạng:

- test
- thông báo hệ thống
- thông báo cá nhân theo `userId`
- message
- presence

Thông báo có thể gửi theo 3 kiểu:

- Toàn hệ thống
- Theo bộ phận
- Theo user cụ thể

Khi gửi thông báo:

- API publish realtime qua Mercure để UI nhận ngay.
- Đồng thời dispatch message sang Messenger để lưu DB bất đồng bộ.

### Queue với Messenger

`config/packages/messenger.yaml` đang route các message sau sang `async`:

- `App\Message\ThongBaoHeThongMessage`
- `App\Message\ThongBaoCaNhanMessage`
- `App\Message\ThongBaoBoPhanMessage`
- Mailer/Notifier messages của Symfony

Điều này giúp request HTTP nhẹ hơn, nhất là khi:

- Gửi thông báo toàn hệ thống.
- Gửi thông báo cho nhiều user.
- Xử lý batch lưu notification.

### Scheduler

`src/Schedule.php` đang đăng ký các job định kỳ:

| Cron         | Command                               | Mục đích                      |
| ------------ | ------------------------------------- | ----------------------------- |
| `0 0 * * *`  | `app:rebuild-user-permissions-cache`  | Rebuild cache quyền user      |
| `0 23 * * *` | `app:keep-alive-db`                   | Giữ kết nối DB luôn hoạt động |
| `0 0 * * *`  | `app:clear-cache-persist-in-database` | Dọn cache persist hết hạn     |

## 9. Import/Export Excel

Project đã có nền tảng import/export người dùng:

- `UserExportService`
- `UserImportService`
- `UserTemplateImportService`
- `BaseExcelTemplateHelper`

Tính năng đáng chú ý:

- Tạo file template import có dropdown.
- Mapping cột hiển thị sang cột code ẩn bằng công thức Excel.
- Ghi log import vào `ImportLog`.
- Lưu chi tiết dòng lỗi để dễ rà soát.

Tài liệu kỹ thuật chi tiết hơn nằm tại:

- `docs/BaseExcelTemplateHelper_Guide.md`

## 10. Hướng dẫn chạy local

### Yêu cầu

- PHP `8.2+`
- Composer
- Node.js `20+`
- PostgreSQL
- Redis
- Mercure hub

### 1. Cài backend

```bash
composer install
```

### 2. Cấu hình môi trường

Tạo file local riêng và không commit secrets:

```bash
cp .env .env.local
```

Cần kiểm tra tối thiểu các biến:

- `DATABASE_URL`
- `REDIS_URL`
- `MESSENGER_TRANSPORT_DSN`
- `MAILER_DSN`
- `JWT_SECRET_KEY`
- `JWT_PUBLIC_KEY`
- `JWT_PASSPHRASE`
- `MERCURE_URL`
- `MERCURE_PUBLIC_URL`
- `MERCURE_JWT_SECRET`

### 3. Chạy migration

```bash
php bin/console doctrine:migrations:migrate
```

### 4. Nạp dữ liệu mẫu nếu cần

```bash
php bin/console doctrine:fixtures:load
```

### 5. Chạy backend

Nếu có Symfony CLI:

```bash
symfony server:start
```

Hoặc dùng PHP built-in server:

```bash
php -S 127.0.0.1:8000 -t public
```

### 6. Chạy worker và scheduler

```bash
php bin/console messenger:consume async -vv
php bin/console messenger:consume scheduler_default -vv
```

### 7. Chạy frontend admin

```bash
cd admin
npm install
npm run dev
```

### 8. Chạy Mercure local

Project có sẵn file hỗ trợ Windows:

```bash
mercure.bat
```

Hoặc chạy Mercure bằng Docker ở phần dưới.

## 11. Hướng dẫn chạy bằng Docker

Project đã có `docker-compose.yml` cho môi trường đầy đủ:

- `be-php`
- `be-nginx`
- `fe-nginx`
- `messenger`
- `scheduler`
- `mercure`
- `npm` (Nginx Proxy Manager)

### Build và chạy

```bash
docker compose up -d --build
```

### Ý nghĩa các service

| Service     | Vai trò                             |
| ----------- | ----------------------------------- |
| `be-php`    | PHP-FPM chạy Symfony backend        |
| `be-nginx`  | Nginx phục vụ backend/public        |
| `fe-nginx`  | Build và phục vụ frontend Vue admin |
| `messenger` | Worker xử lý queue `async`          |
| `scheduler` | Consumer cho `scheduler_default`    |
| `mercure`   | Hub realtime                        |
| `npm`       | Reverse proxy và SSL gateway        |

## 12. Biến môi trường quan trọng

| Biến                                | Ý nghĩa                             |
| ----------------------------------- | ----------------------------------- |
| `APP_ENV`                           | Môi trường chạy ứng dụng            |
| `APP_SECRET`                        | Secret chung của Symfony            |
| `DATABASE_URL`                      | Kết nối PostgreSQL                  |
| `REDIS_URL`                         | Kết nối Redis                       |
| `MESSENGER_TRANSPORT_DSN`           | Transport cho queue async           |
| `MAILER_DSN`                        | Kênh gửi email                      |
| `JWT_SECRET_KEY`                    | Private key JWT                     |
| `JWT_PUBLIC_KEY`                    | Public key JWT                      |
| `JWT_PASSPHRASE`                    | Passphrase JWT                      |
| `MERCURE_URL`                       | URL publish tới Mercure             |
| `MERCURE_PUBLIC_URL`                | URL client subscribe Mercure        |
| `MERCURE_JWT_SECRET`                | Secret ký JWT cho Mercure           |
| `USER_PERMISSION_LOCK_TTL`          | TTL lock rebuild permission         |
| `USER_PERMISSION_LOCK_WAIT_USLEEP`  | Thời gian chờ giữa các lần thử lock |
| `USER_PERMISSION_LOCK_MAX_ATTEMPTS` | Số lần thử lock tối đa              |
| `VITE_API_BASE_URL`                 | Base URL API cho frontend           |
| `VITE_MERCURE_URL`                  | URL Mercure cho frontend            |

## 13. Command hữu ích

### Backend

```bash
php bin/console make:module TenModule --entity=TenEntity
php bin/console remove:module TenModule
php bin/console app:rebuild-user-permissions-cache
php bin/console app:keep-alive-db
php bin/console app:clear-cache-persist-in-database
php bin/console doctrine:migrations:migrate
php bin/console debug:router
```

### Frontend

Trong thư mục `admin/` có CLI tạo module:

```bash
node cli.js
node cli.js make:module TenModule
```

CLI frontend sẽ tự động:

- Tạo file page/component boilerplate.
- Cập nhật route config.
- Cập nhật menu sidebar.
- Cập nhật locale.

## 14. API chính

Tất cả controller chính đều được prefix bởi `/api`.

### Auth

- `POST /api/auth/login`
- `POST /api/auth/refresh`
- `GET /api/auth/me`
- `POST /api/auth/logout`
- `POST /api/auth/verify-otp`
- `POST /api/auth/forgot-password`
- `POST /api/auth/change-password`

### User

- `GET /api/nguoi-dung`
- `GET /api/nguoi-dung/{id}`
- `POST /api/nguoi-dung`
- `PUT /api/nguoi-dung/{id}`
- `DELETE /api/nguoi-dung/{id}`
- `GET /api/nguoi-dung/export`
- `POST /api/nguoi-dung/import`
- `GET /api/nguoi-dung/template-import`
- `GET /api/nguoi-dung/{id}/permission`
- `PUT /api/nguoi-dung/{id}/permission`

### Department

- `GET /api/bo-phan`
- `GET /api/bo-phan/{id}`
- `POST /api/bo-phan`
- `PUT /api/bo-phan/{id}`
- `DELETE /api/bo-phan/{id}`
- `GET /api/bo-phan/{id}/chuc-vu`
- `POST /api/bo-phan/{id}/chuc-vu`
- `PUT /api/bo-phan/{id}/chuc-vu/{positionId}`
- `DELETE /api/bo-phan/{id}/chuc-vu/{positionId}`
- `PUT /api/bo-phan/{id}/phan-quyen`

### Notification / Mercure

- `GET /api/thong-bao`
- `POST /api/thong-bao`
- `GET /api/mercure/danh-sach-thong-bao/{userId}`
- `GET /api/mercure/danh-sach-thong-bao/{userId}/read-one/{code}`
- `GET /api/mercure/danh-sach-thong-bao/{userId}/read-all`
- `POST /api/mercure/thong-bao-he-thong`
- `POST /api/mercure/thong-bao-ca-nhan/{userId}`
- `POST /api/mercure/thong-bao-phong-ban/{departmentId}`

### Khác

- `GET/PUT/POST /api/thoi-gian-lam-viec`
- `GET/POST /api/cau-hinh-chung`
- `POST /api/presence/online`
- `POST /api/presence/offline`
- `GET /api/presence/status`
- `GET /api/conversation`
- `POST /api/conversation`
- `GET /api/message/conversation/{id}`
- `POST /api/message`
- `POST /api/media/upload`
- `GET /api/media`

## 15. Frontend Admin

Frontend được đặt trong `admin/` và hiện có các đặc điểm sau:

- Dùng `Vite` để dev/build nhanh.
- `Vuetify` cho hệ thống UI component.
- `Vue Router` cho chia layout `AuthLayout` và `MainLayout`.
- `Vuex` quản lý state cho auth/chat/media/mercure.
- `Axios interceptor` tự gắn JWT, `Device-Id`, ngôn ngữ và tự refresh token.
- `i18n` hỗ trợ song ngữ `vi` / `en`.
- Có middleware/hook hỗ trợ kiểm tra quyền hiển thị menu và route.

Các file đáng chú ý:

- `admin/src/router/routes.js`
- `admin/src/configs/menuSidebar.js`
- `admin/src/configs/axios.js`
- `admin/src/configs/topicMercure.js`
- `admin/src/store/modules/*`

## 16. Gợi ý quy trình phát triển

### Khi thêm module mới ở backend

1. Tạo entity và migration.
2. Chạy `make:module`.
3. Hoàn thiện DTO validation.
4. Hoàn thiện service mapping nghiệp vụ.
5. Bổ sung permission trong `config/permission.php` nếu cần tinh chỉnh.
6. Tạo UI tương ứng ở `admin/`.

### Khi thêm module mới ở frontend

1. Vào thư mục `admin/`.
2. Chạy `node cli.js make:module TenModule`.
3. Hoàn thiện form, service gọi API, locale và rule hiển thị menu.

### Khi làm việc với permission

1. Cập nhật permission theo bộ phận/chức vụ.
2. Nếu cần, gán custom permission cho user.
3. Chạy rebuild cache nếu muốn đồng bộ tức thì:

```bash
php bin/console app:rebuild-user-permissions-cache
```

# Verify Attendance Flow

Tài liệu này mô tả luồng xử lý `verifyAttendance` ở backend theo cách dễ đọc nhất có thể.

Nó tập trung vào 3 phần:

1. Luồng user bấm chấm công realtime
2. Luồng backend tự chọn `Attendance` phù hợp theo thời điểm hiện tại
3. Luồng cron tự động đồng bộ `absent`

## 1. Mục tiêu của logic này

Hệ thống cần trả lời các câu hỏi:

- User đang chấm công cho ca nào?
- Đây là `check_in` hay `check_out`?
- Tại thời điểm hiện tại, record nào là hợp lý nhất để xử lý?
- Nếu quá hạn check-in thì có cần tự động đánh `absent` không?

Vấn đề của cách cũ là:

- Lấy record `scheduled` đầu tiên
- Nếu 1 user có nhiều ca trong ngày thì rất dễ lấy nhầm record

Ví dụ:

- Ca 1: `08:00 - 12:00`
- Ca 2: `13:00 - 17:00`
- Hiện tại: `13:20`

Nếu chỉ lấy record đầu tiên, hệ thống có thể vẫn chọn `check_in` của Ca 1. Điều đó là sai.

## 2. Tổng quan luồng mới

Luồng mới được tách thành 2 lớp:

1. Luồng realtime khi user bấm chấm công
2. Luồng cron chạy nền để đồng bộ `absent`

Ý tưởng chính:

- Runtime: chọn record theo `time window`, không chọn theo thứ tự query
- Cron: tự động đóng các record `check_in` đã quá hạn, đồng thời đóng luôn `check_out` cùng ca nếu cần

## 3. Luồng realtime khi user bấm chấm công

Điểm bắt đầu: `AttendanceService::verifyAttendance()`

Thứ tự xử lý:

1. Lấy config chung và `now`
2. Gom `attendanceInfo`
3. Resolve trước 1 `Attendance` phù hợp để ghi log sớm
4. Check QR
5. Check IP
6. Check location
7. Gọi `CheckWorkScheduleOfUser::checkWorkingScheduleOfUser()` để xử lý chấm công thực tế

### 3.1. Log sớm là gì

Trước khi verify `check_in/check_out`, hệ thống vẫn log từng bước:

- `qr_code_valid` / `qr_code_invalid`
- `ip_address_valid` / `ip_address_invalid`
- `location_valid` / `location_invalid`

Để log này bám đúng record hơn, backend sẽ resolve record trước bằng:

- `CheckWorkScheduleOfUser::resolveAttendanceRecordByNow()`

## 4. Hàm `checkWorkingScheduleOfUser()` làm gì

Hàm này xử lý phần nghiệp vụ chính:

1. Lấy tất cả `Attendance` của user trong ngày
2. Gọi `resolveAttendanceRecordByNow()` để chọn record phù hợp nhất
3. Từ record đó, tính `startTime` và `endTime`
4. Chọn config áp dụng cho record
5. Nếu là `check_in` thì chạy `verifyTimeCheckIn()`
6. Nếu là `check_out` thì chạy `verifyTimeCheckOut()`

Ý nghĩa:

- Backend không còn lấy record `scheduled` đầu tiên nữa
- Backend tự chọn record phù hợp nhất theo thời điểm hiện tại

## 5. 5 hàm quan trọng trong quá trình resolve

### 5.1. `parseAttendanceDateTime()`

Vị trí: `src/functions.php`

Nhiệm vụ:

- Ghép `workDate` và chuỗi giờ thành `DateTimeImmutable`

Input ví dụ:

- `baseDate = 2026-06-11`
- `time = "08:30"`

Output:

- `2026-06-11 08:30:00`

Input khác:

- `baseDate = 2026-06-11`
- `time = "08:30:15"`

Output:

- `2026-06-11 08:30:15`

Nếu chuỗi giờ sai format thì trả về `null`.

### 5.2. `resolveAttendanceDateRange()`

Nhiệm vụ:

- Đọc `workDate`, `workScheduleStartTime`, `workScheduleEndTime`
- Parse ra `startTime`, `endTime`
- Nếu `endTime <= startTime` thì coi như ca qua đêm, cộng thêm 1 ngày cho `endTime`

Ví dụ:

- `workDate = 2026-06-11`
- `start = 22:00`
- `end = 06:00`

Kết quả:

- `startTime = 2026-06-11 22:00:00`
- `endTime = 2026-06-12 06:00:00`

### 5.3. `resolveAttendanceSetting()`

Nhiệm vụ:

- Chọn config áp dụng cho từng record

Thứ tự ưu tiên:

1. `configSnapshot` của chính attendance
2. Nếu không có snapshot thì dùng config chung hiện tại

Ví dụ:

- Config hiện tại: `LATE_LIMIT_MINUTES = 45`
- Snapshot của attendance: `LATE_LIMIT_MINUTES = 60`

Kết quả:

- Record này vẫn được tính theo `60`

Điều này giúp tránh lỗi khi admin đổi config sau khi attendance đã được tạo.

### 5.4. `buildAttendanceActionWindow()`

Nhiệm vụ:

- Dùng `startTime`, `endTime`, config để tạo "cửa sổ thời gian hợp lệ" cho từng record

Nếu record là `check_in`:

- `windowStart = startTime - CHECK_IN_EARLIEST_MINUTES`
- `windowEnd = min(startTime + LATE_LIMIT_MINUTES, endTime)`
- `anchorTime = startTime`

Nếu record là `check_out`:

- `windowStart = endTime - CHECK_OUT_GRACE_MINUTES`
- `windowEnd = endTime + CHECK_OUT_LATEST_MINUTES`
- `anchorTime = endTime`

Ví dụ cho ca `13:00 - 17:00` với config:

- `CHECK_IN_EARLIEST_MINUTES = 30`
- `LATE_LIMIT_MINUTES = 60`
- `CHECK_OUT_GRACE_MINUTES = 30`
- `CHECK_OUT_LATEST_MINUTES = 60`

Kết quả:

- `check_in` window: `12:30 -> 14:00`
- `check_out` window: `16:30 -> 18:00`

### 5.5. `resolveAttendanceRecordByNow()`

Nhiệm vụ:

- Từ danh sách nhiều record `scheduled`, chọn ra record hợp lý nhất để xử lý tại thời điểm `now`

Hàm này không chọn theo `id`, mà chọn theo `time window`.

## 6. Thứ tự tiêu chí để chọn record

Hàm `resolveAttendanceRecordByNow()` sắp xếp record theo thứ tự sau:

1. `phaseRank`
2. `distanceToWindow`
3. `typePriority`
4. `anchorDistance`
5. `attendanceId`

### 6.1. `phaseRank`

Ý nghĩa:

- `0`: `now` đang nằm trong window
- `1`: `now` còn trước window
- `2`: `now` đã qua window

Ưu tiên:

- Trong window trước
- Sắp tới window sau
- Quá window cuối cùng

### 6.2. `distanceToWindow`

Nếu:

- Chưa vào window: tính khoảng cách từ `now` đến `windowStart`
- Đã qua window: tính khoảng cách từ `now` đến `windowEnd`
- Đang trong window: bằng `0`

Mục đích:

- Trong cùng 1 nhóm `phaseRank`, chọn record gần window nhất

### 6.3. `typePriority`

Ưu tiên:

- `check_out` trước `check_in`

Lý do:

- Ở thời điểm giao ca, backend ưu tiên kết thúc ca cũ trước khi mở ca mới

### 6.4. `anchorDistance`

Công thức:

- `abs(now - anchorTime)`

Với:

- `check_in` thì `anchorTime = startTime`
- `check_out` thì `anchorTime = endTime`

Mục đích:

- Nếu vẫn còn hòa thì chọn record gần mốc chính của nó hơn

### 6.5. `attendanceId`

Đây là tiêu chí cuối cùng để phá hòa.

## 7. Ví dụ tổng quát để nhìn rõ luồng resolve

Giả sử user có 2 ca trong ngày `2026-06-11`.

Config:

- `CHECK_IN_EARLIEST_MINUTES = 30`
- `LATE_LIMIT_MINUTES = 60`
- `CHECK_OUT_GRACE_MINUTES = 30`
- `CHECK_OUT_LATEST_MINUTES = 60`

Ca 1:

- `check_in`: `08:00 - 12:00`
- `check_out`: `08:00 - 12:00`

Ca 2:

- `check_in`: `13:00 - 17:00`
- `check_out`: `13:00 - 17:00`

### 7.1. Window của từng record

Ca 1:

- `check_in`: `07:30 -> 09:00`
- `check_out`: `11:30 -> 13:00`

Ca 2:

- `check_in`: `12:30 -> 14:00`
- `check_out`: `16:30 -> 18:00`

### 7.2. Các mốc giờ và kết quả

`now = 07:10`

- Ca 1 `check_in`: sắp tới sau 20 phút
- Các record khác còn xa hơn

Kết quả:

- Chọn `Ca 1 check_in`

`now = 08:15`

- Ca 1 `check_in` đang nằm trong window

Kết quả:

- Chọn `Ca 1 check_in`

`now = 11:40`

- Ca 1 `check_out` đang nằm trong window
- Ca 2 `check_in` chưa tới window

Kết quả:

- Chọn `Ca 1 check_out`

`now = 12:10`

- Ca 1 `check_out`: chưa tới window 20 phút
- Ca 2 `check_in`: chưa tới window 20 phút

Hai record này bằng nhau về:

- `phaseRank`
- `distanceToWindow`

Lúc này xét tiếp:

- `check_out` được ưu tiên hơn `check_in`

Kết quả:

- Chọn `Ca 1 check_out`

`now = 12:40`

- Ca 1 `check_out`: đang trong window
- Ca 2 `check_in`: đang trong window

Hai record cùng đang hợp lệ, nhưng:

- `check_out` được ưu tiên hơn `check_in`

Kết quả:

- Chọn `Ca 1 check_out`

`now = 13:20`

- Ca 2 `check_in` đang trong window

Kết quả:

- Chọn `Ca 2 check_in`

`now = 16:40`

- Ca 2 `check_out` đang trong window

Kết quả:

- Chọn `Ca 2 check_out`

`now = 18:20`

- Tất cả record đều đã qua window

Lúc này hệ thống chọn record vừa qua window gần nhất.

Kết quả:

- Chọn `Ca 2 check_out`

## 8. Luồng `verifyTimeCheckIn()`

Sau khi resolve được record `check_in`, hệ thống chạy:

1. Tính các mốc:
    - `checkInEarliestStartTime`
    - `checkInGraceStartTime`
    - `lateLimitStartTime`
2. Nếu quá sớm:
    - log `early_check_in`
    - throw lỗi
3. Nếu trong khoảng on-time:
    - log `on_time`
    - update attendance
4. Nếu trong khoảng late:
    - log `late`
    - update attendance
    - throw message báo trễ
5. Nếu quá hạn:
    - log `absent`
    - update attendance `absent`
    - đánh luôn `check_out` cùng ca thành `absent`

## 9. Luồng `verifyTimeCheckOut()`

Sau khi resolve được record `check_out`, hệ thống chạy:

1. Tính:
    - `checkOutGraceEndTime`
    - `checkOutLatestEndTime`
2. Nếu quá sớm:
    - log `early_leave`
    - throw lỗi
3. Nếu trong khoảng hợp lệ:
    - log `on_time`
    - update attendance
4. Nếu quá hạn:
    - log `late_check_out`
    - update attendance
    - throw message

## 10. Luồng cron `sync absent`

Command:

- `php bin/console app:attendance:sync-absent-status`

Nhiệm vụ:

- Quét các record `check_in` còn `scheduled`
- Tính mốc check-in muộn nhất được phép
- Nếu `now > latestAllowedTime` thì:
    - set `check_in = absent`
    - set `validationStatus = invalid`
    - set luôn `check_out` cùng ca = `absent` nếu nó vẫn `scheduled`

### 10.1. Ví dụ để hiểu cron

Ca:

- `13:00 - 17:00`
- `CHECK_IN_EARLIEST_MINUTES = 30`
- `LATE_LIMIT_MINUTES = 60`

Window `check_in`:

- `12:30 -> 14:00`

Nếu cron chạy lúc `14:05`:

- record `check_in` vẫn `scheduled`
- `14:05 > 14:00`

Kết quả:

- `check_in` bị chuyển thành `absent`
- `check_out` cùng ca cũng bị chuyển thành `absent` nếu business rule đang sử dụng là "check-in absent thì check-out vô nghĩa"

## 11. Tại sao cần cả runtime và cron

Chỉ runtime:

- vẫn có thể gặp record cũ treo `scheduled`

Chỉ cron:

- vẫn có khoảng trễ giữa 2 lần cron

Kết hợp cả hai:

- Runtime chọn record theo `time window`
- Cron dọn dẹp record quá hạn

Đây là phương án ổn định nhất trong bối cảnh frontend không gửi thêm `attendanceId`.

## 12. Tóm tắt ngắn gọn

Logic mới hoạt động theo câu này:

- "Tại thời điểm hiện tại, attendance nào hợp lý nhất để xử lý?"

Hệ thống không còn nghĩ:

- "Record scheduled đầu tiên là record đúng"

Mà đổi thành:

- "Record nào đúng khung giờ nhất thì xử lý record đó"

Nếu record đã quá hạn check-in:

- Cron sẽ tự động đánh `absent`
- Và đóng luôn `check_out` cùng ca nếu cần

Do đó, luồng mới sẽ ổn hơn cho:

- Nhiều ca trong 1 ngày
- Part-time
- Giao ca giữa `check_out` ca trước và `check_in` ca sau

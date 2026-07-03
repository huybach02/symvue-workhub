<?php

use App\Class\Request\RequestConstant;
use App\DTO\AttendanceDTO;
use App\Entity\Request;
use App\Entity\User;
use App\Class\TranslationHelper;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request as HttpRequest;

if (!function_exists('t')) {
    function t(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
    {
        return TranslationHelper::trans($id, $parameters, $domain, $locale);
    }
}

if (!function_exists('appEnv')) {
    function appEnv(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $_ENV)) {
            return $_ENV[$key];
        }

        if (array_key_exists($key, $_SERVER)) {
            return $_SERVER[$key];
        }

        $value = getenv($key);

        return $value === false ? $default : $value;
    }
}

if (!function_exists('isAdmin')) {
    function isAdmin($user)
    {
        return in_array("ROLE_ADMIN", $user->getRoles(), true);
    }
}

// Hàm format từ số giây sang chuỗi string (nếu < 60 giây thì format giây, nếu >= 60 giây thì format phút giây)
if (!function_exists('formatSeconds')) {
    function formatSeconds($seconds)
    {
        $seconds = (int) $seconds;

        if ($seconds < 60) {
            return $seconds . ' giây';
        }

        $minutes = floor($seconds / 60);
        $seconds = $seconds % 60;

        return $minutes . ' phút ' . $seconds . ' giây';
    }
}

// Hàm tạo chuỗi string ngẫu nhiên
if (!function_exists('generateRandomString')) {
    function generateRandomString($length = 10)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}

if (!function_exists('validateFilterParams')) {
    function validateFilterParams(array $params)
    {
        // Đảm bảo các tham số có giá trị mặc định
        $params['page'] = isset($params['page']) ? (int) $params['page'] : 1;
        $params['limit'] = isset($params['limit']) ? (int) $params['limit'] : 10;
        $params['sort_direction'] = $params['sort_direction'] ?? 'desc';
        $params['sort_column'] = $params['sort_column'] ?? 'id';

        // Validate filter parameters
        if (isset($params['f']) && is_array($params['f'])) {
            foreach ($params['f'] as $index => $filter) {
                if (!isset($filter['field']) || !isset($filter['operator']) || !isset($filter['value'])) {
                    unset($params['f'][$index]);
                }
            }
            // Reindex array để đảm bảo index liên tục
            $params['f'] = array_values($params['f']);
        }

        return $params;
    }
}

if (!function_exists('getProvinceByCode')) {
    function getProvinceByCode(?string $provinceCode): string
    {
        if (!$provinceCode) {
            return '';
        }

        $projectDir = dirname(__DIR__);
        $filePath = $projectDir . '/public/province.json';

        if (!file_exists($filePath)) {
            throw new \Exception('File province.json không tồn tại');
        }
        $content = file_get_contents($filePath);
        $items = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('File province.json không đúng định dạng JSON');
        }

        $filtered = array_filter($items, fn($item) => $item['code'] == $provinceCode);

        return array_values($filtered)[0]['name'] ?? '';
    }
}

if (!function_exists('getWardByCode')) {
    function getWardByCode(?string $wardCode): string
    {
        if (!$wardCode) {
            return '';
        }

        $projectDir = dirname(__DIR__);
        $filePath = $projectDir . '/public/ward.json';

        if (!file_exists($filePath)) {
            throw new \Exception('File ward.json không tồn tại');
        }
        $content = file_get_contents($filePath);
        $items = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('File ward.json không đúng định dạng JSON');
        }

        $filtered = array_filter($items, fn($item) => $item['code'] == $wardCode);

        return array_values($filtered)[0]['name'] ?? '';
    }
}

/**
 * Convert tên cột Excel (A, B, C, AA, AB...) sang index số (0, 1, 2...)
 * 
 * @param string $column Tên cột Excel (VD: "A", "B", "AA", "AB")
 * @return int Index số tương ứng (0-based)
 * 
 * @example
 * excelColumnToIndex("A") => 0
 * excelColumnToIndex("B") => 1
 * excelColumnToIndex("Z") => 25
 * excelColumnToIndex("AA") => 26
 * excelColumnToIndex("AB") => 27
 */
if (!function_exists('excelColumnToIndex')) {
    function excelColumnToIndex(string $column): int
    {
        $column = strtoupper($column);
        $length = strlen($column);
        $index = 0;

        for ($i = 0; $i < $length; $i++) {
            $index = $index * 26 + (ord($column[$i]) - ord('A') + 1);
        }

        return $index - 1; // Trả về 0-based index
    }
}

/**
 * Helper function để lấy giá trị từ row Excel theo tên cột
 * 
 * @param array $row Mảng dữ liệu từ Excel row
 * @param string $column Tên cột Excel (VD: "A", "B", "AA")
 * @param mixed $default Giá trị mặc định nếu không tồn tại
 * @return mixed Giá trị tại cột đó hoặc giá trị mặc định
 * 
 * @example
 * excelGetValue($row, "A") => Lấy giá trị cột A
 * excelGetValue($row, "B", "default") => Lấy giá trị cột B, nếu null thì trả về "default"
 */
if (!function_exists('excelGetValue')) {
    function excelGetValue(array $row, string $column, mixed $default = null): mixed
    {
        $index = excelColumnToIndex($column);
        return $row[$index] ?? $default;
    }
}

if (!function_exists('convertMethod')) {
    function convertMethod(string $path, string $method): string
    {
        $pathArr = explode("/", $path);

        if (count($pathArr) === 1) {
            switch ($method) {
                case "GET":
                    return "index";
                case "POST":
                    return "create";
            }
        }

        if (count($pathArr) > 1) {
            switch ($method) {
                case "GET":
                    return "show";
                case "PUT":
                case "PATCH":
                    return "edit";
                case "DELETE":
                    return "delete";
            }
        }

        return "index";
    }
}

if (!function_exists('getRequestBodyValue')) {
    function getRequestBodyValue(HttpRequest $request, string $key): mixed
    {
        if ($key === '') {
            return null;
        }

        try {
            $data = $request->toArray();
        } catch (\Throwable) {
            return null;
        }

        return $data[$key] ?? null;
    }
}

if (!function_exists('normalizePermissionSegment')) {
    function normalizePermissionSegment(mixed $value): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        $normalized = trim((string) $value);

        return $normalized !== '' ? $normalized : null;
    }
}

if (!function_exists('resolveEntityClass')) {
    function resolveEntityClass(mixed $entity): ?string
    {
        if (!is_string($entity) || $entity === '') {
            return null;
        }

        return class_exists($entity) ? $entity : null;
    }
}

if (!function_exists('readObjectField')) {
    function readObjectField(object $object, string $field): mixed
    {
        $camelField = str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $field)));
        $getter = 'get' . $camelField;
        $isser = 'is' . $camelField;

        if (method_exists($object, $getter)) {
            return $object->{$getter}();
        }

        if (method_exists($object, $isser)) {
            return $object->{$isser}();
        }

        return null;
    }
}

if (!function_exists('getPermissionSuffixesByPrefix')) {
    function getPermissionSuffixesByPrefix(
        array $permissions,
        string $prefix,
        string $action,
    ): array {
        $suffixes = [];

        foreach ($permissions as $permission) {
            $name = (string) ($permission['name'] ?? '');
            $actions = $permission['actions'] ?? [];

            if (!str_starts_with($name, $prefix)) {
                continue;
            }

            if (($actions[$action] ?? false) !== true) {
                continue;
            }

            $suffixes[] = substr($name, strlen($prefix));
        }

        $suffixes = array_filter(
            array_unique($suffixes),
            static fn(string $suffix): bool => $suffix !== '',
        );

        return array_values($suffixes);
    }
}

if (!function_exists('assertAttendanceLocationWithinConfiguredRadius')) {
    function assertAttendanceLocationWithinConfiguredRadius(
        $latitude,
        $longitude,
        array $configs,
    ): void {
        if (
            !isValidAttendanceLatitude($latitude) ||
            !isValidAttendanceLongitude($longitude)
        ) {
            throw new \Exception(t('error.location_invalid'));
        }

        $configuredLatitude = getAttendanceRequiredFloatConfig($configs, 'LATITUDE');
        $configuredLongitude = getAttendanceRequiredFloatConfig($configs, 'LONGITUDE');
        $radiusMetres = getAttendanceRequiredFloatConfig($configs, 'RADIUS_METERS');

        if (
            !isValidAttendanceLatitude($configuredLatitude) ||
            !isValidAttendanceLongitude($configuredLongitude) ||
            $radiusMetres <= 0
        ) {
            throw new \Exception(t('error.attendance_location_config_invalid'));
        }

        $distanceMetres = calculateAttendanceDistanceMetres(
            $configuredLatitude,
            $configuredLongitude,
            $latitude,
            $longitude,
        );

        if ($distanceMetres > $radiusMetres) {
            throw new \Exception(t('error.location_out_of_range', [
                '%distance%' => (string) round($distanceMetres, 2),
                '%radius%' => (string) round($radiusMetres, 2),
            ]));
        }
    }
}

/**
 * Parse ngày + giờ thành DateTimeImmutable 
 *
 * Input:
 * - $baseDate: 2026-06-11 00:00:00
 * - $time: "08:30" hoặc "08:30:15"
 *
 * Output:
 * - 2026-06-11 08:30:00
 * - 2026-06-11 08:30:15
 *
 */
if (!function_exists('parseAttendanceDateTime')) {
    function parseAttendanceDateTime(
        \DateTimeImmutable $baseDate,
        string $time,
    ): ?\DateTimeImmutable {
        $formats = ['Y-m-d H:i:s', 'Y-m-d H:i'];

        foreach ($formats as $format) {
            $dateTime = \DateTimeImmutable::createFromFormat(
                $format,
                $baseDate->format('Y-m-d') . ' ' . $time,
            );

            if ($dateTime instanceof \DateTimeImmutable) {
                return $dateTime;
            }
        }

        return null;
    }
}

if (!function_exists('getAttendanceRequiredFloatConfig')) {
    function getAttendanceRequiredFloatConfig(array $configs, string $key): float
    {
        if (!isset($configs[$key]) || !is_numeric($configs[$key])) {
            throw new \Exception(t('error.attendance_location_config_invalid'));
        }

        return (float) $configs[$key];
    }
}

if (!function_exists('isValidAttendanceLatitude')) {
    function isValidAttendanceLatitude(float $latitude): bool
    {
        return $latitude >= -90 && $latitude <= 90;
    }
}

if (!function_exists('isValidAttendanceLongitude')) {
    function isValidAttendanceLongitude(float $longitude): bool
    {
        return $longitude >= -180 && $longitude <= 180;
    }
}

if (!function_exists('calculateAttendanceDistanceMetres')) {
    function calculateAttendanceDistanceMetres(
        float $fromLatitude,
        float $fromLongitude,
        float $toLatitude,
        float $toLongitude,
    ): float {
        $earthRadiusMetres = 6371000;
        $latitudeDelta = deg2rad($toLatitude - $fromLatitude);
        $longitudeDelta = deg2rad($toLongitude - $fromLongitude);

        $fromLatitudeRadians = deg2rad($fromLatitude);
        $toLatitudeRadians = deg2rad($toLatitude);

        $a = sin($latitudeDelta / 2) ** 2
            + cos($fromLatitudeRadians) * cos($toLatitudeRadians)
            * sin($longitudeDelta / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadiusMetres * $c;
    }
}

if (!function_exists('uploadFile')) {
    function uploadFile(UploadedFile $file, string $folder, string $baseUrl = '', string $prefix = 'media')
    {
        $fileName = $prefix . '_' . uniqid() . '.' . $file->guessExtension();

        $targetDirectory = dirname(__DIR__) . "/public/uploads/$folder";
        if (!is_dir($targetDirectory)) {
            mkdir($targetDirectory, 0777, true);
        }
        $file->move($targetDirectory, $fileName);

        if ($baseUrl) {
            return $baseUrl . '/uploads/' . $folder . '/' . $fileName;
        }
        return '/uploads/' . $folder . '/' . $fileName;
    }
}

if (!function_exists('convertSlugToNameWithUpperWords')) {
    function convertSlugToNameWithUpperWords(string $slug): string
    {
        return ucwords(str_replace('-', ' ', $slug));
    }
}

if (!function_exists('formatTimeString')) {
    function formatTimeString(string $time): string
    {
        $dateTime = \DateTime::createFromFormat("H:i:s", $time)
            ?: \DateTime::createFromFormat("H:i", $time);

        return $dateTime ? $dateTime->format("H:i") : substr($time, 0, 5);
    }
}

if (!function_exists('generateCode')) {
    function generateCode(string $prefix = 'CODE'): string
    {
        return sprintf(
            '%s-%s%s',
            strtoupper($prefix),
            date('ymdHi'),
            strtoupper(substr(bin2hex(random_bytes(1)), 0, 2))
        );
    }
}

if (!function_exists('buildRequestPermissions')) {
    function buildRequestPermissions(Request $request, User $currentUser): array
    {
        return [
            'canApprove' => $request->getCurrentApprover()?->getId() === $currentUser->getId()
                && $request->getStatus() === RequestConstant::STATUS_PENDING,
            'canReject' => $request->getCurrentApprover()?->getId() === $currentUser->getId()
                && $request->getStatus() === RequestConstant::STATUS_PENDING,
            'canEdit' => $request->getRequester()?->getId() === $currentUser->getId()
                && $request->getStatus() === RequestConstant::STATUS_REJECTED,
            'canCancel' => $request->getRequester()?->getId() === $currentUser->getId()
                && in_array($request->getStatus(), [RequestConstant::STATUS_PENDING, RequestConstant::STATUS_REJECTED], true),
            'canDelete' => $request->getRequester()?->getId() === $currentUser->getId()
                && in_array($request->getStatus(), [RequestConstant::STATUS_REJECTED, RequestConstant::STATUS_CANCELLED], true),
        ];
    }
}

if (!function_exists('canViewRequest')) {
    function canViewRequest(Request $request, User $currentUser): bool
    {
        $watcherIds = array_map(
            fn($watcher) => $watcher->getUser()?->getId(),
            $request->getWatchers()->toArray()
        );

        $result = $request->getRequester()?->getId() === $currentUser->getId()
            || $request->getCurrentApprover()?->getId() === $currentUser->getId()
            || in_array($currentUser->getId(), $watcherIds, true)
            || isAdmin($currentUser);

        if (!$result) {
            throw new \Exception(t('request.error.cannot_view'));
        }
        return $result;
    }
}

if (!function_exists('generateQRCodeAttendance')) {
    function generateQRCodeAttendance(): string
    {
        // Format QR code attendance: dd/mm/yyyy-HH:mm:ss-random
        $randomString = bin2hex(random_bytes(10));
        $qrCode = sprintf(
            '%s-%s-%s',
            date('d/m/Y'),
            date('H\\hi\\ms\\s'),
            $randomString,
        );
        return $qrCode;
    }
}

if (!function_exists('removeVietnameseDiacritics')) {
    function removeVietnameseDiacritics(string $str): string
    {
        $charMap = [
            'à' => 'a', 'á' => 'a', 'ả' => 'a', 'ã' => 'a', 'ạ' => 'a',
            'ă' => 'a', 'ằ' => 'a', 'ắ' => 'a', 'ẳ' => 'a', 'ẵ' => 'a', 'ặ' => 'a',
            'â' => 'a', 'ầ' => 'a', 'ấ' => 'a', 'ẩ' => 'a', 'ẫ' => 'a', 'ậ' => 'a',
            'đ' => 'd',
            'è' => 'e', 'é' => 'e', 'ẻ' => 'e', 'ẽ' => 'e', 'ẹ' => 'e',
            'ê' => 'e', 'ề' => 'e', 'ế' => 'e', 'ể' => 'e', 'ễ' => 'e', 'ệ' => 'e',
            'ì' => 'i', 'í' => 'i', 'ỉ' => 'i', 'ĩ' => 'i', 'ị' => 'i',
            'ò' => 'o', 'ó' => 'o', 'ỏ' => 'o', 'õ' => 'o', 'ọ' => 'o',
            'ô' => 'o', 'ồ' => 'o', 'ố' => 'o', 'ổ' => 'o', 'ỗ' => 'o', 'ộ' => 'o',
            'ơ' => 'o', 'ờ' => 'o', 'ớ' => 'o', 'ở' => 'o', 'ỡ' => 'o', 'ợ' => 'o',
            'ù' => 'u', 'ú' => 'u', 'ủ' => 'u', 'ũ' => 'u', 'ụ' => 'u',
            'ư' => 'u', 'ừ' => 'u', 'ứ' => 'u', 'ử' => 'u', 'ữ' => 'u', 'ự' => 'u',
            'ỳ' => 'y', 'ý' => 'y', 'ỷ' => 'y', 'ỹ' => 'y', 'ỵ' => 'y',
        ];

        $lowerStr = mb_strtolower($str, 'UTF-8');
        $result = strtr($lowerStr, $charMap);

        return $result;
    }
}

if (!function_exists('generateCodeFromName')) {
    function generateCodeFromName(string $name): string
    {
        $normalized = removeVietnameseDiacritics($name);

        return strtoupper(str_replace(' ', '_', $normalized));
    }
}

if (!function_exists('formatDecimal')) {
    function formatDecimal(?string $val): ?string
    {
        if ($val === null) {
            return null;
        }
        $formatted = rtrim($val, '0');
        $formatted = rtrim($formatted, '.');
        return $formatted;
    }
}
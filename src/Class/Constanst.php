<?php

namespace App\Class;

final class Constanst
{
    const THOI_GIAN_LAM_VIEC = [
        "Thứ 2" => [
            "DAY_OF_WEEK" => 1,
            "GIO_BAT_DAU" => "08:00",
            "GIO_KET_THUC" => "17:00",
            "GHI_CHU" => "",
        ],
        "Thứ 3" => [
            "DAY_OF_WEEK" => 2,
            "GIO_BAT_DAU" => "08:00",
            "GIO_KET_THUC" => "17:00",
            "GHI_CHU" => "",
        ],
        "Thứ 4" => [
            "DAY_OF_WEEK" => 3,
            "GIO_BAT_DAU" => "08:00",
            "GIO_KET_THUC" => "17:00",
            "GHI_CHU" => "",
        ],
        "Thứ 5" => [
            "DAY_OF_WEEK" => 4,
            "GIO_BAT_DAU" => "08:00",
            "GIO_KET_THUC" => "17:00",
            "GHI_CHU" => "",
        ],
        "Thứ 6" => [
            "DAY_OF_WEEK" => 5,
            "GIO_BAT_DAU" => "08:00",
            "GIO_KET_THUC" => "17:00",
            "GHI_CHU" => "",
        ],
        "Thứ 7" => [
            "DAY_OF_WEEK" => 6,
            "GIO_BAT_DAU" => "08:00",
            "GIO_KET_THUC" => "17:00",
            "GHI_CHU" => "",
        ],
        "Chủ nhật" => [
            "DAY_OF_WEEK" => 7,
            "GIO_BAT_DAU" => "08:00",
            "GIO_KET_THUC" => "17:00",
            "GHI_CHU" => "",
        ],
    ];

    const CHO_PHEP_NGOAI_GIO = [
        "CHO_PHEP" => 1,
        "KHONG_CHO_PHEP" => 0,
    ];

    const CHECK_THOI_GIAN_LAM_VIEC = [
        "KICH_HOAT" => 1,
        "KHONG_KICH_HOAT" => 0,
    ];

    const CHECK_XAC_THUC_OTP = [
        "KICH_HOAT" => 1,
        "KHONG_KICH_HOAT" => 0,
    ];

    const CONVERT_DATE_TIME = [
        "Monday" => "Thứ 2",
        "Tuesday" => "Thứ 3",
        "Wednesday" => "Thứ 4",
        "Thursday" => "Thứ 5",
        "Friday" => "Thứ 6",
        "Saturday" => "Thứ 7",
        "Sunday" => "Chủ nhật",
    ];

    /**
     * Map URL path prefix → tên module trong hệ thống permission.
     * Key: prefix của URL route (phải khớp với đầu path, dùng str_starts_with)
     * Value: tên module tương ứng trong UserPermission->phanQuyen[]['name']
     * Cần cập nhật khi thêm module mới vào hệ thống.
     */
    const ROUTE_PERMISSION_MAP = [
        '/general-settings'     => 'general-settings',
        '/working-times'        => 'working-times',
        '/users'                => 'users',
        '/departments'          => 'departments',
        '/requests'             => 'requests',
    ];

    /**
     * Map HTTP method → tên action trong permission.
     * GET /list       → index
     * GET /{id}       → show  (được xác định khi path có segment là số)
     * POST            → create
     * PUT / PATCH     → edit
     * DELETE          → delete
     */
    const METHOD_ACTION_MAP = [
        'POST'   => 'create',
        'PUT'    => 'edit',
        'PATCH'  => 'edit',
        'DELETE' => 'delete',
    ];
    const TYPE_CONVERSATION = [
        'private' => 'private',
        'department' => 'department',
    ];

    const HOLIDAY_SCHEDULE = [
        "Tết dương lịch" => "1/1",
        "Ngày Chiến thắng" => "30/4",
        "Quốc tế Lao động" => "1/5",
        "Quốc khánh" => "2/9"
    ];

    const LUNAR_HOLIDAY = [
        "Mùng 1 Tết" => "01/01",
        "Mùng 2 Tết" => "01/02",
        "Mùng 3 Tết" => "01/03",
    ];

    const HINH_THUC_LAM_VIEC = [
        "FULL_TIME" => "FULL_TIME",
        "PART_TIME" => "PART_TIME",
        "INTERN" => "INTERN",
        "CONTRACTOR" => "CONTRACTOR",
    ];
    const COLOR_SHIFT = [
        '#FFCDD2',
        '#EF9A9A',
        '#E57373',
        '#EF5350',
        '#F44336',
        '#E53935',
        '#D32F2F',
        '#C62828',
        '#B71C1C',
        '#F8BBD0',
        '#F48FB1',
        '#F06292',
        '#EC407A',
        '#E91E63',
        '#D81B60',
        '#C2185B',
        '#AD1457',
        '#880E4F',
        '#E1BEE7',
        '#CE93D8',
        '#BA68C8',
        '#AB47BC',
        '#9C27B0',
        '#8E24AA',
        '#7B1FA2',
        '#6A1B9A',
        '#4A148C',
        '#D1C4E9',
        '#B39DDB',
        '#9575CD',
        '#7E57C2',
        '#673AB7',
        '#5E35B1',
        '#512DA8',
        '#4527A0',
        '#311B92',
        '#C5CAE9',
        '#9FA8DA',
        '#7986CB',
        '#5C6BC0',
        '#3F51B5',
        '#3949AB',
        '#303F9F',
        '#283593',
        '#1A237E',
        '#BBDEFB',
        '#90CAF9',
        '#64B5F6',
        '#42A5F5',
        '#2196F3',
        '#1E88E5',
        '#1976D2',
        '#1565C0',
        '#0D47A1',
        '#B3E5FC',
        '#81D4FA',
        '#4FC3F7',
        '#29B6F6',
        '#03A9F4',
        '#039BE5',
        '#0288D1',
        '#0277BD',
        '#01579B',
        '#B2EBF2',
        '#80DEEA',
        '#4DD0E1',
        '#26C6DA',
        '#00BCD4',
        '#00ACC1',
        '#0097A7',
        '#00838F',
        '#006064',
        '#B2DFDB',
        '#80CBC4',
        '#4DB6AC',
        '#26A69A',
        '#009688',
        '#00897B',
        '#00796B',
        '#00695C',
        '#004D40',
        '#C8E6C9',
        '#A5D6A7',
        '#81C784',
        '#66BB6A',
        '#4CAF50',
        '#43A047',
        '#388E3C',
        '#2E7D32',
        '#1B5E20',
        '#DCEDC8',
        '#C5E1A5',
        '#AED581',
        '#9CCC65',
        '#8BC34A',
        '#7CB342',
        '#689F38',
        '#558B2F',
        '#33691E',
        '#F0F4C3',
        '#E6EE9C',
        '#DCE775',
        '#D4E157',
        '#CDDC39',
        '#C0CA33',
        '#AFB42B',
        '#9E9D24',
        '#827717',
        '#FFF9C4',
        '#FFF59D',
        '#FFF176',
        '#FFEE58',
        '#FFEB3B',
        '#FDD835',
        '#FBC02D',
        '#F9A825',
        '#F57F17',
        '#FFECB3',
        '#FFE082',
        '#FFD54F',
        '#FFCA28',
        '#FFC107',
        '#FFB300',
        '#FFA000',
        '#FF8F00',
        '#FF6F00',
        '#FFE0B2',
        '#FFCC80',
        '#FFB74D',
        '#FFA726',
        '#FF9800',
        '#FB8C00',
        '#F57C00',
        '#EF6C00',
        '#E65100',
        '#FFCCBC',
        '#FFAB91',
        '#FF8A65',
        '#FF7043',
        '#FF5722',
        '#F4511E',
        '#E64A19',
        '#D84315',
        '#BF360C',
        '#D7CCC8',
        '#BCAAA4',
        '#A1887F',
        '#8D6E63',
        '#795548',
        '#6D4C41',
        '#5D4037',
        '#4E342E',
        '#3E2723'
    ];
}

enum WorkType: string
{
    case FullTime = 'FULL_TIME';
    case PartTime = 'PART_TIME';
}

enum StatusAttendance: string
{
    case Scheduled = 'scheduled';
    case OnTime = 'on_time';
    case Late = 'late';
    case EarlyLeave = 'early_leave';
    case Absent = 'absent';
    case EarlyCheckIn = 'early_check_in';
    case LateCheckOut = 'late_check_out';
}

enum AttendanceType: string
{
    case CheckIn = 'check_in';
    case CheckOut = 'check_out';
}

enum ValidationStatus: string
{
    case Valid = 'valid';
    case Invalid = 'invalid';
}

enum WarehouseType: string
{
    case Main = 'main';
    case Branch = 'branch';
}

enum StockReceiptStatus: string
{
    case Created = 'CREATED';
    case InProgress = 'IN_PROGRESS';
    case PartiallyCompleted = 'PARTIALLY_COMPLETED';
    case Completed = 'COMPLETED';
    case Cancelled = 'CANCELLED';
}

enum StockReceiptProviderStatus: string
{
    case Created = 'CREATED';
    case AwaitingShipment = 'AWAITING_SHIPMENT';
    case InTransit = 'IN_TRANSIT';
    case Arrived = 'ARRIVED';
    case Inspecting = 'INSPECTING';
    case Completed = 'COMPLETED';
    case Cancelled = 'CANCELLED';
}

enum FulfillmentStatus: string
{
    case Pending = 'PENDING';
    case Full = 'FULL';
    case PartialClosed = 'PARTIAL_CLOSED';
    case BackorderOpen = 'BACKORDER_OPEN';
    case BackorderCreated = 'BACKORDER_CREATED';
}

enum BackorderResolutionStatus: string
{
    case None = 'NONE';
    case Open = 'OPEN';
    case ResolvedFull = 'RESOLVED_FULL';
    case ResolvedPartial = 'RESOLVED_PARTIAL';
    case Cancelled = 'CANCELLED';
}

enum ShortageResolution: string
{
    case AcceptShortage = 'ACCEPT_SHORTAGE';
    case CreateBackorder = 'CREATE_BACKORDER';
}

enum InventoryLotStatus: string
{
    case Available = 'AVAILABLE';
    case Blocked = 'BLOCKED';
    case Expired = 'EXPIRED';
    case Depleted = 'DEPLETED';
}

enum InventoryMovementType: string
{
    case StockIn = 'STOCK_IN';
    case StockOut = 'STOCK_OUT';
    case Adjustment = 'ADJUSTMENT';
    case TransferIn = 'TRANSFER_IN';
    case TransferOut = 'TRANSFER_OUT';
    case Reversal = 'REVERSAL';
}

enum StockReceiptEventType: string
{
    case Created = 'CREATED';
    case StatusChanged = 'STATUS_CHANGED';
    case InspectionStarted = 'INSPECTION_STARTED';
    case InspectionSaved = 'INSPECTION_SAVED';
    case InventoryPosted = 'INVENTORY_POSTED';
    case ShortageAccepted = 'SHORTAGE_ACCEPTED';
    case BackorderCreated = 'BACKORDER_CREATED';
    case Cancelled = 'CANCELLED';
    case Reversed = 'REVERSED';
}

enum ActorType: string
{
    case User = 'USER';
    case System = 'SYSTEM';
    case Job = 'JOB';
}

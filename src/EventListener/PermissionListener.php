<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Class\CustomResponse;
use App\Entity\User;
use App\Service\DepartmentService;
use App\Service\PermissionCheckerService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Listener kiểm tra quyền truy cập của user vào từng route.
 * Chạy SAU khi JWT đã được xác thực (Security Firewall đã xử lý).
 * Priority thấp hơn firewall để đảm bảo user đã được authenticate trước.
 */
#[AsEventListener(event: KernelEvents::REQUEST, method: 'onKernelRequest', priority: 7)]
class PermissionListener
{
    public function __construct(
        private readonly Security $security,
        private readonly DepartmentService $departmentService,
        private readonly PermissionCheckerService $permissionCheckerService,
    ) {
    }

    protected $excludedRoutes = [
        'api/auth/me',
        'api/auth/logout',
        'api/auth/change-password',
        'api/auth/forgot-password',
        'api/mercure/notification-list',
    ];

    // Các từ khóa trong path sẽ được bỏ qua kiểm tra quyền
    protected array $excludedKeywords = ['select', 'import', 'export', 'template-import', 'media', 'conversation', 'message', 'presence', 'request-types', 'qr-attendance'];

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $path = $request->getPathInfo();

        $user = $this->security->getUser();

        if (!str_starts_with($path, '/api')) {
            return;
        }

        if ($this->shouldExcludeRoute($path)) {
            return;
        }

        if (!$user instanceof User) {
            $event->setResponse(CustomResponse::error(
                "Không xác định được vai trò",
                Response::HTTP_UNAUTHORIZED
            ));
            return;
        }

        if (in_array("ROLE_ADMIN", $user->getRoles(), true)) {
            return;
        }

        $userPermission = $this->departmentService->getCachedUserPermissions($user->getId());

        if (!$this->permissionCheckerService->hasAccess($request, $userPermission ?? [])) {
            $event->setResponse(CustomResponse::error(
                "Bạn không có quyền truy cập vào nội dung này",
                Response::HTTP_FORBIDDEN
            ));
        }
    }

    protected function shouldExcludeRoute(string $path): bool
    {
        // Kiểm tra các route cố định và từ khóa đặc biệt
        foreach ([...$this->excludedRoutes, ...$this->excludedKeywords] as $keyword) {
            if (str_contains($path, $keyword)) {
                return true;
            }
        }

        return false;
    }
}

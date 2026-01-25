<?php

namespace App\EventListener;

use App\Class\Constanst;
use App\Entity\User;
use App\Repository\CauHinhChungRepository;
use App\Repository\UserRepository;
use App\Service\AuthService;
use App\Service\DeviceInfoService;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\User\UserInterface;

#[AsEventListener(event: Events::AUTHENTICATION_SUCCESS, method: 'onAuthenticationSuccess', priority: -10)]
class AuthenticationSuccessListener
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private CauHinhChungRepository $cauHinhChungRepository,
        private readonly RequestStack $requestStack,
        private readonly RefreshTokenManagerInterface $refreshTokenManager,
        private readonly DeviceInfoService $deviceInfoService,
        private readonly AuthService $authService,
        private CacheItemPoolInterface $cache,
    ) {}

    public function onAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {
        $data = $event->getData();
        $user = $event->getUser();

        $userEntity = $this->userRepository->findOneBy(['email' => $user->getUserIdentifier()]);

        if (!$userEntity instanceof UserInterface) {
            return;
        }

        $userData = [
            'id' => $userEntity->getId(),
            'email' => $userEntity->getEmail(),
        ];
        $this->handleRefreshTokenTTL($data);

        $finalDataPayload = array_merge(
            $data,
            ['user' => $userData]
        );

        $formattedResponse = [
            'success' => true,
            'message' => t('auth.login.success'),
            'data'    => $finalDataPayload
        ];

        // LOGIC Xử lý xác thực OTP
        $formattedResponse = $this->handleOtpVerification($user, $userEntity, $formattedResponse);
        $event->setData($formattedResponse);
    }

    private function handleRefreshTokenTTL(array $data): void
    {
        if (isset($data['refresh_token'])) {
            $request = $this->requestStack->getCurrentRequest();
            try {
                $payload = $request ? $request->toArray() : [];
            } catch (\Exception $e) {
                $payload = [];
            }

            $rememberMe = $payload['rememberMe'] ?? false;

            $ttl = $rememberMe ? 604800 : 86400;

            $refreshTokenString = $data['refresh_token'];
            $refreshTokenObj = $this->refreshTokenManager->get($refreshTokenString);

            if ($refreshTokenObj) {
                $validDate = new \DateTime();
                $validDate->modify('+' . $ttl . ' seconds');
                $refreshTokenObj->setValid($validDate);
                $this->refreshTokenManager->save($refreshTokenObj);
            }
        }
    }

    private function handleOtpVerification(UserInterface $user, User $userEntity, array $formattedResponse): array
    {
        if ($this->cauHinhChungRepository->getAllConfig()['XAC_THUC_2_YEU_TO'] == Constanst::CHECK_XAC_THUC_OTP['KICH_HOAT']) {
            // Lấy thông tin metadata
            $request = $this->requestStack->getCurrentRequest();
            $metadata = $this->deviceInfoService->getMetadata($request);

            $deviceId = $request->headers->get('Device-Id');

            $isDeviceIdValid = $this->deviceInfoService->verifyDeviceId($request, $user, $metadata);

            if (!$deviceId || !$isDeviceIdValid) {
                $otp = $this->authService->generateOtp();

                $key = 'otp_' . $userEntity->getId();

                // Lưu OTP vào cache
                $otpItem = $this->cache->getItem($key);
                if ($otpItem->isHit()) {
                    $this->cache->deleteItem($key);
                }
                $otpItem->set($otp);
                $otpItem->expiresAfter((int) $this->cauHinhChungRepository->getAllConfig()['THOI_GIAN_HET_HAN_OTP'] * 60);
                $this->cache->save($otpItem);

                // Gửi OTP qua email
                $this->authService->sendOtpEmail($userEntity->getEmail(), $otp);

                $formattedResponse = [
                    'success' => false,
                    "code" => "VERIFY_OTP",
                    'message' => "Xác thực OTP",
                    'data' => []
                ];
            }
        }

        return $formattedResponse;
    }
}

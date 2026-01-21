<?php

namespace App\EventListener;

use App\Repository\UserRepository;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\User\UserInterface;

#[AsEventListener(event: Events::AUTHENTICATION_SUCCESS, method: 'onAuthenticationSuccess', priority: -10)]
class AuthenticationSuccessListener
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly RequestStack $requestStack,
        private readonly RefreshTokenManagerInterface $refreshTokenManager,
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

        $finalDataPayload = array_merge(
            $data,
            ['user' => $userData]
        );
        $formattedResponse = [
            'success' => true,
            'message' => t('auth.login.success'),
            'data'    => $finalDataPayload
        ];

        $event->setData($formattedResponse);
    }
}

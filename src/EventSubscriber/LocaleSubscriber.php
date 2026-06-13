<?php

namespace App\EventSubscriber;

use App\Class\CacheKey;
use App\Class\TranslationHelper;
use App\Entity\User;
use App\Service\CacheService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\Translation\TranslatorInterface;

class LocaleSubscriber implements EventSubscriberInterface
{
    private const USER_LOCALE_TTL = 31536000;

    private string $defaultLocale;

    public function __construct(
        private TranslatorInterface $translator,
        private readonly Security $security,
        private readonly CacheService $cacheService,
        string $defaultLocale = 'vi'
    ) {
        $this->defaultLocale = $defaultLocale;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        // Lấy ngôn ngữ từ header 'Accept-Language', mặc định là defaultLocale
        // Header gửi lên thường có dạng 'vi' hoặc 'vi-VN'
        $locale = $request->headers->get('Accept-Language', $this->defaultLocale);

        // Xử lý đơn giản: chỉ lấy 2 ký tự đầu (ví dụ vi-VN -> vi)
        $locale = substr($locale, 0, 2);

        // Set locale cho request hiện tại
        $request->setLocale($locale);

        TranslationHelper::setTranslator($this->translator);
    }

    public function cacheUserLocale(RequestEvent $event): void
    {
        $user = $this->security->getUser();

        if ($user instanceof User && $user->getId()) {
            $this->cacheService->set(
                sprintf(CacheKey::USER_LOCALE, $user->getId()),
                $event->getRequest()->getLocale(),
                self::USER_LOCALE_TTL,
                false,
            );
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [
                ['onKernelRequest', 20],
                ['cacheUserLocale', 0],
            ],
        ];
    }
}

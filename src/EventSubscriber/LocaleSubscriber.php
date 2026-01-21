<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use App\Class\TranslationHelper;
use Symfony\Contracts\Translation\TranslatorInterface;

class LocaleSubscriber implements EventSubscriberInterface
{
    private string $defaultLocale;

    public function __construct(string $defaultLocale = 'en', private TranslatorInterface $translator)
    {
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

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [['onKernelRequest', 20]], // Chạy sớm trước các xử lý khác
        ];
    }
}

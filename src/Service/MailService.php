<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

class MailService
{
    public function __construct(
        private MailerInterface $mailer
    ) {}

    public function sendOtpEmail(string $toEmail, string $otp): void
    {
        // Cách 1: Gửi text thuần (đơn giản)
        $email = (new Email())
            ->from('boilerplate@gmail.com')
            ->to($toEmail)
            ->subject('Xác thực OTP')
            ->text('Mã OTP của bạn là: ' . $otp);

        // Cách 2: Gửi HTML Template (Khuyên dùng)
        // Bạn cần tạo file templates/emails/welcome.html.twig
        // $email = (new TemplatedEmail())
        //     ->from('your_email@gmail.com') // Có thể cấu hình global trong yaml
        //     ->to($toEmail)
        //     ->subject('Chào mừng bạn đến với hệ thống!')
        //     ->htmlTemplate('emails/welcome.html.twig')
        //     ->context([
        //         'username' => $toEmail, // Biến truyền vào template
        //         'date' => new \DateTime(),
        //     ]);

        $this->mailer->send($email);
    }
}

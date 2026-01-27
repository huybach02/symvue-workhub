<?php

namespace App\Service;

use App\Repository\CauHinhChungRepository;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

class MailService
{
    public function __construct(
        private MailerInterface $mailer,
        private  CauHinhChungRepository $cauHinhChungRepository
    ) {}

    public function sendOtpEmail(string $toEmail, string $otp): void
    {
        $email = (new TemplatedEmail())
            ->from('boilerplate@gmail.com')
            ->to($toEmail)
            ->subject('Xác thực OTP')
            ->htmlTemplate('emails/otp.html.twig')
            ->context([
                'otp' => $otp,
                'expire' => (int) ($this->cauHinhChungRepository->getAllConfig()["THOI_GIAN_HET_HAN_OTP"] ?? 5),
            ]);

        $this->mailer->send($email);
    }

    public function sendForgotPasswordEmail(string $toEmail, string $password): void
    {
        $email = (new TemplatedEmail())
            ->from('boilerplate@gmail.com')
            ->to($toEmail)
            ->subject('Quên mật khẩu')
            ->htmlTemplate('emails/forgot_password.html.twig')
            ->context([
                'userEmail' => $toEmail,
                'password' => $password,
            ]);

        $this->mailer->send($email);
    }
}

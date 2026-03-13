<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

final class BackendLandingController extends AbstractController
{
    public function index(): Response
    {
        return $this->render('backend_landing/index.html.twig', [
            'projectName' => 'ERP Boilerplate',
            'backendStack' => [
                'Symfony',
                'PHP',
                'Doctrine',
                'PostgreSQL',
                'JWT Authentication',
                'Messenger',
                'Mercure',
                'Redis',
                'Twig',
                'Asset Mapper',
            ],
            'frontendStack' => [
                'Vue',
                'Vite',
                'Vue Router',
                'Vuex',
                'Vuetify',
                'i18n',
                'Axios',
                'Vee Validate',
                'Yup',
                'Dayjs',
            ],
            'infrastructureStack' => [
                'Docker Compose',
                'Nginx',
                'PHP-FPM',
                'Mercure Hub',
                'Nginx Proxy Manager',
            ],
            'apiHighlights' => [
                'REST API',
                'Authentication',
                'Realtime',
                'Jobs',
                'Excel',
            ],
        ]);
    }
}

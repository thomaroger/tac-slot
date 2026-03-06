<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Adherent;
use App\Service\HomeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    public function __construct(
        private readonly HomeService $homeService,
    ) {
    }

    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        $user = $this->getUser();
        if (! $user instanceof Adherent) {
            return $this->redirectToRoute('app_login');
        }

        $result = $this->homeService->buildHomeData($user);

        foreach ($result['flashes'] as $flash) {
            $this->addFlash($flash['type'], $flash['message']);
        }

        if ($result['redirectRoute'] !== null) {
            return $this->redirectToRoute($result['redirectRoute']);
        }

        return $this->render('home/index.html.twig', $result['viewData']);
    }
}

<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Adherent;
use App\Service\AuthService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AuthController extends AbstractController
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly Security $security,
    ) {
    }

    #[Route('/login', name: 'app_login', methods: ['GET', 'POST'])]
    public function requestCode(Request $request): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('admin');
        }

        $slotSummary = $this->authService->getCurrentSlotSummary();

        if ($request->isMethod('POST')) {
            $session = $request->getSession();
            $result = $this->authService->requestLoginCode(
                (string) $request->request->get('identifier'),
                $session->getId(),
                $request->getClientIp(),
                $request->headers->get('User-Agent')
            );

            if ($result['status'] === AuthService::REQUEST_CODE_SENT) {
                $session->set('auth_login_adherent_id', $result['adherentId']);
                $this->addFlash('success', 'Code envoyé. Vérifiez votre email.');

                return $this->redirectToRoute('app_login_code');
            }

            $this->addFlash('danger', match ($result['status']) {
                AuthService::REQUEST_EMPTY_IDENTIFIER => 'Saisissez votre email ou numéro de licence.',
                AuthService::REQUEST_ADHERENT_NOT_FOUND,
                AuthService::REQUEST_MISSING_EMAIL,
                AuthService::REQUEST_NOT_ALLOWED => 'Si le compte est éligible, un code a été envoyé.',
                AuthService::REQUEST_RATE_LIMITED => 'Trop de demandes. Réessayez dans quelques minutes.',
                default => 'Impossible d\'envoyer le code. Réessayez.',
            });
        }

        return $this->render('security/login.html.twig', $slotSummary);
    }

    #[Route('/login/code', name: 'app_login_code', methods: ['GET', 'POST'])]
    public function verifyCode(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $session = $request->getSession();
            $result = $this->authService->verifyLoginCode(
                (string) $request->request->get('code'),
                $session->getId(),
                $session->get('auth_login_adherent_id'),
                $request->getClientIp(),
                $request->headers->get('User-Agent')
            );

            if ($result['status'] === AuthService::VERIFY_SUCCESS && $result['adherent'] instanceof Adherent) {
                $this->security->login($result['adherent'], firewallName: 'main');

                return $this->redirectToRoute('app_home');
            }

            if ($result['status'] === AuthService::VERIFY_EMAIL_NOT_VERIFIED) {
                $this->addFlash('danger', 'Compte non vérifié. Ouvrez l\'email de validation puis réessayez.');

                return $this->redirectToRoute('app_login');
            }

            $this->addFlash('danger', match ($result['status']) {
                AuthService::VERIFY_INVALID_SESSION => 'Session expirée. Redemandez un code.',
                AuthService::VERIFY_RATE_LIMITED => 'Trop de tentatives. Réessayez dans quelques minutes.',
                default => 'Code invalide ou expiré. Demandez un nouveau code.',
            });
        }

        return $this->render('security/login_code.html.twig');
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        // Symfony gère la déconnexion
    }

    #[Route('/verify-email/{token}', name: 'app_verify_email')]
    public function verifyEmail(string $token, Request $request): Response
    {
        $status = $this->authService->verifyEmailToken(
            $token,
            $request->getClientIp(),
            $request->headers->get('User-Agent')
        );

        if ($status === AuthService::EMAIL_TOKEN_INVALID) {
            $this->addFlash('danger', 'Lien invalide ou expiré. Redemandez un email de validation.');

            return $this->redirectToRoute('app_login');
        }

        $this->addFlash('success', 'Compte validé. Vous pouvez vous connecter.');

        return $this->redirectToRoute('app_login');
    }
}

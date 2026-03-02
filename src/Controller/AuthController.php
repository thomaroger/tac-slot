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
                $this->addFlash('success', 'Un code de connexion vous a été envoyé par email.');

                return $this->redirectToRoute('app_login_code');
            }

            $this->addFlash('danger', match ($result['status']) {
                AuthService::REQUEST_EMPTY_IDENTIFIER => 'Merci de renseigner votre email ou numéro de licence.',
                AuthService::REQUEST_ADHERENT_NOT_FOUND,
                AuthService::REQUEST_MISSING_EMAIL,
                AuthService::REQUEST_NOT_ALLOWED => 'Si ce compte est éligible, un code de connexion a été envoyé.',
                AuthService::REQUEST_RATE_LIMITED => 'Trop de demandes. Merci de réessayer dans quelques minutes.',
                default => 'Erreur lors de la demande de code.',
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
                $this->addFlash(
                    'danger',
                    'Votre compte n\'a pas été vérifié, vérifiez vos mails pour valider votre compte.'
                );

                return $this->redirectToRoute('app_login');
            }

            $this->addFlash('danger', match ($result['status']) {
                AuthService::VERIFY_INVALID_SESSION => 'Code ou session invalide.',
                AuthService::VERIFY_RATE_LIMITED => 'Trop de tentatives. Merci de réessayer dans quelques minutes.',
                default => 'Code invalide ou expiré.',
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
            $this->addFlash('danger', 'Lien de validation invalide ou expiré.');

            return $this->redirectToRoute('app_login');
        }

        $this->addFlash('success', 'Votre compte est maintenant validé.');

        return $this->redirectToRoute('app_login');
    }
}

<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

/**
 * Authenticator "neutre" pour satisfaire le firewall main
 * (toute l'authentification est gérée manuellement via Security::login()).
 */
class DummyAuthenticator extends AbstractAuthenticator
{
    public function supports(Request $request): ?bool
    {
        // On ne gère jamais directement une requête dans cet authenticator.
        return false;
    }

    public function authenticate(Request $request): Passport
    {
        throw new \LogicException('DummyAuthenticator ne doit jamais être utilisé directement.');
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return null;
    }
}


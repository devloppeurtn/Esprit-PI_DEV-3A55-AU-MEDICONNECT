<?php

namespace App\Security;

use App\Entity\Patient;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class GoogleOAuthAuthenticator extends OAuth2Authenticator
{
    public function __construct(
        private readonly ClientRegistry $clientRegistry,
        private readonly EntityManagerInterface $entityManager,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly AdminLoginSuccessHandler $successHandler,
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'connect_google_check';
    }

    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient('google');
        $accessToken = $this->fetchAccessToken($client);

        $googleUser = $client->fetchUserFromToken($accessToken);
        $googleData = $googleUser->toArray();
        $email = $googleData['email'] ?? null;
        if (!$email) {
            throw new CustomUserMessageAuthenticationException('Adresse email Google indisponible.');
        }

        $googleId = $googleUser->getId();
        $displayName = $googleData['name']
            ?? $googleData['nickname']
            ?? $email;

        return new SelfValidatingPassport(
            new UserBadge($email, function () use ($email, $googleId, $displayName) {
                $repo = $this->entityManager->getRepository(Utilisateur::class);

                $user = null;
                if ($googleId) {
                    $user = $repo->findOneBy(['googleId' => $googleId]);
                }
                if (!$user) {
                    $user = $repo->findOneBy(['email' => $email]);
                }

                if (!$user) {
                    $user = new Patient();
                    $user->setEmail($email);
                    $user->setNomComplet($displayName);
                    $user->setEmailVerified(true);
                    $user->setPassword($this->passwordHasher->hashPassword($user, bin2hex(random_bytes(16))));
                    $this->entityManager->persist($user);
                }

                if ($googleId && $user->getGoogleId() !== $googleId) {
                    $user->setGoogleId($googleId);
                }

                if (!$user->isEmailVerified()) {
                    $user->setEmailVerified(true);
                }

                $this->entityManager->flush();

                return $user;
            }),
            [new RememberMeBadge()]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return $this->successHandler->onAuthenticationSuccess($request, $token);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        if ($request->hasSession()) {
            $session = $request->getSession();
            if ($session !== null && method_exists($session, 'getFlashBag')) {
                $session->getFlashBag()->add('error', 'Connexion Google echouee.');
            }
        }
        return new RedirectResponse($this->urlGenerator->generate('app_login'));
    }

    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        return new RedirectResponse($this->urlGenerator->generate('app_login'));
    }
}

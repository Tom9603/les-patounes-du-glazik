<?php

namespace App\Security;

use App\Entity\Member;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use League\OAuth2\Client\Provider\FacebookUser;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class FacebookAuthenticator extends OAuth2Authenticator
{
    public function __construct(
        private readonly ClientRegistry $clientRegistry,
        private readonly EntityManagerInterface $em,
        private readonly RouterInterface $router,
    ) {}

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'app_oauth_facebook_callback';
    }

    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient('facebook');
        $accessToken = $this->fetchAccessToken($client);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function () use ($accessToken, $client) {
                /** @var FacebookUser $facebookUser */
                $facebookUser = $client->fetchUserFromToken($accessToken);

                $member = $this->em->getRepository(Member::class)
                    ->findOneBy(['facebookId' => $facebookUser->getId()]);

                if ($member) {
                    return $member;
                }

                $email = $facebookUser->getEmail();

                if ($email) {
                    $member = $this->em->getRepository(Member::class)
                        ->findOneBy(['email' => $email]);

                    if ($member) {
                        $member->setFacebookId($facebookUser->getId());
                        $this->em->flush();
                        return $member;
                    }
                }

                $member = new Member();
                $member->setFacebookId($facebookUser->getId());
                $member->setEmail($email ?? $facebookUser->getId() . '@facebook.invalid');
                $member->setFirstName($facebookUser->getFirstName() ?: 'Membre');
                $member->setLastName($facebookUser->getLastName());
                $member->setPassword('!oauth_' . bin2hex(random_bytes(16)));
                $member->setIsVerified(true);

                $this->em->persist($member);
                $this->em->flush();

                return $member;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $user = $token->getUser();
        if ($user instanceof Member && in_array('ROLE_ADMIN', $user->getRoles())) {
            return new RedirectResponse($this->router->generate('admin'));
        }
        return new RedirectResponse($this->router->generate('app_blog'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $request->getSession()->getFlashBag()->add('error', 'Connexion Facebook echouee. Veuillez reessayer.');
        return new RedirectResponse($this->router->generate('app_member_login'));
    }
}

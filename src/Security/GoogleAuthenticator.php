<?php

namespace App\Security;

use App\Entity\Member;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use League\OAuth2\Client\Provider\GoogleUser;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class GoogleAuthenticator extends OAuth2Authenticator
{
    use TargetPathTrait;

    public function __construct(
        private readonly ClientRegistry $clientRegistry,
        private readonly EntityManagerInterface $em,
        private readonly RouterInterface $router,
    ) {}

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'app_oauth_google_callback';
    }

    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient('google');
        $accessToken = $this->fetchAccessToken($client);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function () use ($accessToken, $client) {
                /** @var GoogleUser $googleUser */
                $googleUser = $client->fetchUserFromToken($accessToken);

                $member = $this->em->getRepository(Member::class)
                    ->findOneBy(['googleId' => $googleUser->getId()]);

                if ($member) {
                    return $member;
                }

                $member = $this->em->getRepository(Member::class)
                    ->findOneBy(['email' => $googleUser->getEmail()]);

                if ($member) {
                    $member->setGoogleId($googleUser->getId());
                    $this->em->flush();
                    return $member;
                }

                $member = new Member();
                $member->setGoogleId($googleUser->getId());
                $member->setEmail($googleUser->getEmail());
                $member->setFirstName($googleUser->getFirstName() ?: 'Membre');
                $member->setLastName($googleUser->getLastName());
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
            $this->removeTargetPath($request->getSession(), $firewallName);
            return new RedirectResponse($this->router->generate('admin'));
        }

        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->router->generate('app_home'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $request->getSession()->getFlashBag()->add('error', 'Connexion Google echouee. Veuillez reessayer.');
        return new RedirectResponse($this->router->generate('app_member_login'));
    }
}

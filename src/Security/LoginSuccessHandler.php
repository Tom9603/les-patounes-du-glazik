<?php

namespace App\Security;

use App\Entity\Member;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    use TargetPathTrait;

    public function __construct(private RouterInterface $router) {}

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): RedirectResponse
    {
        $user = $token->getUser();

        // Les admins vont toujours vers le panneau d'administration
        if ($user instanceof Member && in_array('ROLE_ADMIN', $user->getRoles())) {
            $this->removeTargetPath($request->getSession(), 'main');
            return new RedirectResponse($this->router->generate('admin'));
        }

        // Pour les utilisateurs normaux : respecter la page demandée avant la redirection vers le login
        if ($targetPath = $this->getTargetPath($request->getSession(), 'main')) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->router->generate('app_home'));
    }
}

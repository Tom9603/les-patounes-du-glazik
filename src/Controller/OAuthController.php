<?php

namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class OAuthController extends AbstractController
{
    #[Route('/oauth/google/connect', name: 'app_oauth_google_connect')]
    public function connectGoogle(ClientRegistry $clientRegistry): Response
    {
        return $clientRegistry->getClient('google')
            ->redirect(['openid', 'email', 'profile'], []);
    }

    #[Route('/oauth/google/callback', name: 'app_oauth_google_callback')]
    public function callbackGoogle(): Response
    {
        return $this->redirectToRoute('app_blog');
    }

    #[Route('/oauth/facebook/connect', name: 'app_oauth_facebook_connect')]
    public function connectFacebook(ClientRegistry $clientRegistry): Response
    {
        return $clientRegistry->getClient('facebook')
            ->redirect(['public_profile', 'email'], []);
    }

    #[Route('/oauth/facebook/callback', name: 'app_oauth_facebook_callback')]
    public function callbackFacebook(): Response
    {
        return $this->redirectToRoute('app_blog');
    }
}

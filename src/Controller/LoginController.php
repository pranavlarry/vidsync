<?php

namespace App\Controller;

use Google\Auth\AccessToken;
use Google_Client;
use Google\Service\YouTube as Google_Service_YouTube;
use Google\Client;
use Google\Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class LoginController extends AbstractController
{
    /**
     * @Route("/login", name="login")
     */

    public function login(Request $request)
    {
        $session = $request->getSession();
        $access_token = $session->get('access_token');

        if ($access_token) {
            return $this->redirectToRoute('home');
        }

        // Render the login form
        return $this->render('first_login.html.twig');
    }

    /**
     * @Route("/login_with_google", name="login_with_google")
     */

    public function loginWithGoogle(Request $request)
    {
        $client = new Google_Client();
        $client->setClientId('364147634847-fo6idfvun9fp6usn9i2op76cnpnnm0o5.apps.googleusercontent.com');
        $client->setClientSecret('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');
        $client->setRedirectUri($this->generateUrl('login_callback', [], UrlGeneratorInterface::ABSOLUTE_URL));
        $client->addScope(Google_Service_YouTube::YOUTUBE_READONLY);

        // Redirect the user to Google's OAuth 2.0 server to authorize the app
        $authUrl = $client->createAuthUrl();
        return $this->redirect($authUrl);
    }

    /**
     * @Route("/login_callback", name="login_callback")
     */
    public function loginCallback(Request $request)
    {
        // Exchange the authorization code for an access token
        $client = new Google_Client();
        $client->setClientId('364147634847-fo6idfvun9fp6usn9i2op76cnpnnm0o5.apps.googleusercontent.com');
        $client->setClientSecret('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');
        $client->setRedirectUri($this->generateUrl('login_callback', [], UrlGeneratorInterface::ABSOLUTE_URL));
        $client->addScope(Google_Service_YouTube::YOUTUBE_READONLY);

        $session = $request->getSession();
        $refreshToken = $session->get('refresh_token');

        if ($refreshToken) {
            // If refresh token exists, set it in the client
            $client->setAccessType('offline');
            $client->setAccessToken(['refresh_token' => $refreshToken]);

            // Attempt to refresh the access token using the refresh token
            try {
                $client->fetchAccessTokenWithRefreshToken();
                $accessToken = $client->getAccessToken();
                $session->set('access_token', $accessToken);
                return $this->redirectToRoute('home');
            } catch (Exception $e) {
                // Refresh token is invalid, clear it from session and proceed with authorization code flow
                $session->remove('refresh_token');
            }
        }

       
        $code = $request->query->get('code');
        $accessToken = $client->fetchAccessTokenWithAuthCode($code);

        
        if (isset($accessToken['refresh_token'])) {
            $session->set('refresh_token', $accessToken['refresh_token']);
        }

        $session->set('access_token', $accessToken);
        return $this->redirectToRoute('home');
    }
}

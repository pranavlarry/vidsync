<?php

namespace App\Controller;

use Google_Client;
use Google_Service_YouTube;
use Google_Service_Exception;
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
         // Render the login form
         return $this->render('first_login.html.twig');
     }

         /**
     * @Route("/login_with_google", name="login_with_google")
     */

    public function loginWithGoogle(Request $request)
    {
        $client = new Google_Client();
        $client->setClientId('485562837483-5n90i4oj21edf60m0349qr1q6orkt0r8.apps.googleusercontent.com');
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
        $client->setClientId('485562837483-5n90i4oj21edf60m0349qr1q6orkt0r8.apps.googleusercontent.com');
        $client->setClientSecret('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');
        $client->setRedirectUri($this->generateUrl('login_callback', [], UrlGeneratorInterface::ABSOLUTE_URL));
        $client->addScope(Google_Service_YouTube::YOUTUBE_READONLY);
        
        $code = $request->query->get('code');
        $accessToken = $client->fetchAccessTokenWithAuthCode($code);
        $client->setAccessToken($accessToken);


        try {
            $youtube = new Google_Service_YouTube($client);
            $channels = $youtube->channels->listChannels('snippet', ['mine' => true]);
            if (count($channels) === 0) {
                // User doesn't have a YouTube channel, redirect to error page
                return $this->render('error.html.twig', [
                    'message' => 'No channel for this account, try with another one'
                ]);
            }
        } catch (Google_Service_Exception $e) {
            // Error occurred while checking for channels, redirect to error page
            return $this->render('error.html.twig', [
                'message' => $e->getMessage()
            ]);
        }

        // Store the access token in the session for later use
        $session = $request->getSession();
        $session->set('access_token', $accessToken);

        // Redirect the user to the home page
        return $this->redirectToRoute('home');
    }
}
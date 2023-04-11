<?php

namespace App\Controller;

use Google_Client;
use Google\Service\YouTube as Google_Service_YouTube;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class HomeController extends AbstractController
{
    #[Route('/home', name: 'app_home')]
    public function index(Request $request)
    {
        $session = $request->getSession();
        $access_token = $session->get('access_token');
        $refresh_token = $session->get('refresh_token');

        if (!$access_token) {
            return $this->redirectToRoute('login');
        }


        $client = new Google_Client();
        $client->setAccessToken($access_token);

        if ($client->isAccessTokenExpired()) {
            $client->fetchAccessTokenWithRefreshToken($refresh_token);
            $new_access_token = $client->getAccessToken();
            $session->set('access_token', $new_access_token);
            $access_token = $new_access_token;
        }
        $youtube = new Google_Service_YouTube($client);

        $channelsResponse = $youtube->channels->listChannels('id,contentDetails', [
            'mine' => true,
        ]);
        $channelId = $channelsResponse[0]['id'];

        $searchResponse = $youtube->search->listSearch('id,snippet', [
            'order' => 'date',
            'channelId' => $channelId,
            'maxResults' => 500,
        ]);

        $videoIds = array();
        foreach ($searchResponse['items'] as $searchResult) {
            $videoIds[] = $searchResult['id']['videoId'];
        }

        if (empty($videoIds)) {
            return $this->render('home/index.html.twig', [
                'videos' => [],
                'noResults' => true,
            ]);
        }

        $videosResponse = $youtube->videos->listVideos('snippet,contentDetails', [
            'id' => implode(',', $videoIds),
        ]);
        
        $videos = array();
        foreach ($videosResponse as $video) {
            $durationString = $video->contentDetails->duration;
            $duration = new \DateInterval($durationString);
            $durationSeconds = $duration->h * 3600 + $duration->i * 60 + $duration->s;
            
        
            $durationReadable = gmdate('H:i:s', $durationSeconds);
            $videos[] = array(
                'title' => $video->snippet->title,
                'thumbnail' => $video->snippet->thumbnails->default->url,
                'duration' => $durationReadable,
                'videoId' => $video->id,
                'publishedAt' => $video->snippet->publishedAt,
            );
        }

        $keyword = $request->query->get('keyword');
        if ($keyword) {
            $videos = array_filter($videos, function ($video) use ($keyword) {
                $title = $video['title'];
                return (strtolower($title[0]) == strtolower($keyword[0])) &&
                    str_contains(strtolower($title), strtolower($keyword));
            });
        }

        return $this->render('home/index.html.twig', [
            'videos' => $videos,
            'keyword' => $keyword,
            'noResults' => empty($videos),
        ]);
    }
    #[Route('/videos/{videoId}', name: 'video_detail')]
    public function videoDetail(Request $request, string $videoId)
    {
        $session = $request->getSession();
        $access_token = $session->get('access_token');
        $refresh_token = $session->get('refresh_token');

        if (!$access_token) {
            return $this->redirectToRoute('login');
        }

        $client = new Google_Client();
        $client->setAccessToken($access_token);

        if ($client->isAccessTokenExpired()) {
            $client->fetchAccessTokenWithRefreshToken($refresh_token);
            $new_access_token = $client->getAccessToken();
            $session->set('access_token', $new_access_token);
            $access_token = $new_access_token;
        }

        $youtube = new Google_Service_YouTube($client);

        $videosResponse = $youtube->videos->listVideos('snippet', [
            'id' => $videoId,
        ]);

        $video = [
            'title' => $videosResponse[0]->snippet->title,
            'description' => $videosResponse[0]->snippet->description,
            'thumbnail' => $videosResponse[0]->snippet->thumbnails->high->url,
            'videoId' => $videosResponse[0]->id,
        ];

        return $this->render('home/next.html.twig', [
            'video' => $video,
        ]);
    }
}

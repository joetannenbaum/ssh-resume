<?php

namespace ChewieLab;

use SpotifyWebAPI\Session;
use SpotifyWebAPI\SpotifyWebAPI;

class Spotify
{
    public static function get(): SpotifyWebAPI
    {
        $session = new Session(
            $_SERVER['SPOTIFY_CLIENT_ID'],
            $_SERVER['SPOTIFY_CLIENT_SECRET'],
            'http://localhost:8000/callback.php',
        );

        $session->setAccessToken(file_get_contents(__DIR__ . '/../../spotify/access_token.txt'));
        $session->setRefreshToken(file_get_contents(__DIR__ . '/../../spotify/refresh_token.txt'));

        $options = [
            'auto_refresh' => true,
        ];

        $api = new SpotifyWebAPI($options, $session);

        $api->me();

        file_put_contents(__DIR__ . '/../../spotify/access_token.txt', $session->getAccessToken());
        file_put_contents(__DIR__ . '/../../spotify/refresh_token.txt', $session->getRefreshToken());

        return $api;
    }
}

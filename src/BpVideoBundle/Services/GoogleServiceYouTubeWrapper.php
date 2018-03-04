<?php

namespace BpVideoBundle\Services;

/**
 * Wraps up Google's YouTube service
 *
 * Class GoogleServiceYouTubeWrapper
 *
 * @package BpVideoBundle\Services
 */
class GoogleServiceYouTubeWrapper {

    /**
     * YouTube service instance
     *
     * @var \Google_Service_YouTube
     */
    private $youTubeService;

    /**
     * GoogleServiceYouTubeWrapper constructor.
     *
     * @param $apiKey
     */
    public function __construct($apiKey)
    {
        $client = new \Google_Client();
        $client->setApplicationName('Bored Panda Test');
        $client->setDeveloperKey($apiKey);

        $this->youTubeService = new \Google_Service_YouTube($client);
    }

    /**
     * Gets YouTube service instance
     *
     * @return \Google_Service_YouTube
     */
    public function getService()
    {
        return $this->youTubeService;
    }

}

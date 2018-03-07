<?php

namespace BpVideoBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use BpVideoBundle\Model\VideoModel;
use BpVideoBundle\Services\GoogleServiceYouTubeWrapper;

/**
 * Class YoutubeFetchCommand
 *
 * Symfony's command line script to fetch the videos from passed
 * YouTube channel(s)
 *
 * @package BpVideoBundle\Command
 */
class YoutubeFetchCommand extends Command
{

    /**
     * Maximum numbers of videos to fetch per call
     */
    const MAX_RESULTS = 50;

    /**
     * An instance of YouTube client
     *
     * @var \Google_Service_YouTube
     */
    private $youTubeClient;

    /**
     * An instance of Video Model for Database operations
     *
     * @var \BpVideoBundle\Model\VideoModel
     */
    private $videoModel;

    /**
     * YoutubeFetchCommand constructor.
     *
     * @param \BpVideoBundle\Services\GoogleServiceYouTubeWrapper $youTubeClient
     * @param \BpVideoBundle\Model\VideoModel $videoModel
     */
    public function __construct(
      GoogleServiceYouTubeWrapper $youTubeClient,
      VideoModel $videoModel
    ) {
        parent::__construct();
        $this->youTubeClient = $youTubeClient->getService();
        $this->videoModel = $videoModel;
    }

    /**
     * Basic configuration for CLI command:
     * defining the name, description, arguments and usage example
     */
    protected function configure()
    {
        $this
          ->setName('fetch:videos')
          ->setDescription('Fetch YouTube videos from given channel(s)')
          ->setHelp('Usage: php bin/console fetch:videos CHANNEL_ID')
          ->addArgument('channelId', InputArgument::REQUIRED,
            'YouTube channel ID(s) separated by comma to fetch videos from');
    }

    /**
     * Gets all video IDs from the YouTube channel
     *
     * @param string $channelId
     *
     * @return array
     */
    private function getChannelVideoIDs($channelId)
    {
        $videoIDs = [];
        do {
            $channelVids = $this->youTubeClient->search->listSearch('id,snippet',
              [
                'type' => 'video',
                'channelId' => $channelId,
                'pageToken' => isset($channelVids['nextPageToken']) ? $channelVids['nextPageToken'] : '',
                'order' => 'date',
                'maxResults' => self::MAX_RESULTS,
              ]);

            if (!empty($channelVids['items'])) {
                foreach ($channelVids['items'] AS $video) {
                    $videoIDs[] = $video['id']['videoId'];
                }
            }

        } while (!empty($channelVids['nextPageToken']));

        return $videoIDs;
    }

    /**
     * Gets extended video data for the given set of videos
     *
     * @param array $videoIDs - an array of video IDs to query
     *
     * @return \Generator
     */
    private function getVideoData($videoIDs)
    {
        $chunks = array_chunk($videoIDs, self::MAX_RESULTS);
        foreach ($chunks AS $chunk) {
            $vids = $this->youTubeClient->videos->listVideos('snippet,statistics',
              [
                'id' => implode(',', $chunk),
              ]);

            if (empty($vids['items'])) {
                continue;
            }

            foreach ($vids['items'] as $vid) {
                yield $vid;
            }
        }
    }

    /**
     * Parses error data from the YouTube API and retrieves
     * the human-readable error message to display
     *
     * @param string $message
     *
     * @return string
     */
    private function exceptionIntoErrorString($message)
    {
        $messageAssoc = json_decode($message);

        return $messageAssoc->error->message.'(code: '.$messageAssoc->error->code.')';
    }

    /**
     * Command line executor
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $channels = $input->getArgument('channelId');
        $channels = explode(',', $channels);

        foreach ($channels as $channel) {
            $output->writeln('Querying YouTube channel '.$channel);

            try {
                $channelVids = $this->getChannelVideoIDs($channel);
            } catch (\Exception $e) {
                $output->writeln($channel.' - '.$this->exceptionIntoErrorString($e->getMessage()));
                continue;
            }

            if (empty($channelVids)) {
                $output->writeln('No items have been found in '.$channel.' channel');
                continue;
            }

            $i = 0;
            foreach ($this->getVideoData($channelVids) as $videoResult) {
                if ($this->videoModel->persistVideo($videoResult)) {
                    $i++;
                }
            }

            $output->writeln($i.' videos have been updated');
        }
    }
}

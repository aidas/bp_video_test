<?php

namespace BpVideoBundle\Model;

use PDO;

/**
 * Class VideoModel
 *
 * Basic MySQL operations for video data
 *
 * @package BpVideoBundle\Model
 *
 * @todo: implement better data validation where neccessary
 * @todo: consider handling case sensitive tag names?
 * @todo: delete the videos that are no longer in the channel
 * @todo: use transactions where applicable
 */
class VideoModel
{

    /**
     * Instance of PHP's PDO object
     */
    private $pdo;

    /**
     * VideoModel constructor.
     *
     * @param string $dbHost - DB host
     * @param string $dbName - DB name
     * @param string $dbUser - DB user
     * @param string $dbPswd - DB user password
     */
    public function __construct($dbHost, $dbName, $dbUser, $dbPswd)
    {
        if (!($this->pdo instanceof PDO)) {
            $this->pdo = new PDO(
              'mysql:host='.$dbHost.';dbname='.$dbName,
              $dbUser,
              $dbPswd
            );
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        }
    }

    /**
     * Queries the video table by video ID
     *
     * @param string $id
     *
     * @return bool
     */
    public function findById($id)
    {
        $stmt = $this->pdo->prepare(
          'SELECT id FROM videos WHERE id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Main method to record video data
     *
     * @param \Google_Service_YouTube_Video $video
     *
     * @return mixed
     */
    public function persistVideo(\Google_Service_YouTube_Video $video)
    {
        $stmt = $this->pdo->prepare(
          '
            INSERT INTO videos (id, channel_id, published_at, title, description)
            VALUES (:id, :channel_id, UNIX_TIMESTAMP(:published_at), :title, :description)
            ON DUPLICATE KEY UPDATE
            channel_id = VALUES(channel_id),
            published_at = VALUES(published_at),
            title = VALUES(title),
            description = VALUES(description)
        '
        );

        $updateBase = $stmt->execute(
          [
            ':id' => $video['id'],
            ':channel_id' => $video['snippet']['channelId'],
            ':published_at' => $video['snippet']['publishedAt'],
            ':title' => $video['snippet']['title'],
            ':description' => $video['snippet']['description'],
          ]
        );

        if ($updateBase) {
            //record stats
            if (!empty($video['statistics'])) {
                $this->recordStats($video['statistics'], $video['id']);
            }

            //record tags
            if (!empty($video['snippet']['tags'])) {
                $this->recordTags($video['snippet']['tags'], $video['id']);
            }
        }

        return $updateBase;
    }

    /**
     * Records video stats
     *
     * @param \Google_Service_YouTube_VideoStatistics $videoStats
     * @param string $videoID
     *
     * @return mixed
     */
    private function recordStats(
      \Google_Service_YouTube_VideoStatistics $videoStats,
      $videoID
    ) {
        $scrapeTimestamp = time();
        $stmt = $this->pdo->prepare(
          'INSERT INTO
                    video_stats
                    (video_id, scrape_timestamp, comment_count, dislike_count, like_count, view_count)
                    VALUES
                    (:video_id, :scrape_timestamp, :comment_count, :dislike_count, :like_count, :view_count)'
        );

        return $stmt->execute(
          [
            ':video_id' => $videoID,
            ':scrape_timestamp' => $scrapeTimestamp,
            ':comment_count' => $videoStats['commentCount'],
            ':dislike_count' => $videoStats['dislikeCount'],
            ':like_count' => $videoStats['likeCount'],
            ':view_count' => $videoStats['viewCount'],
          ]
        );
    }

    /**
     * Gets a video tag by name
     *
     * @param string $name
     *
     * @return mixed
     */
    public function getTagByName($name)
    {
        $stmt = $this->pdo->prepare(
          'SELECT * FROM video_tags WHERE tag = :name LIMIT 1'
        );

        $stmt->execute([':name' => $name,]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Records tags belonging to the video represented by it's ID
     *
     * @param array $tags
     * @param string $videoID
     *
     * @todo: combine inserts
     */
    private function recordTags(array $tags, $videoID)
    {
        $tagIDs = [];
        foreach ($tags as $tag) {
            //check if the tag is already recorded
            if ($tagData = $this->getTagByName($tag)) {
                $tagIDs[] = $tagData['id'];
                continue;
            }

            //if the tag is not found, record it!
            $stmt = $this->pdo->prepare(
              'INSERT INTO video_tags (tag) VALUES (:tag)'
            );

            $stmt->execute([':tag' => trim(strip_tags($tag)),]);

            $tagIDs[] = $this->pdo->lastInsertId();
        }

        //now that we have tag IDs, we need to update the connections.
        $stmt = $this->pdo->prepare(
          'DELETE FROM video_tag_conn WHERE video_id = :video_id;'
        );
        if ($stmt->execute([':video_id' => $videoID])) {
            foreach (array_unique($tagIDs) as $tagID) {
                $stmt = $this->pdo->prepare(
                  'INSERT INTO video_tag_conn VALUES (:video_id, :tag_id);'
                );
                $stmt->execute([':video_id' => $videoID, ':tag_id' => $tagID]);
            }
        }
    }

    /**
     * Gets tag suggestions based on the initial input
     *
     * @param string $name
     *
     * @return array
     */
    public function getTagSuggestions($name)
    {
        $stmt = $this->pdo->prepare(
          'SELECT * FROM video_tags WHERE tag LIKE ?'
        );

        $stmt->execute(['%'.$name.'%']);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Filters video by tags
     *
     * @param string $tagName
     * @param int $limit
     * @param int $offset
     *
     * @return array
     */
    public function retrieveVideosByTag($tagName, $limit = 25, $offset = 0)
    {

        $stmt = $this->pdo->prepare(
          '
            SELECT vids.* FROM videos vids
            JOIN video_tag_conn conn ON vids.id=conn.video_id
            WHERE conn.tag_id IN (SELECT id FROM video_tags WHERE tag = :tag)
            LIMIT :limit OFFSET :offset
        '
        );

        $stmt->execute(
          [
            ':tag' => $tagName,
            ':limit' => $limit * 1,
            ':offset' => $offset * 1,
          ]
        );

        return $stmt->fetchAll();
    }

    /**
     * Gets the number of views based on the time given
     *
     * @param int|string $time - time in seconds after the video is published
     *
     * @return array
     */
    public function getViewsInTimeframe($time)
    {
        $stmt = $this->pdo->prepare(
          '
            SELECT vid.id, stats.view_count FROM videos vid
            INNER JOIN (
            SELECT a.* FROM video_stats a
            INNER JOIN
            (SELECT video_id, MAX(scrape_timestamp) last_scrape
             FROM video_stats GROUP BY video_id
             HAVING last_scrape <= (SELECT published_at+'.$time * 1 .' FROM videos WHERE id=video_stats.video_id)
             ) b
             ON a.video_id=b.video_id AND a.scrape_timestamp=b.last_scrape
            ) stats ON vid.id=stats.video_id
        '
        );

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_COLUMN, 1);
    }

    /**
     * Gets a list of channels
     *
     * @return array
     */
    public function getChannels()
    {
        $stmt = $this->pdo->prepare('SELECT DISTINCT(channel_id) FROM VIDEOS');
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    }

}

<?php

namespace BpVideoBundle\Controller;

use BpVideoBundle\Helper\CalcHelper;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * Class DashboardController
 *
 * @package BpVideoBundle\Controller
 */
class DashboardController extends Controller
{

    /**
     * Number of videos to display per page
     */
    const PER_PAGE = 25;

    /**
     * @param null $tag - optional tag
     * @param int $page
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction($tag = null, $page)
    {
        $videoModel = $this->get('bp_video.model_video');

        $offset = ($page-1)*self::PER_PAGE;

        //@todo - implement caching here
        $videosByTag = (!empty($tag))
          ? $videoModel->retrieveVideosByTag(htmlspecialchars(strip_tags($tag), ENT_QUOTES), self::PER_PAGE, $offset)
          : false;

        $allVideoMedian = false;
        $firstHourViewsDividedByChannels = false;

        if ($firstHourVideoViews = $videoModel->getViewsInTimeframe(3600)) {
            $allVideoMedian = CalcHelper::calculateMedian($firstHourVideoViews);
        }

        if ($channels = $videoModel->getChannels() && !empty($firstHourVideoViews)) {
            $firstHourViewsDividedByChannels = array_sum($firstHourVideoViews)/count($channels);
        }

        return $this->render('@BpVideo/Dashboard/index.html.twig', [
            'firstHourViewsDividedByChannels' => $firstHourViewsDividedByChannels,
            'allVideoMedian' => $allVideoMedian,
            'videosByTag' => $videosByTag
        ]);
    }
}

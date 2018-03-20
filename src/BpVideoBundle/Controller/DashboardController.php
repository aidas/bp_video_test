<?php

namespace BpVideoBundle\Controller;

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

        return $this->render('@BpVideo/Dashboard/index.html.twig', [
            'videosByTag' => (!empty($tag)) ? $videoModel->retrieveVideosByTag(htmlspecialchars(strip_tags($tag), ENT_QUOTES), self::PER_PAGE, $offset) : false
        ]);
    }
}

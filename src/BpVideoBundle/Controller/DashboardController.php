<?php

namespace BpVideoBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DashboardController extends Controller
{
    const PER_PAGE = 25;

    public function indexAction($tag = null, $page)
    {
        $videoModel = $this->get('bp_video.model_video');

        $offset = ($page-1)*self::PER_PAGE;

        return $this->render('@BpVideo/Dashboard/index.html.twig', [
            'videosByTag' => (!empty($tag)) ? $videoModel->retrieveVideosByTag($tag, self::PER_PAGE, $offset) : false
        ]);
    }
}

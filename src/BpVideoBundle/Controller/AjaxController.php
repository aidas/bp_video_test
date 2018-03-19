<?php

namespace BpVideoBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

class AjaxController extends Controller {
    
    function tagSuggetionsAction($name)
    {
        $videoModel = $this->get('bp_video.model_video');
        if ($tags = $videoModel->getTagSuggestions($name)) {
            return new JsonResponse($tags);
        }
    }
}

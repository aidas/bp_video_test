<?php

namespace BpVideoBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class AjaxController
 *
 * A set of basic Ajax operations
 *
 * @package BpVideoBundle\Controller
 */
class AjaxController extends Controller {

    /**
     * Gets the suggested tags as per string input
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param string $name
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    function tagSuggetionsAction(Request $request, $name)
    {
        if (!$request->isXmlHttpRequest()) {
            return new Response(null,403);
        }

        $videoModel = $this->get('bp_video.model_video');
        if ($tags = $videoModel->getTagSuggestions(htmlspecialchars(strip_tags($name), ENT_QUOTES))) {
            return new JsonResponse($tags);
        }

        throw new NotFoundHttpException();
    }
}

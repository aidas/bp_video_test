<?php

namespace BpVideoBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DashboardController extends Controller
{
    public function indexAction()
    {
        return $this->render('@BpVideoBundle/Dashboard/index.html.twig');
    }
}

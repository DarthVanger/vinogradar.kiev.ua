<?php

namespace Vinogradar\NewsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    /**
     * List news
     */
    public function listNewsAction()
    {
        return $this->render('VinogradarNewsBundle:Default:list.html.twig');
    }
}

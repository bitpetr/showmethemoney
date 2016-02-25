<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class DefaultController extends Controller
{
    /**
     * Index page
     * @Route(name="index", path="/", methods={"GET"})
     */
    public function indexAction()
    {
        return $this->render('AppBundle:Default:index.html.twig');
    }
}

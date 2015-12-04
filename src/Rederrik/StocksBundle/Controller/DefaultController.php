<?php

namespace Rederrik\StocksBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class DefaultController extends Controller
{
    /**
     * Index page
     * @Route("/")
     */
    public function indexAction()
    {
        return $this->render('RederrikStocksBundle:Default:index.html.twig');
    }
}

<?php
/**
 * User: davydov
 * Date: 02.12.2015
 * Time: 10:41
 */

namespace Rederrik\StocksBundle\Controller;

use Rederrik\StocksBundle\Entity\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class PortfolioController
 * @package Rederrik\StocksBundle\Controller
 */
class PortfolioController extends Controller
{

    /**
     * Renders portfolio table
     *
     * @Route(name="portfolio_table", path="/portfolio/table", methods={"GET"}, options={"expose"=true})
     * @return Response
     */
    public function portfolioTableAction()
    {
        $user = $user = $this->getUser();
        if (!$user instanceof User) { //do the correct user?
            throw $this->createAccessDeniedException();
        }

        $portfolio = $user->getPortfolio()->toArray(); //association is indexed, so keys are stock symbols
        //using service to fetch fresh data
        $portfolio = $this->get('rederrik_stocks.stock_provider')->getStock(array_keys($portfolio));
        return $this->render(
            'RederrikStocksBundle:Portfolio:table.html.twig', ['quotes' => $portfolio]
        );
    }

    /**
     * Adds new stock to user portfolio by its symbol
     *
     * @Route(name="stock_add", path="/stock/add", methods={"POST"}, options={"expose"=true})
     * @return JsonResponse
     */
    public function addStockBySymbolAction(Request $request)
    {
        $symbol = $request->request->get('symbol');
        if (!$symbol || strlen($symbol) > 10) {
           return new JsonResponse(['error' => 'Wrong quote symbol'], 400);
        }

        $user = $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        //fetch fresh stock info using service
        $stocks = $this->get('rederrik_stocks.stock_provider')->getStock(strtoupper($symbol));

        if (empty($stocks)) {
            return new JsonResponse(['error' => 'Stock not found.']);
        }

        $portfolio = $user->getPortfolio();
        $stock = array_pop($stocks); //always an array, but we only use one value
        if ($portfolio->contains($stock)) {
            return new JsonResponse(['error' => 'Stock already added to your portfolio.']);
        }
        $user->addToPortfolio($stock);

        $this->getDoctrine()->getManager()->flush();

        $result = $this->get('serializer')->serialize(
            ['result' => $stock], 'json', ['groups' => ['attributes']]
        );

        //Return JSON data with new stock
        return new Response($result, 200, ['Content-type'=>'application/json']);
    }

    /**
     * Removes stock from user portfolio without deleting its data
     *
     * @Route(name="stock_remove", path="/stock/remove", methods={"POST"}, options={"expose"=true})
     * @return JsonResponse
     */
    public function removeStockByIdAction(Request $request)
    {
        $user = $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }
        $id = $request->request->get('id');

        $em = $this->getDoctrine()->getManager();
        $stock = $em->find('RederrikStocksBundle:Stock', $id);
        if (!$stock) {
            return new JsonResponse(['error' => 'Stock not found.']);
        }

        $portfolio = $user->getPortfolio();
        if (!$portfolio->removeElement($stock)) {
            return new JsonResponse(['error' => 'Stock not found in your portfolio.']);
        }
        $em->flush();
        return new JsonResponse(['result' => ['id' => $stock->getId()]]);
    }

    /**
     * Gets and formats data to build line chart of user portfolio price over the last 2 years
     *
     * @Route(name="portfolio_graph_data", path="/portfolio/graph", methods={"GET"}, options={"expose"=true})
     */
    public function portfolioGraphDataAction()
    {
        $user = $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $portfolio = $user->getPortfolio();

        $stocksHistory = $this->get('rederrik_stocks.stock_provider')->getStockHistory($portfolio, 'M Y');

        $chartData = [
            'labels' => array_keys($stocksHistory),
            'datasets' => [['data' => array_values($stocksHistory)]]
        ];

        return new JsonResponse(['result' => $chartData]);
    }
}

<?php
/**
 * User: davydov
 * Date: 02.12.2015
 * Time: 10:41
 */

namespace Rederrik\StocksBundle\Service;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\PersistentCollection;
use Rederrik\StocksBundle\Entity\Stock;
use Rederrik\StocksBundle\Entity\StockHistory;
use Rederrik\StocksBundle\Model\FinanceApiClient;

/**
 * Class StockInfoProvider
 * @package Rederrik\StocksBundle\Service
 */
class StockProvider
{
    /**
     * @var EntityManager
     */
    private $em;
    private $api;
    private $ttl;

    /**
     * StockInfoProvider constructor.
     * @param EntityManager $em
     * @param string $ttl
     */
    public function __construct(EntityManager $em, $ttl = '1 hour')
    {
        $this->em = $em;
        $this->api = new FinanceApiClient();
        $this->ttl = $ttl;
    }

    /**
     * @param $query
     * @return array|void
     */
    public function search($query)
    {
        // TODO: Implement search() method.
    }

    /**
     * Gets quotes by their symbols
     *
     * @param $symbols array|string
     * @return Stock[]
     */
    public function getStock($symbols)
    {
        if (is_string($symbols)) {
            $symbols = [$symbols];
        }
        /** @var Stock[] $storedStocks */
        $storedStocks = $this->em->getRepository('RederrikStocksBundle:Stock')->findBySymbol($symbols);

        $resultStocks = $stocksToUpdate = $quotes = [];
        foreach ($symbols as $symbol) {
            if (isset($storedStocks[$symbol])) {
                $stock = $storedStocks[$symbol];
                $totalTime = $stock->getLastUpdate()->modify($this->ttl);
                if ($totalTime < new \DateTime()) {
                    $stocksToUpdate[] = $symbol;
                } else {
                    $resultStocks[] = $stock;
                }
            } else {
                $stocksToUpdate[] = $symbol;
            }
        }

        if($stocksToUpdate) {
            $quotes = $this->api->getQuotes($stocksToUpdate);
        }

        if ($quotes) {
            foreach ($quotes as $quote) {
                $quoteSymbol = strtoupper($quote['Symbol']);
                if(isset($storedStocks[$quoteSymbol])){
                    $stock = $storedStocks[$quoteSymbol];
                    $stock->setChangeInPercent($quote['ChangeinPercent'])
                        ->setLastTradePrice($quote['LastTradePriceOnly'])
                        ->setLastUpdate(new \DateTime())
                    ;
                    $resultStocks[] = $stock;
                } else {
                    $stock = new Stock();
                    $stock->setSymbol($quoteSymbol)
                        ->setCompanyName($quote['Name'])
                        ->setChangeInPercent($quote['ChangeinPercent'])
                        ->setLastTradePrice($quote['LastTradePriceOnly'])
                        ->setStockExchange($quote['StockExchange'])
                    ;
                    $this->em->persist($stock);
                    $resultStocks[] = $stock;
                }
            }
            $this->em->flush();
        }

        return $resultStocks;
    }

    /**
     * Gets historical data for quotes
     *
     * @param PersistentCollection $portfolio
     * @return \Rederrik\StocksBundle\Entity\StockHistory[]
     */
    public function getStockHistory($portfolio, $dateFormat = 'Y-m-d')
    {
        $rep = $this->em->getRepository('RederrikStocksBundle:StockHistory');
        $startDate = new \DateTime('2013-12-01');
        foreach ($portfolio as $stock) {
            $last = $rep->findLastStockHistory($stock);
            if (!$last instanceof StockHistory) {
                $stocksToUpdate[$stock->getSymbol()] = $startDate;
            } elseif ($last->getDate()->diff(new \DateTime())->days > 1) {
                $lastDate = $last->getDate();
                $stocksToUpdate[$stock->getSymbol()] = $last->getDate()->modify('1 day');
                if ($startDate > $lastDate) {
                    $startDate = $lastDate;
                }
            }
        }

        if (isset($stocksToUpdate)) {
            $counter = 0;
            $historyData = $this->api->getHistory($stocksToUpdate);
            foreach ($historyData as $symbol => $history) {
                $stock = $portfolio[$symbol];
                foreach ($history as $date => $price) {
                    $stockHistory = new StockHistory();
                    $stockHistory->setDate(new \DateTime($date))
                        ->setClosePrice($price)
                        ->setStock($stock);
                    $this->em->persist($stockHistory);
                    if(++$counter % 50 === 0) {
                        $this->em->flush();
                        $this->em->clear('RederrikStocksBundle:StockHistory');
                    }
                }
            }
            $this->em->flush();
            $this->em->clear('RederrikStocksBundle:StockHistory');
        }

        $history = [];
        foreach ($rep->getPortfolioHistory($portfolio) as $row) {
            $history[$row['date']->format($dateFormat)] = round($row['total'], 2);
        }
        return $history;
    }
}

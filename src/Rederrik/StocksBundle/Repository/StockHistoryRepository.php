<?php

namespace Rederrik\StocksBundle\Repository;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\PersistentCollection;
use Rederrik\StocksBundle\Entity\Stock;
use Rederrik\StocksBundle\Entity\StockHistory;

/**
 * StockHistoryRepository
 */
class StockHistoryRepository extends EntityRepository
{
    /**
     * Returns the last, chronologically, StockHistory object for given stock
     *
     * @param Stock $stock
     * @return StockHistory
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findLastStockHistory(Stock $stock)
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.stock = :stock')
            ->setParameter('stock',$stock)
            ->orderBy('h.date', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Return aggregated data for portfolio history
     * Prices of all stocks from the portfolio are summed for each day
     *
     * @param PersistentCollection $portfolio
     * @return array
     */
    public function getPortfolioHistory(PersistentCollection $portfolio)
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select('h.date','sum(h.closePrice) AS total')
            ->from('RederrikStocksBundle:StockHistory', 'h')
            ->where('h.stock in (:portfolio)')
            ->setParameter('portfolio', $portfolio->getValues())
            ->addGroupBy('h.date')
            ->orderBy('h.date')
            ->getQuery()->getArrayResult();
    }
}

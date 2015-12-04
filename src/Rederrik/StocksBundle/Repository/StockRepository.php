<?php

namespace Rederrik\StocksBundle\Repository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityRepository;

/**
 * StockRepository
 */
class StockRepository extends EntityRepository
{
    /**
     * Find Stock by symbols indexing by symbols
     *
     * @param string $symbol
     * @return Collection indexed by symbols
     */
    public function findBySymbol($symbol)
    {
        return $this->createQueryBuilder('s','s.symbol')
            ->where('s.symbol in (:symbol)')
            ->setParameter('symbol',$symbol)
            ->getQuery()
            ->execute();
    }
}

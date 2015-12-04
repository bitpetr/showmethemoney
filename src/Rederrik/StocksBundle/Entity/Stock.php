<?php

namespace Rederrik\StocksBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Stock
 *
 * @ORM\Table(name="stock")
 * @ORM\Entity(repositoryClass="Rederrik\StocksBundle\Repository\StockRepository")
 */
class Stock
{
    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Groups({"attributes"})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=4, unique=true)
     * @Groups({"attributes"})
     */
    private $symbol;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     * @Groups({"attributes"})
     */
    private $companyName;

    /**
     * @var string
     *
     * @ORM\Column(type="float")
     * @Groups({"attributes"})
     */
    private $lastTradePrice;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @Groups({"attributes"})
     */
    private $changeInPercent;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=3)
     * @Groups({"attributes"})
     */
    private $stockExchange;

    /**
     * @var string
     *
     * @ORM\Column(type="datetime")
     * @Groups({"attributes"})
     */
    private $lastUpdate;

    /**
     * @ORM\OneToMany(targetEntity="StockHistory", mappedBy="stock")
     */
    private $history;

    /**
     * Stock constructor.
     */
    public function __construct()
    {
        $this->lastUpdate = new \DateTime();
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set symbol
     *
     * @param string $symbol
     *
     * @return Stock
     */
    public function setSymbol($symbol)
    {
        $this->symbol = $symbol;

        return $this;
    }

    /**
     * Get symbol
     *
     * @return string
     */
    public function getSymbol()
    {
        return $this->symbol;
    }

    /**
     * Set companyName
     *
     * @param string $companyName
     *
     * @return Stock
     */
    public function setCompanyName($companyName)
    {
        $this->companyName = $companyName;

        return $this;
    }

    /**
     * Get companyName
     *
     * @return string
     */
    public function getCompanyName()
    {
        return $this->companyName;
    }

    /**
     * @return string
     */
    public function getLastTradePrice()
    {
        return $this->lastTradePrice;
    }

    /**
     * @param string $lastTradePrice
     *
     * @return Stock
     */
    public function setLastTradePrice($lastTradePrice)
    {
        $this->lastTradePrice = $lastTradePrice ?: 'none';
        return $this;
    }

    /**
     * @return string
     */
    public function getChangeInPercent()
    {
        return $this->changeInPercent;
    }

    /**
     * @param string $changeInPercent
     *
     * @return Stock
     */
    public function setChangeInPercent($changeInPercent)
    {
        $this->changeInPercent = $changeInPercent;
        return $this;
    }

    /**
     * @return string
     */
    public function getStockExchange()
    {
        return $this->stockExchange;
    }

    /**
     * @param string $stockExchange
     *
     * @return Stock
     */
    public function setStockExchange($stockExchange)
    {
        $this->stockExchange = $stockExchange;
        return $this;
    }

    public function __toString()
    {
        return $this->getSymbol();
    }

    /**
     * @return \DateTime
     */
    public function getLastUpdate()
    {
        return $this->lastUpdate;
    }

    /**
     * @param string $lastUpdate
     *
     * @return Stock
     */
    public function setLastUpdate($lastUpdate)
    {
        $this->lastUpdate = $lastUpdate;
        return $this;
    }


    /**
     * Add history
     *
     * @param StockHistory $history
     *
     * @return Stock
     */
    public function addHistory(StockHistory $history)
    {
        $this->history[] = $history;

        return $this;
    }

    /**
     * Remove history
     *
     * @param StockHistory $history
     */
    public function removeHistory(StockHistory $history)
    {
        $this->history->removeElement($history);
    }

    /**
     * Get history
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getHistory()
    {
        return $this->history;
    }
}

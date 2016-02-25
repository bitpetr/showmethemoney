<?php
/**
 * User: davydov
 * Date: 02.12.2015
 * Time: 16:30
 */

namespace AppBundle\Service;


/**
 * Class FinanceApiClient
 * Used to fetch data from Yahoo finance API
 * @package AppBundle\Model
 */
class FinanceApiClient
{

    /**
     * Additional parameters for API query
     *
     * @var array
     */
    private $params = [
        'env' => "http://datatables.org/alltables.env",
        'format' => "json"
    ];

    /**
     * Connection timeout in seconds
     * @var int
     */
    private $timeout = 10;

    /**
     * Maximum days to split queries
     * @var int
     */
    private $maxDays = 200;

    /**
     * Gets quotes data by their symbols
     *
     * @param array|string $symbols one or more symbols to look for
     * @return array [quote[]]
     * @throws \Exception on failed query
     */
    public function getQuotes($symbols)
    {
        if (is_string($symbols)) {
            $symbols = [$symbols];
        }
        $query = "select * from yahoo.finance.quotes where symbol in ('".implode("','", $symbols)."')";
        $results = $this->execQuery($query);

        return array_filter(count($symbols) > 1 ? $results['quote']:$results, function($x){return !empty($x['Name']);});

    }


    /**
     * Gets historical data for each quote by their symbols
     *
     * @param array $stocksToUpdate [symbol => start_date]
     * @return array [symbol => [date => price]]
     */
    public function getHistory(array $stocksToUpdate)
    {
        $yesterday = new \DateTime('yesterday', new \DateTimeZone('America/New_York'));

        $startDate = min($stocksToUpdate); //Gotta know the earliest date

        $data = [];
        /* We gonna be effective. There is no data on Yahoo API max results, but we assume it's around ~170,
           so we gonna split big intervals into smaller ones. For multiple quotes we gonna check their start dates and
           only query the data we really need.
        */
        do {
            $endDate = clone $startDate;
            if ($startDate->diff($yesterday)->days > $this->maxDays) {
                //Too many days - split! Btw, trades only happen on workdays so we take 200 calendar days
                $endDate->modify($this->maxDays.' days');
            } else {
                $endDate = $yesterday; //Time to finish this
            }

            $stocksToFetch = [];
            foreach ($stocksToUpdate as $symbol => $date) { //Optimizing query data
                if($date > $endDate) {
                    continue; //Too early for this one
                } elseif($date < $endDate && $date > $startDate) {
                    $stocksToFetch[$symbol] = ['start' => $date, 'end' => $endDate]; //A little bit too early
                } else {
                    $stocksToFetch[$symbol] = ['start' => $startDate, 'end' => $endDate];
                }
            }

            if(!$stocksToFetch) {
                continue; //wtf but ok
            }

            if ($startDate->diff($endDate)->days*count($stocksToFetch) > $this->maxDays) {
                foreach ($stocksToFetch as $symbol => $dates) { //Too many queries - one query for each stock
                    $res = $this->getHistoricalData([$symbol], $dates['start'], $dates['end']);
                    foreach ($res as $row) { //Format
                        $data[$row['Symbol']][$row['Date']] = $row['Close'];
                    }
                }
            } else {
                $res = $this->getMultiHistoricalData($stocksToFetch);
                foreach ($res as $row) {
                    $data[$row['Symbol']][$row['Date']] = $row['Close'];
                }
            }
            $startDate->modify($this->maxDays.' days'); //Next 200 days
        } while($endDate != $yesterday); //Until yesterday

        return $data;
    }

    /**
     * Gets history for one stock
     *
     * @param string $stockToFetch symbol
     * @param \DateTime $startDate
     * @param $endDate
     * @return array
     * @throws \Exception
     */
    public function getHistoricalData($stockToFetch, \DateTime $startDate, \DateTime $endDate)
    {
        $query = sprintf("select * from yahoo.finance.historicaldata where startDate='%s' and endDate='%s' and symbol = '%s'",
            $startDate->format("Y-m-d"), $endDate->format("Y-m-d"), $stockToFetch);
        $result = $this->execQuery($query);

        return $result['quote'];
    }

    /**
     * Gets history for multiple stocks
     *
     * @param array $stocksToFetch [symbol => [start=>date,end=>date]]
     * @return array
     * @throws \Exception
     */
    public function getMultiHistoricalData(array $stocksToFetch)
    {
        if(empty($stocksToFetch)) {
            throw new \Exception('Empty array passed as argument');
        }
        $clauseString = "(startDate='%s' and endDate='%s' and symbol = '%s')";
        $clause = [];
        foreach ($stocksToFetch as $symbol => $dates) {
            $clause[] = sprintf($clauseString,
                $dates['start']->format("Y-m-d"), $dates['end']->format("Y-m-d"), $symbol);
        }
        $query = "select * from yahoo.finance.historicaldata where ".implode(' or ',$clause);
        $result = $this->execQuery($query);

        return $result['quote'];
    }

    /**
     * Executes HTTP query using cURL
     *
     * @param $query
     * @param null $baseUrl
     * @return mixed
     * @throws \Exception
     */
    private function execQuery($query, $baseUrl = null)
    {
        if (!$baseUrl) {
            $baseUrl = 'http://query.yahooapis.com/v1/public/yql?';
        }

        $url = $baseUrl.http_build_query(array_merge($this->params, ['q' => $query]));

        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
        ]);

        $response = curl_exec($ch);
        $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($httpStatus !== 200) {
            throw new \Exception('HTTP call failed with error '.curl_error($ch).'.');
        } elseif ($response === false) {
            throw new \Exception('HTTP call failed empty response.');
        }

        $decoded = json_decode($response, true);
        if (!isset($decoded['query']['results'])) {
            throw new \Exception('Yahoo Finance API did not return a result.');
        }
        return $decoded['query']['results'];

    }

}

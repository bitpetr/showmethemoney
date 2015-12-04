<?php
/**
 * User: davydov
 * Date: 02.12.2015
 * Time: 16:30
 */

namespace Rederrik\StocksBundle\Model;


class FinanceApiClient
{

    private $params = [
        'env' => "http://datatables.org/alltables.env",
        'format' => "json"
    ];
    private $timeout = 10;

    public function getQuotes($symbols)
    {
        if (is_string($symbols)) {
            $symbols = [$symbols];
        }
        $query = "select * from yahoo.finance.quotes where symbol in ('".implode("','", $symbols)."')";
        $results = $this->execQuery($query);

        return array_filter(count($symbols) > 1 ? $results['quote']:$results, function($x){return !empty($x['Name']);});

    }


    public function getHistory(array $stocksToUpdate)
    {
        $yesterday = new \DateTime('yesterday');

        $startDate = min($stocksToUpdate);

        $data = [];
        do {
            $enddate = clone $startDate;
            if (($days = $startDate->diff($yesterday)->days) > 200) {
                $enddate->modify('200 days');
            } else {
                $enddate = $yesterday;
            }

            if ($days*count($stocksToUpdate) > 200) {
                foreach ($stocksToUpdate as $symbol => $date) {
                    if($date > $enddate) {
                        continue;
                    } elseif($date < $enddate && $date > $startDate) {
                        $res = $this->getHistoricalData([$symbol], $date, $enddate);
                    } else {
                        $res = $this->getHistoricalData([$symbol], $startDate, $enddate);
                    }
                    foreach ($res as $row) {
                        $data[$row['Symbol']][$row['Date']] = $row['Close'];
                    }
                }
            } else {
                $res = $this->getHistoricalData(array_keys($stocksToUpdate), $startDate, $enddate);
                foreach ($res as $row) {
                    $data[$row['Symbol']][$row['Date']] = $row['Close'];
                }
            }
            $startDate->modify('200 days');
        } while($enddate != $yesterday);

        return $data;
    }

    /**
     * @param $stocksToUpdate
     * @param \DateTime $startDate
     * @param $endDate
     * @return mixed
     * @throws \Exception
     */
    public function getHistoricalData(array $stocksToUpdate, \DateTime $startDate, \DateTime $endDate)
    {
        $query = sprintf("select * from yahoo.finance.historicaldata where startDate='%s' and endDate='%s' and symbol in ('%s')",
            $startDate->format("Y-m-d"), $endDate->format("Y-m-d"), implode("','", $stocksToUpdate));
        $result = $this->execQuery($query);

        return $result['quote'];
    }

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
            throw new \Exception("HTTP call failed with error ".curl_error($ch).".");
        } elseif ($response === false) {
            throw new \Exception("HTTP call failed empty response.");
        }

        $decoded = json_decode($response, true);
        if (!isset($decoded['query']['results'])) {
            dump($decoded);
            throw new \Exception("Yahoo Finance API did not return a result.");
        }
        return $decoded['query']['results'];

    }

}
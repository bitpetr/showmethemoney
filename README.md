SHOWMETHEMONEY
=========

## Description
Small web application I made to test my skills of Symfony framework.
In this application registered users can create a portfolio of stocks. Stock data is tracked using
Yahoo Finance API. To avoid too many queries stock data is cached in local database and only updated when needed.
User can also see how his portfolio would perform over the last two years. Historical data is fetched through API and also cached in local database.
Front-end is built using JQuery and Semantic UI and relies on AJAX to communicate with back-end.

It took me about 4 hours to make the user interface part, ~3 hours for portfolio CRUD, 
~5,5 hours for historical data. 

## Installation
```bash
git clone https://github.com/rederrik/showmethemoney.git .
composer install
php app/console doctrine:schema:create
php app/console assetic:dump -e prod
php app/console server:run
```


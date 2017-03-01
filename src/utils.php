<?php


    function currencyConverter($currency_from,$currency_to,$currency_input){
        $yql_base_url = "http://query.yahooapis.com/v1/public/yql";
        $yql_query = 'select * from yahoo.finance.xchange where pair in ("'.$currency_from.$currency_to.'")';
        $yql_query_url = $yql_base_url . "?q=" . urlencode($yql_query);
        $yql_query_url .= "&format=json&env=store%3A%2F%2Fdatatables.org%2Falltableswithkeys";
        $yql_session = file_get_contents($yql_query_url);
        $yql_json =  json_decode($yql_session,true);
        $currency_output = (float) $currency_input*$yql_json['query']['results']['rate']['Rate'];

        return $currency_output;
    }



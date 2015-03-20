<?php

    use \rmartignoni\ElasticSearch;

    require(__DIR__ . '/../vendor/autoload.php');

    $hosts = ['10.0.0.10:9200'];
    $index = 'products';
    $type  = 'product';

    $elasticSearch = new ElasticSearch\ElasticSearch($hosts, $index, $type);

    /*
     * SELECT * FROM products WHERE product_name LIKE '%noodles'
     */
    $query = new ElasticSearch\Query();
    $query->wildcard('product_name', '*noodles');

    $elasticSearch->setQuery($query)->get();
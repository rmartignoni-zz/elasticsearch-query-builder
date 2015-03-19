<?php

    use \rmartignoni\ElasticSearch;

    require(__DIR__ . '/../vendor/autoload.php');

    $hosts = ['10.0.0.10:9200'];

    $elasticSearch = new ElasticSearch\ElasticSearch($hosts, 'products', 'product');

    /*
     * SELECT * FROM products WHERE product_name = 'ElasticSearch' LIMIT 4
     */
    $query = new ElasticSearch\Query();
    $query->where('product_name', 'ElasticSearch');

    $resultSet = $elasticSearch->setQuery($query)->take(4)->get();

    // You could also page the results as follows
    $resultSet = $elasticSearch->page(1)->get();
    $resultSet = $elasticSearch->page(2)->get();
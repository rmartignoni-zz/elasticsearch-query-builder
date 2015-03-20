<?php

    use \rmartignoni\ElasticSearch;

    require(__DIR__ . '/../vendor/autoload.php');

    $hosts = ['10.0.0.10:9200'];
    $index = 'products';
    $type  = 'product';

    $elasticSearch = new ElasticSearch\ElasticSearch($hosts, $index, $type);

    /*
     * SELECT id, product_name, price, updated_at FROM products WHERE product_name LIKE 'car%' AND category = 3 LIMIT 20 OFFSET 0
     */
    $query = new ElasticSearch\Query();
    $query->wildcard('product_name', 'car*');

    $filter = new ElasticSearch\Filter();
    $filter->where('category', 3);

    // You should always use the take method before paging
    $elasticSearch->select('id, product_name, price, updated_at')
                  ->setQuery($query)
                  ->setFilter($filter)
                  ->take(20)
                  ->page(0)
                  ->get();
<?php

    namespace rmartignoni\ElasticSearch;

    use Elasticsearch\Client;

    class ElasticSearch
    {
        /**
         * @var
         */
        private $es;

        /**
         * @var
         */
        private $index;

        /**
         * @var
         */
        private $type;

        /**
         * @var array
         */
        private $body = [];

        /**
         * @var null
         */
        private $query = null;

        /**
         * @var null
         */
        private $postQuery = null;

        /**
         * @var null
         */
        private $queriedFields = null;

        /**
         * @var null
         */
        private $filter = null;

        /**
         * @var null
         */
        private $postFilter = null;

        /**
         * @var array
         */
        private $sort = null;

        /**
         * @var null
         */
        private $groupBy = null;

        function __construct($hosts, $index, $type)
        {
            $this->index = $index;
            $this->type  = $type;

            $this->es = new Client(['hosts' => $hosts]);
        }

        /**
         * @param      $index
         * @param null $document
         *
         * @return $this
         */
        public function changeIndex($index, $document = null)
        {
            $this->index = $index;

            if (!is_null($document)) {
                $this->type = $document;
            }

            return $this;
        }

        /**
         * @param      $document
         * @param null $index
         *
         * @return $this
         */
        public function changeDocument($document, $index = null)
        {
            $this->type = $document;

            if (!is_null($index)) {
                $this->index = $index;
            }

            return $this;
        }

        /**
         * buildRequestBody()
         *
         * @return mixed
         */
        private function buildRequestBody()
        {
            $params['index'] = $this->index;
            $params['type']  = $this->type;
            $params['body']  = $this->body;

            $this->buildQuery($params);

            if (!is_null($this->filter)) {
                $params['body']['query']['filtered']['filter'] = $this->filter;
            }

            if (!is_null($this->postFilter)) {
                $params['body']['post_filter'] = (count($this->postFilter) == 1 ? array_shift($this->postFilter) : $this->postFilter);
            }

            if (!is_null($this->sort)) {
                $params['body']['sort'] = $this->buildSort();
            }

            if (!is_null($this->groupBy)) {
                $params['body']['aggs'] = $this->groupBy;
            }

            // Build highlight object
            if (!is_null($this->queriedFields))
            {
                $params['body']['highlight']['pre_tags']  = '<span>';
                $params['body']['highlight']['post_tags'] = '</span>';

                foreach ($this->queriedFields as $key => $field)
                {
                    $params['body']['highlight']['fields'][$key] = new \stdClass();
                }
            }

            return $params;
        }

        /**
         * buildSort()
         *
         * @return array|mixed
         */
        private function buildSort()
        {
            if (count($this->sort) == 1 && !isset($this->sort[0]['proximity'])) {
                return array_shift($this->sort);
            }

            $sort = [];

            while ($condition = array_shift($this->sort)) {
                $column = key($condition);

                if ($column === 'proximity') {
                    if (is_null($condition[$column])) {
                        continue;
                    }

                    $sort[]['_geo_distance'] = [
                        'lat_lon'       => $condition[$column],
                        'order'         => 'asc',
                        'unit'          => 'km',
                        'mode'          => 'min',
                        'distance_type' => 'sloppy_arc',
                    ];

                    continue;
                }

                $sort[][$column] = $condition[$column];
            }

            return $sort;
        }

        /**
         * buildQuery(&$params)
         *
         * @param $params
         *
         * @return null|\stdClass
         */
        private function buildQuery(&$params)
        {
            if (!is_null($this->filter)) {
                if (empty($this->query)) {
                    return $params['body']['query']['filtered']['query']['match_all'] = new \stdClass;
                }

                return $params['body']['query']['filtered']['query'] = $this->query;
            }

            if (is_null($this->query)) {
                return $params['body']['query']['match_all'] = new \stdClass;
            }

            return $params['body']['query'] = $this->query;
        }

        /**
         * get()
         *
         * @return null|\stdClass
         */
        public function get()
        {
            $body = $this->buildRequestBody();

            try {
                $results = $this->es->search($body);
            } catch (\Exception $e) {
                // TODO - Use monolog to log errors and exceptions
                // log_message('ERROR', 'Erro no ElasticSearch: ' . $e->getMessage());
                // log_message('ERROR', 'JSON enviado para o ElasticSearch: ' . json_encode($body));

                $results = null;
            }

            if (empty($results)) {
                return null;
            }

            // log_message('DEBUG', 'JSON enviado para o ElasticSearch: ' . json_encode($body));
            // log_message('DEBUG', 'Resultado do ElasticSearch: ' . json_encode($results) . "\n\n");

            return $this->processResults($results);
        }

        /**
         * processResults($results)
         *
         * @param $results
         *
         * @return \stdClass
         */
        private function processResults($results)
        {
            // TODO - create response class to set custom header
            // \Response::setCustomHeader('Total', $total);
            // \Response::setCustomHeader('Score', $results['hits']['max_score']);

            return $this->processHits($results['hits']['hits']);
        }

        /**
         * processHits($hits)
         *
         * @param $hits
         *
         * @return array
         */
        private function processHits($hits)
        {
            $count   = count($hits);
            $results = [];

            for ($i = 0; $i < $count; $i++) {
                $results[] = $this->processHit($hits[$i]);
            }

            return $results;
        }

        /**
         * @param $hit
         *
         * @return \stdClass
         */
        private function processHit($hit)
        {
            $result        = new \stdClass;
            $result->id    = $hit['_id'];
            $result->score = $hit['_score'];

            // Quando o usuário especifica os campos na consulta os dados são retornados na propriedade fields
            if (isset($hit['fields'])) {
                foreach ($hit['fields'] as $key => $value) {
                    $result->{$key} = is_array($value) ? array_shift($value) : $value;
                }
            }
            // Caso não encontre os dados na propriedade fields, pega da _source
            else if (isset($hit['_source'])) {
                foreach ($hit['_source'] as $key => $value) {
                    $result->{$key} = is_array($value) ? array_shift($value) : $value;
                }
            }


            if (isset($hit['sort'])) {
                $result->sort = $hit['sort'];
            }

            // Caso o usuário tenha solicitado o highlight dos campos buscados, monta o objeto com estes dados
            if (isset($hit['highlight'])) {
                $result->highlight = new \stdClass;

                foreach ($hit['highlight'] as $key => $value) {
                    $result->highlight->{$key} = is_array($value) ? $value[0] : $value;
                }
            }

            return $result;
        }

        /**
         * page($page)
         *
         * @param $page
         *
         * @return $this
         */
        public function page($page)
        {
            $from = 0;

            if (isset($this->body['size'])) {
                $from = $page * $this->body['size'];
            }

            $this->body['from'] = $from;

            return $this;
        }

        /**
         * take($records)
         *
         * @param $records
         *
         * @return $this
         */
        public function take($records)
        {
            $this->body['size'] = $records;

            return $this;
        }

        /**
         * score($score, $type = 'gt')
         *
         * @param        $score
         * @param string $type
         *
         * @return $this
         */
        public function score($score, $type = 'gt')
        {
            if ($type == 'gt') {
                $this->body['min_score'] = $score;
            }

            if ($type == 'lt') {
                $this->body['max_score'] = $score;
            }

            return $this;
        }

        /**
         * select($fields)
         *
         * @param $fields
         *
         * @return $this
         */
        public function select($fields)
        {
            if (!is_array($fields)) {
                $fields = $this->prepareFields($fields);
            }

            if (isset($this->body['fields'])) {
                $this->body['fields'] = array_merge($this->body['fields'], $fields);
            } else {
                $this->body['fields'] = $fields;
            }

            return $this;
        }

        /**
         * prepareFields($fields)
         *
         * @param $fields
         *
         * @return array
         */
        private function prepareFields($fields)
        {
            $fieldsArray = [];

            if (strpos($fields, '*') !== false) {
                return $fields;
            }

            if (strpos($fields, ',') !== false) {
                $fieldsArray = explode(',', $fields);
            }

            if (strpos($fields, ';') !== false) {
                $fieldsArray = explode(';', $fields);
            }

            foreach ($fieldsArray as $key => $value) {
                $fieldsArray[$key] = trim($value);
            }

            return $fieldsArray;
        }

        /**
         * @param        $column
         * @param string $order
         *
         * @return $this
         */
        public function orderBy($column, $order = 'asc')
        {
            if (is_array($column)) {
                $key                = key($column);
                $this->sort[][$key] = $column[$key];

                return $this;
            }

            $this->sort[][$column] = $order;

            return $this;
        }

        /**
         * @param $column
         *
         * @return $this
         */
        public function groupBy($column)
        {
            $this->groupBy["{$column}s"]['terms']['field'] = $column;

            return $this;
        }

        /**
         * @param Query $query
         *
         * @return $this
         */
        public function setQuery(Query $query)
        {
            $this->query         = $query->getQuery();
            $this->queriedFields = $query->getFields();

            return $this;
        }

        /**
         * @param Query $query
         *
         * @return $this
         */
        public function setPostQuery(Query $query)
        {
            $this->postQuery = $query->getQuery();

            return $this;
        }

        /**
         * @param Filter $filter
         *
         * @return $this
         */
        public function setFilter(Filter $filter)
        {
            $this->filter = $filter->getFilters();

            return $this;
        }

        /**
         * @param Filter $filter
         *
         * @return $this
         */
        public function setPostFilter(Filter $filter)
        {
            $this->postFilter = $filter->getFilters();

            return $this;
        }

        public function cleanRequest()
        {
            $this->cleanOrder()
                 ->cleanFilters()
                 ->cleanQuery()
                 ->cleanGroup();

            return $this;
        }

        /**
         * @return $this
         */
        public function cleanOrder()
        {
            $this->sort = null;

            return $this;
        }

        /**
         * @return $this
         */
        public function cleanFilters()
        {
            $this->filter     = null;
            $this->postFilter = null;

            return $this;
        }

        /**
         * @return $this
         */
        public function cleanQuery()
        {
            $this->query     = null;
            $this->postQuery = null;

            return $this;
        }

        /**
         * @return $this
         */
        public function cleanGroup()
        {
            $this->groupBy = null;

            return $this;
        }
    }

<?php

    namespace eSapiens\Libraries\ElasticSearch;

    class Query extends Search
    {
        /**
         * @return array
         */
        public function getQuery()
        {
            if ((count($this->must) + count($this->should)) === 1)
            {
                return array_merge($this->must, $this->should);
            }

            if (!empty($this->must))
            {
                $this->query['bool']['must'] = $this->must;
            }

            if (!empty($this->should))
            {
                $this->query['bool']['should'] = $this->should;
                $this->query['bool']['minimum_should_match'] = empty($this->must) ? 1 : 0;
            }

            return $this->query;
        }

        /**
         * @return array|null
         */
        public function getFields()
        {
            if(!empty($this->fields))
            {
                return $this->fields;
            }

            return null;
        }

        /**
         * @param        $column
         * @param        $value
         *
         * @return $this
         */
        public function where($column, $value)
        {
            return $this->_where($column, $value, false, 'must');
        }

        /**
         * @param $column
         * @param $value
         *
         * @return Filter
         */
        public function orWhere($column, $value)
        {
            return $this->_where($column, $value, false, 'should');
        }

        /**
         * @param        $column
         * @param        $value
         * @param int    $match
         *
         * @return $this
         */
        public function whereIn($column, $value, $match = 1)
        {
            return $this->_whereIn($column, $value, $match, 'must');
        }

        /**
         * @param     $column
         * @param     $value
         * @param int $match
         *
         * @return Filter
         */
        public function orWhereIn($column, $value, $match = 1)
        {
            return $this->_whereIn($column, $value, $match, 'should');
        }

        /**
         * @param        $column
         * @param        $value
         *
         * @return $this
         */
        public function wildcard($column, $value)
        {
            return $this->_wildcard($column, $value, 'must');
        }

        /**
         * @param $column
         * @param $value
         *
         * @return Filter
         */
        public function orWildcard($column, $value)
        {
            return $this->_wildcard($column, $value, 'should');
        }

        /**
         * @param        $column
         * @param        $terms
         *
         * @return $this
         */
        public function match($column, $terms)
        {
            return $this->_match($column, $terms, 'must');
        }

        /**
         * @param $column
         * @param $terms
         *
         * @return Filter
         */
        public function orMatch($column, $terms)
        {
            return $this->_match($column, $terms, 'should');
        }

        /**
         * @param        $column
         * @param        $phrase
         * @param int    $slop
         *
         * @return $this
         */
        public function matchPhrase($column, $phrase, $slop = 0)
        {
            return $this->_matchPhrase($column, $phrase, $slop, 'must');
        }

        /**
         * @param     $column
         * @param     $phrase
         * @param int $slop
         *
         * @return Filter
         */
        public function orMatchPhrase($column, $phrase, $slop = 0)
        {
            return $this->_matchPhrase($column, $phrase, $slop, 'should');
        }

        /**
         * @param        $column
         * @param        $phrase
         * @param int    $maxExpansions
         *
         * @return $this
         */
        public function matchPhrasePrefix($column, $phrase, $maxExpansions = 5)
        {
            return $this->_matchPhrasePrefix($column, $phrase, $maxExpansions, 'must');
        }

        /**
         * @param     $column
         * @param     $phrase
         * @param int $maxExpansions
         *
         * @return Filter
         */
        public function orMatchPhrasePrefix($column, $phrase, $maxExpansions = 5)
        {
            return $this->_matchPhrasePrefix($column, $phrase, $maxExpansions, 'should');
        }

        /**
         * @param        $columns
         * @param        $phrase
         *
         * @return $this
         */
        public function multiMatch($columns, $phrase)
        {
            return $this->_multiMatch($columns, $phrase, 'must');
        }

        /**
         * @param $columns
         * @param $phrase
         *
         * @return Filter
         */
        public function orMultiMatch($columns, $phrase)
        {
            return $this->_multiMatch($columns, $phrase, 'should');
        }

        /**
         * @param        $column
         * @param        $value
         *
         * @return $this
         */
        public function startsWith($column, $value)
        {
            return $this->_startsWith($column, $value, 'must');
        }

        /**
         * @param $column
         * @param $value
         *
         * @return Filter
         */
        public function orStarsWith($column, $value)
        {
            return $this->_startsWith($column, $value, 'should');
        }

        /**
         * @param      $column
         * @param      $min
         * @param      $max
         * @param bool $nested
         *
         * @return $this
         */
        public function between($column, $min, $max, $nested = false)
        {
            return $this->_between($column, $min, $max, $nested, 'must');
        }

        /**
         * @param      $column
         * @param      $min
         * @param      $max
         * @param bool $nested
         *
         * @return $this
         */
        public function orBetween($column, $min, $max, $nested = false)
        {
            return $this->_between($column, $min, $max, $nested, 'should');
        }

        /**
         * @param        $column
         * @param        $value
         *
         * @return $this
         */
        public function gt($column, $value)
        {
            return $this->_gt($column, $value, 'must');
        }

        /**
         * @param $column
         * @param $value
         *
         * @return Query
         */
        public function orGt($column, $value)
        {
            return $this->_gt($column, $value, 'should');
        }

        /**
         * @param        $column
         * @param        $value
         *
         * @return $this
         */
        public function lt($column, $value)
        {
            return $this->_lt($column, $value, 'must');
        }

        /**
         * @param $column
         * @param $value
         *
         * @return Query
         */
        public function orLt($column, $value)
        {
            return $this->_lt($column, $value, 'should');
        }

        /**
         * @param        $column
         * @param        $pattern
         *
         * @return $this
         */
        public function regex($column, $pattern)
        {
            return $this->_regex($column, $pattern, 'must');
        }

        /**
         * @param $column
         * @param $pattern
         *
         * @return Filter
         */
        public function orRegex($column, $pattern)
        {
            return $this->_regex($column, $pattern, 'should');
        }

        /**
         * @param        $column
         * @param        $value
         *
         * @return $this
         */
        public function fuzzy($column, $value)
        {
            return $this->_fuzzy($column, $value, 'must');
        }

        /**
         * @param $column
         * @param $value
         *
         * @return Filter
         */
        public function orFuzzy($column, $value)
        {
            return $this->_fuzzy($column, $value, 'should');
        }

        /**
         * @param        $column
         * @param        $value
         * @param null   $params
         *
         * @return $this
         */
        public function fuzzyLike($column, $value, $params = null)
        {
            return $this->_fuzzyLike($column, $value, $params, 'must');
        }


        /**
         * @param      $column
         * @param      $value
         * @param null $params
         *
         * @return $this
         */
        public function orFuzzyLike($column, $value, $params = null)
        {
            return $this->_fuzzyLike($column, $value, $params, 'should');
        }
    }
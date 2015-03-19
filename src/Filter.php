<?php

    namespace rmartignoni\ElasticSearch;

    class Filter extends Search
    {
        /**
         * @var array
         */
        private $filters = [];

        public function cleanFilters()
        {
            $this->filters = [];
            $this->must    = [];
            $this->should  = [];
            $this->nested  = [];
            $this->not     = [];
        }

        /**
         * @return bool
         */
        public function hasFilters()
        {
            if (empty($this->must) && empty($this->should) && empty($this->not) && empty($this->nested))
            {
                return false;
            }

            return true;
        }

        /**
         * getFilters()
         *
         * @return array
         */
        public function getFilters()
        {
            if ((count($this->must) + count($this->should) + count($this->nested)) == 1 && empty($this->not))
            {
                return array_merge($this->must, $this->should, $this->buildNested());
            }

            if (!empty($this->must))
            {
                $this->filters['bool']['must'] = (count($this->must) == 1 ? array_shift($this->must) : $this->must);
            }

            if (!empty($this->should))
            {
                $this->filters['bool']['should'] = (count($this->should) == 1 ? array_shift($this->should) : $this->should);
            }

            if (!empty($this->not))
            {
                $this->filters['bool']['must_not'] = (count($this->not) == 1 ? array_shift($this->not) : $this->not);
            }

            if(!empty($this->nested))
            {
                $this->filters['bool']['must'] = isset($this->filters['bool']['must']) ? array_merge($this->filters['bool']['must'], $this->buildNested()) : $this->buildNested();
            }

            return $this->filters;
        }

        /**
         * @param      $column
         * @param      $value
         * @param bool $nested
         *
         * @return $this
         */
        public function where($column, $value, $nested = false)
        {
            return $this->_where($column, $value, $nested, 'must');
        }

        /**
         * @param      $column
         * @param      $value
         * @param bool $nested
         *
         * @return $this
         */
        public function orWhere($column, $value, $nested = false)
        {
            return $this->_where($column, $value, $nested, 'should');
        }

        public function notWhere($column, $value, $nested = false)
        {
            return $this->_where($column, $value, $nested, 'not');
        }

        /**
         * @param     $column
         * @param     $value
         * @param int $match
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
         * @return $this
         */
        public function orWhereIn($column, $value, $match = 1)
        {
            return $this->_whereIn($column, $value, $match, 'should');
        }

        /**
         * @param     $column
         * @param     $value
         * @param int $match
         *
         * @return $this
         */
        public function notWhereIn($column, $value, $match = 1)
        {
            return $this->_whereIn($column, $value, $match, 'not');
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
        public function matchAny($column, $terms)
        {
            return $this->_match($column, $terms, 'must');
        }

        /**
         * @param $column
         * @param $terms
         *
         * @return Filter
         */
        public function orMatchAny($column, $terms)
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
         * @param $column
         * @param $min
         * @param $max
         *
         * @return Filter
         */
        public function orBetween($column, $min, $max, $nested = false)
        {
            return $this->_between($column, $min, $max, $nested, 'should');
        }

        /**
         * @param        $column
         * @param string $operand
         *
         * @return $this
         */
        public function exists($column, $operand = 'must')
        {
            $this->{$operand}[]['exists']['field'] = $column;

            return $this;
        }

        /**
         * @param $column
         *
         * @return Filter
         */
        public function notExists($column)
        {
            return $this->exists($column, 'should');
        }

        /**
         * @param     $latitude
         * @param     $longitude
         * @param int $distance
         *
         * @return $this
         */
        public function location($latitude, $longitude, $distance = 100)
        {
            $pos = count($this->must);

            $this->must[$pos]['geo_distance']['distance'] = "{$distance}km";
            $this->must[$pos]['geo_distance']['lat_lon']  = "{$latitude},{$longitude}";

            return $this;
        }
    }
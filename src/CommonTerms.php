<?php

    namespace rmartignoni\ElasticSearch;

    class CommonTerms
    {
        /**
         * @param \Predis\Client $redis
         * @param        $gender
         * @param        $terms
         */
        public static function storeTerms(\Predis\Client $redis, $gender, $terms)
        {
            $terms = str_replace([',', ';', '.'], ' ', $terms);
            $terms = explode(' ', $terms);

            foreach ($terms as $term)
            {
                if (empty($term))
                {
                    continue;
                }

                self::storeTerm($redis, $gender, $term);
            }
        }

        /**
         * @param \Predis\Client $redis
         * @param        $gender
         * @param        $term
         */
        private static function storeTerm(\Predis\Client $redis, $gender, $term)
        {
            $term = strtolower(trim($term));

            try
            {
                $redis->zincrby("terms|{$gender}", 1, $term);
            }
            catch (\Exception $e)
            {
                log_message('ERROR', 'Erro ao salvar termo buscado no redis: ' . $e->getMessage());
            }
        }

        /**
         * @param \Predis\Client $redis
         * @param        $gender
         * @param int    $limit
         *
         * @return array
         */
        public static function getCommonTerms(\Predis\Client $redis, $gender, $limit = 8)
        {
            $sort = $redis->zrevrange("terms|{$gender}", 0, $limit);

            return $sort;
        }
    }
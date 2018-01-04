<?php

namespace leinonen\Yii2Algolia\ActiveRecord;

use yii\db\ActiveQueryInterface;

class ActiveQueryChunker
{
    /**
     * Chunk the results of an  ActiveQuery.
     *
     * @param ActiveQueryInterface $query
     * @param int $size The size of chunk retrieved from the query.
     * @param callable $callback
     *
     * @return array
     */
    public function chunk(ActiveQueryInterface $query, $size, callable $callback)
    {
        $pageNumber = 1;
        $records = $this->paginateRecords($query, $pageNumber, $size)->all();
        $results = [];

        while (count($records) > 0) {
            // On each chunk, pass the records to the callback and then let the
            // developer take care of everything within the callback. This allows to
            // keep the memory low when looping through large result sets.
            $callableResults = $callback($records);

            if ($callableResults === false) {
                break;
            }

            // If the results of the given callable function were an array
            // merge them into the result array which is returned at the end of the chunking.
            if (\is_array($callableResults)) {
                $results = \array_merge($results, $callableResults);
            }

            $pageNumber++;
            $records = $this->paginateRecords($query, $pageNumber, $size)->all();
        }

        return $results;
    }

    /**
     * Paginate the results of the query.
     *
     * @param ActiveQueryInterface $query
     * @param int $pageNumber
     * @param int $count
     *
     * @return ActiveQueryInterface
     */
    private function paginateRecords(ActiveQueryInterface $query, $pageNumber, $count)
    {
        $offset = ($pageNumber - 1) * $count;
        $limit = $count;

        return $query->offset($offset)->limit($limit);
    }
}

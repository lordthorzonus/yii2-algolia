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
     * @return bool
     */
    public function chunk(ActiveQueryInterface $query, $size, callable $callback)
    {
        $pageNumber = 1;
        $results = $this->paginateResults($query, $pageNumber, $size)->all();

        while(count($results) > 0)
        {
            // On each chunk, pass the results to the callback and then let the
            // developer take care of everything within the callback. This allows to
            // keep the memory low when looping through large result sets.
            if (call_user_func($callback, $results) === false) {
                return false;
            }

            $pageNumber++;

            $results = $this->paginateResults($query, $pageNumber, $size)->all();
        }

        return true;
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
    private function paginateResults(ActiveQueryInterface $query, $pageNumber, $count)
    {
        $offset = ($pageNumber - 1) *  $count;
        $limit = $count;

        return $query->offset($offset)->limit($limit);
    }
}
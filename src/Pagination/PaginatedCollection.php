<?php

namespace App\Pagination;

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\Expose;

/**
 * @ExclusionPolicy("all")
 */
class PaginatedCollection
{
    /**
     * @Expose
     * @Groups({"autor", "publico", "select"})
     */
    private $results;
    /**
     * @Expose
     * @Groups({"autor", "publico", "select"})
     */
    private $total;
    /**
     * @Expose
     * @Groups({"autor", "publico", "select"})
     */
    private $count;

    /**
     * @Expose
     * @Groups({"autor", "publico", "select"})
     */
    private $_links = [];

    public function __construct(array $results, $totalItems)
    {
        $this->results = $results;
        $this->total = $totalItems;
        $this->count = count($results);
    }

    public function addLink($ref, $url)
    {
        $this->_links[$ref] = $url;
    }
}

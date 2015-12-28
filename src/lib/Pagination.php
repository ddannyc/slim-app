<?php
/**
 * Created by wayne.
 * Date: 2015/12/18
 * Time: 12:58
 */

namespace App\lib;


class Pagination
{
    private $perNums;
    private $totalPage;
    private $currentPage;
    private $pageDisplayNums;

    public function __construct($perNums, $pageDisplayNums)
    {
        $this->perNums = $perNums;
        $this->pageDisplayNums = $pageDisplayNums;
        $this->totalPage = 0;
        $this->currentPage = 1;
    }

    public function setPerPage($perPage)
    {
        $this->perNums = $perPage;
    }

    public function show($totalNums, $currentPage)
    {
        $this->currentPage = $currentPage > 0 ? $currentPage: 1;
        $this->totalPage = ceil(floatval($totalNums / $this->perNums));

        $result = [];
        $halfRangeSize = floor($this->pageDisplayNums / 2);
        $rangeStart = $currentPage - $halfRangeSize;
        $rangeEnd = $currentPage + $halfRangeSize;

        if ($rangeEnd < $this->pageDisplayNums) {
            $rangeEnd = $this->pageDisplayNums;
        }
        if ($rangeEnd - $rangeStart < $this->pageDisplayNums) {
            $rangeStart = $rangeEnd - $this->pageDisplayNums;
        }
        if ($rangeStart < 1) {
            $rangeStart = 1;
        }

        for ($i = $rangeStart;
             $i <= $rangeEnd && $i <= $this->totalPage;
             $i++) {

            $result[] = $i;
        }

        // first
        if ($rangeStart - 1 >= 1) {
            array_unshift($result, 1);
        }

        // latest
        if ($rangeEnd + 1 <= $this->totalPage) {
            $result[] = $this->totalPage;
        }

        return array_unique($result);
    }
}
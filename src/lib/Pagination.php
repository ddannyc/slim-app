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
    private $pageDisplayNums;

    public function __construct($perNums, $pageDisplayNums)
    {
        $this->perNums = $perNums;
        $this->pageDisplayNums = $pageDisplayNums;
    }

    public function setPerPage($perNums)
    {
        $this->perNums = $perNums;
    }

    public function show($totalNums, $currentPage)
    {
        $currentPage = $currentPage > 0 ? $currentPage : 1;
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

        $totalPage = ceil(floatval($totalNums / $this->perNums));
        for ($i = $rangeStart;
             $i <= $rangeEnd && $i <= $totalPage;
             $i++) {

            $result[] = $i;
        }

        // first
        if ($rangeStart - 1 >= 1) {
            array_unshift($result, 1);
        }

        // latest
        if ($rangeEnd + 1 <= $totalPage) {
            $result[] = $totalPage;
        }

        return array_unique($result);
    }
}
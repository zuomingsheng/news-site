<?php
namespace app\admin\paginator;

use think\Paginator;

class Bootstrap extends Paginator
{
    protected function getPreviousButton($text = '上一页')
    {
        if ($this->currentPage() <= 1) {
            return '<button class="page-btn page-btn-disabled" disabled>' . $text . '</button>';
        }
        $url = $this->url($this->currentPage() - 1);
        return '<a class="page-btn" href="' . htmlentities($url) . '">' . $text . '</a>';
    }

    protected function getNextButton($text = '下一页')
    {
        if (!$this->hasMore) {
            return '<button class="page-btn page-btn-disabled" disabled>' . $text . '</button>';
        }
        $url = $this->url($this->currentPage() + 1);
        return '<a class="page-btn" href="' . htmlentities($url) . '">' . $text . '</a>';
    }

    protected function getLinks()
    {
        if ($this->simple) return '';

        $html = '';
        $side = 2;
        $window = $side * 2;

        if ($this->lastPage < $window + 4) {
            $range = range(1, $this->lastPage);
        } elseif ($this->currentPage() <= $window) {
            $range = array_merge(range(1, $window + 2), ['...'], range($this->lastPage - 1, $this->lastPage));
        } elseif ($this->currentPage() > ($this->lastPage - $window)) {
            $range = array_merge([1], ['...'], range($this->lastPage - $window - 1, $this->lastPage));
        } else {
            $range = array_merge(
                [1],
                ['...'],
                range($this->currentPage() - $side, $this->currentPage() + $side),
                ['...'],
                [$this->lastPage]
            );
        }

        foreach ($range as $page) {
            if ($page === '...') {
                $html .= '<span class="page-ellipsis">...</span>';
            } elseif ($page == $this->currentPage()) {
                $html .= '<button class="page-num page-num-active" disabled>' . $page . '</button>';
            } else {
                $url = $this->url($page);
                $html .= '<a class="page-num" href="' . htmlentities($url) . '">' . $page . '</a>';
            }
        }

        return $html;
    }

    public function render()
    {
        if ($this->hasPages()) {
            $total = $this->total();
            $html = '<div class="admin-pagination">';
            $html .= '<span class="page-total">共 ' . $total . ' 条</span>';
            $html .= '<div class="page-controls">';
            $html .= $this->getPreviousButton();
            $html .= $this->getLinks();
            $html .= $this->getNextButton();
            $html .= '</div></div>';
            return $html;
        }
        return '';
    }
}

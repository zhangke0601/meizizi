<?php
class PageUtil
{/*{{{*/
    public static function pagination($num, $perpage, $curpage, $mpurl, $maxpages = 0 , $hrefPot = "")
    {/*{{{*/ 
        $multipage = '';
        $mpurl .= strpos($mpurl, '?') ? '&amp;' : '?';
        if($num > $perpage) 
        {
            //max display page num on the html: [1][2]...[10]
            $page = 10;
            $offset = 5;

            $realpages = ceil($num / $perpage);
            $pages = $maxpages && $maxpages < $realpages ? $maxpages : $realpages;

            if($page > $pages) 
            {
                $from = 1;
                $to = $pages;
            } 
            else 
            {
                $from = $curpage - $offset;
                $to = $from + $page - 1;
                if($from < 1) 
                {
                    $to = $curpage + 1 - $from;
                    $from = 1;
                    if($to - $from < $page) 
                    {
                        $to = $page;
                    }
                } 
                elseif($to > $pages) 
                {
                    $from = $pages - $page + 1;
                    $to = $pages;
                }
            } 

            $multipage .= ($curpage - $offset > 1 && $pages > $page ? '<a href="'.$mpurl.'page=1'.$hrefPot.'">首页</a>&nbsp;' : '').
                ($curpage > 1 ? '<a href="'.$mpurl.'page='.($curpage - 1).''.$hrefPot.'">上一页</a>&nbsp;' : '');

            for($i = $from; $i <= $to; $i++) 
            {
                $multipage .= $i == $curpage ? '<a class="p_curpage">'.$i.'</a>&nbsp;' :
                    '<a href="'.$mpurl.'page='.$i.''.$hrefPot.'" class="p_num">['.$i.']</a>&nbsp;';
            }

            $multipage .= ($curpage < $pages ? '<a  href="'.$mpurl.'page='.($curpage + 1).''.$hrefPot.'">下一页</a>&nbsp;' : '').
                ($to < $pages ? '<a class="p_redirect" href="'.$mpurl.'page='.$pages.''.$hrefPot.'">末页</a>' : '').
                ($curpage == $maxpages ? '' : '');

        }

        return $multipage;
    }/*}}}*/
}/*}}}*/

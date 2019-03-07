<?php
//ini_set('display_errors', true);

class execl
{
    function __construct($filename = 'test', $head = false, $charset = 'utf-8')
    {
        header ("Content-type: application/x-msexcel");
        header ("Content-Disposition: attachment; filename=$filename.xls");
        echo '<html><head>', "\n";
        echo '<meta http-equiv="Content-Type" content="text/html; charset=', $charset, '" />', "\n";
        echo '<style type="text/css">#bowser{padding-left:3px; padding-right:3px; white-space:nowrap}</style>', "\n";
        echo '</head>', "\n";
        echo '<body>',"\n",'<table id="bowser" border="1" borderColor=gray>', "\n";
        if (is_array($head))
        {
            echo "<tr>";
            foreach($head as $item)
                echo '<th bgColor="lightsteelblue">', $item, "</th>";
            echo "</tr>\n";
        }
    }
    function __destruct()
    {
        echo '</table>',"\n",'</body></html>', "\n";
    }

    //add new table
    function addtable($head = false,$caption=false)
    {
        if($caption && is_string($caption))
            echo '</table>',"\n",'<table id="bowser" border="1" borderColor=gray><caption>',$caption,'</caption>',"\n";
        else
            echo '</table>',"\n",'<table id="bowser" border="1" borderColor=gray>',"\n";

        if (is_array($head))
        {
            echo "<tr>";
            foreach($head as $item)
                echo '<th bgColor="lightsteelblue">', $item, "</th>";
            echo "</tr>\n";
        }
    }

    function addheader($header, $col=3)
    {
      echo '<tr>';
      echo '<td colspan="'.$col.'" style="vnd.ms-excel.numberformat:@">', $header, '</td>';
      echo '</tr>';
    }

    //add more table data    
    function addrows($rows)
    {
        if ( is_array(reset($rows)) )
        {
            foreach( $rows as $row )
                $this->onerow($row);
        }
        else
            $this->onerow($rows);
    }

    protected function onerow($row)
    {
        echo '<tr>';
        foreach($row as $idx=>$item)
        {
          if($idx == 2)
            echo '<td style="vnd.ms-excel.numberformat:@" style="height:25px;width:60px">', $item, '</td>';
          else
            echo '<td style="vnd.ms-excel.numberformat:@" style="height:25px;">', $item, '</td>';
        }
        echo "</tr>\n";
    }


}

//$m = new execl;
//$m = new execl('test', array('a', 'b', 'c'));
//$m->addrows(array(1,2,3));

?>

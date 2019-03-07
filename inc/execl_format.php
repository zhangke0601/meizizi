<?php
//ini_set('display_errors', true);

class execlformat
{
	private $body;
	private $index;
    function __construct($filename = 'test', $head = false, $caption=false, $charset = 'utf-8')
    {
        $this->body =  '<html><head>'. "\n";
        $this->body .= '<meta http-equiv="Content-Type" content="text/html; charset='. $charset. '" />'. "\n";
        $this->body .= '<style type="text/css">#bowser{padding-left:3px; padding-right:3px; white-space:nowrap}</style>'. "\n";
        $this->body .= '</head>'. "\n";
		$this->index = 0;
    }
    function __destruct()
    {
        //echo '</table>',"\n",'</body></html>', "\n";
    }

    //add new table
    function addtable($head = false,$caption=false)
    {
		if($this->index == 0)
		{
			if($caption && is_string($caption))
				$this->body .= '<table id="bowser" border="1" borderColor=gray><caption>'.$caption.'</caption>'."\n";
			else
				$this->body .= '<table id="bowser" border="1" borderColor=gray>'."\n";
		}
		else
		{
			if($caption && is_string($caption))
				$this->body .= '</table>'."\n<br><br>".'<table id="bowser" border="1" borderColor=gray><caption>'.$caption.'</caption>'."\n";
			else
				$this->body .= '</table>'."\n<br><br>".'<table id="bowser" border="1" borderColor=gray>'."\n";
		}
		$this->index += 1;

        if (is_array($head))
        {
            $this->body .= "<tr>";
            foreach($head as $item)
                $this->body .= '<th bgColor="lightsteelblue">'. $item. "</th>";
            $this->body .= "</tr>\n";
        }
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

	function endexecl()
	{
		$this->body .= '</table>'."\n".'</body></html>'. "\n";
	}

	function getresult()
	{
		return $this->body;
	}

    protected function onerow($row)
    {
        $this->body .= '<tr>';
        foreach($row as $item)
        {
            $this->body .= '<td>'. $item. '</td>';
        }
        $this->body .= "</tr>\n";
    }
}

//$m = new execlformat;
//$m = new execlformat('test', array('a', 'b', 'c'));
//$m->addrows(array(1,2,3));

?>

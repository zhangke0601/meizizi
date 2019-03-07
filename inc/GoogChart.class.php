<?php

class GoogChart
{
    // Constants
    const BASE = 'http://chart.apis.google.com/chart';

    // Variables
    protected $types = array(
                        'pie' => 'p',
                        'pie3d' => 'p3',
                        'line' => 'lc',
                        'sparkline' => 'ls',
                        'bar-horizontal' => 'bhg',
                        'bar-vertical' => 'bvg',
                        'wien' => 'v'
                    );

    protected $type = 'line';
    protected $title;
    protected $data = array();
    protected $size = array(300,200);
    protected $color = array('00A5C6', '1747E5', 'C63325', 'F7A70C', '008E0F', '000000', '551A8B', 'E99F8B', '40723A');
    protected $fill = array();
    protected $legend;
    protected $background = 'a,s,ffffff';
    protected $showlabelsXY = true;
    protected $showLegend = true;
    protected $showGridLine = true;
    protected $showMarker = false;
    protected $showDataLabel = true;
    protected $dataGroups = 0;
    //protected $barSpace = 20;

    protected $query = array();

    // Return string
    public function __toString()
    {
        return $this->img( GoogChart::BASE.'?'.$this->getQuery(), $this->title );
    }

    public function createImage()
    {
        $opts = array('http' => array(
                'method' => 'POST',
                'header' => 'Content-type: application/x-www-form-urlencoded',
                'content' => $this->getQuery()));

        header('Content-type:image/png');
        header('Content-Disposition:attachment;filename=gchart.png');
        $context = stream_context_create($opts);
        fpassthru(fopen(GoogChart::BASE, 'r', false, $context));
        // return file_get_contents(GoogChart::BASE, false, $context);
    }

    public function getImageContent()
    {
        $opts = array('http' => array(
                'method' => 'POST',
                'header' => 'Content-type: application/x-www-form-urlencoded',
                'content' => $this->getQuery()));
        $context = stream_context_create($opts);
        //fpassthru(fopen(GoogChart::BASE, 'r', false, $context));
        return file_get_contents(GoogChart::BASE, false, $context);
    }

    // Create chart
    public function getQuery()
    {
        if (!isset($this->data['values']))
            return '';

        if ($this->showMarker)
        {
            for($i = 0; $i < $this->dataGroups; ++$i)
                $markers[] = "N,303030,$i,,10";
            $markers = implode('|',$markers);
        }
        // Create query
        $this->query = array(
                            'cht'  => $this->types[strtolower($this->type)],      // Type
                            'chtt' => $this->title,                               // Title
                            'chd'  => 't:'.$this->data['values'],                 // Data
                            'chds' => $this->data['scalar'],                      // Data scale
                            'chl'  => ($this->showDataLabel) ? $this->data['names'] : null,     // Data labels
                            'chdl' => ($this->showLegend) ? $this->legend : null, // Data legend
                            'chm'  => ($this->showMarker) ? $markers : null,      // Data Value Markers
                            'chs'  => $this->size[0].'x'.$this->size[1],          // Size
                            //'chbh' => '10,1,'.$this->barSpace,                  // bar width and spacing
                            'chbh' => 'r,0.1,1',                                  // bar width and spacing
                            'chco' => preg_replace( '/[#]+/', '', implode(',',$this->color)),  // Color ( Remove # from string )
                            'chf'  => preg_replace( '/[#]+/', '', $this->background),          // Background color ( Remove # from string )
                            'chxt' => ($this->showlabelsXY) ? 'x,y' : null,                    // X & Y axis labels
                            'chxr' => ($this->showlabelsXY) ? $this->data['range'] : null,     // axis range
                            'chg'  => ($this->showGridLine) ? '0,20' : null,                   // Grid Lines
                            //'chm'  => preg_replace( '/[#]+/', '', implode('|',$this->fill)), // Fill ( Remove # from string )
                            );
        //var_dump($this->query);
        return http_build_query($this->query);
    }

    public function setBarSpace($value)
    {
        //$this->barSpace = $value;
    }

    // Set attributes
    public function setChartAttrs( $attrs )
    {
        foreach( $attrs as $key => $value )
            $this->{"set$key"}($value);
    }

    // Set type
    public function setType( $type )
    {
        $this->type = $type;
    }

    // Set title
    public function setTitle( $title )
    {
        $this->title = str_replace("\n", "|", $title);
    }

    // Set data
    public function setData( $data )
    {
        // Clear any previous data
        unset($this->data);
        $this->dataGroups = 0;
        $this->legend = null;
        $minValue = 0;
        $maxValue = 1;

        // Check if multiple data
        if ( is_array(reset($data)) )
        {
            $namesLen = 0;
            // Multiple sets of data
            foreach( $data as $key => $value )
            {
                if ( count($value) == 0 )
                    continue;
                $this->dataGroups++;
                $minValue = min($minValue, min($value));
                $maxValue = max($maxValue, max($value));
                // Add data values
                $this->data['values'][] = implode( ',', $value );
                // Add data names
                $tmp = implode( '|', array_keys( $value ) );
                if (strlen($tmp) > $namesLen)
                {
                    $this->data['names'] = $tmp;
                    $namesLen = strlen($tmp);
                }
            }
            // Implode data correctly
            $this->data['values'] = implode('|', $this->data['values']);
            // Create legend
            $this->legend = implode('|', array_keys( $data ));
        }
        else
        {
            // Single set of data
            if ( count($data) == 0 )
                return;
            $this->dataGroups++;
            $minValue = min($minValue, min($data));
            $maxValue = max($maxValue, max($data));
            // Add data values
            $this->data['values'] = implode( ',', $data );
            // Add data names
            $this->data['names'] = implode( '|', array_keys( $data ) );
        }
        $minValue = round($minValue);
        $maxValue = round($maxValue*1.01);
        $this->data['scalar'] = "$minValue,$maxValue";
        $this->data['range'] = "1,$minValue,$maxValue";
    }

    // Set data names
    public function setNames( $names )
    {
        if ( is_array( $names ) )
            $this->data['names'] = implode( '|', $names );
        else
            $this->data['names'] = $names;
    }

    // Set size
    public function setSize( $width, $height = null )
    {
        // check if width contains multiple params
        if ( is_array( $width ) )
            $this->size = $width;
        else
        {
            // set each individually
            $this->size[] = $width;
            $this->size[] = $height;
        }
        $size = $this->size[0] * $this->size[1];
        if ($size > 300000)
        {
            $this->size[0] = intval($this->size[0] * (300000 / $size));
            $this->size[1] = intval($this->size[1] * (300000 / $size));
        }
    }

    // Set color
    public function setColor( $color )
    {
        if ( is_array( $color ) )
            $this->color = $color;
        else
        {
            $this->color = array();
            $this->color[] = $color;
        }
    }

    // Set showlegend
    public function setLegend( $isShow )
    {
        $this->showLegend = $isShow;
    }

    public function setDataLabel( $isShow )
    {
        $this->showDataLabel = $isShow;
    }

    // Set showlabels
    public function setLabelsXY($isShow )
    {
        $this->showlabelsXY = $isShow;
    }

    public function setGridLine($isShow)
    {
        $this->showGridLine = $isShow;
    }

    // Set showMarker
    public function setMarker( $isShow )
    {
        $this->showMarker = $isShow;
    }

    // Set fill
    public function setFill( $fill )
    {
        // Fill must have atleast 4 parameters
        if ( count( $fill ) < 4 )
        {
            // Add remaining params
            $count = count( $fill );
            for ( $i = 0; $i < $count; ++$i )
                $fill[$i] = 'b,'.$fill[$i].','.$i.','.($i+1).',0';
        }
        $this->fill = $fill;
    }

    // Set background
    public function setBackground( $background )
    {
        $this->background = 'bg,s,'.$background;
    }

    // Create img html tag
    protected function img( $url, $alt = null )
    {
        return sprintf('<img src="%s" alt="%s" style="width:%spx;height:%spx;" />', $url, $alt, $this->size[0], $this->size[1]);
    }
}

class gPieChart extends GoogChart
{
    function __construct()
    {
        $this->type = 'pie';
        $this->showlabelsXY = false;
        $this->showLegend = false;
        $this->showGridLine = false;
    }

    public function set3D($is3d)
    {
        if ($is3d)
            $this->type = 'pie3d';
        else
            $this->type = 'pie';
    }
}

class gLineChart extends GoogChart
{
    function __construct()
    {
        $this->type = 'line';
    }

    public function setSparkline($isSparkline)
    {
        if ($isSparkline)
            $this->type = 'sparkline';
        else
            $this->type = 'line';
    }
}

class gBarChart extends GoogChart
{
    function __construct()
    {
        $this->type = 'bar-vertical';
        $this->showMarker = true;
    }

    public function setHorizontal($isHorizontal)
    {
        if ($isHorizontal)
            $this->type = 'bar-horizontal';
        else
            $this->type = 'bar-vertical';
    }
}

class gWienChart extends GoogChart
{
    function __construct()
    {
        $this->type = 'wien';
        $this->showMarker = true;
        $this->showlabelsXY = false;
        //$this->showLegend = false;
        $this->showGridLine = false;
        $this->setDataLabel(false);
    }

    public function setData($data)
    {
        unset( $this->data );
        $minValue = 0;
        $maxValue = 1;

        if ( count($data) == 0 )
            return;
        $min = min($data);
        $max = max($data);

        $minValue = min($minValue, $min);
        $maxValue = max($maxValue, $max);

        // Add data values
        $this->data['values'] = implode( ',', $data );

        $minValue = round($minValue);
        $maxValue = round($maxValue);
        $this->data['scalar'] = "$minValue,$maxValue";
    }

    public function setNames( $names )
    {
        $this->legend =  $names;
    }
}

?>

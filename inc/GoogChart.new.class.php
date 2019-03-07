<?php
class GoogleChart
{
  private $data;
  private $options;
  private $width;
  private $height;
  protected $type;

  private static $index = 0;

  public function __toString()
  {
    return $this->generateStr();
  }

  public function setChartAttrs($params)
  {
    $this->data = $params['data'];
    $this->options = $params;
    $this->width  = $params['size'][0];
    $this->height = $params['size'][1];
    unset($this->options['data']);
    unset($this->options['size']);
  }

  private function generateStr()
  {
    $str = '';
    if(GoogleChart::$index == 0)
      $str = '<script type="text/javascript" src="https://www.google.com/jsapi"></script>';
    $str .= "<script type=\"text/javascript\">";
    if($this->type == 'line')
    {
      $funcname = sprintf("drawChart_%s", GoogleChart::$index);
      $str .= "google.load(\"visualization\", \"1\", {packages:[\"corechart\"]});\n";
      $str .= sprintf("google.setOnLoadCallback(%s);", $funcname);
      $str .= sprintf("function %s(){
          var data = google.visualization.arrayToDataTable(%s);
          var options = %s;
          var chart = new google.visualization.LineChart(document.getElementById('%s'));
          chart.draw(data, options);
          }", $funcname, $this->phpArratToJsArr($this->data), $this->phpArrToJsMap($this->options), $funcname);
    }
    $str .= '</script>';
    $str .= sprintf('<div id="%s" style="width:%spx;height:%spx"></div>', $funcname, $this->width, $this->height);

    GoogleChart::$index += 1;
    return $str;
  }

  public function phpArratToJsArr($data)
  {
    $head = array();
    if(!empty($data))
    {
      foreach($data as $row)
      {
        foreach($row as $key=>$value)
          $head[] = $key;
        break;
      }
    }

    $str = '[';
    $str .= sprintf("['%s'],", implode("','", $head));
    $i = 0;
    foreach($data as $key=>$row)
    {
      $str .= '[';
      foreach($row as $k=>$v)
      {
        $str .= sprintf('\'%s\',', $v);
        unset($row[$k]);
        break;
      }
      $str .= implode(",", $row);
      $str .= '],';
    }
    $str = trim($str, ',');
    $str .= ']';

    return $str;
  }

  public function phpArrToJsMap($data)
  {
    $str = '{';
    foreach($data as $key=>$value)
      $str .= sprintf('%s:\'%s\'', $key, $value);
    $str .= '}';

    return $str;
  }
}

/*
 * data数据格式：array(array(),array())
 * k1=>v01, k2=>v02, k3=>v03
 * k1=>v11, k2=>v12, k3=>v13
 * k1作为横坐标，其余各列为展现数据
 */
class gLineChart extends GoogleChart
{
  function __construct()
  {
    $this->type = 'line';
  }

  public function setSparkline($isSparkline)
  {
    if($isSparkline)
      $this->type = 'sparkline';
    else
      $this->type = 'line';
  }
}

?>

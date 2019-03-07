<?php

$base_dir = dirname(__FILE__).'/../';
require_once($base_dir.'model/baseNewModel.php');
//require_once($base_dir.'model/baseModel.php');

class ComicUserplatform extends BaseNewModel
{

  function __construct()
  {
    parent::__construct();
    $this->idkey = "pfid";
    $this->tablename = "platforminfos";
    $this->fieldpre = 'pf';

  }

};

?>

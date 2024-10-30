<?

class ClickParam
{
  function __construct(&$data)
  {
    $this->data = &$data;
    $this->result = array();
  }
}
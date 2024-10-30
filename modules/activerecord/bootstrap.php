<?

if(!isset($this_module_config['prefixes'])) $this_module_config['prefixes'] = array();

if(!isset($this_module_config['tables'])) $this_module_config['tables'] = array();

if(!isset($this_module_config['ft_min_word_len'])) $this_module_config['ft_min_word_len'] = 4;

if(!isset($this_module_config['type_mappings'])) $this_module_config['type_mappings'] = array();
$mappings = array(
  'int'=>'integer',
  'tinyint'=>'check',
  'datetime'=>'date',
  'longtext'=>'textarea',
  'text'=>'text',
  'varchar'=>'text',
  'char'=>'text',
  'decimal'=>'float',
  'double'=>'float',
  'bigint'=>'integer',
  'blob'=>'blob',
  'float'=>'float',
);

foreach($mappings as $k=>$v)
{
  if(!isset($this_module_config['type_mappings'][$k])) $this_module_config['type_mappings'][$k] = $v;
}

if(!isset($this_module_config['conventions'])) $this_module_config['conventions'] = array();
$conventions = array(
  'decimal'=>array(
    'price'=>'currency',
    'budget'=>'currency',
  ),
  'varchar'=>array(
    'status'=>'title',
    'email'=>'email_address',
    'zip'=>'zip_code',
    'phone'=>'phone_number',
  ),
);
foreach($conventions as $k=>$v)
{
  if(!isset($this_module_config['conventions'][$k])) $this_module_config['conventions'][$k] = $v;
}


Click::$meta['autoloads'][] = CLICKLIB_ACTIVERECORD_CACHE_FPATH;

<?

$default_sizes = array(
  'micro'=>25,
  'icon'=>60,
  'tiny'=>100,
  'small'=>160,
  'smallish'=>250,
  'sample'=>275,
  'medium'=>450,
  'large'=>900
);

$default_settings = array(
  'rad'=>false, // 7
  'bg'=>false, // '#fff'
  'ds'=>false, // '#000',
  'zc'=>false, // true
);

if(!isset($this_module_config['sizes'])) $this_module_config['sizes'] = array();
if(!isset($this_module_config['settings'])) $this_module_config['settings'] = array();

$this_module_config['sizes'] = array_merge($default_sizes, $this_module_config['sizes']);
$this_module_config['settings'] = array_merge($default_settings, $this_module_config['settings']);

$base = dirname(__FILE__) . '/lib/plugins';
$plugins = glob($base . '/*.php');
foreach($plugins as $plugin){
	include_once $plugin ;
}
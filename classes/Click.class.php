<?

class Click
{
  static $meta = array();
  static $loaded = array();
  static $event_dispatcher = null;
  static $actions = array(
    'init',
  );
  static $plugins = array();
  
  static function load_config($__fname)
  {
    $__pieces = pathinfo($__fname);
    list($__plugin_name, $__module_name) = explode('.', $__pieces['filename']);
    if(!isset(self::$meta['plugins'][$__plugin_name]['modules'][$__module_name]['config'])) self::$meta['plugins'][$__plugin_name]['modules'][$__module_name]['config'] = array();
    $config = &self::$meta['plugins'][$__plugin_name]['modules'][$__module_name]['config'];
    require($__fname);
  }
  
  static function register($plugin_fpath)
  {
    $plugin_name = basename($plugin_fpath);
    
    self::$meta['plugins'][$plugin_name] = array(
      'uname'=>strtoupper($plugin_name),
      'fpath'=>$plugin_fpath,
      'vpath'=>ftov($plugin_fpath),
      'requires'=>array(),
    );
    $p = &self::$meta['plugins'][$plugin_name];
    $uname = strtoupper($plugin_name);
    define("{$uname}_FPATH", $plugin_fpath);
    define("{$uname}_VPATH", ftov($plugin_fpath));


    foreach(click_glob($plugin_fpath."/config/*.php") as $config_fname)
    {
      self::load_config($config_fname);
    }

    foreach(click_glob($plugin_fpath."/modules/*") as $module_fpath)
    {
      $pieces = pathinfo($module_fpath);
      $module_name = $pieces['filename'];
      foreach(click_glob($module_fpath."/$folder_name/actions/*.*") as $event_fpath)
      {
        $pieces = pathinfo($event_fpath);
        $action_name = $pieces['filename'];
        self::$meta['actions'][$action_name][] = "{$plugin_name}.{$module_name}";
      }
    }
    
    $manifest_fpath = "$plugin_fpath/manifest.php";
    if(file_exists($manifest_fpath))
    {
      require($manifest_fpath);
      $reqs = preg_split("/[\s,]+/im", $manifest['requires']);
      foreach($reqs as $r) $p['requires'][] = trim($r);
    }
    
  }
  
  static function post_register()
  {
    foreach(array_keys(self::$meta['actions']) as $action_name)
    {
      $fn = "click_handle_{$action_name}";
      eval("
        function $fn()
        {
          \$args = func_get_args();
          Click::handle_action('{$action_name}', \$args);
        }
");
      add_action($action_name, $fn);
    }    
  }
  
  static function event($event_name, $data = array())
  {
    $p = new ClickParam($data);
    do_action($event_name, $p);
    return $p->result;
  }
  
  static function handle_action($action_name, $args)
  {
    $actions = self::$meta['actions'][$action_name];
    foreach($actions as $module_name)
    {
      Click::load($module_name);
    }
    $context = null;
    if(get_class($args[0])=='ClickParam')
    {
      $context = $args[0];
    }
    foreach($actions as $module_name)
    {
      self::exec_action($module_name, $action_name, $context);
    }
  }  
  
  static function exec_action($module_name, $action_name, $context)
  {
    list($plugin_name, $module_name) = explode('.',$module_name);

    
    global $current_user;
    $vars = array();
    if($context) $vars = array_merge($vars, $context->data);
    $vars = array_merge($vars, self::$meta['request']);
    $vars = array_merge($vars, array(
      'current_user' => $current_user,
      'this_module_fpath' => Click::$meta['plugins'][$plugin_name]['modules'][$module_name]['fpath'],
      'this_module_vpath' => Click::$meta['plugins'][$plugin_name]['modules'][$module_name]['vpath'],
      'this_module_config'=> Click::$meta['plugins'][$plugin_name]['modules'][$module_name]['config'],
      'this_plugin_settings' => Click::load_settings($plugin_name),
    ));
    
    $engines = array('haml', 'php');
    $folders = array('actions', 'views');
    $fpaths = array();
    foreach($folders as $folder)
    {
      foreach($engines as $e)
      {
        $fpath = $vars['this_module_fpath']."/$folder/$action_name.$e";
        if(!file_exists($fpath)) continue;
        if($e!='php')
        {
          Click::load("clicklib.$e");
          $fpath = call_user_func("{$e}_to_php", $fpath);
        }
        $fpaths[] = $fpath;
      }
    }
    self::exec_container($fpaths, $vars);
  }
  
  static function exec_container($__fpaths, &$vars)
  {
    extract($vars);
    foreach($__fpaths as $__fpath)
    {
      require($__fpath);
    }
  }
    
  static function init($plugin_fpath)
  {
    self::$event_dispatcher = new ClickEventDispatcher();
    
    foreach(self::$actions as $a)
    {
      add_action($a, array(self::$event_dispatcher, $a));
    }
  }
  
  static function load_settings($plugin_name)
  {
    if(Click::$meta['plugins'][$plugin_name]['settings']) return Click::$meta['plugins'][$plugin_name]['settings'];
    $key = $plugin_name."_settings";
    $settings = get_option($key);
    if(!$settings)
    {
      add_option($key, array());
      $settings = get_option($key);
    }
    $settings = (object)$settings;
    Click::$meta['plugins'][$plugin_name]['settings'] = $settings;    
    return $settings;
  }
  
  static function save_all_settings()
  {
    foreach(Click::$meta['plugins'] as $plugin_name=>$plugin_data)
    {
      if(!isset($plugin_data['settings'])) continue;
      $key = $plugin_name."_settings";
      $res = update_option($key, get_object_vars($plugin_data['settings']));
    }
  }
  
  static function load($full_module_name)
  {
    list($plugin_name, $module_name) = explode('.',$full_module_name);
    
    if(!$module_name) click_error("To load a module, you must specify in the format plugin_name.module_name.");
    $p = &self::$meta['plugins'][$plugin_name];
    if($p['modules'][$module_name]['is_loaded']) return;
    $p['modules'][$module_name]['is_loaded'] = true;

    foreach($p['requires'] as $required_module_name)
    {
      self::load($required_module_name);
    }
      
      
    $fpath = $p['fpath']."/modules/{$module_name}";
    if(!$p['modules'][$module_name]) $p['modules'][$module_name] = array();
    $p['modules'][$module_name] = array_merge($p['modules'][$module_name], array(
      'fpath'=>$fpath,
      'vpath'=>ftov($fpath),
      'uname'=>strtoupper($module_name),
    ));
    $m = &$p['modules'][$module_name];
    define("{$p['uname']}_{$m['uname']}_FPATH", $m['fpath']);
    define("{$p['uname']}_{$m['uname']}_VPATH", $m['vpath']);
    define("{$p['uname']}_{$m['uname']}_CACHE_FPATH", $m['fpath']."/cache");
    define("{$p['uname']}_{$m['uname']}_CACHE_VPATH", $m['vpath']."/cache");

    $this_module_fpath = $m['fpath'];
    $this_module_vpath = $m['vpath'];
    
    $class_fpath = $this_module_fpath."/classes";
    if(file_exists($class_fpath)) Click::$meta['autoloads'][] = $class_fpath;

    ensure_writable_folder($m['fpath']."/cache");

    global $current_user, $wpdb;

    foreach(click_glob($fpath."/lib/*.php") as $php)
    {
      require_once($php);
    }
    
    $this_module_config = &$m['config'];
    $this_plugin_settings = Click::load_settings($plugin_name);
    
    $files = array(
      'bootstrap.php',
      'codegen.php',
      'routes.php',
    );
    $routes = array();
    foreach($files as $f)
    {
      $load = $fpath."/$f";
      if(file_exists($load)) 
      {
        $args = array();
        foreach( array('this_plugin_settings', 'this_module_config', 'this_module_fpath', 'this_module_vpath') as $v) $args[$v] = &$$v; 
        eval_php($load, $args);
      }
    }
  }
  
  static function path_to_regex($path)
  {
    $parts = explode("/", $path);
    $keys=array();
    foreach($parts as &$part)
    {
      if (startswith($part, ':'))
      {
        $url_part = '?';
        $key = substr($part, 1);
        $keys[] = $key;
        $part = "(?P<$key>[^\/]+?)";
      } elseif ($part=='*') {
        $part = "(.*?)";
      } else {
        $part = preg_quote($part);
      }
    }
    $pattern = join("\\/",$parts);
    $pattern = $__click['app_routing_prefix'] . $pattern;
    $pattern = "/^$pattern\$/";  
    return array($pattern,$keys);
  }
  
  static function request()
  {
    $params = &Click::$meta['request']['params'];
    $path = array($params['page']);
    if($params['_c']) 
    {
      $parts = explode('?',$params['_c']);
      $path[] = $params['_c'];
    }
    $path = join('/',$path);
    foreach(Click::$meta['route_controlled_actions'] as $regex=>$route_info)
    {
      if(!preg_match($regex, $path, $matches)) continue;
      foreach($route_info['keys'] as $k)
      {
        if($params[$k]) click_error("Key $k in URL route $regex would mask \$param key.");
        $params[$k] = $matches[$k];
      }
      foreach($route_info['listeners'] as $plugin_name=>$module_info)
      {
        foreach($module_info as $module_name=>$action_names)
        {
          foreach($action_names as $action_name)
          {
            _event($plugin_name, $module_name, $action_name);
          }
        }
      }
    }
  }
}

register_shutdown_function(array('Click', 'save_all_settings'));

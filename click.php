<?php
/*
 Plugin Name: Click
 Plugin URI: http://click.benallfree.com
 Description: Aspect-oriented web framework for PHP
 Author: Ben Allfree
 Version: 1.1.2
 Author URI: http://www.benallfree.com
 Text Domain: libraries
 License: GPL
 Copyright 2011  Launchpoint Software Inc., (email ben@launchpointsoftware.com)
 */
 
 
add_action('plugins_loaded', 'clicklib_plugins_loaded');

function clicklib_plugins_loaded()
{
  if(!session_id()) session_start();
  
  require_once('classes/Click.class.php');
  require_once('lib/autoload.php');
  spl_autoload_register('__click_autoload');
  require_once('lib/file.php');
  
  $pieces = parse_url(site_url());
  
  define('ROOT_FPATH', realpath($_SERVER['DOCUMENT_ROOT']).$pieces['path']); // DOCUMENT_ROOT doesn't detect if WP is in a subfolder
  define('ROOT_VPATH', normalize_path($_SERVER['SCRIPT_NAME']."/../.."));
  define('CLICK_FPATH', dirname(__FILE__));
  define('CLICK_VPATH', ftov(CLICK_FPATH));
  Click::$meta['autoloads'] = array(CLICK_FPATH."/classes");
  
  foreach(click_glob(dirname(__FILE__)."/lib/*.php") as $fname)
  {
    require_once($fname);
  }
  
  global $wpdb;
  db_select('default', array(
    'host'=>$wpdb->dbhost, 
    'username'=>$wpdb->dbuser, 
    'password'=>$wpdb->dbpassword, 
    'catalog'=>$wpdb->dbname,
  ));
  
  Click::init(dirname(__FILE__));
  Click::register(dirname(__FILE__));
  do_action('click');
  Click::post_register();
}

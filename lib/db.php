<?


Click::$meta['db']['connections'] = array();
Click::$meta['db']['connection_stack'] = array();

function db_select($handle, $dbs=null)
{
  if(!isset(Click::$meta['db']['connections'][$handle]) && !$dbs) click_error("Tried to select $handle, but no database settings were defined.");
  if($dbs)
  {
    $dbh = db_connect($dbs);
    Click::$meta['db']['connections'][$handle]['handle'] = $dbh;
    Click::$meta['db']['connections'][$handle]['credentials'] = $dbs;
  }
  Click::$meta['db']['current'] = Click::$meta['db']['connections'][$handle];
  return Click::$meta['db']['current'];
}

function db_push($handle, $dbs=null)
{
  Click::$meta['db']['connection_stack'][] = Click::$meta['db']['current'];
  Click::$meta['db']['current'] = db_select($handle, $dbs);
  return Click::$meta['db']['current'];
}

function db_pop()
{
  if(count(Click::$meta['db']['connection_stack'])>0)
  {
    Click::$meta['db']['current'] = array_pop(Click::$meta['db']['connection_stack']);
  }
}

function db_connect($database_settings)
{
  $dbh=mysql_connect ($database_settings['host'], $database_settings['username'],$database_settings['password']);
  if (!$dbh)
  {
    click_error('Cannot connect to the database because: ' . mysql_error());
  }
  if (!mysql_select_db($database_settings['catalog'], $dbh))
  {
    click_error(mysql_error($dbh));
  }
  return $dbh;
}

function query($sql)
{
	$args = func_get_args();
	array_shift($args);
  $s = '';
  $in_quote = false;
  $in_escape = false;
  for($i=0;$i<strlen($sql);$i++)
  {
	  if(count($args)==0)
	  {
	    $s .= substr($sql, $i);
	    break;
	  }
    $c = substr($sql, $i, 1);
    if($in_escape)
    {
      $s.=$c;
      $in_escape = false;
      continue;
    }
    if($c == "'" && !$in_quote)
    {
      $in_quote = true;
      continue;
    }
    if($c == "'" && $in_quote)
    {
      $next = substr($sql, $i+1, 1);
      if($next == "'") continue;
    }
    if($c == '\\')
    {
      $in_escape = true;
      continue;
    }
    $in_quote = false;
	  switch($c)
	  {
	    case "'":
	     $in_quote = true;
	     break;
	    case '?':
	      $s .= "'".mysql_real_escape_string(array_shift($args))."'";
	      break;
	    case '!':
	      $s.= array_shift($args);
	      break;
	    case '@':
	      $s .= mysql_real_escape_string(date( 'Y-m-d H:i:s e', array_shift($args)));
	      break;
	    default:
	      $s .= $c;
	  }
  }
	$sql = $s;
	
	$sql = trim($sql);
	Click::$meta['queries'][]=$sql;
	if ( preg_match('/^delete|^update/mi',$sql)>0 && preg_match('/\s+where\s+/mi', $sql)==0)
	{
		click_error("DELETE or UPDATE error. No WHERE specified", $sql);
	}
	$start = microtime(true);
	$res = mysql_query($sql, Click::$meta['db']['current']['handle']);
	$end = microtime(true);
	Click::$meta['queries'][] = (int)(($end-$start)*1000);
	if ($res===FALSE) {
		click_error(mysql_error(Click::$meta['db']['current']['handle']), $sql);
	}
	if (gettype($res)=='resource') Click::$meta['queries'][] = mysql_num_rows($res); else Click::$meta['queries'][] = 0;
	return $res;
}

function query_assoc($sql)
{
  $args = func_get_args();

	$res = call_user_func_array('query', $args);
	$assoc=array();
	while($rec = mysql_fetch_assoc($res))
	{
		$assoc[]=$rec;
	}
	return $assoc;
}

function query_obj($sql)
{
  $args = func_get_args();
  $recs = call_user_func_array('query_assoc', $args);
  $res = array();
  foreach($recs as $r)
  {
    $res[] = (object)$r;
  }
  return $res;
}

function query_file($fpath)
{
  $d = Click::$meta['build']['database'];
  $cmd = "mysql -u {$d['username']} --password={$d['password']} -h {$d['host']} -D {$d['catalog']} < \"$fpath\"";
  click_exec($cmd);
}

function db_table_exists($name)
{
  $res = query_assoc("show tables");
  
  foreach(array_values($res) as $rec)
  {
    $rec = array_values($rec);
    if ($rec[0]==$name) return true;
  }
  return false;
}

function db_dump($fname='db.gz', $include_data = true)
{
  if(!startswith($fname, '/')) $fname = BUILD_FPATH ."/{$fname}";
  ensure_writable_folder(dirname($fname));
  $extra = '';
  if(!$include_data) $extra .= ' --no-data ';
  $d = Click::$meta['build']['database'];
  $cmd = "mysqldump {$extra} --compact -u {$d['username']} --password={$d['password']}  -h {$d['host']}  {$d['catalog']} | gzip > {$fname}";
  click_exec($cmd);
}

function update_junction($table_name, $left_key_name, $left_key_id, $right_key_name, $right_key_ids)
{
  query("delete from {$table_name} where {$left_key_name} = ?", $left_key_id);
  foreach($right_key_ids as $id)
  {
    query("insert into {$table_name} ({$left_key_name}, {$right_key_name}) values (?, ?)", $left_key_id, $id);
  }
}

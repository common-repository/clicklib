<?

function magick_key($params)
{
  $keys = array_keys($params);
  sort($keys);
  $s = array();
  foreach($keys as $key)
  {
    $s[] = "$key:{$params[$key]}";
  }
  $s = join('|',$s);
  $key = md5($s);
  return folderize($params['path']).'.'.$key;
}


function magick_size($size, $vpath, $params)
{
  $magick_sizes = Click::$meta['config']['magick_images']['sizes'];
  
  $info = pathinfo($vpath);
  $fmt = $info['extension'];
  $params['size'] = $size;
  $params['path'] = substr($vpath,1);

  $src = ROOT_FPATH . $vpath;
  if(!file_exists($src)) click_error("$src does not exist for conversion.");
  $info = stat($src);
  $key=$params;
  $key['size'] = $size;
  $key['width'] = is_numeric($size) ? $size : $magick_sizes[$size];
  $key['fsize'] = $info['size'];
  $key['ctime'] = $info['ctime'];
  $key['mtime'] = $info['mtime'];
  $key['fmt'] = $fmt;

  $key = magick_key($key);

  foreach(array('jpg', 'png') as $ext)
  {
    $dst = CLICKLIB_MAGICK_IMAGES_CACHE_FPATH . "/$key.$ext";
    if(file_exists($dst)) 
    {
      $i = new phMagick($dst);
      return $i->getInfo();
    }
  }
  
  click_error("Destination file not found $dst from $src");
}

function magick_img_url($size, $vpath, $params=array())
{
  return magick_vpath($size, $vpath, $params);
}

function magick_vpath($size, $vpath, $params=array())
{
  return ftov(magick_fpath($size, $vpath, $params));
}

function magick_fpath($size, $vpath, $params=array())
{
  $magick_sizes = Click::$meta['config']['magick_images']['sizes'];

  if(!isset($magick_sizes[$size]))
  {
    click_error("'$size' is not in \$magic_sizes. Better define it in config.", s_var_export($magick_sizes));
  }
  
  $info = pathinfo($vpath);
  $fmt = $info['extension'];
  $params['size'] = $size;
  $params['path'] = substr($vpath,1);

  $src = ROOT_FPATH . $vpath;
  if(!file_exists($src)) click_error("$src does not exist for conversion.");
  $info = stat($src);
  $key=$params;
  $key['size'] = $size;
  $key['width'] = is_numeric($size) ? $size : $magick_sizes[$size];
  $key['fsize'] = $info['size'];
  $key['ctime'] = $info['ctime'];
  $key['mtime'] = $info['mtime'];
  $key['fmt'] = $fmt;
  $key = magick_key($key);

  foreach(array('jpg', 'png') as $ext)
  {
    $dst = CLICKLIB_MAGICK_IMAGES_CACHE_FPATH . "/$key.$ext";
    if(file_exists($dst)) return CLICKLIB_MAGICK_IMAGES_CACHE_FPATH ."/$key.$ext";
  }

  foreach(array('jpg', 'png') as $ext)
  {
    $dst = CLICKLIB_MAGICK_IMAGES_CACHE_FPATH . "/$key.$ext";
    try
    {
      convert($src, $dst, $params);
      $cmp[$ext] = filesize($dst);
    } catch(Exception $e) {
      return '';
    }
  }
  $min = 'jpg';
  foreach(array('jpg','png') as $ext)
  {
    if($cmp[$ext]<$cmp[$min]) $min = $ext;
  }

  foreach(array('jpg', 'png') as $ext)
  {
    if($ext == $min) continue;
    $dst = CLICKLIB_MAGICK_IMAGES_CACHE_FPATH . "/$key.$ext";
    unlink($dst);
  }
  return CLICKLIB_MAGICK_IMAGES_CACHE_FPATH . "/$key.$min";
}

function get_magick_url($o, $k, $size, $params=array())
{
  if (!$o->$k) return '#';
  $vpath = $o->$k->vpath;
  return magick_img_url($size, $vpath, $params);
}


function convert($src, $dst, $params)
{  
  $magick_sizes = Click::$meta['config']['magick_images']['sizes'];
  $magick_settings = Click::$meta['config']['magick_images']['settings'];
  extract($params);
  
  $i = new phMagick($src, $dst.".png");
  $i->convert();
  $info = $i->getInfo();

  $w = $info[0];
  $h = $info[1];
  if (isset($sw))
  {
    $ssw = $magick_sizes[$ssw];
    $mult = $w/$ssw;
    $sx = max(4,(int)($sx * $mult)-4);
    $sy = max(4,(int)($sy * $mult)-4);
    $sw = (int)($sw * $mult);
    $sh = (int)($sh * $mult);
    $i->crop($sw, $sh, $sy, $sx);
    $w = $sw;
    $h = $sh;
  }
  

  $sz = is_numeric($size) ? $size : $magick_sizes[$size];
  $ratio = min($sz/$w,$sz/$h);

  if(!isset($zc)) $zc = $magick_settings['zc'];
  if($zc)
  {
    $cropped_width = $w * $ratio;
    if($w>$h) // crop width
    {
      $i->crop($h, $h, 0, ($w-$h)/2);
    }
    if($h>$w) // crop height
    {
      $i->crop($w, $w, ($h-$w)/2, 0);
    }
    $i->resize($sz, $sz, true);
  } else {
    $i->resize($ratio * $w, $ratio * $h, true);
  }
  
  if(isset($pixelate) && $pixelate < 1 && $pixelate !==false)
  {
    $prev = $i->getInfo();
    $sz = round($prev[0]*$pixelate);
    $i->resize(max(5,$sz));
    $i->resize($prev[0], $prev[1], true);
  }
  
  if(isset($blur))
  {
    $info = explode('x', $blur);
    $radius = $info[0];
    $sigma = $info[1];
    $i->blur($radius, $sigma);
  }


  if(!isset($rad)) $rad=$magick_settings['rad'];
  if(!isset($bg)) $bg = $magick_settings['bg'];

  if($rad) $i->roundCorners($rad, $bg);
  if(!isset($ds)) $ds=$magick_settings['ds'];
  if($ds!==0 && $ds!==false)
  {
    $i->dropShadow($ds, $bg);
  }
  if(isset($polaroid) && $polaroid) $i->fakePolaroid();
  $i->setDestination($dst);
  $i->convert();
  unlink($dst.".png");
}


function magick_montage_fpath($size, $vpaths, $params=array())
{
  $magick_sizes = Click::$meta['config']['magick_images']['sizes'];
  
  $key = $params;
  $fpaths = array();
  foreach($vpaths as $vp)
  {
    $fpath = magick_fpath($size, $vp, $params);
    $key[$vp] = $fpath;
    $fpaths[] = $fpath;
  }
  
  $key['path'] = 'montage';
  
  $key = magick_key($key);
  
  $fpath = CLICKLIB_CLICKLIB_MAGICK_IMAGES_CACHE_FPATH."/{$key}.png";
  
  if(file_exists($fpath)) return $fpath;
  $w = $h = $magick_sizes[$size]+20;
  $cmd = array();
  $cmd[] = "convert -size {$w}x{$h} xc:none -background none -fill white -stroke grey60";
  for($i=count($fpaths)-1;$i>=0;$i--)
  {
    $fp = $fpaths[$i];
    $img = new phMagick($fp);
    $info = $img->getInfo();
    list($w,$h) = $img->getInfo();
    $w+=10;$h+=10;
    $r = rand(-2,2)*5;
    $cmd[] = "$fp -composite -rotate $r -interpolate bicubic";
  }
  $cmd[] = "-trim +repage -background White -flatten";
  $cmd[] = $fpath;
  $cmd = join(" ",$cmd);
  click_exec($cmd);
  
  convert($fpath, $fpath, array('size'=>$size));
  
  return $fpath;
}

function magick_montage_url($size, $vpaths, $params=array())
{
  return ftov(magick_montage_fpath($size, $vpaths, $params));
}
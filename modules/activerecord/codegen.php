<?

$res = query_assoc("show variables where variable_name = 'ft_min_word_len'");
if ($res[0]['Value']> $this_module_config['ft_min_word_len']) click_error("mySQL FullText searching error. Set ft_min_word_len >= {$this_module_config['ft_min_word_len']}. Currently set to: ". $res[0]['Value']);

$cg = new ArCodeGenerator($this_module_config);
$md5 = $cg->calc_hash();

if($settings->activerecord->codegen_hash != $md5)
{
  clear_cache(CLICKLIB_ACTIVERECORD_CACHE_FPATH);
  $cg->generate();
}
$settings->activerecord->codegen_hash = $md5;

<?


function pluralize( $string )
{
  return Inflection::pluralize($string);
}

function singularize( $string )
{
  return Inflection::singularize($string);
}


function tableize($s, $pluralize=true)
{
  return Inflection::tableize($s, $pluralize);
}
	
function classify($s)
{
  return Inflection::classify($s);
}

function humanize($s)
{
  return Inflection::humanize($s);
}


<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

function parse_search_term($raw_query){
  $query_terms = explode(' ', $raw_query);
  return $query_terms;
}

?>

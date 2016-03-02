<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

function parse_search_term($raw_query){
  if(!empty($raw_query)){
    $query_terms = explode(' ', $raw_query);
  }else{
    $query_terms = false;
  }
  return $query_terms;
}

?>

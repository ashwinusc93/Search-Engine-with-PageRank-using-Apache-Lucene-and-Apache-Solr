<?php

// make sure browsers see this page as utf-8 encoded HTML
header('Content-Type: text/json; charset=utf-8');
//error_reporting(E_ALL);
//ini_set('display_errors', 1);
require_once('SpellCorrector.php');
$limit = isset($_REQUEST['limit']) ? $_REQUEST['limit'] : 10;

$query = isset($_REQUEST['q']) ? $_REQUEST['q'] : false;
$string_uncorr = $query;
$arr = split(' ', $string_uncorr);
$string_corr = '';

for($i=0; $i < count($arr); $i++)
{
	$string_corr .= SpellCorrector::correct($arr[$i]).' ';
}
$results = false;

if ($query)
{
  // The Apache Solr Client library should be on the include path
  // which is usually most easily accomplished by placing in the
  // same directory as this script ( . or current directory is a default
  // php include path entry in the php.ini)
  require_once('Apache/Solr/Service.php');
  
  // create a new solr service instance - host, port, and webapp
  // path (all defaults in this example)
  $solr = new Apache_Solr_Service('localhost', 8983, '/solr/myexample');

  // if magic quotes is enabled then stripslashes will be needed
  if (get_magic_quotes_gpc() == 1)
  {
  	 
    $query = stripslashes(trim($string_corr));
    print_r("Here ".$query);
  }

  $query = trim($string_corr);
  $words = split(' ', $query);
  $query = end($words); // just use the last word of query

  function generateQueryString($params)
  {
    $queryString = http_build_query($params);
    return preg_replace('/\\[(?:[0-9]|[1-9][0-9]+)\\]=/', '=', $queryString);
  }

  function suggest($query, $params, $offset=0, $timeout=FALSE)
  {
    global $solr, $limit;
    $params['q'] = $query;
    $params['start'] = $offset;
    $params['suggest.count'] = $limit;
    $params['wt'] = 'json';
    $searchUrl = 'http://localhost:8983/solr/myexample/suggest?';
    $queryStr = generateQueryString($params);
    $url = $searchUrl . $queryStr;
    //echo $url;
    $httpTransport = $solr->getHttpTransport();
    $httpResponse = $httpTransport->performGetRequest($url, $timeout);
    return $httpResponse->getBody();
  }
  
  
  $params = array();
	//$params['spellcheck.build'] = 'true';
	//$params['spellcheck'] = 'true';
	$params['qt'] = '/suggest';
  
  //echo $solr->suggest($query);
  // in production code you'll always want to use a try /catch for any
  // possible exceptions emitted  by searching (i.e. connection
  // problems or a query parsing error)
  try
  {
  	 
    if(isset($_REQUEST['sort']))
    {
		  $params['sort'] = 'pageRankFile desc';    
    }

    $results = suggest($query, $params);
      
    //echo $query;
    echo $results;
    //echo json_encode($results->suggest);
   //  foreach ($results->response->docs as $doc)
   //  {
   //  	foreach ($doc as $field => $value)
   //  	{
			// echo $field.'  \n';    	
   //  	}   
   //  }

  }
  catch (Exception $e)
  {
    // in production you'd probably log or email this error to an admin
    // and then show a special message to the user but for this example
    // we're going to show the full exception
    die("<html><head><title>SEARCH EXCEPTION</title><body><pre>{$e->__toString()}</pre></body></html>");
  }
}

?>
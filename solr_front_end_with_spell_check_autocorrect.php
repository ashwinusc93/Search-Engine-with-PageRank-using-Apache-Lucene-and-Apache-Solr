<?php

// make sure browsers see this page as utf-8 encoded HTML
header('Content-Type: text/html; charset=utf-8');
// error_reporting(E_ALL);
// ini_set('display_errors', 1);
require_once('SpellCorrector.php');
$limit = 10;

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
  
  //echo $solr->suggest($query);
  // in production code you'll always want to use a try /catch for any
  // possible exceptions emitted  by searching (i.e. connection
  // problems or a query parsing error)
  try
  {
  	 
    if(isset($_REQUEST['sort']))

      $results = $solr->search($query, 0, $limit, array('sort'=> 'pageRankFile desc'));


    else

      $results = $solr->search($query, 0, $limit);

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
<html>
  <head>
    <title>Solr Front End</title>
    <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/jquery-1.12.4.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
  </head>
  <body>
    <form  accept-charset="utf-8" method="get">
      <label for="q">Search:</label>
      <input id="q" name="q" type="text" value="<?php echo htmlspecialchars($query, ENT_QUOTES, 'utf-8'); ?>"/>
      <input type="submit"/>
      <br> Sort PageRankFile Desc: <input type="checkbox" name="sort" id="sort" value="desc">
    </form>
<?php

// display results
if ($results)
{
  $total = (int) $results->response->numFound;
  $start = min(1, $total);
  $end = min($limit, $total);
?>
    <div><?php echo "Query string being searched is: " . $query; ?><div>
    <div>Results <?php echo $start; ?> - <?php echo $end;?> of <?php echo $total; ?>:</div>
    <ol>
<?php
  // iterate result documents
  foreach ($results->response->docs as $doc)
  {
?>
      <li>
        <table style="border: 1px solid black; text-align: left">
<?php
    // iterate document fields / values
    foreach ($doc as $field => $value)
    {
      if($field=='id')
      {
?>
          <tr>
            <th><?php echo htmlspecialchars("ID:", ENT_NOQUOTES,'utf-8');?></th>
            <td><?php echo htmlspecialchars($value, ENT_NOQUOTES, 'utf-8'); ?> </td>
          
<?php
      }
    }
    foreach ($doc as $field => $value)
    {
      if($field=='dc_title')
      {
?>
          <th><?php echo htmlspecialchars("Title:", ENT_NOQUOTES,'utf-8');?></th>
          <td><?php echo htmlspecialchars($value, ENT_NOQUOTES, 'utf-8'); ?> </td>
<?php
      }
    }
    foreach ($doc as $field => $value)
    {
      if($field=='og_url')
      {
?>
          <th><?php echo htmlspecialchars("URL:", ENT_NOQUOTES,'utf-8');?></th>
          <td><a href="<?php echo htmlspecialchars($value, ENT_NOQUOTES, 'utf-8'); ?>">Link</a> </td>
<?php
      }
    }
    foreach ($doc as $field => $value)
    {
      if($field=='og_description')
      {
?>
			<th><?php echo htmlspecialchars("Description:", ENT_NOQUOTES,'utf-8');?></th>
         <td><?php echo htmlspecialchars($value, ENT_NOQUOTES, 'utf-8'); ?> </td>
<?php
      }
    }
    foreach ($doc as $field => $value)
    {
    	if($field=='id')
		{
			$snippets = array();

			foreach (split(' ', $query) as $q) {
				//echo "QUERY: ".$q;
				$filename_array = split('/', $value);
				$filename = end($filename_array);
				$contents = " ".file_get_contents("Body Content Assign 4/".$filename.".txt")." ";
				
				// escape special characters in the query
				$pattern = preg_quote($q, '/');
				// finalise the regular expression, matching the whole line
				$pattern = "/\W(.{0,20}$pattern.{0,20})\W/mi";
				//echo $pattern;
				// search, and store all matching occurences in $matches
				//echo $contents;
				if (preg_match_all($pattern, $contents, $matches, PREG_OFFSET_CAPTURE)) {
					//echo "We got matches!".count($matches);
          //print_r($matches);
					for ($i = 0; $i < min(count($matches[0]), 10); $i++) {
						$match = $matches[0][$i];
						//print_r($match);
						if (count($snippets) > 10)
							break;
						$finding = trim($match[0]);
						//$offset = $match[1];
						$snippets[] = $finding;		
					}			
				}
			}
		?>
			<th><?php echo htmlspecialchars("Snippets:", ENT_NOQUOTES,'utf-8');?></th>
			<td><?php echo htmlspecialchars(implode(" ... ", $snippets), ENT_NOQUOTES, 'utf-8'); ?> </td>
<?php
        }
    }
?>
     </tr>

        </table>
      </li>
<?php
  }
?>
    </ol>
<?php
}
?>
  <script>
  $(function() {
    console.log("Setting up autocomplete...");
    function computeLimit()
    {
      var query = $("#q").val();
      if (query.length == 0 || query.endsWith(' '))
        return 0;
      if (query.length == 1)
        return 10;
      if (query.length == 2)
        return 7;
      return 4;
    }
    $( "#q" ).autocomplete({
      source: function( request, response ) {
        $.ajax( {
          url: "autocomplete.php",
          dataType: "json",
          data: {
            q: $("#q").val(),
            sort: document.getElementById("sort").checked,
            limit: computeLimit()
          },
          success: function( data ) {
            console.log("Results for querying for ", $("#q").val());
            console.log(data);
            var beginning = $("#q").val();
            var words = beginning.split(' ');
            if (words.length == 0)
              return;
            beginning = '';
            for (var i = 0; i < words.length-1; i++)
            {
              beginning += words[i];
              beginning += ' ';
            }
            var completions = [];
            //var org_query = words[words.length-1];
            for (var org_query in data.suggest.suggest)
            {
              var raw = data.suggest.suggest[org_query].suggestions;
              var added = [];
              for (var i = 0; i < raw.length; i++)
              {
                var term = raw[i].term;
                term = term.replace(/[.,\/#!$%\^&\*;:{}=\-_`~()].+/g ,'');
                if (added.indexOf(term) >- 0)
                  continue;
                added.push(term);
                completions.push(beginning + term);
              }
              break;
            }
            
            console.log(completions);
            response(completions);
          }
        } );
      }
    } );
  });
  </script>
  </body>
</html>
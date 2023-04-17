<?php
// list.php
// webservice to search grid description for text, for a certain time period, and certain filters like 'photos', and 'featured'
// designed at Michigan Tech in Houghton, Michigan, in cooperation with Chris Marr at Monte Consulting

// Make sure Content-Type is application/json 
//$content_type = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
//if (stripos($content_type, 'application/json') === false) {
//  throw new Exception('Content-Type must be application/json');
//}
ini_set('memory_limit', '150M');
// get variables and decode
$method = $_SERVER['REQUEST_METHOD'];
if($method != 'OPTIONS'){//'OPTIONS'
    $body = file_get_contents("php://input");
    $object = json_decode($body, false);
    
    $f = isset($_GET['f']) ? str_replace("\'","",$_GET['f']) : 'json'; //default is json
    $p = isset($_GET['p']) ? str_replace("\'","",$_GET['p']): 'all'; //options are person, place, business, story, all. default is all
    
    
    //query construction below... 
    
        $dateMapQuery = "SELECT min_year, CASE WHEN max_year > 2000 THEN date_part('year', CURRENT_DATE)::integer ELSE max_year END max_year,
            rep_year, url, map_type FROM grf.kett_date_segments ORDER BY min_year ASC; "; 
                      
    
    if($f == 'json' || $f == 'pjson'){//if no format was set or json was requested, issue the query and format the results as json.
    	
    	$link = pg_connect("host=portal1-geo.sabu.mtu.edu port=5432 dbname=giscore user=webuser password=sp@ghetti") or die('cannot connect to db');
    	//====================================================
    	$dateMapResult = pg_query($link, $dateMapQuery) or die('query error: '.$dateMapQuery);
    	
    
    	// build a JSON feature collection array. should think about making this a GeoJSON in future.
    	$results = array();
       
        //fetch individual segment details
    	$segments = array();
    	$min_min_year = 3000;
    	$max_max_year = 0;
    	   	while ($row = pg_fetch_assoc($dateMapResult)){
    			$properties = $row;
    			if($row['max_year'] > 2000){
    			    $title = $row['min_year'].'-'.date("Y").' ('.$row['map_type'].")";    
    			} else {
    			    $title = $row['min_year'].'-'.$row['max_year'].' ('.$row['map_type']." ".$row['rep_year'].")";
    			}
    			$segment = array(
    			        
    			        "min" => (int)$row['min_year'],
    			        "max" => (int)$row['max_year'],
    			        "map_year" => (int)$row['rep_year'],
    			        "url" => $row['url'],
    			        "title" => $title
    			        
    			);
    			array_push($segments, $segment);
    			if($row['min_year'] < $min_min_year){$min_min_year = $row['min_year']; }
    		    if($row['max_year'] > $max_max_year){$max_max_year = $row['max_year']; }
    		}
    		
    	$results['min'] = (int)$min_min_year;
    	$results['max'] = (int)date("Y");// (int)$max_max_year;
    	//$results['current_location'] = 'Keweenaw';
        $results['segments'] = $segments;
    	//$json = array('active' => $active);
    	$json = $results;
    	
        @ pg_close($link);
    }else{	//if format was set to help, don't issue the query but send user this text with the query that would have been submitted had they selected json format
    	$json = "you created this query: ".$dateMapQuery;
    }
}else{$json = "options request response";//options request 
}

header('Content-type: application/json');
//header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Max-Age: 86400');
if($f == 'pjson' ){
    echo json_encode($json, JSON_PRETTY_PRINT);
} else { 
    echo json_encode($json);
   
}
//disconnect db

?>
<?php
// grid.php - copied from grid4 - 4th iteration of the grid endpoint, renamed 'grid' on 9/30/2021
// webservice to search grid description for text, for a certain time period, and certain filters like 'photos', and 'featured'
// designed at Michigan Tech in Houghton, Michigan, in cooperation with Chris Marr at Monte Consulting

// Make sure Content-Type is application/json 
//$content_type = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
//if (stripos($content_type, 'application/json') === false) {
//  throw new Exception('Content-Type must be application/json');
//}

function generateCacheKey($object)
{
    return md5(json_encode($object)); // You can use a better key generation method
}

// Function to check if the cache exists
function isCacheExists($cacheKey)
{
    return file_exists('cache/' . $cacheKey);
}

// Function to get data from cache
function getFromCache($cacheKey)
{
    return json_decode(file_get_contents('cache/' . $cacheKey), true);
}

// Function to store data in cache
function storeInCache($cacheKey, $data)
{
    file_put_contents('cache/' . $cacheKey, json_encode($data));
}

function handleQueryError($errorMessage , $statusCode = 500) {
    $response = [
        "error" => "An error occurred while processing your request. Please try again later or contact support for assistance.",
        "details" => $errorMessage
    ];
    http_response_code($statusCode);
    header('Content-type: application/json');
    echo json_encode($response);
    exit; // Stop execution after sending the error message
}


$content_type = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';

$body = file_get_contents("php://input");
$trimmedBody = trim($body);
$isEmpty = preg_match('/^(?:\{\s*\}|\[\s*\])$/', $trimmedBody);
$isEmpty = $isEmpty ||  empty($body) || $trimmedBody === '{}' || $trimmedBody === '[]';


function emptyResponse(){
    $response = [
        "message" => "No data provided in the input. To request help, use the following example request:",
        "body" => [
            "request" => "help"
        ]
    ];

    header('Content-type: application/json');
    echo json_encode($response);
    exit; // Stop execution after sending the message
}

// Check if the request is for help
if ($content_type === 'application/json') {
    $object = json_decode($body, false);

    if (isset($object->request) && $object->request === 'help') {
        // Return API documentation with additional details
        $documentation = [
            "API Documentation" => "This is the API documentation. Please provide instructions on how to use the API.",
            "Additional Details" => [
                "search" => "string what the user entered in the search field",
                "size" => "number grid size in km (10, 1, 0.1)",
                "filters" => [
                    "date_range" => "string if date range selector bar is used",
                    "photos" => "boolean should list include results with photos",
                    "type" => "string one of the items in (default is 'everything'): people, places, stories, or everything"
                ]
            ]
        ];

        header('Content-type: application/json');
        echo json_encode($documentation);
        exit; // Stop execution after sending the documentation
    }
}


// Generate a cache key based on the request parameters
$cacheKey = generateCacheKey($body);

// Check if the cache exists
if (isCacheExists($cacheKey)) {
    // Cache exists, retrieve and return data
    $cachedData = getFromCache($cacheKey);
    header('Content-type: application/json');
//header('Access-Control-Allow-Origin: *');
header('Access-Control-Max-Age: 86400');
header('Access-Control-Allow-Headers: Content-Type');
    echo json_encode($cachedData);
} else {
 
// get variables and decode
$method = $_SERVER['REQUEST_METHOD'];
if($method != 'OPTIONS'){
    $body = file_get_contents("php://input");
    $object = json_decode($body, false);
    $text_to_strip = array("\'","=",";","<",">",".","/","'");//attempt to prevent injection, but not recursive, need to improve
    $search = isset($object->search) ? trim(str_replace($text_to_strip,"%",$object->search)) : '0';
    $search = strtolower($search);
     //explode the search terms
    $search_arr = explode(' ', $search, 3);
    if(count($search_arr) > 2){array_pop($search_arr);} //only take the first two terms if more than two are sent.
    
    $gsize = isset($object->size) ? str_replace("\'","",$object->size) : 10;
    if ($gsize === "01"){$gsize = "0.1";}
    
    $dates = isset($object->filters->date_range) ? str_replace("\'","",$object->filters->date_range) : '';
    $photos = isset($object->filters->photos) ? str_replace("\'","",$object->filters->photos) : 'false';
    $type = isset($object->filters->type) ? str_replace("\'","",$object->filters->type) : '%';
   
    if ($type == 'all' || $type == 'everything'){
        $type = '%';
    }
    
    $f = isset($_GET['f']) ? str_replace("\'","",$_GET['f']) : 'json'; //default is json
    $p = isset($_GET['p']) ? str_replace("\'","",$_GET['p']): 'person'; //options are person, place, business, story, all. default is all
    
    
    if ($dates !==''){
        if (strpos($dates, '-') !== false) { // if there's a - in there, it's two years....
            $dateRange = explode("-", $dates); //explode the date range into a from and to. from is first, to is second. 
            if (is_numeric($dateRange[0]) AND is_numeric($dateRange[0])){
                if ($dateRange[0] < $dateRange[1]){
                    $dateQryStrAct = "AND a.byear <= ".$dateRange[1]." AND a.eyear >= ".$dateRange[0].""; //beginning year is ltet the 'to' year AND end year is gtet 'from' year
                    $dateQryStrInact = "OR (a.byear > ".$dateRange[1]." OR a.eyear < ".$dateRange[0].")"; //beginning year is greater than the 'to' year, OR end year is less than the 'from' year
                }else{
                    $dateQryStrAct = "AND a.byear <= ".$dateRange[0]." AND a.eyear >= ".$dateRange[1].""; //beginning year is ltet the 'to' year AND end year is gtet 'from' year
                    $dateQryStrInact = "OR (a.byear > ".$dateRange[0]." OR a.eyear < ".$dateRange[1].")"; //beginning year is greater than the 'to' year, OR end year is less than the 'from' year
                }
            }else{
                $dateQryStr = "-- non-numeric value submitted";
            }
        } else { // no - in the dates, so treat as a single value.
           if (is_numeric($dates)) {
                $dateQryStrAct = "AND a.byear <= ".$dates." AND a.eyear >= ".$dates."";
                $dateQryStrInact = "OR (a.byear > ".$dates." OR a.eyear < ".$dates.")";
           }else{
               $dateQryStr = "-- non-numeric value submitted";
           }
        }
    }else{
            $dateQryStrAct = "";
            $dateQryStrInact = "";
    }
    
    if ($photos == 'true'){
        $photoQryStrAct = "AND LOWER(a.photos) = 'true'";
        $photoQryStrInact = "OR LOWER(a.photos) = 'false'";
    }else{
        $photoQryStrAct = "";
        $photoQryStrInact = "";
    }
    
    if ($search_arr){
        if(count($search_arr)>0 && count($search_arr)<3){
            //search for the terms in original order against the record person name (in the title) and the person table name (p2). Then invert search terms; 2 terms: 1->2 and 2->1; 3 terms 1->3, 3->1, 2 remains. 
            // OR explode the search terms and search for each individually in the title and concatenated p2 name?
            
            //set the prefix:
            $searchQryStrAct = "AND( "; // AND( (search title parts) OR (search p2 name parts))
            $searchQryStrInact = "OR( "; // AND( (search title parts) OR (search p2 name parts))
           
            $searchTitleParts = "("; // put in a leading paren
            $searchP2nameParts = "(";
            $searchDescrParts = "(";
            
            $searchTitlePartsInact = "("; // put in a leading paren
            $searchP2namePartsInact = "(";
            //$searchDescrPartsInact = "(";
            
            foreach ($search_arr as $term){
                //search for each term in the title, first name, and last name. Concat all those together to make a more concise statement. will find the term in any of title, fname, lname.
                $searchTitleParts =  $searchTitleParts . " lower(a.title) LIKE '%".$term."%' AND ";
                $searchP2nameParts = $searchP2nameParts . " lower(concat(p2.fnames, ' ', p2.lnames)) LIKE '%".$term."%' AND ";
                $searchDescrParts = $searchDescrParts . " lower(a.descr) LIKE '%".$term."%' AND ";
               
                $searchTitlePartsInact =  $searchTitlePartsInact . " lower(a.title) NOT LIKE '%".$term."%' OR ";
                $searchP2namePartsInact = $searchP2namePartsInact . " lower(concat(p2.fnames, ' ', p2.lnames))NOT LIKE '%".$term."%' OR ";
                //$searchDescrPartsInact = $searchDescrPartsInact . " lower(a.descr) NOT LIKE '%".$term."%' OR ";
            }
            
            $searchTitleParts = rtrim($searchTitleParts,'AND ') .")"; //take out trainling AND and put in a closing paren
            $searchP2nameParts = rtrim($searchP2nameParts,'AND ') .")";
            $searchDescrParts = rtrim($searchDescrParts,'AND ') ." AND a.typedescr = 'stories' )";
            
            $searchTitlePartsInact = rtrim($searchTitlePartsInact,'OR ') .")"; //take out trainling AND and put in a closing paren
            $searchP2namePartsInact = rtrim($searchP2namePartsInact,'OR ') .")";
            //$searchDescrPartsInact = rtrim($searchDescrPartsInact,'OR ') .")";
            
            $searchQryStrAct = $searchQryStrAct . $searchTitleParts . " OR " . $searchP2nameParts . " OR " . $searchDescrParts ." OR (LOWER(a.title) LIKE ('%".$search."%') )  ) "; //stitch the search strings together and add a closing paren for the combined statement. 
            $searchQryStrInact = $searchQryStrInact . $searchTitlePartsInact .  " AND " . $searchP2namePartsInact . ") ";
            
        } else {
            $searchQryStrAct = "AND( lower(a.title) LIKE ('%".$search."%')  OR LOWER(a.descr) LIKE ('%".$search."%'))  "; // .. 
            $searchQryStrInact = "OR ( lower(a.title) NOT LIKE ('%".$search."%') AND lower(concat(p2.fnames, ' ', p2.lnames)) NOT LIKE ('%".$search."%')) ";
        }
    }else{ 
        $searchQryStrAct = "AND a.geotype not like 'Enumeration District'";
        $searchQryStrInact = "AND a.geotype not like 'Enumeration District'";
    }
    
        $activeQuery = "SELECT concat((cast(((st_x(a.shape))/(".$gsize."*1000)) as INT)),'|',(cast(((st_y(a.shape))/(".$gsize."*1000)) as INT)))   \"id\", 
                        CASE WHEN COUNT(DISTINCT a.typedescr) > 1 THEN 'everything' ELSE MAX(a.typedescr) END AS \"type\", 
                        (cast(((st_x(a.shape))/(".$gsize."*1000)) as INT) * (".$gsize."*1000)) lon, (cast(((st_y(a.shape))/(".$gsize."*1000)) as INT) * (".$gsize."*1000)) lat,
                        COUNT(a.recordid) count
                        FROM grf.kett_record_locs a 
                        LEFT JOIN grf.kett_person_record_union p ON p.linkedrecordid = a.recordid AND p.tablename != 'Sanborn ' 
                        LEFT JOIN grf.kett_people p2 ON p2.personid = p.personid
                        WHERE a.typedescr LIKE '".$type."' AND (cast(((st_x(a.shape))/(".$gsize."*1000)) as INT)) IS  NOT NULL 
                        " . $searchQryStrAct . " ".
                        $photoQryStrAct." ".$dateQryStrAct."  
                        group by cast(((st_x(a.shape))/(".$gsize."*1000)) as INT), cast(((st_y(a.shape))/(".$gsize."*1000)) as INT) ;"; 
                        //AND (LOWER(a.title) LIKE ('%".$search."%') OR LOWER(a.descr) LIKE ('%".$search."%')) ".
                        
        $inactiveQuery = "SELECT concat((cast(((st_x(a.shape))/(".$gsize."*1000)) as INT)),'|',(cast(((st_y(a.shape))/(".$gsize."*1000)) as INT)))   \"id\", 
                        CASE WHEN COUNT(DISTINCT a.typedescr) > 1 THEN 'everything' ELSE MAX(a.typedescr) END AS \"type\", 
                        avg(cast(((st_x(a.shape))/(".$gsize."*1000)) as INT) * (".$gsize."*1000)) lon, avg(cast(((st_y(a.shape))/(".$gsize."*1000)) as INT) * (".$gsize."*1000)) lat,
                        COUNT(a.recordid) count
                        FROM grf.kett_record_locs a 
                        LEFT JOIN grf.kett_person_record_union p ON p.linkedrecordid = a.recordid AND p.tablename != 'Sanborn ' 
                        LEFT JOIN grf.kett_people p2 ON p2.personid = p.personid
                        WHERE a.geotype not like 'Enumeration District' (AND a.typedescr NOT LIKE '".$type."' 
                        " . $searchQryStrInact . " ".
                        $photoQryStrInact." ".$dateQryStrInact." )
                        group by cast(((st_x(a.shape))/(".$gsize."*1000)) as INT), cast(((st_y(a.shape))/(".$gsize."*1000)) as INT) ;"; 
                       //OR (LOWER(a.title) NOT LIKE lower('%".$search."%') AND LOWER(a.descr) NOT LIKE lower('%".$search."%')) ".
            
        
    
    if($f == 'json' || $f == 'pjson'){//if no format was set or json was requested, issue the query and format the results as json.
    	//============== change webuser password when gis-core is accessible ==================
    	$link = pg_connect("host=portal1-geo.sabu.mtu.edu port=5432 dbname=giscore user=webuser password=sp@ghetti") or handleQueryError("Authentication Error",503);;
    	//====================================================
    	$activeResult = pg_query($link, $activeQuery) or handleQueryError(" Query Error",400);
    	//$inactiveResult = pg_query($link, $inactiveQuery) or die('query error: '.$inactiveQuery);
    	
    	// build a JSON feature collection array. should think about making this a GeoJSON in future.
    	$results = array();
        $activeids = array();
    	//loop through rows to fetch person data into arrays
    	if($p == 'person' || $p == 'all' || $p == 'everything'){
    	    $active = array();
    	    $active['length'] = pg_num_rows($activeResult);
    	    $active['size'] = $gsize;
    	    // add up recs
    	     $totalRecs = 0;
    	     $maxCount = 0;
    	     $activeResultArray = array();
    	     while ($record = pg_fetch_assoc($activeResult)){
    	        array_push($activeResultArray, $record) ;
    	        $totalRecs = (int)$totalRecs + (int)$record['count'];
    	        if ((int)$record['count'] > $maxCount){$maxCount = (int)$record['count'];}
    	    }
    	    
    	    $active_results = array();
    	    
    		//while ($row = $activeResultArray){
    		foreach ($activeResultArray as $row){
    			$properties = $row;
    			//$centroid = array('centroid' => $row['grid_id']);//is this used?
    			if((number_format(($row['count'] / $totalRecs)/($maxCount / $totalRecs),2) < 0.2)){
    			    $montenum = strval(number_format(0.2,2)) ;
    			} ELSE {
    			    $montenum = strval(number_format(($row['count'] / $totalRecs)/($maxCount / $totalRecs),2));
    			    
    			}
    			
    			//if($row['type'] == 'person'){
                //    $enttype = 'people';
                //}else if ($row['type'] == 'building'){
                //    $enttype = 'places';
                //}else if ($row['type'] == 'story'){
                //    $enttype = 'stories';
                //}else{
                //    $enttype = $row['type'];
                //}
                
    			$details = array(
    			        
    			        "id" => $row['id'],
    			        "type" => $row['type'],
    			        "centroid"=> array("lon" => $row['lon'], "lat" => $row['lat']),
    			        "count" => $row['count'],
    			        "total" => $totalRecs,
    			        "max" => $maxCount,
    			        "percent" => number_format($row['count'] / $totalRecs,2),
    			        "montenum" => $montenum, 
    			        "title" => strval($row['count']) . ' records located near here'
    			        
    			);
    			array_push($active_results, $details);
    			array_push($activeids, $row['id']);
    			//array_push($active, $properties);
    			//array_push($active, $centroid);
    		}
            $active['results'] = $active_results;
            $results['active'] = $active;
    	}
    	
    	//loop through rows to fetch person data into arrays
    	if($p == 'x' || $p == 'y'){//x & y never happen, so this won't run for now. Intentional.
    	    $inactive = array();
    	    $inactive['length'] = pg_num_rows($inactiveResult);
    	    $inactive_results = array();
    		while ($row = pg_fetch_assoc($inactiveResult)){
    			$properties = $row;
    			if (!in_array($row['id'],$activeids)){
    			    //$centroid = array('centroid' => $row['grid_id']);
    		    	// alternate method
        			$details = array(
        			        
        			        "id" => $row['id'],
        			        "type" => $row['type'],
        			        "centroid"=> array("lon" => $row['lon'], "lat" => $row['lat']),
        			        "count" => $row['count'],
        			        "percent" =>number_format($row['count'] / $active['length'],2),
        			        "size" => $gsize
        			        
        			);
        			array_push($inactive_results, $details);
        			//array_push($active, $properties);
        			//array_push($active, $centroid);
    			}
    		}
            $inactive['results'] = $inactive_results;
            $results['inactive'] = $inactive;
    	}
    	//$json = array('active' => $active);
    	$json = $results;
    	
        pg_close($link);
    }else{	//if format was set to help, don't issue the query but send user this text with the query that would have been submitted had they selected json format
    	$json = "you created this query: ".$activeQuery." and ".$inactiveQuery;
    }
}else{$search = "options request response";//options request 
}

header('Content-type: application/json');
//header('Access-Control-Allow-Origin: *');
header('Access-Control-Max-Age: 86400');
header('Access-Control-Allow-Headers: Content-Type');
storeInCache($cacheKey, $json);
if($f == 'pjson' ){
    echo json_encode($json);
} else { 
    echo json_encode($json);
   
}
}
//disconnect db

?>
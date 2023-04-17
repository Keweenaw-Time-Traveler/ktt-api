<?php
// marker_info.php
// webservice to search grid description for text, for a certain time period, and certain filters like 'photos', and 'featured'
// designed at Michigan Tech in Houghton, Michigan, in cooperation with Chris Marr at Monte Consulting

// Make sure Content-Type is application/json 
//$content_type = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
//if (stripos($content_type, 'application/json') === false) {
//  throw new Exception('Content-Type must be application/json');
//}
// get variables and decode
$method = $_SERVER['REQUEST_METHOD'];
if($method != 'OPTIONS'){
    
    $body = file_get_contents("php://input");
    $object = json_decode($body, false);
    
    $text_to_strip = array("\'","=",";","<",">",".","/","'");//attempt to prevent injection, but not recursive, need to improve
    $search = isset($object->search) ? trim(str_replace($text_to_strip,"%",$object->search)) : '0';
    $search = strtolower(trim($search));
    
    $locid = isset($object->id) ? str_replace("\'","",$object->id) : 0;
    $gsize = isset($object->size) ? str_replace("\'","",$object->size) : 5;
    $recnumber = isset($object->recnumber) ? str_replace("\'","",$object->recnumber) : 0;
    
    $loctype = isset($object->loctype) ? str_replace("\'","",$object->loctype) : 'none';
    if(!in_array(strtolower($loctype), array('home','school'))) $loctype = 'none';
    
    $return_inactive = isset($object->inactive) ? str_replace("\'","",$object->inactive) : 'false';
    if($return_inactive != 'false'){$return_inactive = 'true';}
    $dates = isset($object->filters->date_range) ? str_replace("\'","",$object->filters->date_range) : '';
    $photos = isset($object->filters->photos) ? str_replace("\'","",$object->filters->photos) : 'false';
    $type = isset($object->filters->type) ? str_replace("\'","",$object->filters->type) : 'everything';
    if ($type == 'all') $type = 'everything';
    
    $f = isset($_GET['f']) ? str_replace("\'","",$_GET['f']) : 'json'; //default is json
    //$p = isset($_GET['p']) ? str_replace("\'","",$_GET['p']): 'all'; //options are person, place, business, story, all. default is all
    
    
    //explode the search terms in the q variable of the url on space
    //$searchTerms = explode(" ", $search);
    if ($dates !==''){
        if (strpos($dates, '-') !== false) { // if there's a - in there, it's two years....
            $dateRange = explode("-", $dates); //explode the date range into a from and to. from is first, to is second. 
            if (is_numeric($dateRange[0]) AND is_numeric($dateRange[0])){
                if ($dateRange[0] < $dateRange[1]){
                    $dateQryStrAct = "AND a.byear <= ".$dateRange[1]." AND a.eyear >= ".$dateRange[0].""; //beginning year is ltet the 'to' year AND end year is gtet 'from' year
                    $dateQryStrInact = "OR a.byear > ".$dateRange[1]." OR a.eyear < ".$dateRange[0].""; //beginning year is greater than the 'to' year, OR end year is less than the 'from' year
                }else{
                    $dateQryStrAct = "AND a.byear <= ".$dateRange[0]." AND a.eyear >= ".$dateRange[1].""; //beginning year is ltet the 'to' year AND end year is gtet 'from' year
                    $dateQryStrInact = "OR a.byear > ".$dateRange[0]." OR a.eyear < ".$dateRange[1].""; //beginning year is greater than the 'to' year, OR end year is less than the 'from' year
                }
            }else{
                $dateQryStr = "-- non-numeric value submitted";
            }
        } else { // no - in the dates, so treat as a single value.
           if (is_numeric($dates)) {
                $dateQryStrAct = "AND a.byear <= ".$dates." AND a.eyear >= ".$dates."";
                $dateQryStrInact = "OR a.byear > ".$dates." OR a.eyear < ".$dates."";
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
    
    if ($locid){
        $locidQryStrAct = "AND '".$locid."' IS NOT NULL AND a.loccode = '".$locid."'";
        $locidQryStrInact = "AND '".$locid."' IS NOT NULL AND a.loccode = '".$locid."'";
    }
    
    if ($object->geometry) {
        $bboxQry = "AND st_contains((sde.st_envelope(sde.st_geometry('Linestring(".$object->geometry->xmin." ".$object->geometry->ymin.", ".$object->geometry->xmax." ".$object->geometry->ymax.")',".$object->geometry->spatialReference->wkid."))), b.shape)";
        //geometry query will override grid id query... 
        $gidQryStrAct = "";
        $gidQryStrInact = "";
    }else{
        $bboxQry = "";
    }
    
    //query construction for different groups below... 
    
        $activePersonQuery = "SELECT p.personid \"id\", a.recordid recnumber, a.title title, coalesce(a.photos, 'false') photos, 'false' featured, a.loctype loctype, a.loccode, a.geodescr, a.geotype
                        FROM grf.kett_record_locs a 
                        LEFT JOIN grf.kett_person_record_union p ON p.linkedrecordid = a.recordid AND p.tablename != 'Sanborn '
                        LEFT JOIN grf.kett_people p2 ON p2.personid = p.personid
                        WHERE a.entitytype = 'person' --AND (LOWER(a.title) LIKE ('%".$search."%')  or lower(p2.namelast) LIKE ('%".$search."%') or lower(concat(p2.namefirst, ' ', p2.namelast)) LIKE ('%".$search."%')) 
                        ".$locidQryStrAct." ".$photoQryStrAct." ".$dateQryStrAct." order by a.title;"; 
                      
        $inactivePersonQuery = "SELECT p.personid \"id\", a.recordid recnumber, a.title title, coalesce(a.photos, 'false') photos, 'false' featured, a.loctype loctype, a.loccode
                        FROM grf.kett_record_locs a 
                        LEFT JOIN grf.kett_person_record_union p ON p.linkedrecordid = a.recordid AND p.tablename != 'Sanborn '
                        WHERE a.entitytype = 'person' ".$locidQryStrAct." AND (LOWER(a.title) NOT LIKE ('%".$search."%')  ".$photoQryStrInact." ".$dateQryStrInact.");"; 
                        
                     
        $activePlaceQuery = "SELECT a.loccode \"id\", a.recordid recnumber, 
                        a.title, --CASE WHEN trim(a.title) = ',' THEN CONCAT(a.geodescr, ' ', a.eyear) ELSE CONCAT(a.title,' ',a.geodescr, ' ', a.eyear) END title, 
                        coalesce(a.photos, 'false') photos, 'false' featured, a.loctype loctype, a.loccode, a.geodescr, a.geotype
                        FROM grf.kett_record_locs a 
                        WHERE a.entitytype IN ('building','Settlement') --AND (LOWER(a.title) LIKE ('%".$search."%') OR CONCAT(LOWER(a.geodescr),' ',a.byear) = ('".$search."')) 
                        ".$locidQryStrAct." ".$photoQryStrAct." ".$dateQryStrAct.";"; 
                      
        $inactivePlaceQuery = "SELECT a.loccode \"id\", a.recordid recnumber, 
                        a.title, -- CASE WHEN trim(a.title) = ',' THEN CONCAT(a.geodescr, ' ', a.eyear) ELSE CONCAT(a.title,' ',a.geodescr, ' ', a.eyear) END title,
                        coalesce(a.photos, 'false') photos, 'false' featured, a.loctype loctype, a.loccode
                        FROM grf.kett_record_locs a 
                        WHERE a.entitytype = 'building' ".$locidQryStrInact." AND (LOWER(a.title) NOT LIKE lower('%".$search."%')  ".$photoQryStrInact." ".$dateQryStrInact.");"; 
       
       // $inactivePlaceQuery = "SELECT a.recordid recnumber, a.descr title FROM grf.kett_records_with_grids a WHERE a.entitytype = 'building' AND LOWER(a.grid_id) = lower('".$gid."') AND (".$dateQryStrInact." ".$photoQryStrInact." );"; 
                        
        $activeStoryQuery = "SELECT a.recordid \"id\", a.recordid recnumber, a.title title, coalesce(a.photos, 'false') photos, 'false' featured, a.loctype loctype, a.loccode, a.geodescr, a.geotype
                        FROM grf.kett_record_locs a 
                        WHERE a.entitytype = 'story' --AND (LOWER(a.descr) LIKE ('%".$search."%') OR LOWER(a.title) LIKE ('%".$search."%')) 
                        ".$locidQryStrAct." ".$photoQryStrAct." ;"; //".$dateQryStrAct.";";
                      
        $inactiveStoryQuery = "SELECT a.recordid \"id\", a.recordid recnumber, a.title title, coalesce(a.photos, 'false') photos, 'false' featured, a.loctype loctype, a.loccode
                        FROM grf.kett_record_locs a 
                        WHERE a.entitytype = 'story' ".$locidQryStrInact." AND (LOWER(a.descr) NOT LIKE lower('%".$search."%')  ".$photoQryStrInact." ".$dateQryStrInact.");"; 
    
    
    if($f == 'json' || $f == 'pjson'){//if no format was set or json was requested, issue the query and format the results as json.
    	//============== change webuser password when gis-core is accessible ==================
    	$link = pg_connect("host=portal1-geo.sabu.mtu.edu port=5432 dbname=giscore user=webuser password=sp@ghetti") or die('cannot connect to db');
    	//====================================================
    	$activePersonResult = pg_query($link, $activePersonQuery) or die('query error: '.$activePersonQuery);
    	$activePlaceResult = pg_query($link, $activePlaceQuery) or die('query error: '.$activePlaceQuery);
    	$activeStoryResult = pg_query($link, $activeStoryQuery) or die('query error: '.$activeStoryQuery);
        
        // build a JSON feature collection array. should think about making this a GeoJSON in future.
    	$results = array();
        $active = array();
        $active['length'] = 0 ;
        $active['title'] = '';
        $active['geotype'] = '';
        $geodescrHL = '';
        $geodescrNHL = '';
        $geotypeHL = '';
        $geotypeNHL = '';
        
    	//loop through rows to fetch person data into arrays
    	if($type == 'people' || $type == 'everything'){
    	    $people = array();
    	    $people_count = pg_num_rows($activePersonResult);
    	    $people['length'] = $people_count;
    	    $people_results = array();
    	    
    		while ($row = pg_fetch_assoc($activePersonResult)){
    			$properties = $row;
    			if($row['recnumber'] == $recnumber AND (($loctype == 'none') OR (strtolower($row['loctype']) == strtolower($loctype)) ) ){$highlighted = 'true';} else {$highlighted = 'false';}
    			
    			$person = array(
    			        
    			        "id" => $row['id'],
    			        "recnumber" => $row['recnumber'],
    			        "loctype" => ucwords($row['loctype']),
    			        "title" => (($row['title'])),
    			        "photos" => $row['photos'],
    			        "featured" => $row['featured'],
    			        "highlighted" => $highlighted,
    			        "locid" => $row['loccode']
    			);
    			if($highlighted == 'true'){
    			    array_unshift($people_results, $person);
    			    $geodescrHL = $row['geodescr'];
    			    $geotypeHL = $row['geotype'];
    			    //$active['title'] = $row['geodescr'];
    			    //$active['geotype'] = $row['geotype'];
    			}else{
    			    array_push($people_results, $person);
    			    $geodescrNHL = $row['geodescr'];
    			    $geotypeNHL = $row['geotype'];
    			}
    		}
            $people['results'] = $people_results;
    		$active['people'] = $people;
            $active['length'] = $active['length'] + $people_count;
            
    	}
    	
    	//loop through rows to fetch place data into arrays
    	if($type == 'places' || $type == 'everything'){
    	    $places = array();
    	    $places_count = pg_num_rows($activePlaceResult);
    	    $places['length'] = $places_count;
    	    $places_results = array();
    	    
    		while ($row = pg_fetch_assoc($activePlaceResult)){
    			$properties = $row;
    			if($row['recnumber'] == $recnumber ){$highlighted = 'true';} else {$highlighted = 'false';}
    			$place = array(
    			        
    			        "id" => $row['id'],
    			        "recnumber" => $row['recnumber'],
    			        "loctype" => ucwords($row['loctype']),
    			        "title" => ucwords(strtolower($row['title'])),
    			        "photos" => $row['photos'],
    			        "featured" => $row['featured'],
    			        "highlighted" => $highlighted,
    			        "locid" => $row['loccode']
    			        
    			);
    			if($highlighted == 'true'){
    			    array_unshift($places_results, $place);
    			    $geodescrHL = $row['geodescr'];
    			    $geotypeHL = $row['geotype'];
    			}else{
    			    array_push($places_results, $place);
    			    $geodescrNHL = $row['geodescr'];
    			    $geotypeNHL = $row['geotype'];
    			}
    		}
            $places['results'] = $places_results;
    		$active['places'] = $places;
            $active['length'] = $active['length'] + $places_count;
    	}
    	
    	
    	//loop through rows to fetch story data into arrays
    	if($type == 'stories' || $type == 'everything'){
    	    $stories = array();
    	    $stories_count = pg_num_rows($activeStoryResult);
    	    $stories['length'] = $stories_count;
    	    $stories_results = array();
    	    
    		while ($row = pg_fetch_assoc($activeStoryResult)){
    			$properties = $row;
    			if($row['recnumber'] == $recnumber ){$highlighted = 'true';} else {$highlighted = 'false';}
    			$story = array(
    			        
    			        "id" => $row['id'],
    			        "recnumber" => $row['recnumber'],
    			        "loctype" => ucwords($row['loctype']),
    			        "title" => ucwords(strtolower($row['title'])),
    			        "photos" => $row['photos'],
    			        "featured" => $row['featured'],
    			        "highlighted" => $highlighted,
    			        "locid" => $row['loccode']
    			        
    			);
    			if($highlighted == 'true'){
    			    array_unshift($stories_results, $story);
    			   $geodescrHL = $row['geodescr'];
    			   $geotypeHL = $row['geotype'];
    			}else{
    			    array_push($stories_results, $story);
    			    $geodescrNHL = $row['geodescr'];
    			    $geotypeNHL = $row['geotype'];
    			}
    		}
            $stories['results'] = $stories_results;
            $active['stories'] = $stories;
            $active['length'] = $active['length'] + $stories_count;
            
    	}
    	
    	IF($geodescrHL != ''){ 
    	    $active['title'] = $geodescrHL;
    	    $active['geotype'] = $geotypeHL;
    	}else{
    	    $active['title'] = $geodescrNHL;
    	    $active['geotype'] = $geotypeNHL;
    	}
       
    	$results['active'] = $active;//move below other types
    	 
    // inactive results section below
        if($return_inactive == 'true'){
            // run the inactive queries 
            $inactivePersonResult = pg_query($link, $inactivePersonQuery) or die('query error: '.$inactivePersonQuery);
            $inactivePlaceResult = pg_query($link, $inactivePlaceQuery) or die('query error: '.$inactivePlaceQuery);
            $inactiveStoryResult = pg_query($link, $inactiveStoryQuery) or die('query error: '.$inactiveStoryQuery);
            
        	// build a JSON feature collection array. 
        	$inactive = array();
            $inactive['length'] = 0 ;
            
        	//loop through rows to fetch person data into arrays
        	if($type == 'people' || $type == 'everything'){
        	    $people = array();
        	    $people_count = pg_num_rows($inactivePersonResult);
        	    $people['length'] = $people_count;
        	    $people_results = array();
        	    
        		while ($row = pg_fetch_assoc($inactivePersonResult)){
        			$properties = $row;
        			if($row['recnumber'] == $recnumber AND (($loctype == 'none') OR (strtolower($row['loctype']) == strtolower($loctype)) ) ){$highlighted = 'true';} else {$highlighted = 'false';}
        			
        			$person = array(
        			        
        			        "id" => $row['id'],
        			        "recnumber" => $row['recnumber'],
        			        "loctype" => ucwords($row['loctype']),
        			        "title" => ucwords(strtolower($row['title'])),
        			        "photos" => $row['photos'],
        			        "featured" => $row['featured'],
        			        "highlighted" => $highlighted,
        			        "locid" => $row['loccode']
        			);
        			array_push($people_results, $person);
        		
        		}
                $people['results'] = $people_results;
        		$inactive['people'] = $people;
                $inactive['length'] = $inactive['length'] + $people_count;
                
        	}
        	
        	if($type == 'place' || $type == 'everything'){
        	    $places = array();
        	    $places_count = pg_num_rows($inactivePlaceResult);
        	    $places['length'] = $places_count;
        	    $places_results = array();
        	    
        		while ($row = pg_fetch_assoc($inactivePlaceResult)){
        			$properties = $row;
        			if($row['recnumber'] == $recnumber AND (($loctype == 'none') OR (strtolower($row['loctype']) == strtolower($loctype)) ) ){$highlighted = 'true';} else {$highlighted = 'false';}
        			
        			$place = array(
        			        
        			        "id" => $row['id'],
        			        "recnumber" => $row['recnumber'],
        			        "loctype" => ucwords($row['loctype']),
        			        "title" => ucwords(strtolower($row['title'])),
        			        "photos" => $row['photos'],
        			        "featured" => $row['featured'],
        			        "highlighted" => $highlighted,
        			        "locid" => $row['loccode']
        			);
        			array_push($places_results, $place);
        		
        		}
                $places['results'] = $places_results;
        		$inactive['places'] = $places;
                $inactive['length'] = $inactive['length'] + $places_count;
                
        	}
        	
        	if($type == 'story' || $type == 'everything'){
        	    $stories = array();
        	    $stories_count = pg_num_rows($inactiveStoryResult);
        	    $stories['length'] = $stories_count;
        	    $stories_results = array();
        	    
        		while ($row = pg_fetch_assoc($inactiveStoryResult)){
        			$properties = $row;
        			if($row['recnumber'] == $recnumber AND (($loctype == 'none') OR (strtolower($row['loctype']) == strtolower($loctype)) ) ){$highlighted = 'true';} else {$highlighted = 'false';}
        			
        			$story = array(
        			        
        			        "id" => $row['id'],
        			        "recnumber" => $row['recnumber'],
        			        "loctype" => ucwords($row['loctype']),
        			        "title" => ucwords(strtolower($row['title'])),
        			        "photos" => $row['photos'],
        			        "featured" => $row['featured'],
        			        "highlighted" => $highlighted,
        			        "locid" => $row['loccode']
        			);
        			array_push($stories_results, $story);
        		
        		}
                $stories['results'] = $stories_results;
        		$inactive['stories'] = $stories;
                $inactive['length'] = $inactive['length'] + $stories_count;
                
        	}
        	
        	$results['inactive'] = $inactive;
        }
    	$json = $results;
    	
        @ pg_close($link);
    }else{	//if format was set to help, don't issue the query but send user this text with the query that would have been submitted had they selected json format
    	$json = "you created this query: ".$activePersonQuery." ---------- ".$activePlaceQuery."---------".$activeStoryQuery;
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
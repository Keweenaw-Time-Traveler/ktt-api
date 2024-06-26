<?php
// grid_cell.php
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
    
    $search = isset($object->search) ? str_replace("\'","",$object->search) : '0';
    $search = strtolower($search);
    
    //explode the search terms
    $search_arr = explode(' ', $search, 3);
    if(count($search_arr) > 2){array_pop($search_arr);} //only take the first two terms if more than two are sent.
    
    
    $gid = isset($object->id) ? str_replace("\'","",$object->id) : 0;
    $gsize = isset($object->size) ? str_replace("\'","",$object->size) : 10;
    
    $dates = isset($object->filters->date_range) ? str_replace("\'","",$object->filters->date_range) : '';
    $photos = isset($object->filters->photos) ? str_replace("\'","",$object->filters->photos) : 'false';
    $type = isset($object->filters->type) ? str_replace("\'","",$object->filters->type) : 'everything';
    if ($type == 'all') $type = 'everything';
    $f = isset($_GET['f']) ? str_replace("\'","",$_GET['f']) : 'json'; //default is json
    //$p = isset($_GET['p']) ? str_replace("\'","",$_GET['p']): 'everything'; //options are person, place, business, story, everything. default is everything
    
    
    //explode the search terms in the q variable of the url on space
    //$searchTerms = explode(" ", $search);
    if ($dates !==''){
        if (strpos($dates, '-') !== false) { // if there's a - in there, it's two years....
            $dateRange = explode("-", $dates); //explode the date range into a from and to. from is first, to is second. 
            if (is_numeric($dateRange[0]) AND is_numeric($dateRange[0])){
                if ($dateRange[0] < $dateRange[1]){
                    $dateQryStrAct = "AND a.byear <= ".$dateRange[1]." AND a.eyear >= ".$dateRange[0].""; //beginning year is ltet the 'to' year AND end year is gtet 'from' year
                    $dateQryStrInact = "a.byear > ".$dateRange[1]." OR a.eyear < ".$dateRange[0].""; //beginning year is greater than the 'to' year, OR end year is less than the 'from' year
                }else{
                    $dateQryStrAct = "AND a.byear <= ".$dateRange[0]." AND a.eyear >= ".$dateRange[1].""; //beginning year is ltet the 'to' year AND end year is gtet 'from' year
                    $dateQryStrInact = "a.byear > ".$dateRange[0]." OR a.eyear < ".$dateRange[1].""; //beginning year is greater than the 'to' year, OR end year is less than the 'from' year
                }
            }else{
                $dateQryStr = "-- non-numeric value submitted";
            }
        } else { // no - in the dates, so treat as a single value.
           if (is_numeric($dates)) {
                $dateQryStrAct = "AND a.byear <= ".$dates." AND a.eyear >= ".$dates."";
                $dateQryStrInact = "a.byear > ".$dates." OR a.eyear < ".$dates."";
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
    
    if ($gid){
        $gidQryStrAct = "AND concat((cast(((st_x(a.shape))/(".$gsize."*1000)) as INT)),'|',(cast(((st_y(a.shape))/(".$gsize."*1000)) as INT))) = lower('".$gid."')";
        $gidQryStrInact = "AND concat((cast(((st_x(a.shape))/(".$gsize."*1000)) as INT)),'|',(cast(((st_y(a.shape))/(".$gsize."*1000)) as INT))) != lower('".$gid."')";
    }
    
    if ($object->geometry) {
        $bboxQry = "AND st_contains((sde.st_envelope(sde.st_geometry('Linestring(".$object->geometry->xmin." ".$object->geometry->ymin.", ".$object->geometry->xmax." ".$object->geometry->ymax.")',".$object->geometry->spatialReference->wkid."))), b.shape)";
        //geometry query will override grid id query... 
        $gidQryStrAct = "";
        $gidQryStrInact = "";
    }else{
        $bboxQry = "";
    }
    
    if ($search_arr){
        if(count($search_arr)>0){
            //search for the terms in original order against the record person name (in the title) and the person table name (p2). Then invert search terms; 2 terms: 1->2 and 2->1; 3 terms 1->3, 3->1, 2 remains. 
            // OR explode the search terms and search for each individually in the title and concatenated p2 name?
            
            //set the prefix:
            $searchQryStrPref = "AND( "; // AND( (search title parts) OR (search p2 name parts))
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
            $searchDescrParts = rtrim($searchDescrParts,'AND ') .")";
            
            $searchTitlePartsInact = rtrim($searchTitlePartsInact,'OR ') .")"; //take out trainling AND and put in a closing paren
            $searchP2namePartsInact = rtrim($searchP2namePartsInact,'OR ') .")";
            //$searchDescrPartsInact = rtrim($searchDescrPartsInact,'OR ') .")";
            
            $searchQryStrAct = $searchQryStrPref . $searchTitleParts . " OR " . $searchP2nameParts . "  OR (LOWER(a.title) LIKE ('%".$search."%') )  ) "; //stitch the search strings together and add a closing paren for the combined statement. 
            $searchQryStrActStory = $searchQryStrPref . $searchTitleParts . " OR " . $searchP2nameParts . " OR " . $searchDescrParts . " OR (LOWER(a.title) LIKE ('%".$search."%') ) ) "; 
            
            $searchQryStrInact = $searchQryStrInact . $searchTitlePartsInact . " AND " . $searchP2namePartsInact . ") ";
            
        } else {
            $searchQryStrAct = "AND( lower(a.title) LIKE ('%".$search."%') or lower(concat(p2.fnames, ' ', p2.lnames)) LIKE ('%".$search."%')) "; // this shouldn't happen... 
            $searchQryStrInact = "OR ( lower(a.title) NOT LIKE ('%".$search."%') AND lower(concat(p2.fnames, ' ', p2.lnames)) NOT LIKE ('%".$search."%')) ";
        }
    }else{ 
        $searchQryStrAct = "";
        $searchQryStrInact = "";
    }
    
    //query construction for different groups below... 
    
        $activePersonQuery = "SELECT p.personid \"id\", a.recordid recnumber, a.title title, a.stitle stitle, a.loccode, a.loctype, a.byear min_year, ST_X(a.shape) lon, ST_Y(a.shape) lat
                        FROM grf.kett_record_locs a 
                        LEFT JOIN grf.kett_person_record_union p ON p.linkedrecordid = a.recordid AND p.tablename != 'Sanborn '
                        LEFT JOIN grf.kett_people p2 ON p2.personid = p.personid
                        WHERE a.entitytype = 'person' ". $searchQryStrAct. " ".$gidQryStrAct." ".$photoQryStrAct." ".$dateQryStrAct.";"; 
                        //AND LOWER(a.title) LIKE ('%".$search."%')  ".$gidQryStrAct." ".$photoQryStrAct." ".$dateQryStrAct.";"; 
                      
        //$inactivePersonQuery = "SELECT p.personid \"id\", a.recordid recnumber, a.descr title FROM grf.kett_records_with_grids a LEFT JOIN grf.kett_person_record_union p ON p.linkedrecordid = a.recordid WHERE a.entitytype = 'person' AND LOWER(a.grid_id) = lower('".$gid."') AND (".$dateQryStrInact." ".$photoQryStrInact." );"; 
                        
        $activePlaceQuery = "SELECT a.recordid recnumber, a.title title, a.stitle stitle, a.loccode, a.loctype, a.byear min_year, ST_X(a.shape) lon, ST_Y(a.shape) lat
                        FROM grf.kett_record_locs a 
                        WHERE a.entitytype = 'building' AND LOWER(a.title) LIKE ('%".$search."%')  ".$gidQryStrAct." ".$photoQryStrAct." ".$dateQryStrAct.";"; 
                      
       // $inactivePlaceQuery = "SELECT a.recordid recnumber, a.descr title FROM grf.kett_records_with_grids a WHERE a.entitytype = 'building' AND LOWER(a.grid_id) = lower('".$gid."') AND (".$dateQryStrInact." ".$photoQryStrInact." );"; 
                        
         $activeStoryQuery = "SELECT a.recordid recnumber, a.title title, a.stitle stitle, a.loccode, a.loctype, a.byear min_year, ST_X(a.shape) lon, ST_Y(a.shape) lat
                        FROM grf.kett_record_locs a LEFT JOIN grf.kett_person_record_union ru ON ru.linkedrecordid = a.recordid LEFT JOIN grf.kett_people p2 ON p2.personid = ru.personid
                        WHERE a.entitytype = 'story' ".$searchQryStrActStory." ".$gidQryStrAct." ".$photoQryStrAct.";";
                      
    
    
    if($f == 'json' || $f == 'pjson'){//if no format was set or json was requested, issue the query and format the results as json.
    	//============== change webuser password when gis-core is accessible ==================
    	$link = pg_connect("host=portal1-geo.sabu.mtu.edu port=5432 dbname=giscore user=webuser password=sp@ghetti") or die('cannot connect to db');
    	//====================================================
    	$activePersonResult = pg_query($link, $activePersonQuery) or die('query error: '.$activePersonQuery);
    	//$inactivePersonResult = pg_query($link, $inactivePersonQuery) or die('query error: '.$inactivePersonQuery);
        $activePlaceResult = pg_query($link, $activePlaceQuery) or die('query error: '.$activePlaceQuery);
    	//$inactivePlaceResult = pg_query($link, $inactivePlaceResult) or die('query error: '.$inactivePlaceResult);
        $activeStoryResult = pg_query($link, $activeStoryQuery) or die('query error: '.$activeStoryQuery);
         
    
    	// build a JSON feature collection array. should think about making this a GeoJSON in future.
    	$results = array();
        $active = array();
        $active['length'] = 0 ;
        $active['size'] = $gsize;
        $active['title'] = 'grid cell';
        
    	//loop through rows to fetch person data into arrays
    	if($type == 'people' || $type == 'everything'){
    	    $people = array();
    	    $people_count = pg_num_rows($activePersonResult);
    	    $people['length'] = $people_count;
    	    $people_results = array();
    	    
    		while ($row = pg_fetch_assoc($activePersonResult)){
    			$properties = $row;
    			$person = array(
    			        
    			        "id" => $row['id'],
    			        "recnumber" => $row['recnumber'],
    			        "title" => ucwords(strtolower($row['stitle'].', '.$row['min_year'].' '.$row['loctype'])),
    			        "loctype" => $row['loctype'],
    			        "locid" => $row['loccode'],
    			        "markerid" => $row['loccode'],
    			        "x" => $row['lon'], 
    			        "y" => $row['lat'],
    			        "map_year" => $row['min_year']
    			        
    			);
    			array_push($people_results, $person);
    		
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
    			$place = array(
    			        
    			        "recnumber" => $row['recnumber'],
    			        "title" => ucwords(strtolower($row['stitle'].', '.$row['min_year'].' '.$row['loctype'])),
    			        "loctype" => $row['loctype'],
    			        "locid" => $row['loccode'],
    			        "markerid" => $row['loccode'],
    			        "x" => $row['lon'], 
    			        "y" => $row['lat'],
    			        "map_year" => $row['min_year']
    			        
    			);
    			array_push($places_results, $place);
    		
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
    			$story = array(
    			        
    			        "recnumber" => $row['recnumber'],
    			        "title" => ucwords(strtolower($row['stitle'].', '.$row['min_year'].' '.$row['loctype'])),
    			        "loctype" => $row['loctype'],
    			        "locid" => $row['loccode'],
    			        "markerid" => $row['loccode'],
    			        "x" => $row['lon'], 
    			        "y" => $row['lat'],
    			        "map_year" => $row['min_year']
    			        
    			);
    			array_push($stories_results, $story);
    		
    		}
            $stories['results'] = $stories_results;
            $active['stories'] = $stories;
            $active['length'] = $active['length'] + $stories_count;
            
    	}
    	 $active['title'] = strval($active['length']) . ' Records';
    	 $results['active'] = $active;//move below other types
    	 
    	 
    //	    $inactive = array();
    //		while ($row = pg_fetch_assoc($inactivePersonResult)){
    //			$properties = $row;
    //			$centroid = array('centroid' => $row['grid_id']);
    //			// alternate method
    //			$details = array(
    //			        
    //			        "id" => $row['id'],
    //			        "recnumber" => $row['recnumber'],
    //			        "title" => $row['title']
    //			        
    //			);
    //			array_push($inactive, $details);
    //			//array_push($active, $properties);
    //			//array_push($active, $centroid);
    //		}
    //        $results['inactive'] = $inactive;
    	
    	
    	
    	//$json = array('active' => $active);
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
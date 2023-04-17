<?php
// list.php
// webservice to search grid description for text, for a certain time period, and certain filters like 'photos', and 'featured'
// designed at Michigan Tech in Houghton, Michigan, in cooperation with Chris Marr at Monte Consulting

// Make sure Content-Type is application/json 
//$content_type = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
//if (stripos($content_type, 'application/json') === false) {
//  throw new Exception('Content-Type must be application/json');
//}
ini_set('memory_limit', '250M');
// get variables and decode
$method = $_SERVER['REQUEST_METHOD'];
if($method != 'OPTIONS'){
    
    
    $body = file_get_contents("php://input");
    $object = json_decode($body, false);
    
    $text_to_strip = array("\'","=",";","<",">",".","/","'");//attempt to prevent injection, but not recursive, need to improve
    $search = isset($object->search) ? trim(str_replace($text_to_strip,"%",$object->search)) : '0';
    $search = strtolower($search);
    $search_orig = $search;
    //$search = str_replace(' ','%',$search);//replaces spaces with wildcards to search better
    
    //explode the search terms
    $search_arr = explode(' ', $search, 3);
    //if(count($search_arr) > 2){array_pop($search_arr);} //only take the first two terms if more than two are sent.
    
    $gid = isset($object->id) ? str_replace("\'","",$object->id) : '0';
    $dates = isset($object->filters->date_range) ? str_replace("\'","",$object->filters->date_range) : '';
    $dates = str_replace("2021","3000",$dates);
    $photos = isset($object->filters->photos) ? str_replace("\'","",$object->filters->photos) : 'false';
    $type = isset($object->filters->type) ? str_replace("\'","",$object->filters->type) : 'everything';
    $f = isset($_GET['f']) ? str_replace("\'","",$_GET['f']) : 'json'; //default is json
    $p = isset($_GET['p']) ? str_replace("\'","",$_GET['p']): 'all'; //options are person, place, business, story, all. default is all
    
    
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
    
    
    if ($object->geometry) {
        $bboxQry = " st_contains((sde.st_envelope(sde.st_geometry('Linestring(".$object->geometry->xmin." ".$object->geometry->ymin.", ".$object->geometry->xmax." ".$object->geometry->ymax.")',".$object->geometry->spatialReference->wkid."))), a.shape) AND ";
    }else{
        $bboxQry = "";
    }
    
    if ($search_arr){
        if(count($search_arr)>0 && count($search_arr)<3){
            //search for the terms in original order against the record person name (in the title) and the person table name (p2). Then invert search terms; 2 terms: 1->2 and 2->1; 3 terms 1->3, 3->1, 2 remains. 
            // OR explode the search terms and search for each individually in the title and concatenated p2 name?
            
            //set the prefix:
            $searchQryStrPref = "AND ( "; // AND( (search title parts) OR (search p2 name parts))
            $searchTitleParts = "("; // put in a leading paren
            $searchP2nameParts = "(";
            $searchDescrParts = "(";
            
            foreach ($search_arr as $term){
                //search for each term in the title, first name, and last name. Concat all those together to make a more concise statement. will find the term in any of title, fname, lname.
                $searchTitleParts =  $searchTitleParts . " lower(a.title) LIKE '%".$term."%' AND ";
                $searchP2nameParts = $searchP2nameParts . " lower(concat(p2.fnames, ' ', p2.lnames)) LIKE '%".$term."%' AND ";
                //$searchP2nameParts = $searchP2nameParts . " lower(concat(p2.namefirst, ' ', p2.namelast)) LIKE '%".$term."%' AND ";
                $searchDescrParts = $searchDescrParts . " lower(a.descr) LIKE ('%".$term."%') AND ";
            }
            $searchTitleParts = rtrim($searchTitleParts,'AND ') .")"; //take out trainling AND and put in a closing paren
            $searchP2nameParts = rtrim($searchP2nameParts,'AND ') .")";
            $searchDescrParts = rtrim($searchDescrParts,'AND ') .")";
            
            $searchQryStrAct = $searchQryStrPref . $searchTitleParts . " OR " . $searchP2nameParts . ") "; //stitch the search strings together and add a closing paren for the combined statement. 
            $searchQryStrActStory = $searchQryStrPref . $searchTitleParts . " OR " . $searchP2nameParts . " OR " . $searchDescrParts . ") "; 
            
        } else {
            $searchQryStrAct = "AND( lower(a.title) LIKE ('%".$search."%') OR LOWER(a.descr) LIKE ('%".$search."%'))  ";
            $searchQryStrActStory = "AND( lower(a.title) LIKE ('%".$search."%') or lower(concat(p2.fnames, ' ', p2.lnames)) LIKE ('%".$search."%') OR lower(a.descr) LIKE ('%".$search."%')   ) ";
            //$searchQryStrAct = "AND( lower(a.title) LIKE ('%".$search."%') or lower(concat(p2.namefirst, ' ', p2.namelast)) LIKE ('%".$search."%')) ";
            
        }
    }else{ 
        $searchQryStrAct = "";
    }
    
    //query construction for different groups below... 
    
        $activePersonQuery = "SELECT p.personid \"id\", a.recordid recnumber, a.title title, a.stitle stitle, a.descr descr, concat(p2.namefirst, ' ', p2.namelast) ptbl_name, a.loccode markerid, a.loctype loctype, ST_X(a.shape) lon, ST_Y(a.shape)  lat,
                        a.byear min_year, a.eyear max_year, levenshtein(lower(split_part(a.stitle,',',1))::varchar, '".$search."') levd
                        FROM grf.kett_record_locs a 
                        LEFT JOIN grf.kett_person_record_union p ON p.linkedrecordid = a.recordid AND p.tablename != 'Sanborn '
                        LEFT JOIN grf.kett_people p2 ON p2.personid = p.personid
                        WHERE ".$bboxQry." a.entitytype = 'person' ".
                        $searchQryStrAct. " ".$dateQryStrAct." ".$photoQryStrAct." AND left(stitle,2)  !~ '^[0-9]*.?[0-9]*$' ORDER BY levd, a.title LIMIT 2000;";// avoid results with no name, indicated by a title led by a number for people (buildings are fine)
                        //AND( lower(a.title) LIKE ('%".$search."%') or lower(concat(p2.namefirst, ' ', p2.namelast)) LIKE ('%".$search."%')) ".$dateQryStrAct." ".$photoQryStrAct." ORDER BY a.title LIMIT 2000;";
                        //AND( lower(a.title) LIKE ('%".$search."%') or lower(p2.namelast) LIKE ('%".$search."%') or lower(p2.namefirst) LIKE ('%".$search."%')  or lower(concat(p2.namefirst, ' ', p2.namelast)) LIKE ('%".$search."%')) ".$dateQryStrAct." ".$photoQryStrAct." ORDER BY a.title LIMIT 2000;"; 
                      
                        
        $activePlaceQuery = "SELECT a.loccode \"id\", a.recordid recnumber, CASE WHEN length(trim(a.title)) < 4 THEN a.geodescr ELSE a.title END title, a.stitle stitle, a.descr descr, a.loccode markerid, a.loctype loctype, ST_X(a.shape) lon, ST_Y(a.shape)  lat,
                        a.byear min_year, a.eyear max_year
                        FROM grf.kett_record_locs a 
                        WHERE ".$bboxQry." a.entitytype IN ('building','Settlement')  AND 
                        (LOWER(a.title) LIKE lower('%".$search."%') OR TRIM(CONCAT(LOWER(a.geodescr),' ',a.byear)) LIKE TRIM('".$search."') OR TRIM(CONCAT(a.title,' ',a.geodescr, ' ',a.eyear)) LIKE TRIM('".$search."')) 
                        AND (trim(a.title) != '') ".$dateQryStrAct." ".$photoQryStrAct." ORDER BY a.title LIMIT 2000;"; 
                      
                        
         $activeStoryQuery = "SELECT a.recordid \"id\", a.recordid recnumber, a.title title, a.stitle stitle, a.descr descr, a.loccode markerid, a.loctype loctype, ST_X(a.shape) lon, ST_Y(a.shape)  lat, a.byear min_year, a.eyear max_year
                        FROM grf.kett_record_locs a LEFT JOIN grf.kett_person_record_union ru ON ru.linkedrecordid = a.recordid LEFT JOIN grf.kett_people p2 ON p2.personid = ru.personid
                        WHERE ".$bboxQry." a.entitytype = 'story' ".$searchQryStrActStory." ".$dateQryStrAct." ".$photoQryStrAct." ORDER BY a.title LIMIT 2000;";   
                        //AND (LOWER(a.descr) LIKE ('%".$search."%') OR LOWER(a.title) LIKE ('%".$search."%')  OR p.lnames LIKE ('%".$search."%')) ".$dateQryStrAct." ".$photoQryStrAct." ORDER BY a.title LIMIT 2000;"; 
                      
    
    $tooltip_word_count = 40;
    
    if($f == 'json' || $f == 'pjson'){//if no format was set or json was requested, issue the query and format the results as json.
    	//============== change webuser password when gis-core is accessible ==================
    	//$link = pg_connect("host=gis-core.sabu.mtu.edu port=5432 dbname=giscore user=webuser password=sp@ghetti") or die('cannot connect to db');
    	$link = pg_connect("host=portal1-geo.sabu.mtu.edu port=5432 dbname=giscore user=webuser password=sp@ghetti") or die('cannot connect to db');
    	//====================================================
    	$activePersonResult = pg_query($link, $activePersonQuery) or die('query error: '.$activePersonQuery);
    	//$inactivePersonResult = pg_query($link, $inactivePersonQuery) or die('query error: '.$inactivePersonQuery);
        $activePlaceResult = pg_query($link, $activePlaceQuery) or die('query error: '.$activePlaceQuery);
    	//$inactivePlaceResult = pg_query($link, $inactivePlaceResult) or die('query error: '.$inactivePlaceResult);
        $activeStoryResult = pg_query($link, $activeStoryQuery) or die('query error: '.$activeStoryQuery);
         
    
    	// build a JSON feature collection array. should think about making this a GeoJSON in future.
    	$results = array();
       // $results['length'] = 0;
        $active = array();
        $active['length'] = 0;
        
    	//loop through rows to fetch person data into arrays
    	if($type == 'people' || $type == 'everything'){
    	    $people = array();
    	    $people_count = pg_num_rows($activePersonResult);
    	    $people['length'] = $people_count;
    	    $people_results = array();
    	    
    		while ($row = pg_fetch_assoc($activePersonResult)){
    			$properties = $row;
    			if(str_contains(strtolower($row['stitle']), strtolower($row['ptbl_name']))){
    			    $ftip = ucwords(strtolower(implode(" ", array_slice( explode(" ", str_replace("\n"," ", $row['descr'])), 0, $tooltip_word_count) ) ));
    			}else{
    			     $ftip = ucwords(strtolower(implode(" ", array_slice( explode(" ", str_replace("\n"," ", 'AKA: '. $row['ptbl_name']. ', ' . $row['descr'])), 0, $tooltip_word_count) ) ));
    			}
    			$person = array(
    			        
    			        "id" => $row['id'],
    			        "recnumber" => $row['recnumber'],
    			        "title" => ucwords(strtolower($row['stitle'] .', '.$row['min_year'].' '.$row['loctype'])),
    			        "tooltip" => $ftip,//ucwords(strtolower(implode(" ", array_slice( explode(" ", str_replace("\n"," ", $row['descr'])), 0, $tooltip_word_count) ) )),
    			        "loctype" => ucwords($row['loctype']),
    			        "markerid" => $row['markerid'],
    			        "x" => $row['lon'], 
    			        "y" => $row['lat'],
    			        "map_year" => $row['min_year'],
    			        "method" => $method
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
    			        
    			        "id" => $row['id'],
    			        "recnumber" => $row['recnumber'],
    			        "title" => ucwords(strtolower($row['title'])),
    			        "tooltip" => ucwords(strtolower(implode(" ", array_slice( explode(" ", $row['title']), 0, $tooltip_word_count) ) )),
    			        "loctype" => $row['loctype'],
    			        "markerid" => $row['markerid'],
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
    			        
    			        "id" => $row['id'],
    			        "recnumber" => $row['recnumber'],
    			        "title" => ucwords(strtolower($row['title'])),
    			        "tooltip" => ucwords(strtolower(implode(" ", array_slice( explode(" ", str_replace("\n"," ", $row['descr'])), 0, $tooltip_word_count) ) )),
    			        "loctype" => $row['loctype'],
    			        "markerid" => $row['markerid'],
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
    
        //$results['length'] = $active['length'];
        $results['active'] = $active;
        
    	//$json = array('active' => $active);
    	$json = $results;
    	
         pg_close($link);
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
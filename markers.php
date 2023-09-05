<?php
// markers2.php
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
   // $search = isset($object->search) ? trim(str_replace("\'","",$object->search)) : "finlandia";
    $search = strtolower($search);
    
     //explode the search terms
    $full_detail_search = false; 
    $search_arr = explode(' ', $search, 3);
    //if(count($search_arr) > 2){ 
        //array_pop($search_arr);
        //$full_detail_search = true;
        
    //} //only take the first two terms if more than two are sent.
    
    
    $gsize = isset($object->size) ? str_replace("\'","",$object->size) : 10;
    $valid_sizes = array("10","1","01",10,1,01);
    if (!in_array($gsize,$valid_sizes)){$gsize = 10;}
    $dates = isset($object->filters->date_range) ? str_replace("\'","",$object->filters->date_range) : '';
    $photos = isset($object->filters->photos) ? str_replace("\'","",$object->filters->photos) : 'false';
    $type = isset($object->filters->type) ? str_replace("\'","",$object->filters->type) : 'everything';
    $return_inactive = isset($object->inactive) ? str_replace("\'","",$object->inactive) : 'true';
    $f = isset($_GET['f']) ? str_replace("\'","",$_GET['f']) : 'json'; //default is json
    $p = isset($_GET['p']) ? str_replace("\'","",$_GET['p']): 'all'; //options are person, place, business, story, all. default is all
    
    
    
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
    
    if ($type == 'people'){
        $typeQryStrAct = "AND a.typedescr = 'people' ";
        $typeQryStrInact = "OR a.typedescr != 'people' ";
    }else if ($type == 'places'){
        $typeQryStrAct = "AND a.typedescr IN ('building', 'places') ";
        $typeQryStrInact = "OR a.typedescr NOT IN ('building', 'places')  ";
    }else if ($type == 'stories'){
        $typeQryStrAct = "AND a.typedescr = 'stories' ";
        $typeQryStrInact = "OR a.typedescr != 'stories' ";
    }else if ($type == 'all' || $type == 'everything'){
        $typeQryStrAct = "";
        $typeQryStrInact = "";
    } else { 
        echo ('Invalid type requested; Valid types are people, places, stories, or everything');
    }
    
    if ($object->geometry) {
        $bboxQry = " st_contains((sde.st_envelope(sde.st_geometry('Linestring(".$object->geometry->xmin." ".$object->geometry->ymin.", ".$object->geometry->xmax." ".$object->geometry->ymax.")',".$object->geometry->spatialReference->wkid."))), a.shape)  ";
    }else{
        $bboxQry = " 'a' = 'a' ";
    }
    
    if ($search_arr){
        if(count($search_arr)>0 && count($search_arr)<3 ){
            //search for the terms in original order against the record person name (in the title) and the person table name (p2). Then invert search terms; 2 terms: 1->2 and 2->1; 3 terms 1->3, 3->1, 2 remains. 
            // OR explode the search terms and search for each individually in the title and concatenated p2 name?
            
            //set the prefix:
            $searchQryStrAct = "AND( "; // AND( (search title parts) OR (search p2 name parts))
            $searchQryStrInact = "AND(( "; // AND( (search title parts) OR (search p2 name parts))
            $searchTitleParts = "("; // put in a leading paren
            $searchP2nameParts = "(";
            $searchDescrParts = "(";
            
            $searchTitlePartsInact = "("; // put in a leading paren
            $searchP2namePartsInact = "(";
            $searchDescrPartsInact = "(";
            
            foreach ($search_arr as $term){
                //search for each term in the title, first name, and last name. Concat all those together to make a more concise statement. will find the term in any of title, fname, lname.
                $searchTitleParts =  $searchTitleParts . " lower(a.title) LIKE '%".$term."%' AND ";
                $searchP2nameParts = $searchP2nameParts . " lower(concat(p2.fnames, ' ', p2.lnames)) LIKE '%".$term."%' AND ";
                $searchDescrParts = $searchDescrParts . " lower(a.descr) LIKE '%".$term."%' AND ";
                
                $searchTitlePartsInact =  $searchTitlePartsInact . " lower(a.title) NOT LIKE '%".$term."%' OR ";
                $searchP2namePartsInact = $searchP2namePartsInact . " lower(concat(p2.fnames, ' ', p2.lnames))NOT LIKE '%".$term."%' OR ";
                $searchDescrPartsInact = $searchDescrPartsInact . " lower(a.descr) NOT LIKE '%".$term."%' OR ";
            }
            
            $searchTitleParts = rtrim($searchTitleParts,'AND ') .")"; //take out trainling AND and put in a closing paren
            $searchP2nameParts = rtrim($searchP2nameParts,'AND ') .")";
            $searchDescrParts = rtrim($searchDescrParts,'AND ') ." AND a.typedescr = 'stories')";
            
            $searchTitlePartsInact = rtrim($searchTitlePartsInact,'OR ') .")"; //take out trainling AND and put in a closing paren
            $searchP2namePartsInact = rtrim($searchP2namePartsInact,'OR ') .")";
            $searchDescrPartsInact = rtrim($searchDescrPartsInact,'OR ') .")";
            
            $searchQryStrAct = $searchQryStrAct . $searchTitleParts . " OR " . $searchP2nameParts . " OR " . $searchDescrParts . " OR (LOWER(a.title) LIKE ('%".$search."%') OR LOWER(a.descr) LIKE ('%".$search."%')) ) "; //stitch the search strings together and add a closing paren for the combined statement. 
            $searchQryStrInact = $searchQryStrInact . $searchTitlePartsInact . " AND " . $searchP2namePartsInact . " AND " . $searchDescrPartsInact . ") ";
            
        } elseif (count($search_arr)>2 ) {
            $searchQryStrAct = "AND ( lower(a.title) LIKE ('%".$search."%') OR LOWER(a.descr) LIKE ('%".$search."%')) "; // this happens on a 'direct search' by the app to display the full detail item
            $searchQryStrInact = "AND (( lower(a.title) NOT LIKE ('%".$search."%') AND LOWER(a.descr) NOT LIKE ('%".$search."%')) ";
        }else{ 
            $searchQryStrAct = "";
            $searchQryStrInact = "(";
    }
    }else{ 
        $searchQryStrAct = "";
        $searchQryStrInact = "(";
    }
    
    
       $activeQuery = "SELECT COUNT(a.recordid) count, a.loccode, AVG(ST_X(a.shape)) lon, AVG(ST_Y(a.shape)) lat,
                        CASE WHEN COUNT(DISTINCT a.typedescr) > 1 THEN 'everything' ELSE MAX(a.typedescr) END AS \"type\"
    	                FROM grf.kett_record_locs a
    	                LEFT JOIN grf.kett_person_record_union p ON p.linkedrecordid = a.recordid AND p.tablename != 'Sanborn ' 
                        LEFT JOIN grf.kett_people p2 ON p2.personid = p.personid
    	                WHERE ".$bboxQry." ".
    	                $searchQryStrAct . " " .
    	                $photoQryStrAct." ".$dateQryStrAct."  ".$typeQryStrAct."  
    	                GROUP BY a.loccode;";
    	                //(LOWER(a.title) LIKE ('%".$search."%') OR LOWER(a.descr) LIKE ('%".$search."%')) ".
        
      $inactiveQuery = "SELECT COUNT(a.recordid) count, a.loccode, AVG(ST_X(a.shape)) lon, AVG(ST_Y(a.shape)) lat,
                        CASE WHEN COUNT(DISTINCT a.typedescr) > 1 THEN 'everything' ELSE MAX(a.typedescr) END AS \"type\"
    	                FROM grf.kett_record_locs a
    	                LEFT JOIN grf.kett_person_record_union p ON p.linkedrecordid = a.recordid AND p.tablename != 'Sanborn ' 
                        LEFT JOIN grf.kett_people p2 ON p2.personid = p.personid
                        WHERE ".$bboxQry." ".
                        $searchQryStrInact . " " .
                        $photoQryStrInact." ".$dateQryStrInact." ".$typeQryStrInact."  )
                        group by a.loccode;"; 
                        
                        //(LOWER(a.title) NOT LIKE ('%".$search."%') AND LOWER(a.descr) NOT LIKE ('%".$search."%') ".
                        // AND (a.date_range < ".$dateRange[0]." OR a.date_range > ".$dateRange[1].")
            
        
    
    if($f == 'json' || $f == 'pjson'){//if no format was set or json was requested, issue the query and format the results as json.
    	//============== change webuser password when gis-core is accessible ==================
    	$link = pg_connect("host=portal1-geo.sabu.mtu.edu port=5432 dbname=giscore user=webuser password=sp@ghetti") or die('cannot connect to db');
    	//====================================================
    	$activeResult = pg_query($link, $activeQuery) or die('query error: '.$activeQuery);
    	
    	// build a JSON feature collection array. should think about making this a GeoJSON in future.
    	$results = array();
        $activeids = array();
    	//loop through rows to fetch person data into arrays
    	if($p == 'person' || $p == 'all'){
    	    $active = array();
    	    $active['length'] = pg_num_rows($activeResult);
    	    $active_results = array();
    		while ($row = pg_fetch_assoc($activeResult)){
    			$properties = $row;
    			//$centroid = array('centroid' => $row['grid_id']);//is this used?
    			
    			//if($row['type'] == 'person'){
                //    $enttype = 'people';
                //}else if ($row['type'] == 'building'|| $row['type'] == 'place'|| $row['type'] == 'Settlement'){
                //    $enttype = 'places';
                //}else if ($row['type'] == 'story'){
                //    $enttype = 'stories';
                //}else{
                //    $enttype = $row['type'];
                //}
    			
    			$details = array(
    			        
    			        "id" => $row['loccode'],
    			        "type" => $row['type'], //$enttype,
    			        "x" => $row['lon'], 
    			        "y" => $row['lat'],
    			        "count" => $row['count']
    			       
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
    	if($return_inactive == 'true'){
    	    
    	    $inactiveResult = pg_query($link, $inactiveQuery) or die('query error: '.$inactiveQuery);
    	
        	if($p == 'person' || $p == 'all'){
        	    $inactive = array();
        	    $inactive['length'] = pg_num_rows($inactiveResult);
        	    $inactive_results = array();
        		while ($row = pg_fetch_assoc($inactiveResult)){
        			$properties = $row;
        			if (!in_array($row['loccode'],$activeids)){
        			    //$centroid = array('centroid' => $row['grid_id']);
        			    
        		    	//if($row['type'] == 'person'){
                        //    $enttype = 'people';
                        //}else if ($row['type'] == 'building' || $row['type'] == 'place'){
                        //    $enttype = 'places';
                        //}else if ($row['type'] == 'story'){
                        //    $enttype = 'stories';
                        //}else{
                        //    $enttype = $row['type'];
                        //}
                        
            			$details = array(
            			        
            			        "id" => $row['loccode'],
            			        "type" => $row['type'], //$enttype,
            			        "x" => $row['lon'], 
            			        "y" => $row['lat'],
            			        "count" => $row['count']
            			        
            			);
            			array_push($inactive_results, $details);
            			//array_push($active, $properties);
            			//array_push($active, $centroid);
        			}
        		}
                $inactive['results'] = $inactive_results;
                $results['inactive'] = $inactive;
    	    }
    	}
    	//$json = array('active' => $active);
    	$json = $results;
    	
        @ pg_close($link);
    }else{	//if format was set to help, don't issue the query but send user this text with the query that would have been submitted had they selected json format
    	$json = "you created this query: ".$activeQuery." and ".$inactiveQuery;
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
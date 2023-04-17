<?php
// grid2.php
// webservice to search grid description for text, for a certain time period, and certain filters like 'photos', and 'featured'
// designed at Michigan Tech in Houghton, Michigan, in cooperation with Chris Marr at Monte Consulting

// Make sure Content-Type is application/json 
$content_type = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
if (stripos($content_type, 'application/json') === false) {
  throw new Exception('Content-Type must be application/json');
}
// get variables and decode
$body = file_get_contents("php://input");
$object = json_decode($body, false);

$search = isset($object->search) ? str_replace("\'","",$object->search) : 0;
$gid = isset($object->id) ? str_replace("\'","",$object->id) : 0;
$dates = isset($object->filters->date_range) ? str_replace("\'","",$object->filters->date_range) : '';
$photos = isset($object->filters->photos) ? str_replace("\'","",$object->filters->photos) : 'false';
$type = isset($object->filters->type) ? str_replace("\'","",$object->filters->type) : 'all';
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

//query construction for different groups below... 

    $activePersonQuery = "SELECT p.personid \"id\", a.recordid recnumber, a.descr title
                    FROM grf.kett_records_with_grids a 
                    LEFT JOIN grf.kett_person_record_union p ON p.linkedrecordid = a.recordid
                    WHERE a.entitytype = 'person' AND LOWER(a.grid_id) = lower('".$gid."') AND LOWER(a.descr) LIKE lower('%".$search."%') ".$dateQryStrAct." ".$photoQryStrAct." ;"; 
                  
    //$inactivePersonQuery = "SELECT p.personid \"id\", a.recordid recnumber, a.descr title FROM grf.kett_records_with_grids a LEFT JOIN grf.kett_person_record_union p ON p.linkedrecordid = a.recordid WHERE a.entitytype = 'person' AND LOWER(a.grid_id) = lower('".$gid."') AND (".$dateQryStrInact." ".$photoQryStrInact." );"; 
                    
    $activePlaceQuery = "SELECT a.recordid recnumber, a.descr title
                    FROM grf.kett_records_with_grids a 
                    WHERE a.entitytype = 'building' AND LOWER(a.grid_id) = lower('".$gid."') AND LOWER(a.descr) LIKE lower('%".$search."%') ".$dateQryStrAct." ".$photoQryStrAct." ;"; 
                  
   // $inactivePlaceQuery = "SELECT a.recordid recnumber, a.descr title FROM grf.kett_records_with_grids a WHERE a.entitytype = 'building' AND LOWER(a.grid_id) = lower('".$gid."') AND (".$dateQryStrInact." ".$photoQryStrInact." );"; 
                    
     $activeStoryQuery = "SELECT a.recordid recnumber, a.descr title
                    FROM grf.kett_records_with_grids a 
                    WHERE a.entitytype = 'story' AND LOWER(a.grid_id) = lower('".$gid."') AND (LOWER(a.descr) LIKE lower('%".$search."%') OR LOWER(a.title) LIKE lower('%".$search."%') ) ".$dateQryStrAct." ".$photoQryStrAct." ;"; 
                  


if($f == 'json' || $f == 'pjson'){//if no format was set or json was requested, issue the query and format the results as json.
	//============== change webuser password when gis-core is accessible ==================
	$link = pg_connect("host=gis-core.sabu.mtu.edu port=5432 dbname=giscore user=webuser password=sp@ghetti") or die('cannot connect to db');
	//====================================================
	$activePersonResult = pg_query($link, $activePersonQuery) or die('query error: '.$activePersonQuery);
	//$inactivePersonResult = pg_query($link, $inactivePersonQuery) or die('query error: '.$inactivePersonQuery);
    $activePlaceResult = pg_query($link, $activePlaceQuery) or die('query error: '.$activePlaceQuery);
	//$inactivePlaceResult = pg_query($link, $inactivePlaceResult) or die('query error: '.$inactivePlaceResult);
    $activeStoryResult = pg_query($link, $activeStoryQuery) or die('query error: '.$activeStoryQuery);
     

	// build a JSON feature collection array. should think about making this a GeoJSON in future.
	$results = array();

	//loop through rows to fetch person data into arrays
	if($type == 'people' || $type == 'all'){
	    $person = array();
		while ($row = pg_fetch_assoc($activePersonResult)){
			$properties = $row;
			$details = array(
			        
			        "id" => $row['id'],
			        "recnumber" => $row['recnumber'],
			        "title" => ucwords(strtolower($row['title']))
			        
			);
			array_push($person, $details);
		
		}
        $results['people'] = $person;
	}
	
	//loop through rows to fetch place data into arrays
	if($type == 'places' || $type == 'all'){
	    $place = array();
		while ($row = pg_fetch_assoc($activePlaceResult)){
			$properties = $row;
			$details = array(
			        
			        "recnumber" => $row['recnumber'],
			        "title" => $row['title']
			        
			);
			array_push($place, $details);
		
		}
        $results['places'] = $place;
	}
	
	
	//loop through rows to fetch story data into arrays
	if($type == 'stories' || $type == 'all'){
	    $story = array();
		while ($row = pg_fetch_assoc($activeStoryResult)){
			$properties = $row;
			$details = array(
			        
			        "recnumber" => $row['recnumber'],
			        "title" => $row['title']
			        
			);
			array_push($story, $details);
		
		}
        $results['stories'] = $story;
	}
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

header('Content-type: application/json');
//header('Access-Control-Allow-Origin: *');
header('Access-Control-Max-Age: 600');
if($f == 'pjson' ){
    echo json_encode($json, JSON_PRETTY_PRINT);
} else { 
    echo json_encode($json);
   
}
//disconnect db

?>
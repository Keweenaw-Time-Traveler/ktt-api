<?php
// related_content.php
// webservice to search content in the databases related to the entity_id received, within the same date range for the map year received
// designed at Michigan Tech in Houghton, Michigan, in cooperation with Chris Marr at Monte Consulting

// Make sure Content-Type is application/json 
//$content_type = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
//if (stripos($content_type, 'application/json') === false) {
//  throw new Exception('Content-Type must be application/json');
//}
ini_set('memory_limit', '150M');
// get variables and decode
$method = $_SERVER['REQUEST_METHOD'];
if($method != 'OPTIONS'){
    
    $body = file_get_contents("php://input");
    $object = json_decode($body, false);
    $text_to_strip = array("\'","=",";","<",">",".","/");//attempt to prevent injection, but not recursive, need to improve
    
    $eid = isset($object->id) ? str_replace("\'","",$object->id) : '40F819D5-D072-4E9A-A55A-4E69A9B47F36';
    $mapYear = isset($object->mapyear) ? str_replace("\'","",$object->mapyear) : '1917';
    $markerid = isset($object->markerid) ? str_replace("\'","",$object->markerid) : '1';
    
    $f = isset($_GET['f']) ? str_replace("\'","",$_GET['f']) : 'json'; //default is json
    $p = isset($_GET['p']) ? str_replace("\'","",$_GET['p']): 'all'; //options are person, place, business, story, all. default is all
    
    $etypeQuery = "SELECT entitytype etype FROM grf.kett_record_locs WHERE recordid = '".$eid."';";
    
    $familyQuery = "SELECT p.personid personid, c.uniqueid recnumber, 'home' loctype, concat(c.namefirst, ' ', c.namelast) title, c.age, c.censusyear, ST_X(l.shape) LON, ST_Y(l.shape) LAT, l.loccode markerid
                    FROM grf.kett_census_public_tbl c
                    JOIN grf.kett_record_locs l ON l.recordid = c.uniqueid
                    JOIN grf.kett_person_record_union u ON u.linkedrecordid = c.uniqueid 
                    JOIN grf.kett_people p ON p.personid = u.personid
                    WHERE c.serialp IN (SELECT c2.serialp FROM grf.kett_census_public_tbl c2 JOIN grf.kett_person_record_union u2 ON u2.linkedrecordid = c2.uniqueid WHERE u2.personid = '".$eid."')
                    AND c.censusyear BETWEEN (SELECT min_year FROM grf.kett_date_segments WHERE rep_year = '".$mapYear."') AND (SELECT max_year FROM grf.kett_date_segments WHERE rep_year = '".$mapYear."')
                    AND p.personid != '".$eid."' ORDER BY title, age;";
                    
                    
    $classmateQuery = "SELECT u.personid, s.nuid_src_y recnumber, concat(s.firstname, ' ', s.lastname) title, 
                    CASE WHEN lh.loctype is null then ls.loctype ELSE lh.loctype END as loctype,
                    CASE WHEN lh.loctype is null then ST_X(ls.shape) ELSE ST_X(lh.shape) END as LON,
                    CASE WHEN lh.loctype is null then ST_Y(ls.shape) ELSE ST_Y(lh.shape) END as LAT,
                    CASE WHEN lh.loctype is null then ls.loccode ELSE lh.loccode END as markerid
                    FROM grf.kett_schoolrec1918_input s 
                    LEFT JOIN grf.kett_record_locs ls ON ls.recordid = s.nuid_src_y AND ls.loctype = 'school'
                    LEFT JOIN grf.kett_record_locs lh ON lh.recordid = s.nuid_src_y AND lh.loctype = 'home'
                    JOIN grf.kett_person_record_union u ON u.linkedrecordid = s.nuid_src_y
                    WHERE s.schoolnum = (SELECT s2.schoolnum FROM grf.kett_schoolrec1918_input s2 JOIN grf.kett_person_record_union u2 ON u2.linkedrecordid = s2.nuid_src_y WHERE u2.personid = '".$eid."')
    	            AND s.teachernam = (SELECT s2.teachernam FROM grf.kett_schoolrec1918_input s2 JOIN grf.kett_person_record_union u2 ON u2.linkedrecordid = s2.nuid_src_y	WHERE u2.personid = '".$eid."')
    	            AND s.grade = (SELECT s2.grade FROM grf.kett_schoolrec1918_input s2 JOIN grf.kett_person_record_union u2 ON u2.linkedrecordid = s2.nuid_src_y	WHERE u2.personid = '".$eid."')
                    AND s.schoolyear BETWEEN (SELECT min_year FROM grf.kett_date_segments WHERE rep_year = '".$mapYear."') AND (SELECT max_year FROM grf.kett_date_segments WHERE rep_year = '".$mapYear."') 
                    AND u.personid != '".$eid."' ORDER BY title;";
    
    $occupantsQuery = "SELECT u.personid, min(l.recordid) recnumber, min(l.loctype) loctype, min(l.stitle) title, min(ST_X(l.shape)) LON, min(ST_Y(l.shape)) LAT, l.loccode markerid
                    FROM grf.kett_record_locs l 
                    JOIN grf.kett_person_record_union u ON u.linkedrecordid = l.recordid
                    JOIN grf.kett_people p ON p.personid = u.personid
                    WHERE l.entitytype = 'person' AND l.loccode = '".$markerid."' AND l.byear BETWEEN (SELECT min_year FROM grf.kett_date_segments WHERE rep_year =  '".$mapYear."') AND (SELECT max_year FROM grf.kett_date_segments WHERE rep_year =  '".$mapYear."')
                    AND u.personid != '".$eid."' GROUP BY u.personid, l.loccode ORDER BY title;";
    
    $placesQuery_person = "SELECT DISTINCT b.recordid, b.entitytype, l.loctype, b.byear, b.eyear, 
                    --CASE WHEN trim(b.title) = ',' THEN CONCAT(b.geodescr, ' ', b.eyear) ELSE CONCAT(b.title,' ',b.geodescr, ' ', b.eyear) END title,
                    b.title title,
                    b.descr, b.geotype, b.geodescr, b.loccode, ST_X(b.shape) LON, ST_Y(b.shape) LAT, l.loccode markerid 
    	            FROM grf.kett_record_locs l
                	JOIN grf.kett_person_record_union u ON u.linkedrecordid = l.recordid
                	JOIN grf.kett_record_locs b ON b.loccode = l.loccode
                	where  b.entitytype IN ('building', 'Settlement') 
                	AND l.byear BETWEEN (SELECT min_year FROM grf.kett_date_segments WHERE rep_year = '".$mapYear."') AND (SELECT max_year FROM grf.kett_date_segments WHERE rep_year = '".$mapYear."')
                	AND b.byear BETWEEN (SELECT min_year FROM grf.kett_date_segments WHERE rep_year = '".$mapYear."') AND (SELECT max_year FROM grf.kett_date_segments WHERE rep_year = '".$mapYear."')
                	AND u.personid = '".$eid."' AND l.loccode != '".$eid."' ORDER BY title;";
    
    $placesQuery_story = "SELECT DISTINCT b.recordid, b.entitytype, b.loctype, b.byear, b.eyear, b.title title,
                    --CASE WHEN trim(b.title) = ',' THEN CONCAT(b.geodescr, ' ', b.eyear) ELSE CONCAT(b.title,' ',b.geodescr, ' ', b.eyear) END title,
                    b.descr, b.geotype, b.geodescr, b.loccode, ST_X(b.shape) LON, ST_Y(b.shape) LAT, b.loccode markerid 
    	            FROM grf.kett_record_locs b
                	where  b.entitytype = 'building' 
                	--AND b.byear BETWEEN (SELECT min_year FROM grf.kett_date_segments WHERE rep_year = '".$mapYear."') AND (SELECT max_year FROM grf.kett_date_segments WHERE rep_year = '".$mapYear."')
                	AND b.loccode = '".$markerid."' ORDER BY title;";
                	
    $placesQuery_building = "SELECT l.recordid, l.entitytype, l.loctype, l.byear, l.eyear, l.title title, l.descr, l.geotype, l.geodescr, l.loccode, 
                    ST_X(l.shape) LON, ST_Y(l.shape) LAT, l.loccode markerid 
                    FROM grf.kett_record_locs l JOIN grf.kett_entity_entity_union u ON u.linkedbundlecode = l.loccode 
                    where l.entitytype = 'building' AND u.bundlecode = '".$eid."' ORDER BY l.title ASC;";
                	
    $storiesQuery = "SELECT b.recordid, b.entitytype, b.loctype, b.byear, b.eyear, b.title, b.descr, b.geotype, b.geodescr, b.loccode, ST_X(b.shape) LON, ST_Y(b.shape) LAT, b.loccode markerid 
    	            FROM grf.kett_record_locs b
                	where  b.entitytype = 'story' -- AND
                	-- b.byear BETWEEN (SELECT min_year FROM grf.kett_date_segments WHERE rep_year = '".$mapYear."') AND (SELECT max_year FROM grf.kett_date_segments WHERE rep_year = '".$mapYear."')
                	AND b.loccode = '".$markerid."'  AND b.recordid != '". $eid ."' ORDER BY title  ;";           	
    // get stories linked to a person...            	
    $storiesQuery_person = "SELECT b.recordid, b.entitytype, b.loctype, b.byear, b.eyear, b.title, b.descr, b.geotype, b.geodescr, b.loccode, ST_X(b.shape) LON, ST_Y(b.shape) LAT, b.loccode markerid 
                    FROM grf.kett_record_locs b
                    LEFT JOIN grf.kett_person_record_union pu ON pu.linkedrecordid = b.recordid  -- get the other records associated with this person
                    where  b.entitytype = 'story' AND pu.personid = '". $eid ."' AND b.recordid != '". $eid ."' ORDER BY title ;";
                    
    $personQuery_story = "SELECT b.recordid, rp.personid, b.entitytype, b.loctype, b.byear, b.eyear, b.title, b.descr, b.geotype, b.geodescr, b.loccode, ST_X(b.shape) LON, ST_Y(b.shape) LAT, b.loccode markerid 
                    FROM grf.kett_record_locs b
                    LEFT JOIN grf.kett_person_record_union pu ON pu.linkedrecordid = b.recordid -- get the person associated with this record
                    LEFT JOIN grf.kett_person_record_union rp ON rp.personid = pu.personid
                    where  b.entitytype = 'person' AND rp.linkedrecordid = '". $eid ."' ORDER BY title ;";                 
    
    
    if($f == 'json' || $f == 'pjson'){//if no format was set or json was requested, issue the query and format the results as json.
    	
    	// put in bit to identify the etype and change the queries used based on that; Story should do a spatial join to places, non-story should use the eid, maybe building should use spatial?? 
    	// Mainly used to enable stories to get things they overlap with, since they may have a different bundle_code... 
    	
    	
    	$link = pg_connect("host=portal1-geo.sabu.mtu.edu port=5432 dbname=giscore user=webuser password=sp@ghetti") or die('cannot connect to db');
    	//====================================================
    	
    	$etypeResult = pg_query($link, $etypeQuery) or die('query error: '.$etypeQuery);
        if(substr($eid,-4,4) == 'bldg'){
            $etype = 'building';
        }else{
            while ($row = pg_fetch_assoc($etypeResult)){
    	        $etype = $row['etype'];
    	        }
        }
    	
    	
    	$familyResult = pg_query($link, $familyQuery) or die('query error: '.$familyQuery);
    	$classmateResult = pg_query($link, $classmateQuery) or die('query error: '.$classmateQuery);
    	$occupantsResult = pg_query($link, $occupantsQuery) or die('query error: '.$occupantsQuery);
    	if($etype == 'story'){	
    	    $placesResult = pg_query($link, $placesQuery_story) or die('query error: '.$placesQuery_story);
    	}elseif($etype == 'building'){
    	    $placesResult = pg_query($link, $placesQuery_person) or die('query error: '.$placesQuery_person);
    	    $otherPlacesResult = pg_query($link, $placesQuery_building) or die('query error: '.$placesQuery_building);
    	}else{
    	    $placesResult = pg_query($link, $placesQuery_person) or die('query error: '.$placesQuery_person);
    	}
        $storiesResult = pg_query($link, $storiesQuery) or die('query error: '.$storiesQuery);
        $storiesPersonResult = pg_query($link, $storiesQuery_person) or die('query error: '.$storiesQuery_person);
        $personStoryResult = pg_query($link, $personQuery_story) or die('query error: '.$personQuery_story);
    
    
    	// build a JSON feature collection array. should think about making this a GeoJSON in future.
    	$results = array();
        $results['length'] = 0;
        $people = array();
        $peopleGroups = array();
        
         //=====================================================//
    	//loop through rows to fetch family data into arrays
    	
        $family = array();
        $family_count = pg_num_rows($familyResult);
        $family['title'] = 'Family';
        $family['length'] = $family_count;
        $family['map']= false;
        $family_results = array();
       
    	while ($row = pg_fetch_assoc($familyResult)){
    		$properties = $row;
    		$member = array(
    		        
    		        "id" => $row['personid'],
    		        "recnumber" => $row['recnumber'],
    		        "title" => ucwords(strtolower($row['title'].', '.$row['age'])),//.' in '. $row['censusyear'])),
    		        "tooltip" => ucwords(strtolower($row['title'])),
    		        "loctype" => ucwords($row['loctype']),
    		        "markerid" => $row['markerid'],
    		        "x" => $row['lon'], 
    		        "y" => $row['lat'],
    		        "map_year" => $mapYear
    		);
    		array_push($family_results, $member);
    	
    	}
    	
    	$family['results'] = $family_results;
    	
    	if($family_count > 0){
    	    array_push($peopleGroups, $family);
    	    $results['length'] = $results['length'] + $family_count;
    	}
    	
    	//=====================================================//
    	//loop through rows to fetch classmate data into arrays
    	
        $classmate = array();
        $classmate_count = pg_num_rows($classmateResult);
        $classmate['title'] = 'Classmates';
        $classmate['length'] = $classmate_count;
        $classmate['map']= true;
        $classmate_results = array();
        
    	while ($row = pg_fetch_assoc($classmateResult)){
    		$properties = $row;
    		$student = array(
    		        
    		        "id" => $row['personid'],
    		        "recnumber" => $row['recnumber'],
    		        "title" => ucwords(strtolower($row['title'])),
    		        "loctype" => ucwords($row['loctype']),
    		        "markerid" => $row['markerid'],
    		        "x" => $row['lon'], 
    		        "y" => $row['lat'],
    		        "map_year" => $mapYear
    		);
    		array_push($classmate_results, $student);
    	
    	}
    	
    	$classmate['results'] = $classmate_results;
    	
    	if($classmate_count > 0){
    	    array_push($peopleGroups, $classmate);
    	    $results['length'] = $results['length'] + $classmate_count;
    	}
    	
    	//=====================================================//
    	//loop through rows to fetch classmate data into arrays
    	
        $occupants = array();
        $occupants_count = pg_num_rows($occupantsResult);
        $occupants['title'] = 'People at the Same Location';
        $occupants['length'] = $occupants_count;
        $occupants['map']= false;
        $occupants_results = array();
        
    	while ($row = pg_fetch_assoc($occupantsResult)){
    		$properties = $row;
    		$occupant = array(
    		        
    		        "id" => $row['personid'],
    		        "recnumber" => $row['recnumber'],
    		        "title" => ucwords(strtolower($row['title'])),
    		        "loctype" => ucwords($row['loctype']),
    		        "markerid" => $row['markerid'],
    		        "x" => $row['lon'], 
    		        "y" => $row['lat'],
    		        "map_year" => $mapYear
    		);
    		array_push($occupants_results, $occupant);
    	
    	}
    	
    	$occupants['results'] = $occupants_results;
    	
    	if($occupants_count > 0){
        	array_push($peopleGroups, $occupants);
        	$results['length'] = $results['length'] + $occupants_count;
    	}
    	
    	//=====================================================//
    	//loop through rows to fetch people related to a story
    	
        if($etype == 'story'){
            	$storylinks = array();
                $storylinks_count = pg_num_rows($personStoryResult);
                $storylinks['title'] = 'People Related to this Story';
                $storylinks['length'] = $storylinks_count;
                $storylinks['map']= false;
                $storylinks_results = array();
                
            	while ($row = pg_fetch_assoc($personStoryResult)){
            		$properties = $row;
            		$storylink = array(
            		        
            		        "id" => $row['personid'],
            		        "recnumber" => $row['recordid'],
            		        "title" => ucwords(strtolower($row['title'])),
            		        "loctype" => ucwords($row['loctype']),
            		        "markerid" => $row['markerid'],
            		        "x" => $row['lon'], 
            		        "y" => $row['lat'],
            		        "map_year" => $mapYear
            		);
            		array_push($storylinks_results, $storylink);
            	
            	}
            	
            	$storylinks['results'] = $storylinks_results;
            	
            	if($storylinks_count > 0){
                	array_push($peopleGroups, $storylinks);
                	$results['length'] = $results['length'] + $storylinks_count;
            	}
        }
    	
    	
        $people['groups'] =  $peopleGroups;
    	$results['people'] = $people;
    //============================================================//	
    	$places = array();
        $placesGroups = array();
    	
    	 //=====================================================//
    	//loop through rows to fetch home data into arrays
    	
    	//Get the places array; loop through and look for rows where loctype == 'home'; put each in a 'homes' array; Add 1 to a home_count for each record; add home_count to all places count and add all places count to results length
    	
        $homes = array();
        $homes_count = 0;
        $homes['title'] = 'Homes';
        $homes['length'] = 0;
        $homes['map']= true;
        $homes_results = array();
        
        $schools = array();
        $schools_count = 0;
        $schools['title'] = 'Schools';
        $schools['length'] = 0;
        $schools['map']= true;
        $schools_results = array();
        
        $buildings = array();
        $buildings_count = 0;
        $buildings['title'] = 'Buildings';
        $buildings['length'] = 0;
        $buildings['map']= true;
        $buildings_results = array();
        
        $othBuildings = array();
        $othBuildings_count = 0;
        $othBuildings['title'] = 'Other Buildings at this Location';
        $othBuildings['length'] = 0;
        $othBuildings['map']= true;
        $othBuildings_results = array();
        
    	while ($row = pg_fetch_assoc($placesResult)){
    		if($row['loctype'] == 'home'){
        		$home = array(
        		        
        		        "id" => $row['markerid'],
        		        "recnumber" => $row['recordid'],
        		        "title" => ucwords(strtolower($row['title'])),
        		        "loctype" => ucwords($row['loctype']),
        		        "markerid" => $row['markerid'],
        		        "x" => $row['lon'], 
        		        "y" => $row['lat'],
        		        "map_year" => $mapYear
        		);
        		array_push($homes_results, $home);
        		$homes_count = $homes_count + 1;
        	
        	}elseif($row['loctype'] == 'school'){
    		    $school = array(
    		        
    		        "id" => $row['markerid'],
    		        "recnumber" => $row['recordid'],
    		        "title" => ucwords(strtolower($row['title'])),
    		        "loctype" => ucwords($row['loctype']),
    		        "markerid" => $row['markerid'],
    		        "x" => $row['lon'], 
    		        "y" => $row['lat'],
    		        "map_year" => $mapYear
    		);
    		array_push($schools_results, $school);
    		$schools_count = $schools_count + 1;
    	
    	    }else{
    		    $building = array(
    		        
    		        "id" => $row['markerid'],
    		        "recnumber" => $row['recordid'],
    		        "title" => ucwords(strtolower($row['title'])),
    		        "loctype" => ucwords($row['loctype']),
    		        "markerid" => $row['markerid'],
    		        "x" => $row['lon'], 
    		        "y" => $row['lat'],
    		        "map_year" => $mapYear
    		);
    		array_push($buildings_results, $building);
    		$buildings_count = $buildings_count + 1;
    	    }
    	}
    	
    	if($etype == 'building'){
        	while ($row = pg_fetch_assoc($otherPlacesResult)){
        		$properties = $row;
        		$othBuilding = array(
        		        
        		        "id" => $row['markerid'],
        		        "recnumber" => $row['recordid'],
        		        "title" => ucwords(strtolower($row['title'])),
        		        "loctype" => ucwords($row['loctype']),
        		        "markerid" => $row['markerid'],
        		        "x" => $row['lon'], 
        		        "y" => $row['lat'],
        		        "map_year" => $mapYear
        		);
        		array_push($othBuildings_results, $othBuilding);
        		$othBuildings_count = $othBuildings_count + 1;
        	}
    	}
    	
    	$homes['length'] = $homes_count;
    	$homes['results'] = $homes_results;
    	
    	if($homes_count > 0){
    	    array_push($placesGroups, $homes);
    	    $results['length'] = $results['length'] + $homes_count;
    	}
    	
    	$schools['length'] = $schools_count;
    	$schools['results'] = $schools_results;
    	
    	if($schools_count > 0){
    	    array_push($placesGroups, $schools);
    	    $results['length'] = $results['length'] + $schools_count;
    	}
    	
    	$buildings['length'] = $buildings_count;
    	$buildings['results'] = $buildings_results;
    	
    	if($buildings_count > 0){
    	    array_push($placesGroups, $buildings);
    	    $results['length'] = $results['length'] + $buildings_count;
    	}
    	
    	$othBuildings['length'] = $othBuildings_count;
    	$othBuildings['results'] = $othBuildings_results;
    	
    	if($othBuildings_count > 0){
        	array_push($placesGroups, $othBuildings);
        	$results['length'] = $results['length'] + $othBuildings_count;
    	}
    	
    	
    	$places['groups'] = $placesGroups;
        $results['places'] = $places;
        	
    //============================================================//	
    	$stories = array();
        $storiesGroups = array();
    	
    	 //=====================================================//
    	//loop through rows to fetch home data into arrays
    	
    	//Get the stories array; loop through and look for rows where loctype == 'story'; put each in a 'stories' array; Add 1 to a home_count for each record; add story_count to all stories count and add all stories count to results length
    	//stories related to places
    	
        $xstories = array();
        $xstories_count = 0;
        $xstories['title'] = 'Stories At This Place';
        $xstories['length'] = 0;
        $xstories['map']= false;
        $xstories_results = array();
        	
        while ($row = pg_fetch_assoc($storiesResult)){
        		$story = array(
        		        
        		        "id" => $row['recordid'],
        		        "recnumber" => $row['recordid'],
        		        "title" => ucwords(strtolower($row['title'])),
        		        "loctype" => ucwords($row['loctype']),
        		        "markerid" => $row['markerid'],
        		        "x" => $row['lon'], 
        		        "y" => $row['lat'],
        		        "map_year" => $mapYear
        		);
        		array_push($xstories_results, $story);
        		$xstories_count = $xstories_count + 1;
        }
        
        $xstories['length'] = $xstories_count;
    	$xstories['results'] = $xstories_results;
    	
    	if($xstories_count > 0){
    	    array_push($storiesGroups, $xstories);
    	    $results['length'] = $results['length'] + $xstories_count;
    	}
    	
    	//stories related to people
    	
    	$pstories = array();
        $pstories_count = 0;
        $pstories['title'] = 'Stories About This Person';
        $pstories['length'] = 0;
        $pstories['map']= false;
        $pstories_results = array();
        	
        while ($row = pg_fetch_assoc($storiesPersonResult)){
        		$story = array(
        		        
        		        "id" => $row['recordid'],
        		        "recnumber" => $row['recordid'],
        		        "title" => ucwords(strtolower($row['title'])),
        		        "loctype" => ucwords($row['loctype']),
        		        "markerid" => $row['markerid'],
        		        "x" => $row['lon'], 
        		        "y" => $row['lat'],
        		        "map_year" => $mapYear
        		);
        		array_push($pstories_results, $story);
        		$pstories_count = $pstories_count + 1;
        }
        
        $pstories['length'] = $pstories_count;
    	$pstories['results'] = $pstories_results;
    	
    	if($pstories_count > 0){
    	    array_push($storiesGroups, $pstories);
    	    $results['length'] = $results['length'] + $pstories_count;
    	}
        
        $stories['groups'] = $storiesGroups;
        $results['stories'] = $stories;
        	
    	$json = $results;
    	
        @ pg_close($link);
    }else{	//if format was set to help, don't issue the query but send user this text with the query that would have been submitted had they selected json format
    	$json = "you created this query: family===>: ".$familyQuery." ========= Classmates ========> " .$classmateQuery ." ========= Occupants ========> " .$occupantsQuery . " ========= Places person ========> " . $placesQuery_person . "=====Places Story======> " . $placesQuery_story . "====== Stories ======>". $storiesQuery;
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
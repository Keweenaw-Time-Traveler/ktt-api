<?php
// full_details.php
// webservice to records from census for selected person and record
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
    //personid sets the person to look for data for
    $personid = isset($object->personid) ? str_replace("\'","",$object->personid) : 0;
    //recnumber is used to get the year to show data for; data is actually gleaned from multiple datasets
    $recnumber = isset($object->recnumber) ? str_replace("\'","",$object->recnumber) : 0;
    $isStory = False;
    $loctype = isset($object->loctype) ? str_replace("\'","",$object->loctype) : 'none';
    if(!in_array(strtolower($loctype), array('home','school'))) $loctype = 'home';
    $f = isset($_GET['f']) ? str_replace("\'","",$_GET['f']) : 'json'; //default is json
    
    //query construction for different groups below... 
    
    //put a query in here to the record_locs that gets the type, then alters output based on that. 
    
    $entityTypeQuery = "SELECT entitytype FROM grf.kett_record_locs WHERE recordid = '".$recnumber ."';";
    
    $personIdentityQuery = "SELECT a.uniqueid, max(a.attrsource) personsrc, max(case when a.attr IN ('namefirst','firstname','ch_firstname')  then a.val end) namefirst ,max(case when a.attr IN ('namelast','lastname','ch_lastname') then a.val end ) namelast FROM grf.kett_public_attr_val a 
                            WHERE a.uniqueid = '".$recnumber ."' AND attr IN ('namefirst','namelast','firstname','lastname','ch_firstname', 'ch_lastname') group by a.uniqueid ;";
                            
   // "SELECT a.uniqueid, a. FROM grf.kett_people a WHERE a.personid = '".$personid."';";
    //$personIdentityQuery = "SELECT a.personid, a.namelast, a.namefirst, a.birthplace, a.birthyear, a.personsrc FROM grf.kett_people a WHERE a.personid = '".$personid."';";
       
    $activeRecordQuery = "SELECT STRING_AGG(uniqueid,', ') uniqueid, min(a.attr) attr, 
                    CASE WHEN length(STRING_AGG(DISTINCT val, ', ')) < length(STRING_AGG(val, ', ')) THEN STRING_AGG(DISTINCT val, '; ') ELSE STRING_AGG( val, '; ') END val, 
                    STRING_AGG(ryear::varchar(4),', ') ryear, STRING_AGG(attrsource,', ') attrsource, d.attrdescr  
                    FROM grf.kett_public_attr_val a 
                    LEFT JOIN grf.kett_person_record_union b ON b.linkedrecordid = a.uniqueid
                    LEFT JOIN grf.kett_attr_descr d ON d.attr = a.attr
    	            WHERE ((b.personid = '".$personid."' OR a.uniqueid = '".$recnumber."') 
    	            AND a.byear <= (SELECT s.max_year FROM grf.kett_date_segments s JOIN grf.kett_record_locs l ON l.recordid = '".$recnumber."' WHERE s.min_year <= l.mapyear AND s.max_year >= l.mapyear LIMIT 1)
    	            AND a.eyear >= (SELECT s.min_year FROM grf.kett_date_segments s JOIN grf.kett_record_locs l ON l.recordid = '".$recnumber."' WHERE s.min_year <= l.mapyear AND s.max_year >= l.mapyear LIMIT 1))
    	            OR ((b.personid = '".$personid."' OR a.uniqueid = '".$recnumber."') AND a.attr IN('namelast','namefirst','firstname','lastname')) 
       	            OR ( a.uniqueid = '".$recnumber."' AND a.attrsource ilike 'story%')
    	            GROUP BY d.attrdescr ORDER BY d.attrdescr ASC;";
                      
    $availableRecordQuery = "SELECT distinct v.recordid, v.stitle, v.loccode markerid, v.entitytype etype, i.namelast lname, i.namefirst fname, v.byear, p.tablename source, v.loctype, lower(v.geotype) geotype, v.geodescr, p.rectype, ST_X(v.shape) lon, ST_Y(v.shape) lat, s.min_year, s.rep_year, s.max_year, s.url, s.map_type
    	            FROM grf.kett_record_locs v 
    	            LEFT JOIN grf.kett_person_record_union p ON v.recordid = p.linkedrecordid
    	            LEFT JOIN grf.kett_people i ON i.personid = p.personid
        	        JOIN grf.kett_date_segments s ON s.min_year <= v.byear AND s.max_year >= v.byear
    	            WHERE (p.personid = '".$personid."' OR v.recordid = '".$personid."') ORDER BY s.rep_year asc
    	            -- AND v.eyear <= (SELECT s.max_year FROM grf.kett_date_segments s JOIN grf.kett_record_locs l ON l.recordid = '".$recnumber."' WHERE s.min_year <= l.mapyear AND s.max_year >= l.mapyear LIMIT 1)
    	            -- AND v.byear >= (SELECT s.min_year FROM grf.kett_date_segments s JOIN grf.kett_record_locs l ON l.recordid = '".$recnumber."' WHERE s.min_year <= l.mapyear AND s.max_year >= l.mapyear LIMIT 1)
    	            ;";
    	            
    $recordAttachmentQuery = "SELECT a.attachmentid, a.rel_globalid, a.content_type, a.att_name, a.data_size, b.objectid
                	FROM grf.cchsdi_storypts__attach a
                	JOIN grf.cchsdi_storypts b ON b.globalid = a.rel_globalid
                	WHERE a.rel_globalid = '".$recnumber."';";	            
                        
     
    if($f == 'json' || $f == 'pjson'){//if no format was set or json was requested, issue the query and format the results as json.
    	//============== change webuser password when gis-core is accessible ==================
    	$link = pg_connect("host=portal1-geo.sabu.mtu.edu port=5432 dbname=giscore user=webuser password=sp@ghetti") or die('cannot connect to db');
    	//====================================================
    	$entityTypeQueryResult = pg_query($link, $entityTypeQuery) or die('query error: '.$entityTypeQuery);
    	$personIdentityResult = pg_query($link, $personIdentityQuery) or die('query error: '.$personIdentityQuery);
    	$activeRecordResult = pg_query($link, $activeRecordQuery) or die('query error: '.$activeRecordQuery);
    	$availableRecordResult = pg_query($link, $availableRecordQuery) or die('query error: '.$availableRecordQuery);
    	$recordAttachmentResult = pg_query($link, $recordAttachmentQuery) or die('query error: '.$recordAttachmentQuery);
    	
    	//find out what kind of thing this is...
    	$type = 'person'; //default setting in case we get a random id that doesn't match any records. 
    	while ( $row = pg_fetch_assoc($entityTypeQueryResult)){
    	    $type = $row['entitytype'];
    	}
    	
    	
       	// build a JSON feature collection array. should think about making this a GeoJSON in future.
    	$results = array();
       
    	//loop through rows to fetch person data into arrays
        //if(pg_num_rows($personIdentityQuery) > 0){$type = 'person';}else{$type = 'non-person';}
    	if($type == 'person' OR $type == 'story'OR $type == 'building' OR $type == 'Settlement'){
    	   	//details about the person (or, in the future, places or things) 
    	   while ($row = pg_fetch_assoc($personIdentityResult)){
    	        $title_name = ucwords(strtolower($row['namefirst']. " ". $row['namelast']));
    	        $title_src =  $row['personsrc'];
    	        $personDetails = array(
    	             array("title" => "Name", "value" => ucwords(strtolower($row['namefirst']. " ". $row['namelast'])), "tooltip" => $row['personsrc'])//,
    	            //array("title" => "Birth place", "value" => ucwords(strtolower($row['birthplace'])), "tooltip" => $row['personsrc']),
    	            //array("title" => "Birth year", "value" => $row['birthyear'], "tooltip" => $row['personsrc'])
    	            );
    	   }
    	  
    	   //list source records for this entity     
    	   $sources = array();
    	   
    	   
    	   if($type == 'person' OR $type == 'story' OR $type == 'building' OR $type == 'Settlement'){
    	   
        	   while ($row = pg_fetch_assoc($availableRecordResult)){
        	       if($row['etype'] == $type){
            	        if($row['recordid'] == $recnumber AND (($loctype == 'none') OR (strtolower($row['loctype']) == strtolower($loctype)) OR $row['etype'] != 'person') ){$highlighted = 'true';} else {$highlighted = 'false';}
            			if($row['etype'] == 'person'){ 
            			    if($row['geotype'] != 'building') $geotypeDescr = '(Approximate)'; else $geotypeDescr = '';
            	                if($row['rectype'] == 'directory') $recname = $row['source'] . " " . ucwords(strtolower( $row['byear'] ." - " . $row['loctype']) . " " . $geotypeDescr ."" ); else $recname = $row['source'] ." - " . ucwords(strtolower($row['loctype']) . " " . $geotypeDescr ."" );
            	                //if($row['rectype'] == 'directory') $recname = ucwords(strtolower($row['source'] . " " . $row['byear'] ." - " . $row['loctype']) . " " . $geotypeDescr ."" ); else $recname = ucwords(strtolower($row['source'] ." - " . $row['loctype']) . " " . $geotypeDescr ."" );
            	                $isStory = False;
            	            } elseif ($type == 'story') { 
            	                $recname = 'Story Location';
            	                $isStory = True;
            	            } elseif ($type == 'building') {
            	                $recname = ucwords(strtolower($row['rep_year'] ."  " . $row['map_type']));
            	                $isStory = False;
            	            } elseif ($type == 'Settlement') {
            	                $recname = ucwords(strtolower($row['rep_year'] ."  " . $row['map_type']) . " (Approximate)" );
            	                $isStory = False;
            	            }
            	        $source = array(
            	            "recname" => $recname,
            	            "historyname" => rtrim(ucwords(strtolower($row['stitle'] .', '.$row['loctype'])),', '),
            	            "recnumber" => $row['recordid'],
            	            "markerid" => $row['markerid'],
            	            "loctype" => $row['loctype'],
            	            "geodescr" => $row['geodescr'],
            	            "geotype" => $row['geotype'],
            	            "x" => $row['lon'], 
            			    "y" => $row['lat'],
            			    "map_year" => $row['rep_year'],
            			    "selected" => $highlighted
            	            );
            	        array_push($sources, $source);
            	        
            	        if($row['recordid'] == $recnumber AND (($loctype == 'none') OR (strtolower($row['loctype']) == strtolower($loctype)) OR $row['etype'] != 'person') ){
            	            
            	            $results['title'] = $title_name;
            	            //$results['title'] = ucwords(strtolower($row['fname'])) ." " . ucwords(strtolower($row['lname']));
            	            $geodescr = ucwords(strtolower($row['geodescr']));
            	            $geodescr = str_replace('Enumeration District', '', $geodescr);
            	            $geotype = $row['geotype'];
            	            $geotype = str_replace('building', 'Address', $geotype);
            	            $geosource = $row['source'];
            	            $results['geodescr'] = $geodescr;
            	            $results['geotype'] = $geotype;
            	            if($row['etype'] == 'person'){ 
            	                $etype = 'people';
            	            }elseif ($row['etype'] == 'building'){
            	                $results['title'] = ucwords(strtolower($row['geodescr']));
            	                //$etype = 'place';
            	                $etype = $row['etype'];
            	            }else{
            	                $etype = $row['etype'];
            	            }
            	            $results['type'] = $etype;
            	            $results['id'] = $personid;
            	            $results['loctype'] = $loctype;
            	            $results['map_year'] = $row['rep_year'];
            	        }
        	       }
        	    }// done fetching records list
        	    
        	    
    	   }else{
    	       //option for storys
    	       //$isStory = True;
    	   }
    	    // fetch attachments
    	    // setup a container for multiple attachments that will hold attachment objects
    	    $attachments = array();
    	    while ($row = pg_fetch_assoc($recordAttachmentResult)){    
    	        
    	        $attachment = array(
    	            "name" => $row['att_name'],
    	            "content_type" => $row['content_type'],
    	            "content_size" => $row['data_size'],
    	            //"attachmentid" => $row['attachmentid'],
    	            "url" => ("https://portal1-geo.sabu.mtu.edu:6443/arcgis/rest/services/KeweenawHSDI/story_pts_watts2/FeatureServer/0/".$row['objectid']."/attachments/".$row['attachmentid'])
    	            //"parentid" => $row['objectid']
    	            );
    	        array_push($attachments, $attachment);
    	    }// done with attachments
    	        
    	    //list attributes from the selected record
    	    $recordLocation = array();
    	    $recordLocation['title'] = "Location";
    	    $recordLocationData = array();
    	    
    	    //$demographicAttributeNames = array("censusyear", "namefirst", "firstname", "namelast", "lastname", "birthplace", "birthyear","mbirthplace", "fbirthplace","relhouse","sex","age","mstatus","race");   
    	    $recordLocationData = array();
    	    
    	    $recordDemographic = array();
    	    $recordDemographic['title'] = "Demographics";
    	    $demographicAttributeNames = array("censusyear", "namefirst", "firstname", "namelast", "lastname", "ch_firstname", "ch_lastname", "birthplace", "ch_birthplace", "birthyear","ch_birthyear","mbirthplace", "fbirthplace","relhouse","sex","age","mstatus","race");   
    	    $recordDemographicData = array();
    	    
    	    $recordOccupation = array();
    	    $recordOccupation['title'] = "Employment";
    	    $occupationAttributeNames = array("industry", "occupation", "ch_occupation", "ch_employer", "ch_department", "wrkclass");    
    	    $recordOccupationData = array();
    	    
    	    $recordEducation = array();
    	    $recordEducation['title'] = "Education";
    	    $educationAttributeNames = array("natvlang", "speaks", "speakeng", "canwrite", "ch_languagewritten", "canread", "school", "schoolname","teachernam", "schoolyear","grade");    
    	    $recordEducationData = array();
    	    
    	    $recordImmigration = array();
    	    $recordImmigration['title'] = "Immigration";
    	    $immigrationAttributeNames = array("citizenship", "year_immigrated", "ch_portofentry");    
    	    $recordImmigrationData = array();
    	    
    	    $recordHousing = array();
    	    $recordHousing['title'] = "Housing";
    	    $housingAttributeNames = array("ownership");    
    	    $recordHousingData = array();
    	    
    	    $recordStoryTitle = array();
    	    $recordStoryTitle['title'] = "Title";
    	    $storyTitleAttributeNames = array("title","userdate");    
    	    $recordStoryTitleData = array();
    	    
    	    $recordStory = array();
    	    $recordStory['title'] = "Story";
    	    $storyAttributeNames = array("description", "name");    
    	    $recordStoryData = array();
    	    
    	    $recordPlace = array();
    	    $recordPlace['title'] = "Place";
    	    $placeAttributeNames = array("address","place_nam","number_sto","bldguse","textfunc","textocc", "bldgmats", "textnote");    
    	    $recordPlaceData = array();
    	    
    	    $recordIdentifiers = array();
    	    $recordIdentifiers['title'] = "Identifier";
    	    $recordIdentifiersData = array();
    	    array_push($recordIdentifiersData, array("title" => "PersonID", "value" => $personid, "tooltip" => "unique identifier in database"));
    	    $recordIdentifiers["fields"] = $recordIdentifiersData;
    	    
    	    //$activeRecordId = array();
    	    //$activeRecordAttributeNames = array("tablename","rectype","uniqueid","censusyear");
    	    
    	    // setup a varialble for objid and array for attachment objids
    	    
    	    array_push($recordLocationData, array("title" => $geotype, "value" => $geodescr, "tooltip" => $geosource));
            $recordLocation['fields'] = $recordLocationData;
    	    
    	    //for all the attributes in the list for this record, group them and push them    
    	    while ($row = pg_fetch_assoc($activeRecordResult)){
        	    if($type == 'person'){  
        	       if(in_array($row['attr'],$demographicAttributeNames)){//optional sorting idea: Push rows into an array here, then do an array_merge to sort this array by the AttributeNames array. Finally, do the below portion AFTER all rows outside this While
        	           array_push($recordDemographicData, array("title" => $row['attrdescr'], "value" => (is_null(ucwords(strtolower($row['val'])))) ? "" : ucwords(strtolower($row['val'])), "tooltip" => (is_null($row['attrsource'])) ? "" : $row['attrsource']));
        	       }
        	       //$recordDemographicSorted = array_merge($demographicAttributeNames,$recordDemographicData);
        	       $recordDemographic['fields'] = $recordDemographicData;
        	       
        	       if(in_array($row['attr'],$occupationAttributeNames)){
        	           array_push($recordOccupationData, array("title" => $row['attrdescr'], "value" => (is_null(ucwords(strtolower($row['val'])))) ? "" : ucwords(strtolower($row['val'])), "tooltip" => (is_null($row['attrsource'])) ? "" : $row['attrsource']));
        	       }
        	       $recordOccupation['fields'] = $recordOccupationData;
        	       
        	       if(in_array($row['attr'],$educationAttributeNames)){
        	           array_push($recordEducationData, array("title" => $row['attrdescr'], "value" => (is_null(ucwords(strtolower($row['val'])))) ? "" : ucwords(strtolower($row['val'])), "tooltip" => (is_null($row['attrsource'])) ? "" : $row['attrsource']));
        	       }
        	       $recordEducation['fields'] = $recordEducationData;
        	       
        	       if(in_array($row['attr'],$immigrationAttributeNames)){
        	           array_push($recordImmigrationData, array("title" => $row['attrdescr'], "value" => (is_null(ucwords(strtolower($row['val'])))) ? "" : ucwords(strtolower($row['val'])), "tooltip" => (is_null($row['attrsource'])) ? "" : $row['attrsource']));
        	       }
        	       $recordImmigration['fields'] = $recordImmigrationData;
        	       
        	       if(in_array($row['attr'],$housingAttributeNames)){
        	           array_push($recordHousingData, array("title" => $row['attrdescr'], "value" => (is_null(ucwords(strtolower($row['val'])))) ? "" : ucwords(strtolower($row['val'])), "tooltip" => (is_null($row['attrsource'])) ? "" : $row['attrsource']));
        	       }
        	       $recordHousing['fields'] = $recordHousingData;
        	       
        	       
        	    }elseif($type =='story'){
    	            $attids = array();
    	           if(in_array($row['attr'],$storyTitleAttributeNames)){
        	           array_push($recordStoryTitleData, array("title" => $row['attrdescr'], "value" => (is_null((($row['val'])))) ? "" : (($row['val'])), "tooltip" => (is_null($row['attrsource'])) ? "" : $row['attrsource']));
        	           if($row['attr'] == "title"){ $results['title'] = $row['val'];}
        	       }
        	       $recordStoryTitle['fields'] = $recordStoryTitleData;
        	       
        	       if(in_array($row['attr'],$storyAttributeNames)){
        	           array_push($recordStoryData, array("title" => $row['attrdescr'], "value" => (is_null((($row['val'])))) ? "" : (($row['val'])), "tooltip" => (is_null($row['attrsource'])) ? "" : $row['attrsource']));
        	           
        	       }
    	           $recordStory['fields'] = $recordStoryData; 
    	      
    	       }elseif($type == 'building'){
    	           if(in_array($row['attr'],$placeAttributeNames)){
    	               if($row['attrdescr'] == 'Floors'){
    	                   array_push($recordPlaceData, array("title" => $row['attrdescr'], "value" => (is_null($row['val']) ? "" : str_replace('.0','',number_format($row['val'],1))), "tooltip" => (is_null($row['attrsource'])) ? "" : $row['attrsource']));
    	               }else{
    	                   array_push($recordPlaceData, array("title" => $row['attrdescr'], "value" => (is_null(ucwords(strtolower($row['val'])))) ? "" : ucwords(strtolower($row['val'])), "tooltip" => (is_null($row['attrsource'])) ? "" : $row['attrsource']));
        	           }
    	           }
        	       $recordPlace['fields'] = $recordPlaceData;
    	           
    	       }
    	    }// end of the row fetching while 
    	    
    	    
    	   $data = array();
    	    
    	    $results['sources'] = $sources;
        
        	if($type == 'story'){
            	$results['type'] = 'story';
        	    $results['id'] = $personid;
                $results['loctype'] = 'place';
                $results['sources'] = $sources;
                if(is_null($geotype)==false AND $geotype != '') array_push($data, $recordLocation);
        	    array_push($data, $recordStoryTitle);
        	    array_push($data, $recordStory);
        	    
        	}elseif($type == 'building'){
        	   // $results['type'] = 'building';
        	   // $results['id'] = $recnumber;
               // $results['loctype'] = 'place';
                $results['sources'] = $sources;
                array_push($data, $recordPlace);
        	    
        	}elseif($type == 'person'){
        	    array_push($data, $recordLocation);
        	    array_push($data, $recordDemographic);
            	array_push($data, $recordOccupation);
            	array_push($data, $recordEducation);
            	array_push($data, $recordImmigration);
            	array_push($data, $recordHousing);
        	}
        	
        	//array_push($data, $recordIdentifiers);
        	
        	$results['data'] = $data;
        	$results['attachments'] = $attachments;
        	
        }
    	$json = $results;
    	
        @ pg_close($link);
    }else{	//if format was set to help, don't issue the query but send user this text with the query that would have been submitted had they selected json format
    	$json = "you created this query: active person: ".$personIdentityQuery." ---------- active record:  ".$activeRecordQuery."--------- AvailRecords: ".$availableRecordQuery;
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
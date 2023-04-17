<?php
// flag.php
// webservice to set the flag field of the stories service so they don't appear on the web.
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
    $action = isset($object->action) ? $object->action: 'flag'; //what action is requested, default to flag
    $id = $object->id; 
    if($id == '*') $id == '';
    
    $f = isset($_GET['f']) ? str_replace("\'","",$_GET['f']) : 'json'; //default is json
    $p = isset($_GET['p']) ? str_replace("\'","",$_GET['p']): 'all'; //options are person, place, business, story, all. default is all
    
    
    //query construction below... 
    
        $flagQuery = "UPDATE grf.cchsdi_storypts SET flag = 'i' WHERE globalid = '". $id ."' ;"; // "SELECT globalid FROM grf.cchsdi_storypts WHERE globalid = '". $id ."' ;";   
        $unflagQuery = "UPDATE grf.cchsdi_storypts SET flag = null WHERE globalid = '". $id ."' ;"; // "SELECT globalid FROM grf.cchsdi_storypts WHERE globalid = '". $id ."' ;"; 
                      
    
    if($f == 'json' || $f == 'pjson'){//if no format was set or json was requested, issue the query and format the results as json.
    	
    	$link = pg_connect("host=portal1-geo.sabu.mtu.edu port=5432 dbname=giscore user=webuser password=sp@ghetti") or die('cannot connect to db');
    	//====================================================
    	$results = array();
    	
    	if($id){
    	    if($action == 'flag'){
        	    $flagResult = pg_query($link, $flagQuery) or die('query error: '.$flagQuery);
        	    if(pg_num_rows($flagResult) > 0){
        	    
        	        $results['result'] = "Item ID '".$id."' flagged for removal";
        	    }else{
        	         $results['result'] = "Item ID '".$id."' not found";
        	    }
        	    
        	}elseif($action == 'unflag'){
        	    
        	    $unflagResult = pg_query($link, $unflagQuery) or die('query error: '.$unflagQuery);
        	    if(pg_num_rows($unflagResult) > 0){
        	        
        	        $results['result'] = "Item ID '".$id."' unflagged for removal";
        	        
        	    }}else{
        	         $results['result'] = "Item ID '".$id."' not found";
        	} 
        	
    	}else{    
    	    $results['result'] = "Item ID Required, no item ID submitted";
    
    	}// end if
    	
    	// build a JSON feature collection array. should think about making this a GeoJSON in future.
 
    	$json = $results;
    	
        @ pg_close($link);
    }else{	//if format was set to help, don't issue the query but send user this text with the query that would have been submitted had they selected json format
    	$json = "you created this query: " .$flagQuery." ======= " .$unflagQuery;
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
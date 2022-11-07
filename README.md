
### Grid

Summary of data within variable sized rectangular grids based on truncating the Web-Mercator Coordinates for each item and grouping results by those truncated coordinates. Returned as grid points with attributes about number of items within each grid and size relative to all results returned in all grids. 
size argument changes the size of grid returned.
search and filters arguments will return a summary of grid cells that contain items matching those arguments. 
Only Active results (those matching search and filter arguments) will be included in the grid summary. Inactive results are not implemented. 
The items being counted are people, buildings, stories, and places from the live Kett explorer app.

**Definition**

`POST http://geospatialresearch.mtu.edu/grid.php`

**Request Body Arguments**

- `"search": string` what the user entered in the search field
- `"size": number` grid size in km (10,1,01)
- `"filters": object`
  - `"date_range": string` if date range selector bar is used
  - `"photos": boolean` should list include results with photos
  - `"type": string` one of the items in (default is 'everything'): people, places, stories, or everything

**Response**

- `200 OK` on success

```
{
  "active": {
        "length": 4,  //number of grid cells returned 
        "size": "10", //size of grid requested
        "results": [
            {
                "id": "-989|589", //ID of grid cell
                "type": "people", //Type of items within this grid cell that match the search and filter arguments
                "centroid": { //Lon and Lat of the grid cell 
                    "lon": "-9890000", 
                    "lat": "5890000"
                }, //count, total, max, percent, and montenum support rendering grid cells based on the number of items within them, 
                   //relative to all items returned that match the search and filter arguments
                "count": "1", //Count of items within this grid cell that match the search and filter arguments
                "total": 13, //Total items within all grid cells returned that match the search and filter arguments
                "max": 8, //Largest number of items in any grid cell that match the search and filter arguments
                "percent": "0.08", //Percent contribution of this grid cell to total items returned that match the search and filter arguments
                "montenum": "0.20", //Similar to percent but with a floor of 0.20 applied
                "title": "1 records located near here"
            },
            {
                "id": "-987|595",
                "type": "people",
                "centroid": {
                    "lon": "-9870000",
                    "lat": "5950000"
                },
                "count": "1",
                "total": 13,
                "max": 8,
                "percent": "0.08",
                "montenum": "0.20",
                "title": "1 records located near here"
            },
            {
                "id": "-986|596",
                "type": "people",
                "centroid": {
                    "lon": "-9860000",
                    "lat": "5960000"
                },
                "count": "8",
                "total": 13,
                "max": 8,
                "percent": "0.62",
                "montenum": "1.00",
                "title": "8 records located near here"
            },
            {
                "id": "-985|598",
                "type": "people",
                "centroid": {
                    "lon": "-9850000",
                    "lat": "5980000"
                },
                "count": "3",
                "total": 13,
                "max": 8,
                "percent": "0.23",
                "montenum": "0.38",
                "title": "3 records located near here"
            }
        ]
    }
}
```

### The Grid Cell

What goes in the popup when you click on a grid cell. Records in the grid cell matching the submitted "id", that also match the search text and filters. 

**Definition**

`POST http://geospatialresearch.mtu.edu/grid_cell.php`

**Request Body Arguments**

- `"search": string` what the user entered in the search field
- `"id": string` grid id (truncated lon|lat) normally obtained from the grid results
- `"filters": object`
  - `"date_range": string` date range to return results for, years
  - `"photos": boolean` should list include only results with photos?
  - `"type": string` one of the items in (default is 'everything'): people, places, stories, or everything
   
**Response**

- `200 OK` on success

```json
{
  "active": {
          "length": 6,  //Total number of records of all types in this grid cell
          "size": "10",  //'size' of the grid
          "people": {  //results for people
               "length":3,  //number of people records in this grid cell
               "results": [   //record info
                    {
                      "id": "E4D43ADB-35C6-4981-BF75-358929DD871C",
                      "recnumber": "74917173CENSUS1920",
                      "title": "GLADIS JOHNSON, 8, Albion School Grade KA, 1918, school"
                    },
                    {
                      "id": "E4D43ADB-35C6-4981-BF75-358929DD871C",
                      "recnumber": "74917173CENSUS1920",
                      "title": "GLADIS JOHNSON, 8, Albion School Grade KA, 1918, home"
                    },
                    {
                      "id": "E4D43ADB-35C6-4981-BF75-358929DD871C",
                      "recnumber": "74917173CENSUS1920",
                      "title": "GLADIS JOHNSON, 9, 1920"
                    }
                 ],
              },
              "places": {
                 "length": 0,
                 "results": [
                       {
                         "id": "E4D43ADB-35C6-4981-BF75-358929DD871C",
                         "title": "JOHNSON HOCKEY ARENA"
                       },
                       {
                         "id": "E4D43ADB-35C6-4981-BF75-358929DD871C",
                         "title": "JOHNSON MINING COMPANY"
                       }
                 ],
              },
              "stories": {
                 "length": 0,
                 "results": [
                        {
                          "id": "E4D43ADB-35C6-4981-BF75-358929DD871C",
                          "title": "GLADIS JOHNSON was amazing!"
                        }
                 ],
            }
      }
}
```

### Markers

Used to generate markers based on visible area. Active and inactive; results for every marker location. Each marker represents a collection of records coincident at that location. 

**Definition**

`POST http://geospatialresearch.mtu.edu/markers.php`

**Request Body Arguments**

- `"search": string` what the user entered in the search field
- `"geometry": object` https://developers.arcgis.com/javascript/latest/api-reference/esri-views-MapView.html#extent
  - `xmin: number`
  - `ymin: number`
  - `xmax: number`
  - `ymax: number`
  - `spatialReference: wkid`
- `"filters": object`
  - `"date_range": string` if date range selector bar is used
  - `"photos": boolean` should list include results with photos
  - `"type": string` one of the items in (default is 'everything'): people, places, stories, or everything
  
**Response**

- `200 OK` on success

```json
{
  "active": {  //'active' results that match search and filters
    "length": 2, //number of markers found with matching records in them
    "results": [  
        {
                "id": "-9845873|5981915",  //marker id, truncated lon|lat OR building ID; many records may be in each marker
                "type": "person", //type of items in this marker (person, place, story, everything)
                "x": "-9845873", //longitude
                "y": "5981915", //latitude
                "count": "1" //number of matching records in this marker
        },
        {
                "id": "-9846844.4305|5982883.272",
                "type": "person",
                "x": "-9846844.4305",
                "y": "5982883.272",
                "count": "2"
         }
      ]
    },
  "inactive": {
     "length": 96003,
     "results": [
           {
                "id": "-9780518.9565779|6019850.74715956",
                "type": "story",
                "x": "-9780518.9565779",
                "y": "6019850.74715956",
                "count": "1"
           },
           {
                "id": "-9782729.72299697|6016928.4476213",
                "type": "story",
                "x": "-9782729.72299697",
                "y": "6016928.4476213",
                "count": "1"
           }, 
           ...,
           {
                "id": "-9791721.368|5997838.6167",
                "type": "person",
                "x": "-9791721.368",
                "y": "5997838.6167",
                "count": "95"
            }
         ]
    }
}
```

### Marker Info

Get info needed for marker popup; When a search string is passed, a selected record matching that string will be highlighted in this list of all records coincident at this marker location.   
Active items are all items within the passed date_range, whether or not they match the search text. Inactive items will be those that don't match these, perhaps in a different time period or not matching filters. 
ID is the Marker ID and is required. This is typically passed from the marker that the user clicks on. All results are for a single marker location. 
Recnumber is passed when a record is clicked from the search list at left. List items in this marker info list that match this record are highlighted. 

**Definition**

`POST http://geospatialresearch.mtu.edu/marker_info.php`

**Request Body Arguments**

- `"search": string` what the user entered in the search field
- `"id": string` Marker ID (pipe delimited concatenation of marker lon|lat OR building ID)
- `"recnumber": string`
- `"loctype" : string' (optional; helps identify the record to highlight, currently 'home' or 'school'
- `"filters": object`
  - `"date_range": string` if date range selector bar is used
  - `"photos": boolean` should list include results with photos
  - `"type": string` one of the items in (default is 'everything'): people, places, stories, or everything

**Response**

- `200 OK` on success

```json
{
    "active": {
        "length": 2175,  //total records at this marker matching filters and date_range
        "title": "Enumeration District 169, 1920 Census", //title for the marker returned by this search
        "geotype": "Enumeration District", //Type of geography represented
        "people": {
            "length": 2175, //total person records at this marker matching filters and date_range
            "results": [
                {
                    "id": "7FDC589E-79D0-4F42-806A-14027F6A3936", //Person ID
                    "recnumber": "74954582CENSUS1920", //Record id
                    "loctype": "Home",
                    "title": "FRANCIS N MILLER, 14, SINGLE", //Title of record instance
                    "photos": "false", 
                    "featured": "false",
                    "highlighted": "true" //true because this matches the location type and record id and search text 
                    "locid": "-9841558.5194|5974137.3698" // location id for this record
                },
                {
                    "id": "0E72EB47-3A4B-40D1-9296-6606016B63E6",
                    "recnumber": "74956099CENSUS1920",
                    "loctype": "Home",
                    "title": "Ada Gurry, 43, Home",
                    "photos": "false",
                    "featured": "false",
                    "highlighted": "false", // false because this does not match the record number and location type
                    "locid": "-9841558.5194|5974137.3698"
                }, 
                ...,
                {
                    "id": "CB9FCE1F-8D6A-4FB9-9C2F-A89B6A565B7B",
                    "recnumber": "74955180CENSUS1920",
                    "loctype": "Home",
                    "title": "Ada Swanson, 28, Home",
                    "photos": "false",
                    "featured": "false",
                    "highlighted": "false",
                    "locid": "-9841558.5194|5974137.3698"
                }
            ]
        },
        "places": {
            "length": 0,
            "results": []
        },
        "stories": {
            "length": 0,
            "results": []
        }
    }
}
```

### List

Get info needed for list component

**Definition**

`POST http://geospatialresearch.mtu.edu/list.php`

**Request Body Arguments**

- `"search": string` what the user entered in the search field
- `"area": object` geometry of the area being viewed, optional
- `"filters": object`
  - `"date_range": string` if date range selector bar is used
  - `"photos": boolean` should list include only results with photos?
  - `"type": string` one of the items in (default is 'everything'): people, places, stories, or everything
  
**Response**

- `200 OK` on success

```json
{
  "length": 3,
  "results": [
    {
      "id": "E4D43ADB-35C6-4981-BF75-358929DD871C",  //Person id
      "recnumber": "74917173CENSUS1920",  //record id
      "title": "GLADIS JOHNSON, 8, Albion School Grade KA, 1918, school"  //record instance title
      "tooltip": "Born 1911 In Michigan, Single ", //Popup text
      "loctype": "Home", //location type
      "markerid": "-9844930.3306|5982575.1564",  //marker id
      "x": "-9844930.3306",  // coordinates of location
      "y": "5982575.1564",
      "map_year": "1920", // corresponding map year
      "method": "POST"  //request method used
    },
    { 
      "id": "E4D43ADB-35C6-4981-BF75-358929DD871C",
      "recnumber": "12304SCLRCRD1918",
      "title": "Gladys Johnson, 8, 1918 School",
      "tooltip": "Aka: Gladis Johnson, Gladys Johnson,  Student, Ka Grade, Albion, 8, School",
      "loctype": "School",
      "markerid": "19171053|Laurium|bldg",
      "x": "-9844746",
      "y": "5983037",
      "map_year": "1918",
      "method": "POST"
     },
     {
       "id": "E4D43ADB-35C6-4981-BF75-358929DD871C",
        "recnumber": "14449131CENSUS1930",
        "title": "Gladys C Johnson, 19, 1930 Home",
        "tooltip": "Aka: Gladis Johnson, Born 1911 In Michigan, Servant, Single ",
        "loctype": "Home",
        "markerid": "-9844863.6468|5982639.7921",
        "x": "-9844863.6468",
        "y": "5982639.7921",
        "map_year": "1930",
        "method": "POST"
    }
  ]
}
```

### Full Details

Get info needed for full details component

**Definition**

`POST http://geospatialresearch.mtu.edu/full_details.php`

**Request Body Arguments**

- `"loctype" : string`
- `"id": string`
- `"recnumber": string`

**Response**

- `200 OK` on success

```json
{
  "title": "Francis N Miller",  //Title for details section
    "geodescr": " 169, 1920 Census",  //description of mapped location
    "geotype": "enumeration district",  //type of geography mapped
    "type": "people",  //item type
    "id": "7FDC589E-79D0-4F42-806A-14027F6A3936",  //Person id
    "loctype": "Home",  //location type
    "map_year": "1917",  //map year to display
    "sources": [  //sources of data that can be displayed. Changing changes location displayed on map, and changes the time bin if a source is in another 'bin'
        {
            "recname": "Census 1910 - Home (Approximate)", // record instance name
            "historyname": "Francis Muller, 5, Home", // text to put in search history
            "recnumber": "197243802CENSUS1910", //record id
            "markerid": "-9841902.3643|5973343.9977",  //marker id
            "loctype": "home",  //location type
            "geodescr": "3rd Street",  //description of geographic location mapped
            "geotype": "street", //type of geography mapped
            "x": "-9841902.3643", //coordinates of location mapped
            "y": "5973343.9977",
            "map_year": "1908", //map year to display
            "selected": "false"  //Is this the record selected in the menu? Controls display of sources menu. 
        },
        {
            "recname": "Census 1920 - Home (Approximate)",
            "historyname": "Francis N Miller, 14, Home",
            "recnumber": "74954582CENSUS1920",
            "markerid": "-9841558.5194|5974137.3698",
            "loctype": "home",
            "geodescr": "Enumeration District 169, 1920 Census",
            "geotype": "enumeration district",
            "x": "-9841558.5194",
            "y": "5974137.3698",
            "map_year": "1917",
            "selected": "true"
        }
    ],
    "data": [
        {
            "title": "Location",  //Location description information
            "fields": [
                {
                    "title": "enumeration district",
                    "value": " 169, 1920 Census",
                    "tooltip": "Census 1920"
                }
            ]
        },
        {
            "title": "Demographics",  //demographics fields found for selected person and time bin
            "fields": [
                {
                    "title": "Age",  // Title of record 
                    "value": "14",   // Value of record
                    "tooltip": "census 1920"  //Source of record
                },
                {
                    "title": "Birth Year",
                    "value": "1906",
                    "tooltip": "census 1920"
                },
                {
                    "title": "Birthplace",
                    "value": "Michigan",
                    "tooltip": "census 1920"
                },
                ...
                {
                    "title": "Relationship in House",
                    "value": "Son",
                    "tooltip": "census 1920"
                }
            ]
        },
        {
            "title": "Employment",  //employment records
            "fields": [
                {
                    "title": "Industry",
                    "value": "",
                    "tooltip": "census 1920"
                },
                {
                    "title": "Occupation",
                    "value": "",
                    "tooltip": "census 1920"
                }
            ]
        },
        {
            "title": "Education", // education records  
            "fields": [
                {
                    "title": "Attended School This Year",
                    "value": "Yes",
                    "tooltip": "census 1920"
                },
                {
                    "title": "Can Read",
                    "value": "Yes",
                    "tooltip": "census 1920"
                },
                {
                    "title": "Can Write",
                    "value": "Yes",
                    "tooltip": "census 1920"
                },
               ...
                {
                    "title": "Speaks English",
                    "value": "Yes",
                    "tooltip": "census 1920"
                }
            ]
        },
        {
            "title": "Immigration", //immigration records
            "fields": [
                {
                    "title": "Citizenship",
                    "value": "",
                    "tooltip": "census 1920"
                },
                {
                    "title": "Year Immigrated",
                    "value": "",
                    "tooltip": "census 1920"
                }
            ]
        },
        {
            "title": "Housing", //housing records 
            "fields": [
                {
                    "title": "Ownership",
                    "value": "",
                    "tooltip": "census 1920"
                },
                {
                    "title": "Owns Home",
                    "value": "",
                    "tooltip": "census 1920"
                }
            ]
        }
  ],
  "attachments": [  //Array of attachments to this record. These are examples only. Currently only stories have attachments in Kett. 
    { "url": "http://ktt.com/image1.jpg", "alt": "First image" }, //URL of attached record
    { "url": "http://ktt.com/image2.jpg", "alt": "Second image" }
  ]
}
```

### Related Content

Get info needed for related content component. Items related to the full details item currently displayed. 

**Definition**

`POST http://geospatialresearch.mtu.edu/related_content.php`

**Request Body Arguments**

- `"id": string`  // Person ID or Item ID to find families or classmates
- `"mapyear": integer` // map year   
- `"markerid": string` // marker ID to find items in same place

  **Response**

- `200 OK` on success

```json
{
    "length": 5, //Total records in all groups
    "people": {  // Related people records
        "groups": [
            {
                "title": "Family", // Group title
                "length": 2, //Number of records in this group
                "map": false,  //displayed on the map? 
                "results": [
                    {
                        "id": "FE02BE98-03BE-4E71-916B-ECDED2AE2AB9", //person id
                        "recnumber": "74952594CENSUS1920",  // record number
                        "title": "Maria Hagman, 61",  //record title
                        "tooltip": "Maria Hagman",  //popup text
                        "loctype": "Home", //location type
                        "markerid": "5009|Lake Linden|bldg",  //Marker ID
                        "x": "-9841576.7176",  //coords of marker
                        "y": "5973198.3601",
                        "map_year": "1917"  //map year to display
                    },
                    {
                        "id": "C0EEF2BB-880A-4B2D-BF3C-82A56AFD689F",
                        "recnumber": "74952593CENSUS1920",
                        "title": "Oscar Hagman, 59",
                        "tooltip": "Oscar Hagman",
                        "loctype": "Home",
                        "markerid": "5009|Lake Linden|bldg",
                        "x": "-9841576.7176",
                        "y": "5973198.3601",
                        "map_year": "1917"
                    }
                ]
            },
            {
                "title": "People at the Same Location",  
                "length": 2,
                "map": false,
                "results": [
                    {
                        "id": "FE02BE98-03BE-4E71-916B-ECDED2AE2AB9",
                        "recnumber": "74952594CENSUS1920",
                        "title": "Maria Hagman, 61",
                        "loctype": "Home",
                        "markerid": "5009|Lake Linden|bldg",
                        "x": "-9841576.7176",
                        "y": "5973198.3601",
                        "map_year": "1917"
                    },
                    {
                        "id": "C0EEF2BB-880A-4B2D-BF3C-82A56AFD689F",
                        "recnumber": "74952593CENSUS1920",
                        "title": "Oscar Hagman, 59",
                        "loctype": "Home",
                        "markerid": "5009|Lake Linden|bldg",
                        "x": "-9841576.7176",
                        "y": "5973198.3601",
                        "map_year": "1917"
                    }
                ]
            }
        ]
    },
    "places": {  //Related places (homes, schools...) 
        "groups": [
            {
                "title": "Homes",
                "length": 1,
                "map": true,
                "results": [
                    {
                        "id": "5009|Lake Linden|bldg",
                        "recnumber": "5009|Lake Linden|1917",
                        "title": "5994 Front, Lake Linden 1917",
                        "loctype": "Home",
                        "markerid": "5009|Lake Linden|bldg",
                        "x": "-9841576.7176",
                        "y": "5973198.3604",
                        "map_year": "1917"
                    }
                ]
            }
        ]
    },
    "stories": {
        "groups": []
    }
}
```


### Date Picker

Get info needed render date picker component

**Definition**

`POST http://geospatialresearch.mtu.edu/date_picker.php`

**Request Body Arguments**

- none; returns valid date ranges

**Response**

- `200 OK` on success

```json
{
    "min": 1850,  //earliest year
    "max": 2022,  //latest year
    "segments": [  //date segments (aka 'bins') 
        {
            "min": 1850,  //min of this segment
            "max": 1894,  //max of this segment
            "map_year": 1888,  //map year to display with this segment
            "url": "https://portal1-geo.sabu.mtu.edu/server/rest/services/KeweenawHSDI/KeTT_1888_FIPS/MapServer",  //URL of map to display
            "title": "1850-1894 (Sanborn Map 1888)"  //Title of displayed map
        },
       ... 
        {
            "min": 1946,
            "max": 1955,
            "map_year": 1949,
            "url": "https://portal1-geo.sabu.mtu.edu/server/rest/services/KeweenawHSDI/Kett_19496_FIPS/MapServer",
            "title": "1946-1955 (Sanborn Map 1949)"
        },
        {
            "min": 1956,
            "max": 2022,
            "map_year": 2022,
            "url": null,
            "title": "1956-2022 (No Historic Map)"
        }
    ]
}
```
### Map Picker

Get info needed to populate maps list 

**Definition**

`POST http://geospatialresearch.mtu.edu/map_picker.php`

**Request Body Arguments**

- none; returns all maps in the collection (maps associated with date_picker + additional maps in DB table)

**Response**

- `200 OK` on success

```json
{
    "maps": [  //array of maps
        {
            "min": 1925,  //date range of map, used for querying and selecting a 'date_bin' 
            "max": 1938,
            "url": "https://portal1-geo.sabu.mtu.edu/server/rest/services/Hosted/38_Aerials/MapServer",  //URL of map service
            "title": "Aerial Photography 1938"  //Title of map
        },
        {
            "min": 1914,
            "max": 1924,
            "url": "https://portal1-geo.sabu.mtu.edu/server/rest/services/Hosted/CH1915OwnershipMap/MapServer",
            "title": "Calumet and Hecla Property Map 1915"
        },
        {
            "min": 1956,
            "max": 2022,
            "url": "https://portal1-geo.sabu.mtu.edu/server/rest/services/Hosted/Cannon_Nicholson_Geology_Maps/MapServer",
            "title": "Cannon & Nicholson Geology 1995"
        },
        ...
        {
            "min": 1946,
            "max": 1955,
            "url": "https://portal1-geo.sabu.mtu.edu/server/rest/services/KeweenawHSDI/Kett_19496_FIPS/MapServer",
            "title": "Sanborn Map 1949"
        }
    ]
}
```

### Flag

Flag content for removal. Returns confirmation if succesful, 'Item ID not found' if not found, 'tem ID Required' if none was submitted. 
Removes content in the record locations table and flags for removal in the stand-alone story_pts feature class in EGDB. 

**Definition**

`POST http://geospatialresearch.mtu.edu/flag.php`

**Request Body Arguments**

- `"id": string`  // Record ID
- `"action": string` // flag or unflag; default is flag
- 
**Response**

- `200 OK` on success

```json
{
    "results": [
        { 
          "Item ID ... flagged for removal";
        }
    ]
} 

```

### Share a Story

Internal to code, not part of API. Should still document the RESTful ESRI method used here with an example

**Definition**

`POST http://portal1-geo.sabu.mtu.edu/mtuarcgis.....`

**Request Body Arguments**
// need to update content below..... 
- `"title": string`
- `"date": string`
- `"story": string`
- `"images": string`
- `"name": string`
- `"related": object` information needed to relate story to id or record

**Response**
// need to update content below..... 
- `200 OK` on success

```json
{
  "message": "Story has been added",
  "id": "2D5AD5AE-880C-41BC-BDEE-FF07E5C7DA81"
}
```

- `500 Internal Server Error` on server error

```json
{
  "message": "Sorry something went wrong"
}
```



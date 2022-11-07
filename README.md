
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

```json
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
                "id": "-9845873|5981915",  //marker id, truncated lon|lat, many records may be in each marker
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
- `"id": string` Marker ID (pipe delimited concatenation of marker lon|lat)
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

Get info needed for related content component

**Definition**

`GET http://geospatialresearch.mtu.edu/related_content.php`

**Request Body Arguments**

- `"recnumber": "3740SCLRCRD1918"`
- `"markerid": "19173278|Laurium|bldg"`
- `"loctype": "home"`
- `"map_year": "1917"`

  **Response**

- `200 OK` on success

```json
{
  "length": 7,
  "people": {
    "groups": [
      {
        "title": "Family",
        "length": 1,
        "results": [
          {
            "id": "40F819D5-D072-4E9A-A55A-4E69A9B47F36",
            "recnumber": "74917173CENSUS1920",
            "loctype": "home",
            "title": "JANE JOHNSON",
            "x": "-9845281.4237",
            "y": "5980492.3729",
          }
        ]
      },
      {
        "title": "Classmates",
        "length": 1,
        "results": [
          {
            "id": "40F819D5-D072-4E9A-A55A-4E69A9B47F36",
            "recnumber": "74917173CENSUS1920",
            "loctype": "home",
            "title": "TOM SMITH",
            "x": "-9844975",
            "y": "5980771",
          }
        ]
      }
    ]
  },
  "places": {
    "groups": [
      {
        "title": "Homes",
        "length": 1,
        "results": [
          {
            "id": "19173278|Laurium|bldg",
            "recnumber": "3740SCLRCRD1918",
            "loctype": "",
            "title": "Birth Home",
            "x": "-9845281.4237",
            "y": "5980492.3729",
          }
        ]
      },
      {
        "title": "Places of Work",
        "length": 2,
        "results": [
          {
            "id": "19173278|Laurium|bldg",
            "recnumber": "3740SCLRCRD1918",
            "loctype": "",
            "title": "Houghton Grocery",
            "x": "-9845281.4237",
            "y": "5980492.3729",
          },
          {
            "id": "19173278|Laurium|bldg",
            "recnumber": "3740SCLRCRD1918",
            "loctype": "",
            "title": "Downtown Cafe",
            "x": "-9845281.4237",
            "y": "5980492.3729",
          }
        ]
      }
    ]
  },
  "stories": {
    "length": 2,
    "results": [
      {
        "id": "E4D43ADB-35C6-4981-BF75-358929DD871C",
        "recnumber": "74917173CENSUS1920",
        "loctype": "",
        "title": "GLADIS JOHNSON was my grandma!",
        "x": "-9845281.4237",
        "y": "5980492.3729",
      },
      {
        "id": "E4D43ADB-35C6-4981-BF75-358929DD871C",
        "recnumber": "74917173CENSUS1920",
        "loctype": "",
        "title": "GLADIS JOHNSON saved my cat!",
        "x": "-9845281.4237",
        "y": "5980492.3729",
      }
    ]
  }
}
```

### Related Content Grid

Get grid layout based on specific related content

**Definition**

`GET http://geospatialresearch.mtu.edu/grid_related.php`

**Request Body Arguments**

- `"ids": object` list of related content ids
- `"size": number` grid size in km

**Response**

- `200 OK` on success

```json
{
  "results": [
    {
      "id": "1",
      "centroid": { "lon": "-9844863.6469", "lat": "5982639.7919" },
      "percent": 0.2
    },
    {
      "id": "2",
      "centroid": { "lon": "-9844863.6469", "lat": "5982639.7919" },
      "percent": 0.8
    }
  ]
}
```

### Related Content Markers

Get marker locations based on specific related content

**Definition**

`GET http://geospatialresearch.mtu.edu/markers_related.php`

**Request Body Arguments**

- `"ids": object` list of related content ids

**Response**

- `200 OK` on success

```json
{
  "results": [
    {
      "id": "2D5AD5AE-880C-41BC-BDEE-FF07E5C7DA81",
      "recnumber": "74917173CENSUS1920",
      "lon": "-9844863.6469",
      "lat": "5982639.7919"
    },
    {
      "id": "2D5AD5AE-880C-41BC-BDEE-FF07E5C7DA81",
      "recnumber": "74917173CENSUS1920",
      "lon": "-9844863.6469",
      "lat": "5982639.7919"
    }
  ]
}
```

### Date Picker

Get info needed render date picker component

**Definition**

`GET http://geospatialresearch.mtu.edu/date_picker.php`

**Request Body Arguments**

- `"area": object` geometry of the area being viewed

**Response**

- `200 OK` on success

```json
{
  "min": 1888,
  "max": 2020,
  "current_location": "Keweenaw",
  "segments": [
    {
      "min": 1888,
      "max": 1902,
      "url": "https://portal1-geo.sabu.mtu.edu:6443/arcgis/rest/services/KeweenawHSDI/KeTT_YYYY_FIPS/MapServer"
    },
    {
      "min": 1903,
      "max": 1940,
      "url": "https://portal1-geo.sabu.mtu.edu:6443/arcgis/rest/services/KeweenawHSDI/KeTT_YYYY_FIPS/MapServer"
    }
  ]
}
```

### Share a Story

Get info needed render date picker component

**Definition**

`PUT http://geospatialresearch.mtu.edu/share_story.php`

**Request Body Arguments**

- `"title": string`
- `"date": string`
- `"story": string`
- `"images": string`
- `"name": string`
- `"related": object` information needed to relate story to id or record

**Response**

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

### Print Record

Download a pdf of a record

**Definition**

`PUT http://geospatialresearch.mtu.edu/print.php`

**Request Body Arguments**

- `"id": string`
- `"recnumber": string`

**Response**

- `200 OK` on success

```json
{
  "url": "http://ktt.com/record.pdf"
}
```

### Download Record

Download a csv of a record

**Definition**

`PUT http://geospatialresearch.mtu.edu/download.php`

**Request Body Arguments**

- `"id": string`
- `"recnumber": string`

**Response**

- `200 OK` on success

```json
{
  "url": "http://ktt.com/record.csv"
}
```

### List people

One item for each unique person

**Definition**

`GET http://geospatialresearch.mtu.edu/list_people.php?q=johnson&f=pjson`

**Query Params**

- `"q": string` filter by last name
- `"f": string` responce format, options include json or pjson if you want a more readable output

**Response**

- `200 OK` on success

```json
{
  "results": {
    "people": [
      {
        "person": {
          "personid": "2D5AD5AE-880C-41BC-BDEE-FF07E5C7DA81",
          "namelast": "JOHNSON",
          "namefirst": "WM",
          "birthplace": "MICHIGAN",
          "birthyear": "1884",
          "fbirthplac": "FINLAND",
          "mbirthplac": "FINLAND",
          "personsrc": "1920_census_MPC"
        }
      },
      {
        "person": {
          "personid": "B9E2B070-DD70-42AD-8361-41F7E4802E0D",
          "namelast": "JOHNSON",
          "namefirst": "ELIZABETH",
          "birthplace": "FINLAND",
          "birthyear": "1888",
          "fbirthplac": "FINLAND",
          "mbirthplac": "FINLAND",
          "personsrc": "1920_census_MPC"
        }
      }
    ]
  }
}
```

### Search by Person ID

Multiple entries; one item for each record for each person found

**Definition**

`GET http://geospatialresearch.mtu.edu/search_by_personid.php?q=E4D43ADB-35C6-4981-BF75-358929DD871C&f=pjson`

**Query Params**

- `"q": string` person ID
- `"f": string` responce format, options include json or pjson if you want a more readable output

**Response**

- `200 OK` on success

```json
{
  "results": {
    "records": [
      {
        "record": {
          "personid": "E4D43ADB-35C6-4981-BF75-358929DD871C",
          "lastname": "JOHNSON",
          "firstname": "GLADIS",
          "recyear": "1920",
          "occupation": null,
          "age": "9",
          "addtype": "home",
          "location_type": "Street",
          "address": "Caledonia",
          "lon": "-9844863.6469",
          "lat": "5982639.7919",
          "featoid": "77726",
          "recnumber": "74917173CENSUS1920"
        }
      },
      {
        "record": {
          "personid": "E4D43ADB-35C6-4981-BF75-358929DD871C",
          "lastname": "JOHNSON",
          "firstname": "GLADIS",
          "recyear": "1918",
          "occupation": "Albion School Grade KA",
          "age": "8",
          "addtype": "home",
          "location_type": null,
          "address": "398 Caledonia",
          "lon": "-9844937.4925",
          "lat": "5982580.0083",
          "featoid": "3681",
          "recnumber": "12304SCLRCRD1918"
        }
      },
      {
        "record": {
          "personid": "E4D43ADB-35C6-4981-BF75-358929DD871C",
          "lastname": "JOHNSON",
          "firstname": "GLADIS",
          "recyear": "1918",
          "occupation": "Albion School Grade KA",
          "age": "8",
          "addtype": "school",
          "location_type": null,
          "address": "Albion",
          "lon": "-9844746",
          "lat": "5983037",
          "featoid": "6429",
          "recnumber": "12304SCLRCRD1918"
        }
      }
    ]
  }
}
```

### Search by Person Name

Multiple entries: one item for each record each person found
Can filter by lastname by populating q= lastname, and filter by firstname & lastname by populating q = firstname lastname (currently finishing firstname & lastname function)

**Definition**

`GET http://geospatialresearch.mtu.edu/search_by_personid.php?q=E4D43ADB-35C6-4981-BF75-358929DD871C&f=pjson`

**Query Params**

- `"q": string` person first and/or last name (appears only last name works right now)
- `"f": string` responce format, options include json or pjson if you want a more readable output

**Response**

- `200 OK` on success

```json
{
  "results": {
    "people": [
      {
        "person": {
          "personid": "9C0BFA20-427D-462A-957B-EF797E5EE104",
          "lastname": "JOENANN",
          "firstname": "IRENE",
          "recyear": "1920",
          "occupation": null,
          "age": "19",
          "addtype": "home",
          "location_type": "Enumeration District",
          "address": "173",
          "lon": "-9839031.4306",
          "lat": "5956940.3407",
          "featoid": "61458",
          "recnumber": "74960837CENSUS1920"
        }
      }
    ]
  }
}
```

### Register User

Register new user

**Definition**

`GET http://geospatialresearch.mtu.edu/user_register.php`

**Request Body Arguments**

- `"name": string`
- `"email": string`
- `"password": string`

**Response**

- `200 OK` on success

```json
{
  "message": "New user created!",
  "id": "2D5AD5AE-880C-41BC-BDEE-FF07E5C7DA81"
}
```

- `400 Bad Request` user already exists

```json
{
  "message": "Email already exists"
}
```

- `400 Bad Request` error

```json
{
  "message": "Sorry there was a problem"
}
```

### Login User

Login existing user

**Definition**

`GET http://geospatialresearch.mtu.edu/user_login.php`

**Request Body Arguments**

- `"email": string`
- `"password": string`

**Response**

- `200 OK` on success

```json
{
  "message": "Logged in!",
  "jwt": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyfQ.SflKxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c"
}
```

- `400 Bad Request` wrong email or password

```json
{
  "message": "Email or password is wrong, please try again"
}
```

### Like

Like a record

**Definition**

`GET http://geospatialresearch.mtu.edu/like.php`

**Request Header Arguments**

- `"auth-token": jwt`

**Request Body Arguments**

- `"id": string`
- `"recnumber": string`

**Response**

- `200 OK` on success

```json
{
  "message": "[Name of record] has been added to your likes!"
}
```

- `401 Unauthorized` not logged in

```json
{
  "message": "Please login to like things"
}
```

### Add to History

Save entry in users history

**Definition**

`GET http://geospatialresearch.mtu.edu/history.php`

**Request Header Arguments**

- `"auth-token": jwt`

**Request Body Arguments**

- `"id": string` user id
- `"entry": object` history info to be saved on user account

**Response**

- `200 OK` on success

```json
{
  "message": "Successfully added to history!"
}
```

- `401 Unauthorized` not logged in

```json
{
  "message": "Please login"
}
```

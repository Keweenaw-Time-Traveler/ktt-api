[DB Tables - Google Doc](https://docs.google.com/spreadsheets/d/1kBcGiiDpZbhpxYJAISRiu1Ta57z13zNx1jdwm6KrLr8/edit?usp=sharing)
In progress google sheet of columns in each table in the database. Currently includes Census & school records. Adding column types and description. Kind of a schema.

### Grid

Summary of data within variable sized rectangular grids based on truncating the Web-Mercator Coordinates for each item and grouping results by those truncated coordinates. Returned as grid points with attributes about number of items within each grid and size relative to all results returned in all grids. 
size argument changes the size of grid returned.
search and filters arguments will return a summary of grid cells that contain items matching those arguments. 
Only Active results (those matching search and filter arguments) will be included in the grid summary. Inactive results are not implemented. 
The items being counted are people, buildings, stories, and places from the live Kett explorer app.

**Definition**

`GET http://geospatialresearch.mtu.edu/grid.php`

**Request Body Arguments**

- `"search": string` what the user entered in the search field
- `"size": number` grid size in km (10,1,01)
- `"filters": object`
  - `"date_range": string` if date range selector bar is used
  - `"photos": boolean` should list include results with photos
  - `"featured": boolean` not yet implemented -- should list include results that are featured
  - `"type": object` list of results of a specific type ie. people, buildings, stories

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

`GET http://geospatialresearch.mtu.edu/grid_cell.php`

**Request Body Arguments**

- `"search": string` what the user entered in the search field
- `"id": string` grid id (truncated lon|lat) normally obtained from the grid results
- `"filters": object`
  - `"date_range": string` date range to return results for, years
  - `"photos": boolean` should list include only results with photos
  - `"featured": boolean` should list include only results that are featured
  
**Response**

- `200 OK` on success

```json
{
  "active": {
          "length": 6,
          "size": "10",
          "people": {
               "length":2,
               "results": [
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

Used to generate markers based on visible area. Active and inactive; results for every marker location. Each marker actually represents a collection of records coincident at that location. 

**Definition**

`GET http://geospatialresearch.mtu.edu/markers.php`

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
  - `"featured": boolean` should list include results that are featured
  - `"type": string` all, people, places, stories
  - `"advanced": object` list of advanced fileters like record type

**Response**

- `200 OK` on success

```json
{
  "active": {
    "length": 2,
    "results": [
        {
                "id": "-9845873|5981915",
                "type": "person",
                "x": "-9845873",
                "y": "5981915",
                "count": "1"
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

Get info needed for marker popup; A selected record from the search results list will be highlighted in this list of all records coincident at this marker location.  
When search string and/or filters are passed, active items in marker info results will be those that match this string. Inactive items will be those that don't match these.
ID is required. This is passed from the marker that the user clicks on. All results are for a single marker location. 
Recnumber is passed when a record is clicked from the search list at left. List items in this marker info list that match this record are highlighted. 

**Definition**

`GET http://geospatialresearch.mtu.edu/marker.php`

**Request Body Arguments**

- `"search": string` what the user entered in the search field
- `"id": string` Marker ID (pipe delimited concatenation of marker lon|lat)
- `"recnumber": string`
- `"filters": object`
  - `"date_range": string` if date range selector bar is used
  - `"photos": boolean` should list include results with photos
  - `"featured": boolean` should list include results that are featured
  - `"type": string`

**Response**

- `200 OK` on success

```json
{
    "active": {
        "length": 40,
        "people": {
            "length": 40,
            "results": [
                {
                    "id": "7FDC589E-79D0-4F42-806A-14027F6A3936",
                    "recnumber": "74954582CENSUS1920",
                    "title": "FRANCIS N MILLER, 14, SINGLE",
                    "photos": "false",
                    "featured": "false",
                    "highlighted": "true"
                },
                {
                    "id": "DB8C38C6-1A10-44D3-AB1E-65AA9A8FE0A6",
                    "recnumber": "74954548CENSUS1920",
                    "title": "THOMAS MILLS, HEAD FEEDER, 60, WIDOW",
                    "photos": "false",
                    "featured": "false",
                    "highlighted": "false"
                }, 
                ...,
                {
                    "id": "463A84FE-74B2-4CD3-8B96-5E4F9A0B4791",
                    "recnumber": "74955631CENSUS1920",
                    "title": "ROBERT MILLER, 5, SINGLE",
                    "photos": "false",
                    "featured": "false",
                    "highlighted": "false"
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

`GET http://geospatialresearch.mtu.edu/list.php`

**Request Body Arguments**

- `"search": string` what the user entered in the search field
- `"area": object` geometry of the area being viewed
- `"filters": object`
  - `"type": string` Everything, People, Place, Stories
  - `"date_range": string` if date range selector bar is used
  - `"photos": boolean` should list include results with photos
  - `"featured": boolean` should list include results that are featured
  - `"advanced": object` list of advanced fileters like record type

**Response**

- `200 OK` on success

```json
{
  "length": 3,
  "results": [
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
  ]
}
```

### Full Details

Get info needed for full details component

**Definition**

`GET http://geospatialresearch.mtu.edu/full_details.php`

**Request Body Arguments**

- `"id": string`
- `"recnumber": string`

**Response**

- `200 OK` on success

```json
{
  "title": "John Smith",
  "type": "people", //options: people, places, stories
  "sources": [
    {
      "recname": "Census 1940 - home",
      "recnumber": "044D128C-766A-40DD-B50C-9E7C92BD1386CENSUS1940",
      "markerid": "-9845957.50182324|5980951.74837218",
      "x": "-9845957.5018",
      "y": "5980951.7484",
      "map_year": "1942"
    },
    {
      "recname": "Census 1930 - home",
      "recnumber": "14446419CENSUS1930",
      "markerid": "-9846103.02941176|5980673.38840383",
      "x": "-9846103.0294",
      "y": "5980673.3884",
      "map_year": "1928"
    },
  ],
  "data": [
    {
      "title": "Demographics",
      "fields": [
        {
          "title": "Record year",
          "value": "1930"
        },
        {
          "title": "Last name",
          "value": "MUNCH"
        },
      ]
    },
    {
      "title": "Employment",
      "fields": [
        {
          "title": "Industry",
          "value": "COPPER MINE"
        },
        {
          "title": "Occupation",
          "value": "LABORER-SURFACE"
        },
      ]
    },
  ],
  "images": [
    { "url": "http://ktt.com/image1.jpg", "alt": "First image" },
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

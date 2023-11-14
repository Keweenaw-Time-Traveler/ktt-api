Now redesign the middle ware to add new API called Grid cell where i will give the request and response, we are dealing with pagination,
 i need to redesing the boave middleware to match the below request. Also i need to add the marker from the response x and y labels, also make sure we add a variable or some deogn in frontend where know which page is loading or someking of progress
ALso i will just pass the Place name only all the page numbers must be handled by middleware. And it is the primary reason for creating middleware, remember i need to give a report about this so make it more professional.

Request API is http://localhost:8888/ktt-api/grid_cell.php
Request is 
{
                "search": "Michigan",
                "pageSize":1,
                "page":1
}
and the response is below
{
    "page": 1,
    "pageSize": 1,
    "totalPages": null,
    "totalRecords": null,
    "results": {
        "people": {
            "length": 1,
            "results": [
                {
                    "id": "10820|CD",
                    "recnumber": "10820|CD",
                    "title": "Bldg Loan Michigan, 1928 Home",
                    "loctype": "home",
                    "locid": "1129|Hancock|bldg",
                    "markerid": "1129|Hancock|bldg",
                    "x": "-9860192.181",
                    "y": "5962841.6166",
                    "map_year": "1928"
                }
            ]
        },
        "places": {
            "length": 1,
            "results": [
                {
                    "recnumber": "329|Houghton|1949",
                    "title": "Michigan Gollege Of Mines Frat Ho. , 1405 College, Houghton 1949, 1949 ",
                    "loctype": "",
                    "locid": "329|Houghton|bldg",
                    "markerid": "329|Houghton|bldg",
                    "x": "-9857525.4386",
                    "y": "5961625.6329",
                    "map_year": "1949"
                }
            ]
        },
        "stories": {
            "length": 1,
            "results": [
                {
                    "recnumber": "{C04EF878-77F0-4577-B397-0CB388C46964}",
                    "title": "My Paternal Grandfather, 1957 ",
                    "loctype": "",
                    "locid": "1996|Calumet|bldg",
                    "markerid": "1996|Calumet|bldg",
                    "x": "-9847175.2622",
                    "y": "5982632.5155",
                    "map_year": "1957"
                }
            ]
        }
    },
    "nextPage": true
}


make the new request depending upon the nextPage boolean value
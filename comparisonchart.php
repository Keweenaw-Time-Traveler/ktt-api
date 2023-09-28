<?php
// Include the pChart library
require_once('../pChart2.1.4/class/pData.class.php');
require_once('../pChart2.1.4/class/pDraw.class.php');
require_once('../pChart2.1.4/class/pImage.class.php');

// Function to make an API request and return the response time
function makeApiRequest($url, $requestData) {
    $start = microtime(true);

    // Make the API request (replace this with your API request code)
    $options = [
        'http' => [
            'header' => 'Content-Type: application/json',
            'method' => 'POST',
            'content' => json_encode($requestData),
        ],
    ];

    $context = stream_context_create($options);
    $response = file_get_contents($url, false, $context);

    $end = microtime(true);

    // Calculate the response time in milliseconds
    $responseTime = ($end - $start) * 1000;

    return $responseTime;
}

// API endpoints before and after optimization
$apiUrlBefore = 'http://geospatialresearch.mtu.edu/grid_cell.php';
$apiUrlAfter = 'http://localhost:8888/ktt-api/grid_cell.php';

// Number of requests to make for testing
$numRequests = 1;

// Request data (JSON)
$requestData = [
    "search" => ""
];

// Arrays to store response times
$responseTimesBefore = [];
$responseTimesAfter = [];

// Make API requests and record response times (before optimization)
for ($i = 0; $i < $numRequests; $i++) {
    $responseTime = makeApiRequest($apiUrlBefore, $requestData);
    $responseTimesBefore[] = $responseTime;
    echo "Request (Before Optimization) #$i: $responseTime ms<br>";
}

// Make API requests and record response times (after optimization)
for ($i = 0; $i < $numRequests; $i++) {
    $responseTime = makeApiRequest($apiUrlAfter, $requestData);
    $responseTimesAfter[] = $responseTime;
    echo "Request (After Optimization) #$i: $responseTime ms<br>";
}
// ... Your previous code ...

// Create a pChart object with title
$myPicture = new pImage(400, 200);

// Create a pData object
$myData = new pData();

// Add data points for before and after optimization
$myData->addPoints($responseTimesBefore, 'Before Optimization');
$myData->addPoints($responseTimesAfter, 'After Optimization');

// Set axis labels
$myData->setAxisName(0, 'Response Time (ms)');

// Add data to the chart
$myData->addPoints(['Request 1', 'Request 2', 'Request 3', 'Request 4', 'Request 5', 'Request 6', 'Request 7', 'Request 8', 'Request 9', 'Request 10'], 'Requests');
$myData->setSerieDescription('Requests', 'Request');
$myData->setAbscissa('Requests');

// Create a pChart object with title
$myPicture = new pImage(400, 200);
//$myPicture->setFontProperties(['FontName' => 'pChart/fonts/verdana.ttf', 'FontSize' => 10]);
//$myPicture->set(50, 50, 380, 180); // Adjusted graph area coordinates

// Draw the background and title
$myPicture->drawFilledRectangle(0, 0, 400, 200, ['R' => 240, 'G' => 240, 'B' => 240]);
$myPicture->drawText(150, 22, 'API Performance Before and After Optimization', ['FontSize' => 12, 'Align' => TEXT_ALIGN_TOPMIDDLE]);

// Create the bar chart
$myPicture->drawBarChart(['DisplayValues' => true, 'DisplayR' => 0, 'DisplayG' => 0, 'DisplayB' => 0]);

// Render the chart to a file (you can also display it in a web page)
$myPicture->Render('comparison_chart.png');

echo "Comparison chart generated: <img src='comparison_chart.png'>";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Comparison</title>
    <!-- Add Bootstrap CSS Link here -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">API Comparison</h1>
        <form method="POST">
            <div class="form-group">
                <label for="api1">API 1 URL:</label>
                <input type="text" class="form-control" id="api1" name="api1" required>
            </div>
            <div class="form-group">
                <label for="api2">API 2 URL:</label>
                <input type="text" class="form-control" id="api2" name="api2" required>
            </div>
            <div class="form-group">
                <label for="requestBody">Request Body:</label>
                <textarea class="form-control" id="requestBody" name="requestBody" rows="6" required></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Compare APIs</button>
        </form>

        <?php
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            // Get API URLs and request body from the form
            $api1Url = $_POST["api1"];
            $api2Url = $_POST["api2"];
            $requestBody = $_POST["requestBody"];
            
            // Create cURL handles for API 1 and API 2 requests
            $ch1 = curl_init($api1Url);
            $ch2 = curl_init($api2Url);

            // Set cURL options
            curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch1, CURLOPT_POST, true);
            curl_setopt($ch2, CURLOPT_POST, true);
            curl_setopt($ch1, CURLOPT_POSTFIELDS, $requestBody);
            curl_setopt($ch2, CURLOPT_POSTFIELDS, $requestBody);

            // Execute the cURL requests
            $response_api1 = curl_exec($ch1);
            $response_api2 = curl_exec($ch2);

            // Get HTTP response codes
            $httpCode_api1 = curl_getinfo($ch1, CURLINFO_HTTP_CODE);
            $httpCode_api2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);

            // Check for cURL errors
            if (curl_errno($ch1) || curl_errno($ch2)) {
                echo "<p class='text-danger'>cURL Error: " . curl_error($ch1) . "</p>";
                echo "<p class='text-danger'>cURL Error: " . curl_error($ch2) . "</p>";
            }

            // Check HTTP response codes
            if ($httpCode_api1 === 200 && $httpCode_api2 === 200) {
                // Both APIs returned a successful response
                // Compare responses and determine which is better
                $response1 = json_decode($response_api1, true);
                $response2 = json_decode($response_api2, true);

                // Perform your response comparison logic here

                // Display comparison results
                echo "<h2>Comparison Results</h2>";
                echo "<p>API 1 Response:</p>";
                echo "<pre>" . print_r($response1, true) . "</pre>";
                echo "<p>API 2 Response:</p>";
                echo "<pre>" . print_r($response2, true) . "</pre>";
            } else {
                // One or both APIs returned an error response
                echo "<p class='text-danger'>API 1 HTTP Error Code: $httpCode_api1</p>";
                echo "<p class='text-danger'>API 2 HTTP Error Code: $httpCode_api2</p>";
            }

            // Close cURL handles
            curl_close($ch1);
            curl_close($ch2);
        }
        ?>

    </div>
</body>
</html>

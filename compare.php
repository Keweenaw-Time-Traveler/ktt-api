<!DOCTYPE html>
<html>
<head>
    <title>API Performance Comparison</title>
    <!-- Include Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <!-- Include Chart.js CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(45deg, #e66465, #9198e5, #62b957, #e66465);
            background-size: 400% 400%;
            animation: gradient 15s linear infinite;
            color: white;
        }

        @keyframes gradient {
            0% {
                background-position: 0% 50%;
            }
            50% {
                background-position: 100% 50%;
            }
            100% {
                background-position: 0% 50%;
            }
        }

        .container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .btn-container {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 20px;
        }
        .loader {
            display: none;
        }
        .results-container {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
        }
        .card {
            flex-basis: calc(50% - 20px); /* Two columns with gap */
            margin-bottom: 20px;
            background-color: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 15px;
            color: white;
        }
        canvas {
            display: none;
            max-width: 100%;
            background:white;
            padding:15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); /* Add box shadow to graphs */
        }
        .heading {
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            margin: 20px 0;
            opacity: 0;
            transform: translateY(-20px);
            transition: opacity 0.5s, transform 0.5s;
        }
        .fade-in {
            opacity: 1 !important;
            transform: translateY(0) !important;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">API Performance Comparison</h1>
        <div class="btn-container">
            <button class="btn btn-primary" onclick="compareAPIs()">Compare APIs</button>
            <div class="loader" id="loader">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only"></span>
                </div>
            </div>
        </div>
        <div class="results-container" id="results">
            <div id="api1-results">
                <h2>Old API Responses</h2>
            </div>
            <div id="api2-results">
                <h2>New API Responses</h2>
            </div>
        </div>
        <canvas id="chart-response-times" style="max-width: 800px;"></canvas>
        <h2 class="heading" id="chart-response-times-heading">Charts Representing the Response times between Old API and New API</h2>
        <canvas id="chart-response-sizes" style="max-width: 800px;"></canvas>
        <h2 class="heading" id="chart-response-sizes-heading">Charts Representing the Response sizes between Old API and New API</h2>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js"></script>
   
    <script>
        const apiEndpoints = [
            "http://geospatialresearch.mtu.edu/grid_cell.php",
            "http://localhost:8888/ktt-api/grid_cell.php"
        ];

        const requests = [
            {
                "search": "Michigan",
                "filters": {
                    "date_range": "2010-2011"
                }
            },
            {
                "search": "HG",
                "filters": {
                    "date_range": "1900-2011"
                },
                "pageSize": 10
            },
            {
                "search": "Johnson",
            },
            {
                "search": "Houghton",
                "filters": {
                    "date_range": "1910-2011"
                }
                ,
                "pageSize": 1000
            }
        ];

        async function compareAPIs() {
            const loader = document.getElementById("loader");
            const api1ResultsDiv = document.getElementById("api1-results");
            const api2ResultsDiv = document.getElementById("api2-results");
            const chartResponseTimes = document.getElementById("chart-response-times");
            const chartResponseSizes = document.getElementById("chart-response-sizes");
            const responseTimes = [[], []];
            const responseSizes = [[], []];

            loader.style.display = "block"; // Show loader

            for (let i = 0; i < apiEndpoints.length; i++) {
                const endpoint = apiEndpoints[i];

                for (let j = 0; j < requests.length; j++) {
                    const request = requests[j];
                    const searchData = JSON.stringify(request);
                    const startTime = performance.now();

                    try {
                        const response = await fetch(endpoint, {
                            method: 'POST',
                            body: searchData,
                            headers: {
                                'Content-Type': 'application/json'
                            }
                        });

                        const endTime = performance.now();
                        const executionTime = endTime - startTime;
                        const responseText = await response.text();
                        const contentLengthBytes = new TextEncoder().encode(responseText).length;
                        const contentLengthKB = (contentLengthBytes / 1024).toFixed(2); // Convert to KB

                        const resultHTML = `
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">API ${i + 1} - Request ${j + 1} URL</h5>
                                    <p class="card-text">Response Time: ${executionTime.toFixed(2)} ms</p>
                                    <p class="card-text">Response Size: ${contentLengthKB} KB</p>
                                </div>
                            </div>
                        `;

                        if (i === 0) {
                            api1ResultsDiv.innerHTML += resultHTML;
                        } else if (i === 1) {
                            api2ResultsDiv.innerHTML += resultHTML;
                        }

                        // Update the chart data
                        responseTimes[i].push(executionTime);
                        responseSizes[i].push(parseFloat(contentLengthKB));
                    } catch (error) {
                        console.error(error);

                        // Handle errors by pushing placeholders (NaN) for missing data
                        responseTimes[i].push(NaN);
                        responseSizes[i].push(NaN);
                    }
                }
            }

            loader.style.display = "none"; // Hide loader

            // Create separate responsive bar charts for response times and sizes
            chartResponseTimes.style.display = "block";
            chartResponseSizes.style.display = "block";

            createChart(chartResponseTimes, "Response Times (ms)", responseTimes);
            createChart(chartResponseSizes, "Response Sizes (KB)", responseSizes);

            // Display chart headings with animation
            setTimeout(() => {
                document.getElementById("chart-response-times-heading").classList.add("fade-in");
                document.getElementById("chart-response-sizes-heading").classList.add("fade-in");
            }, 500);
        }

        function createChart(canvas, label, data) {
            const ctx = canvas.getContext("2d");
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ["Request 1", "Request 2", "Request 3", "Request 4"],
                    datasets: [
                        {
                            label: 'Old API',
                            data: data[0],
                            backgroundColor: `rgba(255, 99, 132, 0.6)`,
                            borderColor: `rgba(255, 99, 132, 1)`,
                            padding:`10px`,
                            borderWidth: 2
                        },
                        {
                            label: 'New API',
                            data: data[1],
                            backgroundColor: `rgba(54, 162, 235, 1)`,
                            borderColor: `rgba(54, 162, 235, 1)`,
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
    </script>

    <!-- Include Bootstrap JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
</body>
</html>

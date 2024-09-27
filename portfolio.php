<?php

$apiKey = 'your_polygon_api_key'; // Replace with your actual Polygon API key
$csvFile = 'portfolio.csv'; // Path to your CSV file
$yesterdayPricesFile = 'yesterday_prices.json'; // File to store yesterday's stock prices
$gainFile = 'daily_gain.txt'; // File to store the result of portfolio change

// Read the CSV file and build the portfolio array (ticker => amount)
function readPortfolio($csvFile) {
    $portfolio = array();
    if (($handle = fopen($csvFile, "r")) !== FALSE) {
        while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
            // Assuming CSV structure is Ticker;Amount (e.g., AAPL;5)
            $portfolio[] = array( 
                'ticker' => $data[0],
                'amount' => (int)$data[1]
            );
        }
        fclose($handle);
    }
    return $portfolio;
}

// Fetch previous day stock data from Polygon.io for a specific ticker using cURL
function fetchStockData($ticker, $apiKey) {
    $url = "https://api.polygon.io/v2/aggs/ticker/{$ticker}/prev?adjusted=true&apiKey={$apiKey}";

    // Initialize cURL session
    $ch = curl_init();

    // Set cURL options
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return the response as a string
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); // Disable SSL host verification (use only if necessary)
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // Disable SSL peer verification (use only if necessary)

    // Execute cURL request
    $response = curl_exec($ch);

    // Check for cURL errors
    if (curl_errno($ch)) {
        // Log error to STDERR
        fwrite(STDERR, date('Y-m-d H:i:s') . " ERROR: Failed to fetch data for ticker {$ticker}. cURL Error: " . curl_error($ch) . "\n");
        curl_close($ch);
        return null;
    }

    // Close cURL session
    curl_close($ch);

    // Decode the JSON response
    $data = json_decode($response, true);

    // Return previous day's closing price if available
    if (isset($data['results']) && count($data['results']) > 0) {
        return $data['results'][0]['c']; // 'c' is the close price
    }

    return null;
}

// Save today's prices for all tickers to a file (for tomorrow's comparison)
function saveTickerPrices($file, $tickerPrices) {
    file_put_contents($file, json_encode($tickerPrices));
}

// Load yesterday's prices from the file
function loadYesterdayPrices($file) {
    if (file_exists($file)) {
        return json_decode(file_get_contents($file), true);
    }
    return array(); // No file exists, assume no previous data
}

// Calculate the total gains or losses based on price changes
function calculateDailyPriceGains($portfolio, $apiKey, $yesterdayPrices) {
    $totalGain = 0;
    $todayPrices = array();

    foreach ($portfolio as $stock) {
        $ticker = $stock['ticker'];
        $amount = $stock['amount'];

        // Fetch today's closing price for the ticker
        $todayPrice = fetchStockData($ticker, $apiKey);

        if ($todayPrice !== null) {
            $todayPrices[$ticker] = $todayPrice; // Store today's price

            // If we have a price from yesterday, calculate the gain/loss based on price change
            if (isset($yesterdayPrices[$ticker])) {
                $priceChange = $todayPrice - $yesterdayPrices[$ticker];
                $gainLoss = $priceChange * $amount; // Multiply by amount of stocks held

                // Log info to STDERR
		fwrite(STDERR, date('Y-m-d H:i:s') . " INFO: Ticker: {$ticker}, Yesterday's Price: \${$yesterdayPrices[$ticker]}, Today's Price: \${$todayPrice}, Change: \${$priceChange}, Gain/Loss: " . formatToDollars($gainLoss) . "\n");

                // Add the gain/loss for this stock to the total
                $totalGain += $gainLoss;
            } else {
                // No data from yesterday (new stock in portfolio)
                fwrite(STDERR, date('Y-m-d H:i:s') . " INFO: Ticker: {$ticker} is new in the portfolio. Today's price: \${$todayPrice}\n");
            }

            // Sleep for 20 seconds to respect API limits
            sleep(20);
        } else {
            fwrite(STDERR, date('Y-m-d H:i:s') . " ERROR: Could not fetch price for {$ticker}\n");
        }
    }

    return array($totalGain, $todayPrices);
}

// Function to format numbers as dollars
function formatToDollars($number) {
    // Format the number to 2 decimal places and add thousands separators
    $formattedNumber = number_format(abs($number), 2, ',', '.');

    // Check if the number is negative
    if ($number < 0) {
        return "-$" . $formattedNumber;
    }

    // If positive
    return "+$" . $formattedNumber;
}

// Save today's gain to a file
function savePortfolioChange($file, $totalGain) {
    file_put_contents($file, $totalGain);
}

// Main execution
$portfolio = readPortfolio($csvFile);
$yesterdayPrices = loadYesterdayPrices($yesterdayPricesFile);
list($totalGains, $todayPrices) = calculateDailyPriceGains($portfolio, $apiKey, $yesterdayPrices);

// Save the total daily gain to a file
savePortfolioChange($gainFile, formatToDollars($totalGains));

// Output only the portfolio change result (e.g., +500, -300)
echo formatToDollars($totalGains) . "\n";

// Save today's prices for tomorrow's comparison
saveTickerPrices($yesterdayPricesFile, $todayPrices);

?>

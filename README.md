# Portfolio Daily Gain Script

## About

This is a **PHP script** that outputs your **daily portfolio gains** in the stock and cryptocurrency markets. The script calculates the gain or loss based on the previous day's closing prices. You can use this result for various purposes, such as automatically tweeting your portfolio gains via services like [Make.com](https://www.make.com) or integrating it with other tools.

## Configuration

### 1. **API Setup**
The script uses the free API from [Polygon.io](https://polygon.io) to retrieve the previous day's stock prices. For more features, such as live ticker data, you can upgrade to a paid plan.

To configure the API key:
- Open the script file.
- Replace the placeholder `your_polygon_api_key` with your own Polygon.io API key.

### 2. **Portfolio File**
The script relies on a `portfolio.csv` file to define the stocks and cryptocurrencies you own. This file should contain your portfolio in a simple CSV format:

ticker;quantity

For example:

AAPL;5
X:BTCUSD;0.5
TSLA;10

### 3. **Local Files**

The script uses two local files for storing data between runs:
- `yesterday_prices.json`: Stores the stock prices from the previous day.
- `daily_gain.txt`: Stores the calculated portfolio change (gain/loss).

Both file paths are configurable within the script.

## How It Works

1. The script reads your portfolio from `portfolio.csv`.
2. It fetches the previous day's closing prices for each ticker from Polygon.io.
3. It compares the previous day's prices to today's prices to compute the total portfolio gain or loss.
4. The result is stored in `daily_gain.txt` for future reference or integration with other tools.
5. It stores the closing prices in `yesterday_prices.json` to compare with future data.

## License

This project is open-source and available under the MIT License.

#!/usr/bin/env python3
"""
Yahoo Finance Data Fetcher
Ruft historische Kursdaten von Yahoo Finance ab
"""

import yfinance as yf
import sys
import json
from datetime import datetime
# import logging

# Logging für Debugging (optional)
# logging.basicConfig(
#     level=logging.WARNING,  # INFO für mehr Details
#     format='%(asctime)s - %(levelname)s - %(message)s'
# )


def get_historical_data(symbol: str, start_date: str, end_date: str) -> dict:
    """
    Holt historische Kursdaten von Yahoo Finance

    Args:
        symbol: Ticker-Symbol (z.B. 'AAPL', 'CAH')
        start_date: Start-Datum im Format 'YYYY-MM-DD'
        end_date: End-Datum im Format 'YYYY-MM-DD'

    Returns:
        Dictionary mit Erfolg/Fehler und Daten
    """
    try:
        ticker = yf.Ticker(symbol)
        df = ticker.history(interval='1d', start=start_date, end=end_date)

        if df.empty:
            return {
                "success": False,
                "error": f"No data available for {symbol} from {start_date} to {end_date}"
            }

        # Konvertiere DataFrame zu JSON-kompatiblem Format
        data = []
        for date, row in df.iterrows():
            data.append({
                'date': date.strftime('%Y-%m-%d'),
                'open': round(float(row['Open']), 2),
                'high': round(float(row['High']), 2),
                'low': round(float(row['Low']), 2),
                'close': round(float(row['Close']), 2),
                'volume': int(row['Volume']) if 'Volume' in row else 0,
            })

        return {
            "success": True,
            "symbol": symbol,
            "count": len(data),
            "data": data
        }

    except Exception as e:
        # logging.error(f"Error fetching data for {symbol}: {str(e)}")
        return {
            "success": False,
            "error": str(e)
        }


def get_current_quote(symbol: str) -> dict:
    """
    Holt aktuellen Quote für ein Symbol
    """
    try:
        ticker = yf.Ticker(symbol)
        info = ticker.info

        return {
            "success": True,
            "symbol": symbol,
            "data": {
                'current_price': info.get('currentPrice') or info.get('regularMarketPrice'),
                'previous_close': info.get('previousClose'),
                'open': info.get('open') or info.get('regularMarketOpen'),
                'day_high': info.get('dayHigh') or info.get('regularMarketDayHigh'),
                'day_low': info.get('dayLow') or info.get('regularMarketDayLow'),
                'volume': info.get('volume') or info.get('regularMarketVolume'),
                'market_cap': info.get('marketCap'),
                'company_name': info.get('longName') or info.get('shortName'),
            }
        }
    except Exception as e:
        return {
            "success": False,
            "error": str(e)
        }


if __name__ == "__main__":
    if len(sys.argv) < 2:
        print(json.dumps({
            "success": False,
            "error": "Usage: python yahoo_finance.py <command> <symbol> [start_date] [end_date]"
        }))
        sys.exit(1)

    command = sys.argv[1]

    if command == "historical":
        if len(sys.argv) < 4:
            print(json.dumps({
                "success": False,
                "error": "Usage: python yahoo_finance.py historical <symbol> <start_date> [end_date]"
            }))
            sys.exit(1)

        symbol = sys.argv[2]
        start_date = sys.argv[3]
        end_date = sys.argv[4] if len(sys.argv) > 4 else datetime.now().strftime('%Y-%m-%d')

        result = get_historical_data(symbol, start_date, end_date)
        print(json.dumps(result))

    elif command == "quote":
        if len(sys.argv) < 3:
            print(json.dumps({
                "success": False,
                "error": "Usage: python yahoo_finance.py quote <symbol>"
            }))
            sys.exit(1)

        symbol = sys.argv[2]
        result = get_current_quote(symbol)
        print(json.dumps(result))

    else:
        print(json.dumps({
            "success": False,
            "error": f"Unknown command: {command}"
        }))
        sys.exit(1)

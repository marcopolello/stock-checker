<?php

namespace App\Console\Commands;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Console\Command;

class CheckStockCommand extends Command
{
    protected $signature = 'stock:check {symbol} {--d|date= : The date to check the stock price for}';

    protected $description = 'Check stock price for a given symbol.';

    public function handle()
    {
        $symbol = Str::upper($this->argument('symbol'));

        // Get the most recent trading weekday.
        $date = now()->previousWeekday();

        if ($dateOption = $this->option('date')) {
            $date = Carbon::parse($dateOption);
            if ($date->isToday() || $date->isFuture()) {
                $this->error('Date must be in the past.');
                return;
            }
        }

        if ($date->lt(now()->subYear())) {
            $this->error('Date must be within the last year.');
            return;
        }

        // Find the Ticker Details
        $ticker = $this->getClient()
            ->withUrlParameters(['symbol' => $symbol])
            ->withQueryParameters(['date' => $date->toDateString()])
            ->throw()
            ->get("https://api.polygon.io/v3/reference/tickers/{symbol}")
            ->json('results');

        $openClose = $this->getClient()
            ->withUrlParameters([
                'symbol' => $symbol,
                'date' => $date->toDateString()
            ])
            ->get("https://api.polygon.io/v1/open-close/{symbol}/{date}?adjusted=true");

        if ($openClose->failed()) {
            $this->error("Could not retrieve stock data.\nStatus: " . $openClose->json('status') . "\nMessage: " . $openClose->json('message') . "\n");
            return;
        }


        $this->info("Stock: {$ticker['name']} ({$ticker['ticker']})");
        $this->info("Date: {$date->toDateString()}");
        $this->info("Currency: {$ticker['currency_name']}");
        $this->table(['Open', 'Close', 'High', 'Low'], [
            [
                number_format($openClose['open'], 2),
                number_format($openClose['close'], 2),
                number_format($openClose['high'], 2),
                number_format($openClose['low'], 2),
            ],
        ]);
    }

    protected function getClient(): PendingRequest
    {
        return Http::withToken(config('services.polygon.api_key'));
    }
}
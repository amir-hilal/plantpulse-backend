<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;

class OpenWeatherController extends Controller
{
    public function currentWeather()
    {
        $lat = request('lat');
        $lon = request('lon');
        $apiKey = env('OPENWEATHER_API_KEY');
        $weatherUrl = "https://api.openweathermap.org/data/2.5/weather?lat={$lat}&lon={$lon}&appid={$apiKey}&units=metric";

        $response = Http::withOptions([
            'verify' => false
        ])->get($weatherUrl);

        if ($response->successful()) {
            return $response->json();
        }

        return response()->json(['error' => 'Failed to fetch current weather data'], 500);
    }

    public function forecastWeather()
    {
        $lat = request('lat');
        $lon = request('lon');
        $apiKey = env('OPENWEATHER_API_KEY');
        $forecastUrl = "https://api.openweathermap.org/data/2.5/forecast?lat={$lat}&lon={$lon}&appid={$apiKey}&units=metric";

        $response = Http::withOptions([
            'verify' => false
        ])->get($forecastUrl);

        if ($response->successful()) {
            return $response->json();
        }

        return response()->json(['error' => 'Failed to fetch weather forecast data'], 500);
    }
}

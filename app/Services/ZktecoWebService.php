<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZktecoWebService
{
    /**
     * Pull users from ZKTeco device via HTTP ADMS web interface
     */
    public static function pullUsersFromDevice(string $deviceIp, int $port = 8000, int $timeout = 5): ?array
    {
        try {
            $url = "http://{$deviceIp}:{$port}/api/users";

            Log::info("Attempting to pull users from ZKTeco device at {$deviceIp}:{$port}");

            // Try HTTP GET to fetch users
            $response = Http::timeout($timeout)
                ->retry(2, 100)
                ->get($url);

            if ($response->successful()) {
                $data = $response->json();
                Log::info("Successfully pulled users from ZKTeco device", [
                    'count' => count($data['users'] ?? []),
                ]);

                return $data['users'] ?? [];
            } else {
                Log::warning("ZKTeco device returned status {$response->status()}", [
                    'url' => $url,
                    'body' => $response->body(),
                ]);

                return null;
            }
        } catch (\Exception $e) {
            Log::warning("Failed to pull users from ZKTeco device: {$e->getMessage()}", [
                'device_ip' => $deviceIp,
            ]);

            return null;
        }
    }

    /**
     * Get device information
     */
    public static function getDeviceInfo(string $deviceIp, int $port = 8000, int $timeout = 5): ?array
    {
        try {
            $url = "http://{$deviceIp}:{$port}/api/device";

            $response = Http::timeout($timeout)
                ->retry(2, 100)
                ->get($url);

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::warning("Failed to get device info: {$e->getMessage()}");

            return null;
        }
    }

    /**
     * Test device connectivity
     */
    public static function testConnectivity(string $deviceIp, int $port = 8000, int $timeout = 5): bool
    {
        try {
            $url = "http://{$deviceIp}:{$port}/";

            $response = Http::timeout($timeout)->get($url);

            return $response->successful() || $response->status() === 302 || $response->status() === 401;
        } catch (\Exception $e) {
            return false;
        }
    }
}

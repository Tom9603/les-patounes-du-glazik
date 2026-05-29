<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Estimates travel time between two addresses using Nominatim geocoding
 * (OpenStreetMap, free, no API key required) + Haversine distance formula.
 *
 * Driving time estimate: distance_km / 40 km/h + 5 min fixed overhead.
 * Accurate enough for the Quimper area; no external billing.
 */
class TravelTimeService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        #[Autowire('%env(SOPHIE_BASE_ADDRESS)%')]
        private string $sophieBaseAddress,
    ) {}

    /**
     * Returns estimated driving minutes between two addresses, or null on failure.
     */
    public function estimateMinutes(string $fromAddress, string $toAddress): ?int
    {
        $coordsFrom = $this->geocode($fromAddress);
        $coordsTo   = $this->geocode($toAddress);

        if ($coordsFrom === null || $coordsTo === null) {
            return null;
        }

        $km = $this->haversineKm($coordsFrom[0], $coordsFrom[1], $coordsTo[0], $coordsTo[1]);

        // Rough driving estimate: 40 km/h average + 5 min fixed
        return (int) ceil($km / 40 * 60) + 5;
    }

    /**
     * Returns estimated minutes from Sophie's base to the given address.
     */
    public function fromSophieBase(string $toAddress): ?int
    {
        return $this->estimateMinutes($this->sophieBaseAddress, $toAddress);
    }

    /**
     * Returns [lat, lon] for an address string, or null.
     */
    private function geocode(string $address): ?array
    {
        try {
            $response = $this->httpClient->request('GET', 'https://nominatim.openstreetmap.org/search', [
                'query' => [
                    'q'              => $address,
                    'format'         => 'json',
                    'limit'          => 1,
                    'countrycodes'   => 'fr',
                    'addressdetails' => 0,
                ],
                'headers' => [
                    'User-Agent' => 'LesPatounesDuGlazik/1.0 (sophielukomski.pro@gmail.com)',
                ],
                'timeout' => 5,
            ]);

            $data = $response->toArray();
            if (empty($data)) {
                return null;
            }

            return [(float) $data[0]['lat'], (float) $data[0]['lon']];
        } catch (\Throwable $e) {
            $this->logger->warning('TravelTimeService geocode failed', ['address' => $address, 'error' => $e->getMessage()]);
            return null;
        }
    }

    private function haversineKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $r = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
        return $r * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}

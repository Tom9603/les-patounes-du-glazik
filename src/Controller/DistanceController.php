<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class DistanceController extends AbstractController
{
    private const KM_RATE = 0.52;
    private const FREE_KM = 5;

    /** @var array{lat:float,lng:float}|null */
    private static ?array $sophieCoordsCache = null;

    public function __construct(
        private readonly HttpClientInterface $http,
        #[Autowire('%env(SOPHIE_BASE_ADDRESS)%')]
        private readonly string $sophieAddress,
    ) {}

    #[Route('/api/distance', name: 'app_api_distance', methods: ['GET'])]
    public function distance(Request $request): JsonResponse
    {
        $address = trim($request->query->get('address', ''));

        if (mb_strlen($address) < 10) {
            return $this->json(['error' => 'Adresse trop courte'], 400);
        }

        // Geocode de l'adresse client : précision à la rue requise
        $clientCoords = $this->geocodeFR($address, strict: true);
        if ($clientCoords === null) {
            return $this->json(['found' => false], 200);
        }

        // Sophie's coordinates (cached for process lifetime)
        $sophieCoords = $this->getSophieCoords();
        if ($sophieCoords === null) {
            return $this->json(['error' => 'Erreur de géolocalisation'], 503);
        }

        // Road distance via OSRM
        $distanceKm = $this->getRoadDistanceKm($sophieCoords, $clientCoords);
        if ($distanceKm === null) {
            return $this->json(['error' => 'Impossible de calculer la distance'], 503);
        }

        $billableKm = max(0.0, $distanceKm - self::FREE_KM);
        $feeEuros   = round($billableKm * self::KM_RATE, 2);

        return $this->json([
            'found'        => true,
            'distanceKm'   => round($distanceKm, 1),
            'feeEuros'     => $feeEuros,
            'addressLabel' => $clientCoords['label'],
        ]);
    }

    /**
     * Geocode using the official French government API (api-adresse.data.gouv.fr).
     * In strict mode, rejects results that are not at housenumber/street level.
     *
     * @return array{lat:float,lng:float,label:string}|null
     */
    private function geocodeFR(string $address, bool $strict = false): ?array
    {
        try {
            $response = $this->http->request('GET', 'https://api-adresse.data.gouv.fr/search/', [
                'query'   => ['q' => $address, 'limit' => 1],
                'timeout' => 6,
                'headers' => ['User-Agent' => 'LesPatounesDuGlazik/1.0'],
            ]);
            $data = $response->toArray(throw: false);
        } catch (\Throwable) {
            return null;
        }

        if (empty($data['features'])) {
            return null;
        }

        $f     = $data['features'][0];
        $score = (float) ($f['properties']['score'] ?? 0);
        $type  = $f['properties']['type'] ?? '';

        if ($strict && ($score < 0.4 || !in_array($type, ['housenumber', 'street'], true))) {
            return null;
        }

        [$lng, $lat] = $f['geometry']['coordinates'];

        return ['lat' => (float) $lat, 'lng' => (float) $lng, 'label' => $f['properties']['label'] ?? $address];
    }

    /**
     * Geocode via Nominatim (OpenStreetMap), adapté à la toponymie bretonne.
     *
     * @return array{lat:float,lng:float,label:string}|null
     */
    private function geocodeNominatim(string $address): ?array
    {
        try {
            $response = $this->http->request('GET', 'https://nominatim.openstreetmap.org/search', [
                'query'   => ['q' => $address, 'format' => 'json', 'limit' => 1, 'countrycodes' => 'fr'],
                'timeout' => 6,
                'headers' => ['User-Agent' => 'LesPatounesDuGlazik/1.0 (sophielukomski.pro@gmail.com)'],
            ]);
            $data = $response->toArray(throw: false);
        } catch (\Throwable) {
            return null;
        }

        if (empty($data)) {
            return null;
        }

        return [
            'lat'   => (float) $data[0]['lat'],
            'lng'   => (float) $data[0]['lon'],
            'label' => $data[0]['display_name'] ?? $address,
        ];
    }

    /** @return array{lat:float,lng:float,label:string} */
    private function getSophieCoords(): array
    {
        if (self::$sophieCoordsCache !== null) {
            return self::$sophieCoordsCache;
        }

        // Try French geocoder first, then Nominatim (handles Breton names better)
        $coords = $this->geocodeFR($this->sophieAddress, strict: false)
               ?? $this->geocodeNominatim($this->sophieAddress)
               ?? ['lat' => 48.0556, 'lng' => -4.0096, 'label' => 'Landudal']; // fallback

        return self::$sophieCoordsCache = $coords;
    }

    /**
     * One-way road distance in km via OSRM public API.
     */
    private function getRoadDistanceKm(array $from, array $to): ?float
    {
        $url = sprintf(
            'https://router.project-osrm.org/route/v1/driving/%f,%f;%f,%f?overview=false',
            $from['lng'], $from['lat'],
            $to['lng'],   $to['lat']
        );

        try {
            $response = $this->http->request('GET', $url, [
                'timeout' => 8,
                'headers' => ['User-Agent' => 'LesPatounesDuGlazik/1.0'],
            ]);
            $data = $response->toArray(throw: false);
        } catch (\Throwable) {
            return null;
        }

        if (($data['code'] ?? '') !== 'Ok' || empty($data['routes'])) {
            return null;
        }

        return $data['routes'][0]['distance'] / 1000.0;
    }
}

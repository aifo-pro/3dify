<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class DeliveryDirectoryService
{
    public function cities(string $carrier, ?string $query = null): array
    {
        $query = trim((string) $query);

        if (Str::length($query) < 2) {
            return [];
        }

        return match ($carrier) {
            'nova_poshta' => $this->novaPoshtaCities($query),
            'ukrposhta' => $this->ukrposhtaCities($query),
            default => [],
        };
    }

    public function warehouses(string $carrier, ?string $cityRef = null, ?string $query = null): array
    {
        $cityRef = trim((string) $cityRef);
        $query = trim((string) $query);

        if ($cityRef === '' && Str::length($query) < 2) {
            return [];
        }

        return match ($carrier) {
            'nova_poshta' => $this->novaPoshtaWarehouses($cityRef, $query),
            'ukrposhta' => $this->ukrposhtaWarehouses($cityRef, $query),
            default => [],
        };
    }

    private function novaPoshtaCities(string $query): array
    {
        $response = $this->novaPoshtaRequest('AddressGeneral', 'getCities', [
            'FindByString' => $query,
            'Limit' => 20,
        ]);

        return collect(Arr::get($response, 'data', []))
            ->map(fn (array $city): array => [
                'ref' => (string) ($city['Ref'] ?? ''),
                'name' => (string) ($city['Description'] ?? $city['DescriptionRu'] ?? ''),
                'region' => (string) ($city['AreaDescription'] ?? ''),
            ])
            ->filter(fn (array $city): bool => filled($city['ref']) && filled($city['name']))
            ->values()
            ->all();
    }

    private function novaPoshtaWarehouses(string $cityRef, string $query = ''): array
    {
        $properties = [
            'CityRef' => $cityRef,
            'Limit' => 50,
        ];

        if ($query !== '') {
            $properties['FindByString'] = $query;
        }

        $response = $this->novaPoshtaRequest('AddressGeneral', 'getWarehouses', $properties);

        return collect(Arr::get($response, 'data', []))
            ->map(fn (array $warehouse): array => [
                'ref' => (string) ($warehouse['Ref'] ?? ''),
                'name' => (string) ($warehouse['Description'] ?? $warehouse['DescriptionRu'] ?? ''),
                'number' => (string) ($warehouse['Number'] ?? ''),
            ])
            ->filter(fn (array $warehouse): bool => filled($warehouse['ref']) && filled($warehouse['name']))
            ->values()
            ->all();
    }

    private function novaPoshtaRequest(string $model, string $method, array $properties): array
    {
        $endpoint = config('services.nova_poshta.endpoint', 'https://api.novaposhta.ua/v2.0/json/');

        $response = Http::timeout(12)->acceptJson()->post($endpoint, [
            'apiKey' => (string) config('services.nova_poshta.key', ''),
            'modelName' => $model,
            'calledMethod' => $method,
            'methodProperties' => $properties,
        ]);

        return $response->ok() ? $response->json() : [];
    }

    private function ukrposhtaCities(string $query): array
    {
        $response = $this->ukrposhtaGet('cities', [
            'cityName' => $query,
            'size' => 20,
        ]);

        $items = Arr::get($response, 'Entries.Entry', $response['data'] ?? $response['items'] ?? $response ?? []);

        return collect(is_array($items) ? $items : [])
            ->map(fn (array $city): array => [
                'ref' => (string) ($city['CITY_ID'] ?? $city['id'] ?? $city['uuid'] ?? ''),
                'name' => (string) ($city['CITY_UA'] ?? $city['name'] ?? $city['cityName'] ?? ''),
                'region' => (string) ($city['REGION_UA'] ?? $city['region'] ?? ''),
            ])
            ->filter(fn (array $city): bool => filled($city['ref']) && filled($city['name']))
            ->take(20)
            ->values()
            ->all();
    }

    private function ukrposhtaWarehouses(string $cityRef, string $query = ''): array
    {
        $response = $this->ukrposhtaGet('postoffices', array_filter([
            'cityId' => $cityRef,
            'address' => $query,
            'size' => 50,
        ]));

        $items = Arr::get($response, 'Entries.Entry', $response['data'] ?? $response['items'] ?? $response ?? []);

        return collect(is_array($items) ? $items : [])
            ->map(fn (array $warehouse): array => [
                'ref' => (string) ($warehouse['POSTOFFICE_ID'] ?? $warehouse['id'] ?? $warehouse['uuid'] ?? ''),
                'name' => (string) ($warehouse['ADDRESS_UA'] ?? $warehouse['name'] ?? $warehouse['address'] ?? ''),
                'number' => (string) ($warehouse['POSTOFFICE_ID'] ?? $warehouse['number'] ?? ''),
            ])
            ->filter(fn (array $warehouse): bool => filled($warehouse['ref']) && filled($warehouse['name']))
            ->take(50)
            ->values()
            ->all();
    }

    private function ukrposhtaGet(string $path, array $query): array
    {
        $endpoint = rtrim((string) config('services.ukrposhta.endpoint', 'https://www.ukrposhta.ua/ecom/0.0.1'), '/');
        $token = (string) config('services.ukrposhta.token', '');

        $request = Http::timeout(12)->acceptJson();

        if ($token !== '') {
            $request = $request->withToken($token);
        }

        $response = $request->get($endpoint.'/'.ltrim($path, '/'), $query);

        return $response->ok() ? $response->json() : [];
    }
}

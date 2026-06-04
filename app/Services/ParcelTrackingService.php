<?php

namespace App\Services;

use App\Models\CustomOrderShipment;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Polls carrier APIs (Nova Poshta / Ukrposhta) for a parcel's current status.
 *
 * Returns a normalised array:
 *   [
 *     'status'      => one of CustomOrderShipment statuses,
 *     'description' => human text from the carrier,
 *     'location'    => warehouse / city,
 *     'happened_at' => Carbon|null,
 *     'raw'         => original payload,
 *   ]
 * or null when the carrier could not be reached / number is unknown.
 */
class ParcelTrackingService
{
    public const STATUS_TRACKING_ADDED = 'tracking_added';
    public const STATUS_SHIPPED        = 'shipped';
    public const STATUS_IN_TRANSIT     = 'in_transit';
    public const STATUS_ARRIVED        = 'arrived';
    public const STATUS_DELIVERED      = 'delivered';
    public const STATUS_FAILED         = 'failed';

    public function track(CustomOrderShipment $shipment): ?array
    {
        $number = trim((string) $shipment->tracking_number);
        if ($number === '') {
            return null;
        }

        return match ($shipment->carrier) {
            'nova_poshta' => $this->trackNovaPoshta($number),
            'ukrposhta'   => $this->trackUkrposhta($number),
            default       => null,
        };
    }

    // ─── Nova Poshta ─────────────────────────────────────────────────────────

    private function trackNovaPoshta(string $number): ?array
    {
        $endpoint = (string) config('services.nova_poshta.endpoint', 'https://api.novaposhta.ua/v2.0/json/');
        $apiKey   = (string) config('services.nova_poshta.key', '');

        try {
            $response = Http::timeout(15)->acceptJson()->post($endpoint, [
                'apiKey'        => $apiKey,
                'modelName'     => 'TrackingDocument',
                'calledMethod'  => 'getStatusDocuments',
                'methodProperties' => [
                    'Documents' => [['DocumentNumber' => $number, 'Phone' => '']],
                ],
            ]);
        } catch (\Throwable $e) {
            Log::warning('parcel.nova_poshta.http_error', ['number' => $number, 'message' => $e->getMessage()]);

            return null;
        }

        if (! $response->ok()) {
            return null;
        }

        $row = Arr::get($response->json(), 'data.0');
        if (! is_array($row)) {
            return null;
        }

        $code = (int) ($row['StatusCode'] ?? 0);

        return [
            'status'      => $this->mapNovaPoshtaCode($code),
            'description' => (string) ($row['Status'] ?? ''),
            'location'    => (string) ($row['WarehouseRecipient'] ?? $row['CityRecipient'] ?? ''),
            'happened_at' => $this->parseDate($row['RecipientDateTime'] ?? $row['ActualDeliveryDate'] ?? null),
            'raw'         => $row,
        ];
    }

    /**
     * Nova Poshta status codes → our shipment statuses.
     * https://developers.novaposhta.ua (TrackingDocument status codes)
     */
    private function mapNovaPoshtaCode(int $code): string
    {
        return match (true) {
            in_array($code, [2, 3, 102, 103, 105, 106], true) => self::STATUS_FAILED,      // deleted / refused / returned
            $code === 1                                        => self::STATUS_SHIPPED,     // awaiting sender
            in_array($code, [4, 41, 5], true)                  => self::STATUS_IN_TRANSIT,  // in transit
            $code === 6                                        => self::STATUS_IN_TRANSIT,  // arrived in recipient city
            in_array($code, [7, 8], true)                      => self::STATUS_ARRIVED,     // at warehouse / postomat
            in_array($code, [9, 10, 11], true)                 => self::STATUS_DELIVERED,   // received
            default                                            => self::STATUS_IN_TRANSIT,
        };
    }

    // ─── Ukrposhta ─────────────────────────────────────────────────────────

    private function trackUkrposhta(string $number): ?array
    {
        $endpoint = rtrim((string) config('services.ukrposhta.status_endpoint', 'https://www.ukrposhta.ua/status-tracking/0.0.1'), '/');
        $token    = (string) config('services.ukrposhta.status_token', config('services.ukrposhta.token', ''));

        try {
            $request = Http::timeout(15)->acceptJson();
            if ($token !== '') {
                $request = $request->withToken($token);
            }
            $response = $request->get($endpoint.'/statuses/last', ['barcode' => $number]);
        } catch (\Throwable $e) {
            Log::warning('parcel.ukrposhta.http_error', ['number' => $number, 'message' => $e->getMessage()]);

            return null;
        }

        if (! $response->ok()) {
            return null;
        }

        $row = $response->json();
        if (! is_array($row) || empty($row)) {
            return null;
        }

        $eventId   = (int) ($row['eventId'] ?? 0);
        $eventName = (string) ($row['eventName'] ?? '');

        return [
            'status'      => $this->mapUkrposhtaEvent($eventId, $eventName),
            'description' => $eventName,
            'location'    => (string) ($row['index'] ?? $row['name'] ?? ''),
            'happened_at' => $this->parseDate($row['date'] ?? null),
            'raw'         => $row,
        ];
    }

    private function mapUkrposhtaEvent(int $eventId, string $name): string
    {
        // Ukrposhta event ids: 1 accepted, 41 arrived at office, 51 handed out for delivery,
        // 3 delivered/handed, 4 returned. Fall back to keyword matching.
        $lower = mb_strtolower($name);

        return match (true) {
            $eventId === 3 || str_contains($lower, 'вручен')          => self::STATUS_DELIVERED,
            $eventId === 4 || str_contains($lower, 'поверн')          => self::STATUS_FAILED,
            $eventId === 41 || str_contains($lower, 'прибул')         => self::STATUS_ARRIVED,
            $eventId === 1 || str_contains($lower, 'прийнят') || str_contains($lower, 'надіслан') => self::STATUS_SHIPPED,
            default                                                   => self::STATUS_IN_TRANSIT,
        };
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}

<?php

namespace App\Services\Cartrack;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Client tipis untuk Cartrack Fleet API (https://developer.cartrack.com).
 *
 * Auth: HTTP Basic (username akun + API password).
 * Base URL: https://fleetapi-<region>.cartrack.com/rest
 */
class CartrackClient
{
    public function __construct(
        private readonly ?string $baseUrl = null,
        private readonly ?string $username = null,
        private readonly ?string $password = null,
        private readonly ?int $timeout = null,
    ) {
    }

    public function baseUrl(): string
    {
        $custom = $this->baseUrl ?? config('services.cartrack.base_url');
        if ($custom) {
            return rtrim($custom, '/');
        }

        $region = strtolower((string) config('services.cartrack.region', 'id'));

        return "https://fleetapi-{$region}.cartrack.com/rest";
    }

    private function http(): PendingRequest
    {
        $username = $this->username ?? config('services.cartrack.username');
        $password = $this->password ?? config('services.cartrack.password');

        if (! $username || ! $password) {
            throw new CartrackException('CARTRACK_USERNAME / CARTRACK_PASSWORD belum diisi di .env');
        }

        return Http::baseUrl($this->baseUrl())
            ->withBasicAuth($username, $password)
            ->acceptJson()
            ->timeout($this->timeout ?? (int) config('services.cartrack.timeout', 20))
            ->retry(2, 500, function ($e) {
                // retry hanya untuk error sementara (429/5xx/koneksi), bukan 4xx lain
                return $e instanceof RequestException
                    ? in_array($e->response->status(), [429, 500, 502, 503, 504], true)
                    : true;
            }, throw: false);
    }

    private function periksa(Response $response, string $metode, string $path): array
    {
        if ($response->failed()) {
            $msg = $response->json('error.message') ?? $response->body();
            throw new CartrackException(
                "Cartrack {$metode} {$path} gagal (HTTP {$response->status()}): ".mb_substr((string) $msg, 0, 300),
                $response->status()
            );
        }

        return $response->json() ?? [];
    }

    /**
     * GET ke endpoint Cartrack; kembalikan body JSON (array). Lempar CartrackException bila gagal.
     */
    public function get(string $path, array $query = []): array
    {
        return $this->periksa($this->http()->get('/'.ltrim($path, '/'), $query), 'GET', $path);
    }

    public function post(string $path, array $json): array
    {
        return $this->periksa($this->http()->asJson()->post('/'.ltrim($path, '/'), $json), 'POST', $path);
    }

    public function delete(string $path, array $json): array
    {
        return $this->periksa(
            $this->http()->asJson()->send('DELETE', '/'.ltrim($path, '/'), ['json' => $json]),
            'DELETE',
            $path
        );
    }

    /**
     * Ambil semua halaman dari endpoint ber-pagination (page & limit).
     */
    public function getAll(string $path, array $query = [], int $limit = 100, int $maxPages = 50): array
    {
        $all = [];
        for ($page = 1; $page <= $maxPages; $page++) {
            $body = $this->get($path, $query + ['page' => $page, 'limit' => $limit]);
            $rows = $body['data'] ?? [];
            if (empty($rows)) {
                break;
            }
            array_push($all, ...$rows);
            if (count($rows) < $limit) {
                break;
            }
        }

        return $all;
    }

    public function vehicles(): array
    {
        return $this->getAll('vehicles');
    }

    public function drivers(): array
    {
        return $this->getAll('drivers');
    }

    /**
     * Trip satu kendaraan dalam rentang waktu (maks 31 hari per request, aturan Cartrack).
     * Trip yang beririsan dengan rentang ikut terambil utuh. Trip privat ikut disertakan.
     *
     * @return array<int, array> baris trip mentah dari Cartrack
     */
    public function tripsKendaraan(string $registration, string $mulai, string $selesai): array
    {
        return $this->getAll('trips/'.rawurlencode($registration), [
            'start_timestamp' => $mulai,
            'end_timestamp' => $selesai,
            'incl_private' => 'true',
        ], limit: 100);
    }

    // ---- Tulis (mengubah data di akun Cartrack) -------------------------------------------------

    /**
     * Hubungkan driver ke kendaraan. Link buatan API sebelumnya untuk kendaraan atau driver yang sama
     * akan ditimpa oleh Cartrack.
     */
    public function linkDriver(string $registration, string $driverId): void
    {
        $this->post('vehicles/drivers/link', ['registration' => $registration, 'driver_id' => $driverId]);
    }

    /**
     * Lepas driver dari kendaraan. Bila link sudah tidak ada (404) dianggap berhasil.
     */
    public function unlinkDriver(string $registration, string $driverId): void
    {
        try {
            $this->delete('vehicles/drivers/link', ['registration' => $registration, 'driver_id' => $driverId]);
        } catch (CartrackException $e) {
            if ($e->status !== 404) {
                throw $e;
            }
        }
    }
}

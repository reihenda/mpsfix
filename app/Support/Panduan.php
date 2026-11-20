<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * Panduan Sistem: artikel cara kerja tiap menu.
 *
 * Artikel = file Markdown di resources/panduan/*.md dengan header:
 *
 *   ---
 *   judul: Membuat Proforma Invoice
 *   kategori: Tagihan
 *   urutan: 30
 *   menu: Tagihan > Tambah Proforma
 *   route: proforma-invoices.create
 *   kata_kunci: proforma, pi, tagihan awal
 *   ringkasan: Satu kalimat ringkas.
 *   alur: Pilih customer | Isi form | Simpan
 *   ---
 *   (isi Markdown)
 *
 * Akses: artikel tampil bila user boleh membuka SALAH SATU route di `route`
 * (dibaca dari middleware `role:` route tsb). `route: *` = semua user login.
 * Jadi kalau akses menu berubah di routes/web.php, akses artikel ikut berubah.
 */
class Panduan
{
    public const FOLDER = 'panduan';

    /** Kata yang tidak berarti untuk pencarian. */
    private const STOPWORDS = [
        'cara', 'gimana', 'bagaimana', 'gmn', 'yang', 'yg', 'di', 'ke', 'dari', 'untuk', 'utk',
        'dan', 'atau', 'dengan', 'saya', 'gue', 'gua', 'aku', 'kita', 'apa', 'itu', 'ini', 'ya', 'sih',
        'dong', 'mau', 'ingin', 'bisa', 'tolong', 'kalau', 'kalo', 'nya', 'pada', 'adalah', 'the', 'how', 'to',
    ];

    /** Sinonim sederhana: kata di kiri dicari juga sebagai kata di kanan. */
    private const SINONIM = [
        'bikin' => 'buat', 'membuat' => 'buat', 'tambah' => 'buat', 'input' => 'isi', 'masukin' => 'isi',
        'pi' => 'proforma', 'tagihan' => 'billing', 'faktur' => 'invoice', 'saldo' => 'deposit',
        'driver' => 'operator', 'sopir' => 'operator', 'supir' => 'operator', 'mobil' => 'kendaraan',
        'nopol' => 'kendaraan', 'gps' => 'cartrack', 'absen' => 'trip', 'harga' => 'pricing',
        'edit' => 'ubah', 'dolar' => 'usd', 'dollar' => 'usd', 'ketimpa' => 'timpa', 'tertimpa' => 'timpa', 'ganti' => 'ubah', 'print' => 'cetak',
    ];

    /** Semua artikel (tanpa filter akses), di-cache sampai ada file yang berubah. */
    public static function semua(): Collection
    {
        $files = glob(resource_path(self::FOLDER . '/*.md')) ?: [];
        $versi = md5(implode('|', array_map(fn ($f) => $f . filemtime($f), $files)));

        $data = Cache::remember('panduan:' . $versi, now()->addDay(), function () use ($files) {
            return collect($files)
                ->map(fn ($f) => self::parse($f))
                ->filter()
                ->sortBy([['urutan', 'asc'], ['judul', 'asc']])
                ->values()
                ->all();
        });

        return collect($data);
    }

    /** Artikel yang boleh dibaca user ini. */
    public static function untuk(User $user): Collection
    {
        return self::semua()->filter(fn ($a) => self::bolehAkses($user, $a['route']))->values();
    }

    public static function cari(User $user, ?string $q): Collection
    {
        $artikel = self::untuk($user);
        $token = self::token((string) $q);
        if (empty($token)) {
            return $artikel;
        }

        $hasil = $artikel
            ->map(function ($a) use ($token) {
                $skor = 0;
                $cocok = 0;
                foreach ($token as $t) {
                    $s = 0;
                    if (self::ada($a['_judul'], $t)) $s += 10;
                    if (self::ada($a['_kunci'], $t)) $s += 6;
                    if (self::ada($a['_menu'], $t)) $s += 4;
                    if (self::ada($a['_isi'], $t)) $s += 1;
                    if ($s > 0) $cocok++;
                    $skor += $s;
                }
                // Utamakan artikel yang cocok dengan semua kata yang dicari
                $a['skor'] = $skor + ($cocok === count($token) ? 20 : 0);
                $a['cocok'] = $cocok;
                return $a;
            })
            ->filter(fn ($a) => $a['skor'] > 0)
            ->sortByDesc('skor')
            ->values();

        // Buang hasil yang cuma kebetulan menyebut kata itu sekilas di isi artikel
        $batas = max(5, ($hasil->first()['skor'] ?? 0) * 0.2);
        return $hasil->filter(fn ($a) => $a['skor'] >= $batas)->values();
    }

    public static function cariSlug(User $user, string $slug): ?array
    {
        return self::untuk($user)->firstWhere('slug', $slug);
    }

    /**
     * Cek akses ke salah satu route. Route tanpa middleware `role:` = semua user login.
     * Route yang tidak ditemukan dianggap tidak boleh (artikel disembunyikan).
     */
    public static function bolehAkses(User $user, array $routeNames): bool
    {
        if (in_array('*', $routeNames, true)) {
            return true;
        }

        foreach ($routeNames as $name) {
            $route = Route::getRoutes()->getByName($name);
            if (!$route) {
                continue;
            }
            $roles = null;
            foreach ($route->gatherMiddleware() as $mw) {
                if (is_string($mw) && str_starts_with($mw, 'role:')) {
                    $roles = array_map('trim', explode(',', substr($mw, 5)));
                }
            }
            if ($roles === null || in_array($user->role, $roles, true)) {
                return true;
            }
        }

        return false;
    }

    // ------------------------------------------------------------------

    private static function parse(string $file): ?array
    {
        $raw = str_replace("\r\n", "\n", file_get_contents($file));
        if (!preg_match('/\A---\n(.*?)\n---\n(.*)\z/s', $raw, $m)) {
            return null;
        }

        $meta = [];
        foreach (explode("\n", $m[1]) as $baris) {
            if (str_contains($baris, ':')) {
                [$k, $v] = explode(':', $baris, 2);
                $meta[trim($k)] = trim($v);
            }
        }

        $isi = trim($m[2]);
        $slug = basename($file, '.md');
        $slug = preg_replace('/^\d+-/', '', $slug); // "10-proforma.md" -> "proforma"
        $daftar = fn ($s, $sep) => array_values(array_filter(array_map('trim', explode($sep, (string) $s))));

        $artikel = [
            'slug' => $slug,
            'judul' => $meta['judul'] ?? $slug,
            'kategori' => $meta['kategori'] ?? 'Umum',
            'urutan' => (int) ($meta['urutan'] ?? 999),
            'menu' => $meta['menu'] ?? '',
            'route' => $daftar($meta['route'] ?? '', ','),
            'kata_kunci' => $daftar($meta['kata_kunci'] ?? '', ','),
            'ringkasan' => $meta['ringkasan'] ?? '',
            'alur' => $daftar($meta['alur'] ?? '', '|'),
            'html' => Str::markdown($isi, ['html_input' => 'strip', 'allow_unsafe_links' => false]),
        ];

        // Teks ternormalisasi untuk pencarian
        $artikel['_judul'] = self::normal($artikel['judul']);
        $artikel['_kunci'] = self::normal(implode(' ', $artikel['kata_kunci']) . ' ' . $artikel['kategori']);
        $artikel['_menu'] = self::normal($artikel['menu'] . ' ' . $artikel['ringkasan']);
        $artikel['_isi'] = self::normal(strip_tags($artikel['html']) . ' ' . implode(' ', $artikel['alur']));

        return $artikel;
    }

    /** Pecah query jadi kata dasar yang dicari. */
    private static function token(string $q): array
    {
        $kata = preg_split('/[^a-z0-9]+/', Str::lower(Str::ascii($q)), -1, PREG_SPLIT_NO_EMPTY);
        $hasil = [];
        foreach ($kata as $k) {
            if (in_array($k, self::STOPWORDS, true) || strlen($k) < 2) {
                continue;
            }
            $k = self::SINONIM[$k] ?? $k;
            $hasil[] = self::dasar($k);
        }
        return array_values(array_unique($hasil));
    }

    /** Teks -> " kata1 kata2 ... " (sudah di-stem) supaya cocok per kata. */
    private static function normal(string $teks): string
    {
        $kata = preg_split('/[^a-z0-9]+/', Str::lower(Str::ascii($teks)), -1, PREG_SPLIT_NO_EMPTY);
        $kata = array_map(fn ($k) => self::dasar(self::SINONIM[$k] ?? $k), $kata);
        return ' ' . implode(' ', $kata) . ' ';
    }

    private static function ada(string $teksNormal, string $token): bool
    {
        // Cocok di awal kata: "prof" cocok dengan "proforma"
        return str_contains($teksNormal, ' ' . $token);
    }

    /** Stemmer bahasa Indonesia yang sangat sederhana (membuat -> buat, tagihannya -> tagih). */
    private static function dasar(string $k): string
    {
        if (strlen($k) <= 4 || ctype_digit($k)) {
            return $k;
        }
        foreach (['nya', 'kan', 'an', 'i'] as $akhir) {
            if (str_ends_with($k, $akhir) && strlen($k) - strlen($akhir) >= 4) {
                $k = substr($k, 0, -strlen($akhir));
                break;
            }
        }
        foreach (['meng', 'meny', 'mem', 'men', 'me', 'peng', 'pem', 'pen', 'di', 'ber', 'ter'] as $awal) {
            if (str_starts_with($k, $awal) && strlen($k) - strlen($awal) >= 4) {
                $k = substr($k, strlen($awal));
                break;
            }
        }
        return $k;
    }
}

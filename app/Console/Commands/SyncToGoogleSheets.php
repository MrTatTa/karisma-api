<?php

namespace App\Console\Commands;

use App\Models\DailySerialNumber;
use App\Models\Transaction;
use Google\Client;
use Google\Service\Sheets;
use Google\Service\Sheets\ValueRange;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SyncToGoogleSheets extends Command
{
    protected $signature   = 'karisma:sync-sheets {--date= : Tanggal spesifik (Y-m-d), default hari ini}';
    protected $description = 'Sync data harian KARISMA ke Google Sheets';

    public function handle()
    {
        $date     = $this->option('date') ?? now()->toDateString();
        $carbon   = Carbon::parse($date);
        $monthTab = strtoupper($carbon->locale('id')->monthName);

        $this->info("Syncing data untuk $date ke tab $monthTab...");

        // 1. Ambil data transaksi
        $categories   = ['roda23', 'roda4', 'rodaplus4', 'bermalam'];
        $transactions = Transaction::where('date', $date)
            ->selectRaw('vehicle_type, COUNT(*) as total, SUM(tariff_amount) as pendapatan')
            ->groupBy('vehicle_type')
            ->get()
            ->keyBy('vehicle_type');

        // 2. Ambil data serial
        $serials = DailySerialNumber::where('date', $date)
            ->get()
            ->keyBy('vehicle_type');

        // 3. Hitung data per kategori
        $data = [];
        foreach ($categories as $cat) {
            $trx        = $transactions->get($cat);
            $serial     = $serials->get($cat);
            $total      = $trx ? (int) $trx->total : 0;
            $pendapatan = $trx ? (int) $trx->pendapatan : 0;

            $serialStart = $serial ? $serial->serial_start : '-';
            $serialEnd   = '-';

            if ($serial && $total > 0) {
                $serialEnd = str_pad(
                    (int) $serial->serial_start + ($total - 1),
                    strlen($serial->serial_start),
                    '0',
                    STR_PAD_LEFT
                );
            } elseif ($serial) {
                $serialEnd = $serial->serial_start;
            }

            $data[$cat] = [
                'total'        => $total,
                'pendapatan'   => $pendapatan,
                'serial_start' => $serialStart,
                'serial_end'   => $serialEnd,
            ];
        }

        $totalPendapatan = collect($data)->sum('pendapatan');

        // 4. Format baris untuk Sheets
        $row = [
            $carbon->format('d/m/Y'),                    // Tanggal
            $data['roda23']['serial_start'],             // Seri Awal R2/3
            $data['roda23']['serial_end'],               // Seri Akhir R2/3
            $data['roda23']['total'],                    // Jumlah R2/3
            $data['roda23']['pendapatan'],               // Pendapatan R2/3
            $data['roda4']['serial_start'],              // Seri Awal R4
            $data['roda4']['serial_end'],                // Seri Akhir R4
            $data['roda4']['total'],                     // Jumlah R4
            $data['roda4']['pendapatan'],                // Pendapatan R4
            $data['rodaplus4']['serial_start'],          // Seri Awal R+4
            $data['rodaplus4']['serial_end'],            // Seri Akhir R+4
            $data['rodaplus4']['total'],                 // Jumlah R+4
            $data['rodaplus4']['pendapatan'],            // Pendapatan R+4
            $data['bermalam']['total'],                  // Jumlah Bermalam
            $data['bermalam']['pendapatan'],             // Pendapatan Bermalam
            $totalPendapatan,                            // Total Pendapatan
        ];

        // 5. Koneksi ke Google Sheets
        try {
            $client = new Client();
            $credentialsPath = storage_path('app/google-credentials.json');
            if (file_exists($credentialsPath)) {
                $client->setAuthConfig($credentialsPath);
            } else {
                $credentials = json_decode(env('GOOGLE_CREDENTIALS_JSON'), true);
                $client->setAuthConfig($credentials);
            }
            $client->addScope(Sheets::SPREADSHEETS);

            $service       = new Sheets($client);
            $spreadsheetId = env('GOOGLE_SPREADSHEET_ID');

            // Cek apakah tab bulan sudah ada
            $spreadsheet = $service->spreadsheets->get($spreadsheetId);
            $sheetTitles = collect($spreadsheet->getSheets())
                ->map(fn($s) => $s->getProperties()->getTitle())
                ->toArray();

            // Buat tab baru kalau belum ada
            if (!in_array($monthTab, $sheetTitles)) {
                $this->createMonthTab($service, $spreadsheetId, $monthTab);
                $this->info("Tab $monthTab dibuat.");
            }

            // === PERBAIKAN: Pastikan header dibuat SEBELUM insert/cek data ===
            $this->ensureHeader($service, $spreadsheetId, $monthTab);

            // Cari baris yang sudah ada untuk tanggal ini (hindari duplikat)
            $range    = "$monthTab!A:A";
            $response = $service->spreadsheets_values->get($spreadsheetId, $range);
            $values   = $response->getValues() ?? [];

            $targetRow = null;
            $dateStr   = $carbon->format('d/m/Y');

            foreach ($values as $i => $val) {
                if (isset($val[0]) && $val[0] === $dateStr) {
                    $targetRow = $i + 1;
                    break;
                }
            }

            // Kalau belum ada, append. Kalau sudah ada, update
            if ($targetRow === null) {
                $body = new ValueRange(['values' => [$row]]);
                $service->spreadsheets_values->append(
                    $spreadsheetId,
                    "$monthTab!A:P",
                    $body,
                    ['valueInputOption' => 'RAW']
                );
                $this->info("Data ditambahkan ke tab $monthTab.");
            } else {
                $body = new ValueRange(['values' => [$row]]);
                $service->spreadsheets_values->update(
                    $spreadsheetId,
                    "$monthTab!A{$targetRow}:P{$targetRow}",
                    $body,
                    ['valueInputOption' => 'RAW']
                );
                $this->info("Data baris $targetRow diupdate.");
            }

            $this->info("✓ Sync berhasil untuk $date!");
        } catch (\Exception $e) {
            $this->error("Sync gagal: " . $e->getMessage());
            return 1;
        }

        return 0;
    }

    private function createMonthTab($service, $spreadsheetId, $monthTab)
    {
        $requests = [
            new \Google\Service\Sheets\Request([
                'addSheet' => [
                    'properties' => ['title' => $monthTab],
                ],
            ]),
        ];

        $batchUpdate = new \Google\Service\Sheets\BatchUpdateSpreadsheetRequest([
            'requests' => $requests,
        ]);

        $service->spreadsheets->batchUpdate($spreadsheetId, $batchUpdate);
    }

    private function ensureHeader($service, $spreadsheetId, $monthTab)
    {
        $range    = "$monthTab!A1:P1";
        $response = $service->spreadsheets_values->get($spreadsheetId, $range);
        $values   = $response->getValues() ?? [];

        if (empty($values) || empty($values[0])) {
            $header = [[
                'Tanggal',
                'Seri Awal R2/3',
                'Seri Akhir R2/3',
                'Jumlah R2/3',
                'Pendapatan R2/3',
                'Seri Awal R4',
                'Seri Akhir R4',
                'Jumlah R4',
                'Pendapatan R4',
                'Seri Awal R+4',
                'Seri Akhir R+4',
                'Jumlah R+4',
                'Pendapatan R+4',
                'Jumlah Bermalam',
                'Pendapatan Bermalam',
                'Total Pendapatan',
            ]];

            $body = new ValueRange(['values' => $header]);
            $service->spreadsheets_values->update(
                $spreadsheetId,
                "$monthTab!A1:P1",
                $body,
                ['valueInputOption' => 'RAW']
            );
        }
    }
}

<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Illuminate\Support\Facades\Log;

class ExcelHelpers
{
    /**
     * Parse tanggal dari Excel ke format Y-m-d
     * 
     * @param mixed $excelDate
     * @return string|null
     */
    public static function parseExcelDate($excelDate)
    {
        if (!$excelDate) {
            return null;
        }
        
        try {
            // Coba parse sebagai tanggal Excel
            if (is_numeric($excelDate)) {
                // Jika angka, anggap sebagai serial date Excel
                $dateObj = Carbon::instance(Date::excelToDateTimeObject($excelDate));
                return $dateObj->format('Y-m-d');
            }
            
            // Coba parse sebagai string tanggal
            if (is_string($excelDate)) {
                // Coba beberapa format tanggal umum
                $dateFormats = [
                    'd-M-y', 'd-M-Y', 'd/m/Y', 'm/d/Y', 'Y-m-d', 
                    'd-m-Y', 'd.m.Y', 'Y/m/d', 'Y.m.d'
                ];
                
                foreach ($dateFormats as $format) {
                    try {
                        $dateObj = Carbon::createFromFormat($format, $excelDate, 'Asia/Jakarta');
                        if ($dateObj !== false) {
                            return $dateObj->format('Y-m-d');
                        }
                    } catch (\Exception $e) {
                        continue; // Try next format
                    }
                }
                
                // Coba parse dengan strtotime
                $timestamp = strtotime($excelDate);
                if ($timestamp !== false) {
                    return date('Y-m-d', $timestamp);
                }
            }
            
            return null;
        } catch (\Exception $e) {
            Log::error('Error parsing Excel date: ' . $e->getMessage(), [
                'excel_date' => $excelDate
            ]);
            return null;
        }
    }
    
    /**
     * Bersihkan dan format time string
     * 
     * @param mixed $timeStr
     * @return string|null
     */
    public static function cleanTime($timeStr)
    {
        if (!$timeStr) {
            return null;
        }
        
        try {
            // Jika waktu dalam format numeric Excel (jam sebagai desimal)
            if (is_numeric($timeStr)) {
                $hours = floor($timeStr * 24);
                $minutes = floor(($timeStr * 24 * 60) % 60);
                $seconds = floor(($timeStr * 24 * 60 * 60) % 60);
                
                return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
            }
            
            // Bersihkan string waktu
            $cleaned = trim($timeStr);
            
            // Coba parse sebagai waktu (HH:MM:SS atau HH:MM)
            if (preg_match('/^(\d{1,2}):(\d{1,2})(?::(\d{1,2}))?$/', $cleaned, $matches)) {
                $hour = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                $minute = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
                $second = isset($matches[3]) ? str_pad($matches[3], 2, '0', STR_PAD_LEFT) : '00';
                
                return "$hour:$minute:$second";
            }
            
            // Default return jika format tidak dikenali
            return $cleaned;
            
        } catch (\Exception $e) {
            Log::error('Error cleaning time string: ' . $e->getMessage(), [
                'time_string' => $timeStr
            ]);
            return null;
        }
    }
    
    /**
     * Parse angka dari Excel (handling ribuan)
     * 
     * @param mixed $excelNumber
     * @return float|null
     */
    public static function parseExcelNumber($excelNumber)
    {
        if ($excelNumber === null || $excelNumber === '') {
            return null;
        }
        
        try {
            // Jika sudah dalam format angka
            if (is_numeric($excelNumber)) {
                return floatval($excelNumber);
            }
            
            // Bersihkan string angka dari format ribuan dan desimal
            if (is_string($excelNumber)) {
                // Hapus semua karakter kecuali angka, titik, dan koma
                $cleaned = trim($excelNumber);
                
                // Deteksi format angka (ID/EN)
                $hasCommaDecimal = strpos($cleaned, ',') !== false && (strpos($cleaned, '.') === false || strpos($cleaned, ',') > strpos($cleaned, '.'));
                
                if ($hasCommaDecimal) {
                    // Format Indonesia/Eropa: 1.234,56
                    $cleaned = str_replace('.', '', $cleaned); // Hapus pemisah ribuan
                    $cleaned = str_replace(',', '.', $cleaned); // Ganti koma jadi titik desimal
                } else {
                    // Format English: 1,234.56
                    $cleaned = str_replace(',', '', $cleaned); // Hapus pemisah ribuan
                }
                
                // Konversi ke float jika valid
                if (is_numeric($cleaned)) {
                    return floatval($cleaned);
                }
            }
            
            return null;
        } catch (\Exception $e) {
            Log::error('Error parsing Excel number: ' . $e->getMessage(), [
                'excel_number' => $excelNumber
            ]);
            return null;
        }
    }
}

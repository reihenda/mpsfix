<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'cartrack' => [
        // Kode negara akun Cartrack (id = Indonesia) -> https://fleetapi-id.cartrack.com/rest
        'region' => env('CARTRACK_REGION', 'id'),
        'base_url' => env('CARTRACK_BASE_URL'), // opsional, menimpa region
        'username' => env('CARTRACK_USERNAME'),
        'password' => env('CARTRACK_PASSWORD'),
        'timeout' => (int) env('CARTRACK_TIMEOUT', 20),

        // false = mode uji: jadwal tidak mengirim apa pun ke Cartrack (link_status jadi "dilewati")
        'push_enabled' => (bool) env('CARTRACK_PUSH_ENABLED', true),
        // Zona waktu data kita (jadwal & trip disimpan sebagai waktu lokal tanpa zona)
        'zona_lokal' => env('CARTRACK_ZONA_LOKAL', 'Asia/Jakarta'),
        // Driver di-link ke kendaraan sekian menit SEBELUM waktu mulai jadwal
        'link_lead_menit' => (int) env('CARTRACK_LINK_LEAD_MENIT', 0),
        // Driver dilepas & trip ditarik sekian menit SESUDAH tujuan terakhir pada rute tercapai
        'selesai_grace_menit' => (int) env('CARTRACK_SELESAI_GRACE_MENIT', 30),
        // Selama jadwal berjalan, rute dicek ke Cartrack paling sering sekali per sekian menit
        'rute_cek_menit' => (int) env('CARTRACK_RUTE_CEK_MENIT', 10),
        // Jaring pengaman: jadwal yang rutenya tak kunjung selesai ditutup paksa setelah sekian jam sejak mulai
        'batas_berjalan_jam' => (int) env('CARTRACK_BATAS_BERJALAN_JAM', 48),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];

<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

/** Ringkasan harian customer Rupiah yang saldonya di bawah minimum. */
class SaldoMinimumHarian extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param Collection $diBawah     [['nama','saldo','minimum','kurang','url']]
     * @param Collection $belumDiatur nama customer yang minimum saldonya belum diatur
     */
    public function __construct(
        public Collection $diBawah,
        public Collection $belumDiatur,
        public string $tanggal,
        public bool $tes = false,
    ) {
    }

    public function envelope(): Envelope
    {
        $judul = $this->diBawah->count()
            ? $this->diBawah->count() . ' customer saldonya di bawah minimum'
            : 'Tidak ada customer di bawah minimum saldo';

        return new Envelope(subject: ($this->tes ? '[TES] ' : '') . "[MPS] {$judul} — {$this->tanggal}");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.saldo-minimum-harian');
    }
}

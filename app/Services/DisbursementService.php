<?php

namespace App\Services;

class DisbursementService
{
    public const CHANNELS = [
        'tunai' => 'Tunai di Pos RT',
        'transfer_bank' => 'Transfer Rekening Bank',
        'ewallet' => 'Dompet Digital (E-Wallet)',
    ];

    public const BANKS = [
        'BCA' => 'Bank Central Asia (BCA)',
        'BRI' => 'Bank Rakyat Indonesia (BRI)',
        'Mandiri' => 'Bank Mandiri',
        'BNI' => 'Bank Negara Indonesia (BNI)',
        'BSI' => 'Bank Syariah Indonesia (BSI)',
        'Bank Jago' => 'Bank Jago',
        'CIMB' => 'CIMB Niaga',
    ];

    public const EWALLETS = [
        'GoPay' => 'GoPay',
        'OVO' => 'OVO',
        'DANA' => 'DANA',
        'ShopeePay' => 'ShopeePay',
        'LinkAja' => 'LinkAja',
    ];

    public static function formatChannelLabel(?string $channel, ?string $provider = null, ?string $accountNumber = null, ?string $accountHolder = null): string
    {
        if ($channel === 'transfer_bank') {
            $bank = $provider ? strtoupper($provider) : 'Bank';
            $num = $accountNumber ? " ({$accountNumber})" : '';
            $holder = $accountHolder ? " a.n {$accountHolder}" : '';

            return "Transfer {$bank}{$num}{$holder}";
        }

        if ($channel === 'ewallet') {
            $wallet = $provider ?: 'E-Wallet';
            $num = $accountNumber ? " ({$accountNumber})" : '';
            $holder = $accountHolder ? " a.n {$accountHolder}" : '';

            return "{$wallet}{$num}{$holder}";
        }

        return 'Tunai di Posko RT';
    }

    /**
     * @param  array<string, mixed>  $receipt
     */
    public static function generateWhatsAppMessage(array $receipt): string
    {
        $id = strtoupper(substr((string) ($receipt['id'] ?? '-'), 0, 8));
        $name = (string) ($receipt['residentName'] ?? '-');
        $amount = number_format((int) ($receipt['totalAmount'] ?? 0), 0, ',', '.');
        $newBalance = number_format((int) ($receipt['newBalance'] ?? 0), 0, ',', '.');
        $date = (string) ($receipt['date'] ?? now()->translatedFormat('d F Y, H:i'));
        $officer = (string) ($receipt['officerName'] ?? 'Pengurus RT');
        $type = (string) ($receipt['type'] ?? 'withdrawal');

        if ($type === 'deposit') {
            $itemsList = '';
            if (! empty($receipt['items']) && is_array($receipt['items'])) {
                foreach ($receipt['items'] as $item) {
                    $itemName = $item['name'] ?? 'Sampah';
                    $itemWeight = $item['weight'] ?? 0;
                    $itemUnit = $item['unit'] ?? 'kg';
                    $itemSubtotal = number_format((int) ($item['subtotal'] ?? 0), 0, ',', '.');
                    $itemsList .= "  • {$itemName} ({$itemWeight} {$itemUnit}): Rp {$itemSubtotal}\n";
                }
            }

            return "🧾 *BUKTI SETORAN SAMPAH*\n"
                ."*Bank Sampah RT 05 / RW 08*\n"
                ."━━━━━━━━━━━━━━━━━━━━━━\n"
                ."🆔 *No. Transaksi:* #{$id}\n"
                ."👤 *Nasabah:* {$name}\n"
                .($itemsList !== '' ? "📦 *Rincian Setoran:*\n{$itemsList}" : '')
                ."💰 *Total Pendapatan:* +Rp {$amount}\n"
                ."🏦 *Total Saldo Tabungan:* Rp {$newBalance}\n"
                ."📅 *Waktu:* {$date} WIB\n"
                ."✍️ *Petugas Timbang:* {$officer}\n"
                ."━━━━━━━━━━━━━━━━━━━━━━\n"
                .'🌱 _Terima kasih telah aktif memilah sampah untuk lingkungan asri & bernilai!_';
        }

        $channelLabel = (string) ($receipt['channelLabel'] ?? $receipt['channel'] ?? 'Tunai di Posko RT');

        return "🧾 *BUKTI PENCAIRAN TABUNGAN SAMPAH*\n"
            ."*Bank Sampah RT 05 / RW 08*\n"
            ."━━━━━━━━━━━━━━━━━━━━━━\n"
            ."🆔 *No. Transaksi:* #{$id}\n"
            ."👤 *Nasabah:* {$name}\n"
            ."💰 *Nominal Dicairkan:* Rp {$amount}\n"
            ."💳 *Metode:* {$channelLabel}\n"
            ."🏦 *Sisa Saldo Tabungan:* Rp {$newBalance}\n"
            ."📅 *Waktu Pencairan:* {$date} WIB\n"
            ."✍️ *Petugas Pengesah:* {$officer}\n"
            ."━━━━━━━━━━━━━━━━━━━━━━\n"
            ."✨ *Catatan:* Simpan bukti digital ini sebagai tanda terima sah penarikan saldo tabungan Anda.\n"
            .'🌱 _Terima kasih telah aktif memilah sampah untuk lingkungan asri & bernilai!_';
    }

    /**
     * @param  array<string, mixed>  $receipt
     */
    public static function generateWhatsAppUrl(?string $phone, array $receipt): string
    {
        if (! $phone) {
            return '';
        }

        $cleanPhone = preg_replace('/\D/', '', $phone);
        if (str_starts_with($cleanPhone, '0')) {
            $cleanPhone = '62'.substr($cleanPhone, 1);
        }

        $message = self::generateWhatsAppMessage($receipt);

        return 'https://wa.me/'.$cleanPhone.'?text='.rawurlencode($message);
    }
}

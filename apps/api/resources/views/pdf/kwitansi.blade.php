<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kwitansi {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; }
        .header h2 { margin: 0; font-size: 18px; }
        .header p { margin: 2px 0; }
        .info-row { display: flex; justify-content: space-between; margin-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th { background: #f0f0f0; padding: 6px; text-align: left; border: 1px solid #ddd; }
        td { padding: 6px; border: 1px solid #ddd; }
        .total-row { font-weight: bold; background: #f9f9f9; }
        .footer { margin-top: 30px; text-align: center; font-size: 10px; color: #888; }
        .paid-stamp { text-align: right; margin-top: 20px; }
        .paid-stamp span { border: 2px solid green; color: green; padding: 4px 12px; font-weight: bold; font-size: 14px; }
    </style>
</head>
<body>
    <div class="header">
        <h2>{{ $invoice->clinic?->name ?? 'Klinik' }}</h2>
        <p>KWITANSI PEMBAYARAN</p>
    </div>

    <div class="info-row">
        <span>No. Invoice: <strong>{{ $invoice->invoice_number }}</strong></span>
        <span>Tanggal: <strong>{{ $invoice->paid_at?->format('d M Y H:i') }}</strong></span>
    </div>
    <div class="info-row">
        <span>Pasien: <strong>{{ $invoice->patient?->name ?? '-' }}</strong></span>
        <span>Metode: <strong>{{ strtoupper($invoice->payment_method ?? '-') }}</strong></span>
    </div>

    <table>
        <thead>
            <tr>
                <th>Keterangan</th>
                <th style="text-align:center">Qty</th>
                <th style="text-align:right">Harga Satuan</th>
                <th style="text-align:right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
            <tr>
                <td>{{ $item->description }}</td>
                <td style="text-align:center">{{ $item->quantity }}</td>
                <td style="text-align:right">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                <td style="text-align:right">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            @if($invoice->discount > 0)
            <tr>
                <td colspan="3" style="text-align:right">Diskon</td>
                <td style="text-align:right">- Rp {{ number_format($invoice->discount, 0, ',', '.') }}</td>
            </tr>
            @endif
            <tr class="total-row">
                <td colspan="3" style="text-align:right">TOTAL</td>
                <td style="text-align:right">Rp {{ number_format($invoice->total, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="paid-stamp">
        <span>LUNAS</span>
    </div>

    <div class="footer">
        Terima kasih atas kunjungan Anda. Dokumen ini digenerate secara otomatis oleh sistem Klinikku.
    </div>
</body>
</html>

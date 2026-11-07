<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $mailSubject ?? 'Sapaan Ulang Tahun' }}</title>
</head>
<body style="margin:0; padding:0; background:#f4f1ea; color:#241c2b; font-family:Arial, Helvetica, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f1ea; padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px; background:#ffffff; border:1px solid #e4ded2; border-radius:12px; overflow:hidden;">
                    <tr>
                        <td style="padding:22px 26px; background:#241c2b; color:#ffffff;">
                            <div style="font-size:12px; font-weight:700; letter-spacing:1.5px; text-transform:uppercase; color:#d8c9a8;">VeeStore</div>
                            <div style="margin-top:6px; font-size:22px; font-weight:700;">Sapaan Ulang Tahun</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:26px;">
                            <div style="font-size:15px; line-height:1.7; color:#241c2b;">{!! nl2br(e($mailBody)) !!}</div>

                            @if (! empty($voucher))
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:22px; border:2px dashed #c7a85a; border-radius:10px; background:#fbf7ec;">
                                    <tr>
                                        <td style="padding:18px 20px;">
                                            <div style="font-size:11px; font-weight:700; letter-spacing:1.2px; text-transform:uppercase; color:#8a6d2f;">Voucher Spesial Ulang Tahun</div>
                                            <div style="margin-top:6px; font-size:18px; font-weight:700; color:#241c2b;">{{ $voucher['name'] }}</div>
                                            <div style="margin-top:14px; font-size:12px; color:#6d6474;">Kode voucher</div>
                                            <div style="margin-top:4px; font-family:'Courier New', Courier, monospace; font-size:24px; font-weight:700; letter-spacing:1px; color:#241c2b; word-break:break-all;">{{ $voucher['code'] }}</div>
                                            <div style="margin-top:6px; font-size:14px; font-weight:700; color:#8a6d2f;">{{ $voucher['value_label'] }}</div>

                                            @if (! empty($voucher['min_purchase_label']))
                                                <div style="margin-top:8px; font-size:13px; color:#6d6474;">{{ $voucher['min_purchase_label'] }}</div>
                                            @endif

                                            @if (! empty($voucher['valid_from_label']) || ! empty($voucher['valid_until_label']))
                                                <div style="margin-top:8px; font-size:13px; color:#6d6474;">
                                                    Masa berlaku: {{ $voucher['valid_from_label'] ?? 'sekarang' }} sampai {{ $voucher['valid_until_label'] ?? 'tanpa batas' }}
                                                </div>
                                            @endif

                                            @if (! empty($voucher['description']))
                                                <div style="margin-top:10px; font-size:13px; line-height:1.6; color:#6d6474;">{{ $voucher['description'] }}</div>
                                            @endif
                                        </td>
                                    </tr>
                                </table>

                                <div style="margin-top:12px; font-size:12px; line-height:1.6; color:#6d6474;">
                                    Tunjukkan atau gunakan kode voucher ini saat bertransaksi.
                                </div>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 26px; background:#f4f1ea; font-size:12px; color:#6d6474; text-align:center;">
                            Email ini dikirim oleh VeeStore.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        @page {
            margin: 24px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #222
        }

        table {
            width: 100%;
            border-collapse: collapse
        }

        .header td,
        .info td,
        .footer td {
            border: none
        }

        .title {
            font-size: 24px;
            font-weight: bold
        }

        .subtitle {
            font-size: 14px;
            color: #666
        }

        .company {
            text-align: right
        }

        hr {
            border: none;
            border-top: 3px solid #222;
            margin: 18px 0
        }

        .info td {
            padding: 4px 0
        }

        .label {
            width: 180px;
            color: #666
        }

        /* .cards {
            text-align: center;
            margin: 20px 0;
        }

        .card {
            display: inline-block;
            width: 28%;
            margin: 0 1%;
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 12px;
            vertical-align: top;
            box-sizing: border-box;
        } */

        .card:last-child {
            margin-right: 0
        }

        .card-title {
            font-size: 11px;
            color: #666
        }

        .card-value {
            font-size: 18px;
            font-weight: bold;
            margin-top: 6px
        }

        .red {
            color: #c00
        }

        .green {
            color: #0c0
        }

        .alert {
            margin: 16px 0;
            padding: 10px;
            border: 1px solid #f2b0b0;
            background: #fff4f4;
            color: #900
        }

        .tx th {
            background: #efefef;
            border: 1px solid #aaa;
            padding: 8px
        }

        .tx td {
            border: 1px solid #ccc;
            padding: 8px
        }

        .right {
            text-align: right
        }

        .center {
            text-align: center
        }

        .footer {
            margin-top: 60px
        }

        .sign {
            margin-top: 60px;
            border-top: 1px solid #222;
            display: inline-block;
            width: 180px;
            padding-top: 5px;
            text-align: center
        }
    </style>
</head>

<body>

    <table class="header">
        <tr>
            <td>
                <div class="title">Laporan Pertanggungjawaban CA PL</div>
                <div class="subtitle">Periode #{{ $ca->periode ?? '-' }} ({{ ucfirst($ca->status) }})</div>
            </td>
            <td class="company">
                <b>PT Hanatekindo Mulia Abadi</b><br>
                Cash Advance - Dompet PL
            </td>
        </tr>
    </table>

    <hr>

    <table class="info">
        <tr>
            <td class="label">Jenis Dompet</td>
            <td>: CA PL (Imprest / Dana Tetap)</td>
        </tr>
        <tr>
            <td class="label">Saldo Float</td>
            <td>: Rp {{ number_format($ca->saldo_awal_priode, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="label">Periode</td>
            <td>: {{ $ca->tanggal_mulai }} s/d {{ $ca->tanggal_selesai }}</td>
        </tr>
        <tr>
            <td class="label">Tanggal Cetak</td>
            <td>: {{ now()->format('d-m-Y') }}</td>
        </tr>
    </table>

    <table class="cards" style="width:100%; margin:20px 0; border-collapse:separate; border-spacing:10px 0;">
        <tr>
            <td style="width:33.33%; border:1px solid #ddd; border-radius:8px; padding:12px; text-align:center;">
                <div class="card-title">Saldo Awal</div>
                <div class="card-value">
                    Rp {{ number_format($ca->saldo_awal_priode, 0, ',', '.') }}
                </div>
            </td>

            <td style="width:33.33%; border:1px solid #ddd; border-radius:8px; padding:12px; text-align:center;">
                <div class="card-title">Total Pengeluaran</div>
                <div class="card-value red">
                    Rp {{ number_format(
    $ca->tr_ca_transaction
        ->where('jenis', 'pengeluaran')
        ->sum('jumlah'),
    0,
    ',',
    '.'
) }}
                </div>
            </td>

            <td style="width:33.33%; border:1px solid #ddd; border-radius:8px; padding:12px; text-align:center;">
                <div class="card-title">Saldo Akhir</div>
                <div class="card-value {{ $ca->saldo_akhir < 0 ? 'red' : '' }}">
                    Rp {{ number_format($ca->saldo_akhir, 0, ',', '.') }}
                </div>
            </td>
        </tr>
    </table>

    <div class="alert">
        <b>Reimbursement / Talangan PL :</b>
        Rp {{ number_format(abs(min($ca->saldo_akhir, 0)), 0, ',', '.') }}
    </div>

    <table class="tx">
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Kategori</th>
                <th>Jenis</th>
                <th>Deskripsi</th>
                <th>Pengeluaran</th>
                <th>Saldo Setelah</th>
                <th>Bukti</th>
            </tr>
        </thead>
        <tbody>
            @foreach($ca->tr_ca_transaction as $i => $trx)
                <tr>
                    <td class="center">{{ $i + 1 }}</td>
                    <td>{{ $trx->tanggal }}</td>
                    <td>{{ $trx->kategori }}</td>
                    <td>{{ $trx->jenis }}</td>
                    <td>{{ $trx->deskripsi }}</td>
                    @if ($trx->jenis == "penerimaan")

                        <td class="right green">Rp {{ number_format($trx->jumlah, 0, ',', '.') }}</td>
                    @else
                        <td class="right red">Rp {{ number_format($trx->jumlah, 0, ',', '.') }}</td>
                    @endif
                    <td class="right {{ $trx->saldo_setelah < 0 ? 'red' : '' }}">Rp
                        {{ number_format($trx->saldo_setelah, 0, ',', '.') }}
                    </td>
                    <td class="center">@if($trx->bukti)✔@else-@endif</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="footer">
        <tr>
            <td align="center">
                Dibuat Oleh<br><br><br><br>
                <div class="sign">Project Leader</div>
            </td>
            <td align="center">
                Diverifikasi<br><br><br><br>
                <div class="sign">Finance</div>
            </td>
        </tr>
    </table>

</body>

</html>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CA Project Leader</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icon -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        body {
            background: #f4f7fb;
        }

        .wallet-card {
            border: none;
            border-radius: 20px;
            overflow: hidden;
            transition: .3s;
            box-shadow: 0 4px 15px rgba(0, 0, 0, .05);
        }

        .wallet-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, .08);
        }

        .wallet-header {
            background: linear-gradient(135deg, #0d6efd, #3b82f6);
            color: white;
            padding: 20px;
        }

        .wallet-balance {
            font-size: 32px;
            font-weight: bold;
        }

        .wallet-category {
            background: rgba(255, 255, 255, .2);
            padding: 5px 12px;
            border-radius: 50px;
            font-size: 13px;
        }

        .wallet-body {
            padding: 20px;
        }

        .info-label {
            font-size: 13px;
            color: #6c757d;
        }

        .info-value {
            font-weight: 600;
        }

        .status-badge {
            border-radius: 50px;
            padding: 8px 14px;
            font-size: 12px;
        }

        .topbar {
            background: white;
            border-radius: 20px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, .05);
        }
    </style>
</head>

<body>

    <div class="container py-4">

        <!-- Header -->
        <div class="topbar mb-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h3 class="fw-bold mb-1">
                    <i class="bi bi-wallet2 text-primary"></i>
                    Cash Advance Project Leader
                </h3>
                <small class="text-muted">
                    List Dompet Cash Advance
                </small>
            </div>

            <button class="btn btn-primary rounded-pill px-4">
                <i class="bi bi-plus-circle"></i>
                Tambah Dompet
            </button>
        </div>

        <!-- Main Wallet -->
        <div class="card border-0 rounded-4 shadow-sm mb-4 overflow-hidden">

            <div class="row g-0">

                <!-- Left -->
                <div class="col-lg-8">

                    <div class="p-4 bg-primary text-white h-100">

                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">

                            <div>
                                <small class="text-white-50">
                                    Dompet Utama
                                </small>

                                <h2 class="fw-bold mb-0">
                                    Rp 23.500.000
                                </h2>
                            </div>

                            <div>
                                <span class="badge bg-light text-primary rounded-pill px-3 py-2">
                                    Cash Advance PL
                                </span>
                            </div>

                        </div>

                        <div class="row mt-4">

                            <div class="col-md-4 mb-3">
                                <small class="text-white-50 d-block">
                                    Total Penerimaan
                                </small>

                                <h5 class="fw-bold text-white mb-0">
                                    Rp 30.000.000
                                </h5>
                            </div>

                            <div class="col-md-4 mb-3">
                                <small class="text-white-50 d-block">
                                    Total Pengeluaran
                                </small>

                                <h5 class="fw-bold text-warning mb-0">
                                    Rp 6.500.000
                                </h5>
                            </div>

                            <div class="col-md-4 mb-3">
                                <small class="text-white-50 d-block">
                                    Total Dompet
                                </small>

                                <h5 class="fw-bold mb-0">
                                    2 Wallet
                                </h5>
                            </div>

                        </div>

                    </div>

                </div>

                <!-- Right -->
                <div class="col-lg-4">

                    <div class="p-4 bg-white h-100 d-flex flex-column justify-content-center">

                        <div class="mb-3">
                            <small class="text-muted d-block">
                                Status
                            </small>

                            <span class="badge bg-success rounded-pill px-3 py-2">
                                Active
                            </span>
                        </div>

                        <div class="mb-3">
                            <small class="text-muted d-block">
                                Last Update
                            </small>

                            <strong>
                                16 Mei 2026
                            </strong>
                        </div>

                        <button class="btn btn-primary rounded-pill w-100">
                            <i class="bi bi-eye"></i>
                            Lihat Detail Dompet
                        </button>

                    </div>

                </div>

            </div>

        </div>

        <!-- List Wallet -->
        <div class="row g-4">

            <!-- Card -->
            <div class="col-md-6 col-lg-4">
                <div class="card wallet-card">

                    <div class="wallet-header">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <small>Kode CA</small>
                                <h5 class="fw-bold mb-0">CA-2026-001</h5>
                            </div>

                            <span class="wallet-category">
                                Operasional
                            </span>
                        </div>

                        <div class="mt-4">
                            <small>Total Saldo</small>
                            <div class="wallet-balance">
                                Rp 15.000.000
                            </div>
                        </div>
                    </div>

                    <div class="wallet-body">

                        <div class="mb-3">
                            <div class="info-label">Judul Kegiatan</div>
                            <div class="info-value">
                                Pengadaan Infrastruktur Server
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-6 mb-3">
                                <div class="info-label">Penerimaan</div>
                                <div class="info-value text-success">
                                    Rp 20.000.000
                                </div>
                            </div>

                            <div class="col-6 mb-3">
                                <div class="info-label">Pengeluaran</div>
                                <div class="info-value text-danger">
                                    Rp 5.000.000
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-3">

                            <span class="badge bg-success status-badge">
                                Active
                            </span>

                            <button class="btn btn-outline-primary btn-sm rounded-pill px-3">
                                Detail
                            </button>

                        </div>

                    </div>

                </div>
            </div>

            <!-- Card -->
            <div class="col-md-6 col-lg-4">
                <div class="card wallet-card">

                    <div class="wallet-header bg-success">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <small>Kode CA</small>
                                <h5 class="fw-bold mb-0">CA-2026-002</h5>
                            </div>

                            <span class="wallet-category">
                                Marketing
                            </span>
                        </div>

                        <div class="mt-4">
                            <small>Total Saldo</small>
                            <div class="wallet-balance">
                                Rp 8.500.000
                            </div>
                        </div>
                    </div>

                    <div class="wallet-body">

                        <div class="mb-3">
                            <div class="info-label">Judul Kegiatan</div>
                            <div class="info-value">
                                Event Promosi Produk
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-6 mb-3">
                                <div class="info-label">Penerimaan</div>
                                <div class="info-value text-success">
                                    Rp 10.000.000
                                </div>
                            </div>

                            <div class="col-6 mb-3">
                                <div class="info-label">Pengeluaran</div>
                                <div class="info-value text-danger">
                                    Rp 1.500.000
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-3">

                            <span class="badge bg-warning text-dark status-badge">
                                Pending
                            </span>

                            <button class="btn btn-outline-success btn-sm rounded-pill px-3">
                                Detail
                            </button>

                        </div>

                    </div>

                </div>
            </div>

        </div>

    </div>

</body>

</html>
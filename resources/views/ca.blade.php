<!DOCTYPE html>
<html>
<head>
    <title>Data Cash Advance</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-5">
    <h3 class="mb-4">Data Cash Advance</h3>

    <div class="d-flex justify-content-between mb-3">
        <h3>Data Cash Advance</h3>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
            + Tambah CA
        </button>
    </div>
    <table id="caTable" class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>No</th>
                <th>Kode CA</th>
                <th>Judul</th>
                <th>Tahun</th>
                <th>Status</th>
                <th width="180">Aksi</th>
            </tr>
        </thead>
    </table>
</div>

<!-- ================= MODAL DETAIL ================= -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5>Detail Cash Advance</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">

                <div class="mb-3">
                    <strong>Kode:</strong> <span id="d_kode"></span><br>
                    <strong>Judul:</strong> <span id="d_judul"></span><br>
                    <strong>Tahun:</strong> <span id="d_tahun"></span><br>
                    <strong>Status:</strong> <span id="d_status"></span><br>
                    <strong>Saldo Akhir:</strong> <span id="d_saldo"></span>
                </div>

                <hr>

                <h6>Transaksi</h6>
                <table class="table table-bordered">
                    <thead class="table-dark">
                        <tr>
                            <th>Tanggal</th>
                            <th>Jenis</th>
                            <th>Deskripsi</th>
                            <th>Jumlah</th>
                            <th>Saldo Setelah</th>
                        </tr>
                    </thead>
                    <tbody id="detailTransactions"></tbody>
                </table>

            </div>
        </div>
    </div>
</div>

<!-- ================= MODAL EDIT ================= -->
<div class="modal fade" id="editModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5>Edit Cash Advance</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editForm">
                    <input type="hidden" id="edit_id">

                    <div class="mb-3">
                        <label>Kode CA</label>
                        <input type="text" id="edit_kode" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label>Judul</label>
                        <input type="text" id="edit_judul" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label>Tahun</label>
                        <input type="number" id="edit_tahun" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label>Status</label>
                        <select id="edit_status" class="form-control">
                            <option value="draft">Draft</option>
                            <option value="submitted">Submitted</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Update</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ================= MODAL ADD ================= -->
<div class="modal fade" id="addModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5>Tambah Cash Advance</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addForm">

                    <div class="mb-3">
                        <label>Judul</label>
                        <input type="text" id="add_judul" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label>Tahun Anggaran</label>
                        <input type="number" id="add_tahun" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label>Tanggal Mulai</label>
                        <input type="date" id="add_mulai" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label>Tanggal Selesai</label>
                        <input type="date" id="add_selesai" class="form-control" required>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        Simpan
                    </button>

                </form>
            </div>
        </div>
    </div>
</div>

<!-- JQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<!-- Bootstrap -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- DataTables -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<!-- SweetAlert -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function () {

    let table = $('#caTable').DataTable({
        processing: true,
        ajax: {
            url: "/api/ca",
            dataSrc: "data"
        },
        columns: [
            {
                data: null,
                render: function (data, type, row, meta) {
                    return meta.row + 1;
                }
            },
            { data: 'kode_ca' },
            { data: 'judul_kegiatan' },
            { data: 'tahun_anggaran' },
            { data: 'status' },
            {
                data: null,
                render: function(data) {
                    return `
                        <button class="btn btn-info btn-sm detailBtn">Detail</button>
                        <button class="btn btn-warning btn-sm editBtn">Edit</button>
                        <button class="btn btn-danger btn-sm deleteBtn">Delete</button>
                    `;
                }
            }
        ]
    });

    // ================= ADD =================
    $('#addForm').submit(function(e){
        e.preventDefault();

        $.ajax({
            url: '/api/post_ca',
            type: 'POST',
            data: {
                judul_kegiatan: $('#add_judul').val(),
                tahun_anggaran: $('#add_tahun').val(),
                tanggal_mulai: $('#add_mulai').val(),
                tanggal_selesai: $('#add_selesai').val()
            },
            success: function(res){
                Swal.fire('Success','Data berhasil ditambahkan','success');
                $('#addModal').modal('hide');
                $('#addForm')[0].reset();
                table.ajax.reload();
            },
            error: function(xhr){
                let errors = xhr.responseJSON.errors;
                let pesan = '';
                for (let field in errors) {
                    pesan += errors[field][0] + '<br>';
                }
                Swal.fire('Error', pesan, 'error');
            }
        });
    });

    // ================= DETAIL =================
    $('#caTable').on('click', '.detailBtn', function() {

        let data = table.row($(this).parents('tr')).data();
        let kode = data.kode_ca;

        $.ajax({
            url: '/api/ca/' + kode,
            type: 'GET',
            success: function(res){

                let d = res.data;

                $('#d_kode').text(d.kode_ca);
                $('#d_judul').text(d.judul_kegiatan);
                $('#d_tahun').text(d.tahun_anggaran);
                $('#d_status').text(d.status);
                $('#d_saldo').text(formatRupiah(d.saldo_akhir));

                let rows = '';

                d.tr_c_a.forEach(function(tr){
                    rows += `
                        <tr>
                            <td>${tr.tanggal}</td>
                            <td>${tr.jenis}</td>
                            <td>${tr.deskripsi ?? '-'}</td>
                            <td>${formatRupiah(tr.jumlah)}</td>
                            <td>${formatRupiah(tr.saldo_setelah)}</td>
                        </tr>
                    `;
                });

                $('#detailTransactions').html(rows);

                new bootstrap.Modal(document.getElementById('detailModal')).show();
            }
        });

    });

    // ================= EDIT =================
    $('#caTable').on('click', '.editBtn', function() {
        let data = table.row($(this).parents('tr')).data();

        $('#edit_id').val(data.id);
        $('#edit_kode').val(data.kode_ca);
        $('#edit_judul').val(data.judul_kegiatan);
        $('#edit_tahun').val(data.tahun_anggaran);
        $('#edit_status').val(data.status);

        new bootstrap.Modal(document.getElementById('editModal')).show();
    });

    // Submit Edit
    $('#editForm').submit(function(e){
        e.preventDefault();

        let id = $('#edit_id').val();

        $.ajax({
            url: '/api/ca/' + id,
            type: 'PUT',
            data: {
                kode_ca: $('#edit_kode').val(),
                judul_kegiatan: $('#edit_judul').val(),
                tahun_anggaran: $('#edit_tahun').val(),
                status: $('#edit_status').val()
            },
            success: function(res){
                Swal.fire('Success','Data berhasil diupdate','success');
                $('#editModal').modal('hide');
                table.ajax.reload();
            }
        });
    });

    // ================= DELETE =================
    $('#caTable').on('click', '.deleteBtn', function() {
        let data = table.row($(this).parents('tr')).data();

        Swal.fire({
            title: 'Yakin hapus?',
            text: "Data tidak bisa dikembalikan!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, hapus!'
        }).then((result) => {
            if (result.isConfirmed) {

                $.ajax({
                    url: '/api/ca/' + data.id,
                    type: 'DELETE',
                    success: function(){
                        Swal.fire('Deleted!','Data berhasil dihapus.','success');
                        table.ajax.reload();
                    }
                });

            }
        });
    });

});

function formatRupiah(angka) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR'
    }).format(angka);
}
</script>

</body>
</html>
<!DOCTYPE html>
<html>
<head>
<title>Cash Advance</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>

body{
    background:#f4f6f9;
}

.card{
    border-radius:12px;
    box-shadow:0 4px 10px rgba(0,0,0,0.08);
}

.jumlah-plus{
    color:#198754;
    font-weight:bold;
}

.jumlah-minus{
    color:#dc3545;
    font-weight:bold;
}

</style>

</head>
<body>

<div class="container mt-5">

<div class="card p-4 mb-4">

<h4 id="judul"></h4>

<div class="row mt-3">

<div class="col-md-3">
<strong>Kode CA</strong>
<div id="kode"></div>
</div>

<div class="col-md-3">
<strong>User</strong>
<div id="user"></div>
</div>

<div class="col-md-3">
<strong>Tahun</strong>
<div id="tahun"></div>
</div>

<div class="col-md-3">
<strong>Saldo Akhir</strong>
<div id="saldo"></div>
</div>

</div>

</div>


<div class="card p-4">

<div class="d-flex justify-content-between mb-3">

<h5>Transaksi Cash Advance</h5>

<button class="btn btn-primary" id="btnTambah">
+ Tambah Transaksi
</button>

</div>

<table class="table table-bordered">

<thead>
<tr>
<th>Tanggal</th>
<th>Jenis</th>
<th>Deskripsi</th>
<th>Jumlah</th>
<th>Saldo Setelah</th>
</tr>
</thead>

<tbody id="listTransaksi"></tbody>

</table>

</div>

</div>


<!-- MODAL -->
<div class="modal fade" id="modalTransaksi">

<div class="modal-dialog">

<div class="modal-content">

<div class="modal-header">
<h5 class="modal-title">Tambah Transaksi</h5>
<button class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">

<form id="formTransaksi">

<div class="mb-3">
<label>Tanggal</label>
<input type="date" name="tanggal" class="form-control" required>
</div>

<div class="mb-3">
<label>Jenis</label>
<select name="jenis" class="form-control">
<option value="penerimaan">Penerimaan</option>
<option value="pengeluaran">Pengeluaran</option>
</select>
</div>

<div class="mb-3">
<label>Deskripsi</label>
<input type="text" name="deskripsi" class="form-control">
</div>

<div class="mb-3">
<label>Jumlah</label>
<input type="number" name="jumlah" class="form-control" required>
</div>

<button class="btn btn-success w-100">
Simpan
</button>

</form>

</div>

</div>

</div>

</div>



<!-- JS -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


<script>
    const BASE_URL = "{{ env('API_URL') }}";
</script>
<script>

let kodeCA = null;


/* FORMAT RUPIAH */
function rupiah(angka){
    return new Intl.NumberFormat('id-ID',{
        style:'currency',
        currency:'IDR'
    }).format(angka);
}


/* LOAD DATA */
function loadData(){

    $.ajax({
        // url:"http://127.0.0.1:8000/api/ca-pl",
        url: BASE_URL + "/ca-pl",
        method:"GET",
        headers:{
            "x-api-key":1
        },

        success:function(res){

            let data = res.data[0];

            kodeCA = data.kode_ca;

            $("#judul").text(data.judul_kegiatan);
            $("#kode").text(data.kode_ca);
            $("#user").text(data.username);
            $("#tahun").text(data.tahun_anggaran);
            $("#saldo").html("<b>"+rupiah(data.saldo_akhir)+"</b>");

            let html = "";

            data.tr_c_a.forEach(function(tr){

                let jumlahClass = tr.jenis === "pengeluaran" ? "jumlah-minus" : "jumlah-plus";
                let prefix = tr.jenis === "pengeluaran" ? "-" : "+";

                html += `
                <tr>
                    <td>${tr.tanggal}</td>
                    <td>
                        <span class="badge ${tr.jenis === 'pengeluaran' ? 'bg-danger' : 'bg-success'}">
                            ${tr.jenis}
                        </span>
                    </td>
                    <td>${tr.deskripsi ?? '-'}</td>
                    <td class="${jumlahClass}">
                        ${prefix} ${rupiah(tr.jumlah)}
                    </td>
                    <td>${rupiah(tr.saldo_setelah)}</td>
                </tr>
                `;

            });

            $("#listTransaksi").html(html);

        },

        error:function(){
            Swal.fire("Error","Gagal mengambil data","error")
        }

    });

}



/* DOCUMENT READY */
$(document).ready(function(){

    loadData();


    /* BUKA MODAL */
    $("#btnTambah").click(function(){
        $("#modalTransaksi").modal("show");
    });


    /* SUBMIT FORM */
    $("#formTransaksi").submit(function(e){

        e.preventDefault();

        let formData = $(this).serialize();

        $.ajax({

            // url:`http://127.0.0.1:8081/api/ca/${kodeCA}/transaksi`,
            url: BASE_URL + `/ca/${kodeCA}/transaksi`,
            method:"POST",

            headers:{
                "x-api-key":1
            },

            data:formData,

            success:function(res){

                Swal.fire({
                    icon:"success",
                    title:"Berhasil",
                    text:"Transaksi berhasil ditambahkan"
                });

                $("#modalTransaksi").modal("hide");

                $("#formTransaksi")[0].reset();

                loadData();

            },

            error:function(){

                Swal.fire({
                    icon:"error",
                    title:"Gagal",
                    text:"Gagal menyimpan transaksi"
                });

            }

        });

    });

});

</script>

</body>
</html>
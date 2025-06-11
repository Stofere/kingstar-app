@extends('layouts.app')

@section('title', 'Retur Penjualan - Cari Transaksi')

@push('styles')
<style>
    .item-info-retur { padding: 0.75rem; border-bottom: 1px solid #eee; }
    .item-info-retur:last-child { border-bottom: none; }
    .item-info-retur strong { display: block; margin-bottom: .25rem; } /* Membuat nama produk lebih menonjol */
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="mb-0">Retur Penjualan - Cari Transaksi</h1>
        <a href="{{ route('kasir.retur_penjualan.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-list-ul me-1"></i> Daftar Retur
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0">Pencarian Nota Penjualan</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-8 col-lg-6"> {{-- Batasi lebar input --}}
                    <label for="nomor_penjualan_cari" class="form-label">Masukkan Nomor Nota Penjualan:</label>
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" id="nomor_penjualan_cari" placeholder="Contoh: INV-KGT-020625-002" autofocus>
                        <button class="btn btn-primary" type="button" id="btn-cari-penjualan">
                            <i class="bi bi-search me-1"></i> Cari Nota
                        </button>
                    </div>
                </div>
            </div>

            <div id="hasil-pencarian-container" class="mt-3" style="display:none;">
                <hr>
                <div id="detail-transaksi-content" class="mb-3">
                    {{-- Detail transaksi akan diisi di sini --}}
                </div>

                <div id="item-retur-list" class="mb-3">
                    {{-- Daftar item yang bisa diretur --}}
                </div>

                <div class="text-end" id="area-tombol-lanjut-retur" style="display:none;">
                    <a href="#" id="btn-lanjut-ke-form-retur" class="btn btn-success">
                        Lanjutkan ke Form Retur <i class="bi bi-arrow-right-circle ms-1"></i>
                    </a>
                </div>
            </div>

            <div id="pesan-area" class="mt-3" style="display:none;">
                 {{-- Untuk pesan error atau info dari AJAX --}}
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script>
    $(document).ready(function() {
        function resetHasilPencarian() {
            $('#hasil-pencarian-container').hide();
            $('#detail-transaksi-content').empty();
            $('#item-retur-list').empty();
            $('#area-tombol-lanjut-retur').hide();
            $('#pesan-area').empty().hide(); // Bersihkan juga pesan area
        }

        function formatRupiah(angka) { // Helper  untuk format rupiah di JS
            return 'Rp ' + Number(angka).toLocaleString('id-ID');
        }

        $('#btn-cari-penjualan').on('click', function() {
            const nomorPenjualan = $('#nomor_penjualan_cari').val().trim();
            const btn = $(this);

            if (!nomorPenjualan) {
                Swal.fire('Input Kosong', 'Nomor nota penjualan wajib diisi.', 'warning');
                return;
            }

            resetHasilPencarian();
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Mencari...');
            console.log("Mencari nomor penjualan:", nomorPenjualan); // Log 1: Pencarian dimulai

            $.ajax({
                url: "{{ route('kasir.retur_penjualan.ajax.get_transaksi_detail') }}",
                type: 'GET',
                data: { nomor_penjualan: nomorPenjualan },
                success: function(response) {
                    console.log("AJAX Response:", response); // Log 2: Response dari server

                    if (response.success) {
                        const penjualan = response.penjualan;
                        const detailItems = response.detail_items_info; // Sesuai dengan key dari controller

                        let detailHtml = `<h5>Detail Transaksi Ditemukan:</h5>
                                          <p class="mb-1"><strong>No. Nota:</strong> ${penjualan.nomor_penjualan}</p>
                                          <p class="mb-1"><strong>Tanggal:</strong> ${penjualan.tanggal_penjualan_formatted}</p> {{-- Menggunakan key yang sudah diformat --}}
                                          <p class="mb-0"><strong>Pelanggan:</strong> ${penjualan.pelanggan_nama}</p>`;
                        $('#detail-transaksi-content').html(detailHtml);

                        let itemsHtml = '<h6>Item dalam Transaksi (yang masih bisa diretur):</h6>';
                        console.log("Detail Items dari response:", detailItems); // Log 3: Data item sebelum loop

                        if (detailItems && detailItems.length > 0) {
                            itemsHtml += '<div class="list-group">';
                            detailItems.forEach(function(item, index) {
                                console.log(`Processing item ${index}:`, item); // Log 4: Setiap item yang diloop
                                itemsHtml += `<div class="list-group-item item-info-retur">
                                                <strong>${item.nama_produk}</strong>
                                                <small class="d-block">Qty Beli: ${item.jumlah_beli_awal} | Harga Saat Beli: ${formatRupiah(item.harga_jual_item)}</small>
                                                <small class="d-block text-success">Sisa bisa diretur: ${item.sisa_qty_bisa_diretur_item} unit</small>`;
                                if (item.produk_memiliki_serial) {
                                    itemsHtml += `<small class="d-block text-info">Serial bisa diretur: ${item.serials_yang_masih_bisa_diretur.length > 0 ? item.serials_yang_masih_bisa_diretur.join(', ') : '-'}</small>`;
                                }
                                itemsHtml += `</div>`;
                            });
                            itemsHtml += '</div>';
                            $('#area-tombol-lanjut-retur').show();
                            // Pastikan route model binding di controller showReturForm menggunakan 'penjualan' sebagai nama parameter
                            $('#btn-lanjut-ke-form-retur').attr('href', "{{ url('kasir/retur-penjualan/form') }}/" + penjualan.id);
                        } else {
                             console.log("Tidak ada item yang bisa diretur berdasarkan response.detail_items_info");
                            itemsHtml += '<div class="alert alert-warning mt-2">Tidak ada item yang dapat diretur dari transaksi ini.</div>';
                        }
                        $('#item-retur-list').html(itemsHtml);
                        $('#hasil-pencarian-container').show();

                    } else {
                        console.error("AJAX request tidak sukses:", response.message);
                        $('#pesan-area').html(`<div class="alert alert-danger">${response.message || 'Gagal mengambil detail transaksi.'}</div>`).show();
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    let errorMsg = 'Terjadi kesalahan saat menghubungi server.';
                    if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                        errorMsg = jqXHR.responseJSON.message;
                    } else if (jqXHR.status === 404) {
                        errorMsg = 'Transaksi penjualan tidak ditemukan dengan nomor tersebut.';
                    } else if (jqXHR.status === 422) {
                        // Pesan dari validasi atau kondisi tidak terpenuhi di backend
                        errorMsg = jqXHR.responseJSON.message || 'Data tidak valid atau tidak memenuhi syarat.';
                    } else {
                        errorMsg = `Error: ${textStatus}, ${errorThrown}`;
                    }
                    console.error("AJAX Error:", errorMsg, jqXHR.responseText);
                    $('#pesan-area').html(`<div class="alert alert-danger">${errorMsg}</div>`).show();
                },
                complete: function() {
                    btn.prop('disabled', false).html('<i class="bi bi-search me-1"></i> Cari Nota');
                }
            });
        });

        $('#nomor_penjualan_cari').on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                $('#btn-cari-penjualan').click();
            }
        });
    });
    </script>
@endpush
@extends('layouts.app')

@section('title', 'Cek Stok Barang Gudang')

@push('styles')
    <style>
        .hasil-cek-stok { margin-top: 1.5rem; }
        .produk-stok-item {
            border: 1px solid #dee2e6;
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: .375rem;
        }
        .produk-stok-item h5 { margin-bottom: 0.5rem; }
        .stok-rendah { color: #dc3545; font-weight: bold; } /* Merah untuk stok rendah */
        .stok-habis { color: #dc3545; font-weight: bold; background-color: #f8d7da; padding: 0.2rem 0.4rem; border-radius: .25rem; }
        .batch-detail-list { font-size: 0.85rem; padding-left: 1.2rem; }
        .batch-detail-list li { margin-bottom: 0.2rem; }
    </style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="mb-0 h3">Cek Stok Barang Gudang</h1>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="bi bi-search me-2"></i>Pencarian Produk</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-8 col-lg-6">
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" id="search-produk-gudang-input" placeholder="Ketik Nama atau Kode Produk...">
                        <button class="btn btn-primary" type="button" id="btn-search-produk-gudang">
                            <i class="bi bi-search"></i> Cari
                        </button>
                    </div>
                </div>
            </div>

            <div id="hasil-pencarian-stok-gudang" class="hasil-cek-stok" style="display: none;">
                {{-- Hasil akan ditampilkan di sini --}}
            </div>
            <div id="loading-indicator-gudang" style="display: none;" class="text-center mt-3">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Memuat...</span>
                </div>
            </div>
             <div id="pesan-error-gudang" style="display: none;" class="alert alert-danger mt-3"></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script>
    $(document).ready(function() {
        function formatRupiah(angka) { 
            const numberString = angka.toString().replace(/[^,\d]/g, '');
            const split = numberString.split(',');
            let sisa = split[0].length % 3;
            let rupiah = split[0].substr(0, sisa);
            const ribuan = split[0].substr(sisa).match(/\d{3}/gi);

            if (ribuan) {
                const separator = sisa ? '.' : '';
                rupiah += separator + ribuan.join('.');
            }

            return split[1] !== undefined ? rupiah + ',' + split[1] : rupiah;
        }

        function tampilkanHasilStok(items) {
            const container = $('#hasil-pencarian-stok-gudang');
            container.empty().hide();
            $('#pesan-error-gudang').hide();

            if (!items || items.length === 0) {
                container.html('<p class="text-muted">Produk tidak ditemukan atau tidak ada stok di lokasi yang dipantau.</p>').show();
                return;
            }

            items.forEach(function(produk) {
                let statusStokClass = '';
                let statusStokText = '';
                // Perhitungan status rendah/habis sekarang berdasarkan total_stok_di_lokasi_diminati
                if (produk.total_stok_di_lokasi_diminati <= 0) {
                    statusStokClass = 'stok-habis';
                    statusStokText = '(HABIS)';
                } else if (produk.stok_minimum > 0 && produk.total_stok_di_lokasi_diminati <= produk.stok_minimum) {
                    statusStokClass = 'stok-rendah';
                    statusStokText = '(STOK RENDAH)';
                }

                let produkHtml = `
                    <div class="produk-stok-item">
                        <h5>${produk.nama_produk_lengkap}</h5>
                        <p class="mb-1">
                            <strong>Total Stok Tersedia (di Gudang & Toko):</strong> 
                            <span class="${statusStokClass}">${produk.total_stok_di_lokasi_diminati} ${produk.satuan} ${statusStokText}</span>
                        </p>
                        ${produk.stok_minimum > 0 ? `<p class="mb-1"><small class="text-muted">Stok Minimum Global: ${produk.stok_minimum} ${produk.satuan}</small></p>` : ''}
                        ${produk.memiliki_serial ? '<p class="mb-1"><small class="text-info">Produk ini memiliki nomor seri.</small></p>' : ''}
                `;

                if (produk.detail_batch_di_lokasi_diminati && produk.detail_batch_di_lokasi_diminati.length > 0) {
                    produkHtml += '<p class="mb-1 mt-2"><strong>Detail Batch Tersedia:</strong></p><ul class="list-unstyled batch-detail-list">';
                    produk.detail_batch_di_lokasi_diminati.forEach(function(batch) {
                        produkHtml += `<li>
                            Lokasi: <strong class="text-primary">${batch.lokasi_batch}</strong> | 
                            ID Batch: ${batch.id_batch} | Sisa: ${batch.sisa_stok_batch} ${produk.satuan} | Tgl Terima: ${batch.diterima_at_batch} | Kondisi: ${batch.kondisi_batch}
                        </li>`;
                    });
                    produkHtml += '</ul>';
                } else {
                    produkHtml += '<p class="mb-1 mt-2"><small class="text-muted">Tidak ada detail batch aktif di lokasi yang dipantau untuk produk ini.</small></p>';
                }
                produkHtml += '</div>';
                container.append(produkHtml);
            });
            container.show();
        }

        function cariProdukStok() {
            const searchTerm = $('#search-produk-gudang-input').val().trim();
            const btn = $('#btn-search-produk-gudang');

            if (searchTerm.length < 2 && searchTerm.length !== 0) { // Minimal 2 karakter atau kosongkan untuk semua
                // Swal.fire('Info', 'Masukkan minimal 2 karakter untuk pencarian.', 'info');
                // Atau biarkan saja, AJAX akan return kosong jika backend menghandlenya
                $('#hasil-pencarian-stok-gudang').empty().hide();
                return;
            }
            if (searchTerm.length === 0){ // Jika input kosong, bersihkan hasil
                 $('#hasil-pencarian-stok-gudang').empty().hide();
                 $('#pesan-error-gudang').hide();
                 return;
            }


            $('#loading-indicator-gudang').show();
            $('#hasil-pencarian-stok-gudang').hide();
            $('#pesan-error-gudang').hide();
            btn.prop('disabled', true);

            $.ajax({
                url: "{{ route('gudang.stok.ajax_search_produk_gudang') }}",
                type: "GET",
                data: { q: searchTerm },
                success: function(response) {
                    if (response.items) {
                        tampilkanHasilStok(response.items);
                    } else {
                        $('#pesan-error-gudang').text('Format respons tidak sesuai.').show();
                    }
                },
                error: function(jqXHR) {
                    let errorMsg = jqXHR.responseJSON?.message || jqXHR.responseText || "Terjadi kesalahan saat mencari stok.";
                    $('#pesan-error-gudang').text(errorMsg).show();
                },
                complete: function() {
                    $('#loading-indicator-gudang').hide();
                    btn.prop('disabled', false);
                }
            });
        }

        $('#btn-search-produk-gudang').on('click', cariProdukStok);
        $('#search-produk-gudang-input').on('keypress', function(e) {
            if (e.which === 13) { // Tombol Enter
                e.preventDefault();
                cariProdukStok();
            }
        });
        $('#search-produk-gudang-input').on('input', function() { // Live search sederhana (opsional)
            if ($(this).val().length === 0 || $(this).val().length >= 2) {
                // cariProdukStok(); // Bisa aktifkan live search atau biarkan user klik tombol
            }
            if ($(this).val().length === 0) {
                $('#hasil-pencarian-stok-gudang').empty().hide();
                $('#pesan-error-gudang').hide();
            }
        });

    });
    </script>
@endpush
@extends('layouts.app')

@section('title', 'Laporan Pembelian')

@push('styles')
    <style>
        .filter-form .form-label {
            margin-bottom: 0.2rem;
            font-size: 0.85em;
            font-weight: 500;
        }
        .filter-form .form-select-sm,
        .filter-form .form-control-sm {
            font-size: 0.875em;
        }
        #laporan-pembelian-table th,
        #laporan-pembelian-table td {
            font-size: 0.875rem; /* Ukuran font isi tabel agar tidak terlalu besar */
        }
        #laporan-pembelian-table .btn-sm {
            padding: 0.2rem 0.4rem; /* Padding tombol aksi lebih kecil */
            font-size: 0.75rem;
        }
    </style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="mb-0">Laporan Pembelian</h1>
        {{-- Bisa ditambahkan tombol export jika diperlukan --}}
        {{-- <button class="btn btn-success btn-sm"><i class="bi bi-file-earmark-excel"></i> Export Excel</button> --}}
    </div>


    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="bi bi-funnel me-2"></i>Filter Laporan Pembelian</h5>
        </div>
        <div class="card-body filter-form">
            <form id="filter-form-pembelian" class="py-2">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3 col-lg-3">
                        <label for="tanggal_filter_pembelian" class="form-label">Rentang Tanggal Beli:</label>
                        <input type="text" class="form-control form-control-sm" id="tanggal_filter_pembelian" placeholder="Pilih tanggal..." autocomplete="off">
                        <input type="hidden" id="tanggal_mulai_filter_pembelian" name="tanggal_mulai">
                        <input type="hidden" id="tanggal_akhir_filter_pembelian" name="tanggal_akhir">
                    </div>
                    <div class="col-md-3 col-lg-2">
                        <label for="supplier_filter" class="form-label">Supplier:</label>
                        <select class="form-select form-select-sm select2-filter" id="supplier_filter" name="id_supplier" data-placeholder="Semua Supplier">
                            <option value=""></option>
                            @if(isset($supplierOptions))
                                @foreach($supplierOptions as $id => $nama)
                                    <option value="{{ $id }}">{{ $nama }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="col-md-2 col-lg-2">
                        <label for="status_pembelian_filter" class="form-label">Status Pembelian:</label>
                        <select class="form-select form-select-sm" id="status_pembelian_filter" name="status_pembelian">
                            <option value="">Semua Status</option>
                            @if(isset($statusPembelianOptions))
                                @foreach($statusPembelianOptions as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="col-md-2 col-lg-2">
                        <label for="status_pembayaran_filter" class="form-label">Status Bayar:</label>
                        <select class="form-select form-select-sm" id="status_pembayaran_filter" name="status_pembayaran">
                            <option value="">Semua Status</option>
                             @if(isset($statusPembayaranOptions))
                                @foreach($statusPembayaranOptions as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="col-md-2 col-lg-1">
                        <button type="button" class="btn btn-primary btn-sm w-100" id="btn-apply-filter-pembelian" title="Terapkan Filter">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                     <div class="col-md-2 col-lg-1">
                         <button type="button" class="btn btn-secondary btn-sm w-100" id="btn-reset-filter-pembelian" title="Reset Filter">
                            <i class="bi bi-arrow-clockwise"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
           <h5 class="mb-0"><i class="bi bi-table me-2"></i>Daftar Transaksi Pembelian</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="laporan-pembelian-table" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>No. Pembelian</th>
                            <th>No. Faktur Supp.</th>
                            <th>Tgl Beli</th>
                            <th>Supplier</th>
                            <th>User Pembuat</th>
                            <th class="text-end">Total Harga</th>
                            <th class="text-center">Status Beli</th>
                            <th class="text-center">Status Bayar</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- Data diisi oleh DataTables AJAX --}}
                    </tbody>
                     <tfoot>
                        <tr>
                            <th colspan="6" class="text-end fw-bold">Total Keseluruhan:</th>
                            <th id="total-pembelian-keseluruhan" class="text-end fw-bold"></th>
                            <th colspan="3"></th> {{-- Menyesuaikan colspan agar pas --}}
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            // Inisialisasi Select2 untuk filter
            $('#supplier_filter.select2-filter').select2({
                theme: "bootstrap-5",
                width: '100%', // atau 'style'
                placeholder: $(this).data('placeholder') || "Pilih Supplier",
                allowClear: true
            });

            // Inisialisasi Litepicker
            const pickerPembelian = new Litepicker({
                element: document.getElementById('tanggal_filter_pembelian'),
                singleMode: false,
                allowRepick: true,
                format: 'DD MMM YYYY',
                plugins: ['ranges'],
                tooltipText: { one: 'hari', other: 'hari' },
                ranges: {
                    customRanges: {
                        'Hari Ini': [new Date(), new Date()],
                        'Kemarin': [new Date(new Date().setDate(new Date().getDate() - 1)), new Date(new Date().setDate(new Date().getDate() - 1))],
                        '7 Hari Terakhir': [new Date(new Date().setDate(new Date().getDate() - 6)), new Date()],
                        '30 Hari Terakhir': [new Date(new Date().setDate(new Date().getDate() - 29)), new Date()],
                        'Bulan Ini': [new Date(new Date().getFullYear(), new Date().getMonth(), 1), new Date(new Date().getFullYear(), new Date().getMonth() + 1, 0)],
                        'Bulan Lalu': [new Date(new Date().getFullYear(), new Date().getMonth() - 1, 1), new Date(new Date().getFullYear(), new Date().getMonth(), 0)]
                    }
                },
                setup: (pickerInstance) => {
                    pickerInstance.on('selected', (date1, date2) => {
                        if (date1 && date2) {
                            $('#tanggal_mulai_filter_pembelian').val(date1.dateInstance.toISOString().split('T')[0]);
                            $('#tanggal_akhir_filter_pembelian').val(date2.dateInstance.toISOString().split('T')[0]);
                        } else {
                            $('#tanggal_mulai_filter_pembelian').val('');
                            $('#tanggal_akhir_filter_pembelian').val('');
                        }
                    });
                }
            });

            // Set default tanggal ke 1 bulan terakhir dan isi hidden input
            function setDefaultDateRangePembelian() {
                const today = new Date();
                const oneMonthAgo = new Date(new Date().setMonth(today.getMonth() - 1)); // Lebih akurat untuk bulan lalu
                // Jika ingin 30 hari terakhir:
                // const oneMonthAgo = new Date(new Date().setDate(today.getDate() - 29));
                pickerPembelian.setDateRange(oneMonthAgo, today);
                $('#tanggal_mulai_filter_pembelian').val(oneMonthAgo.toISOString().split('T')[0]);
                $('#tanggal_akhir_filter_pembelian').val(today.toISOString().split('T')[0]);
            }
            setDefaultDateRangePembelian(); // Panggil saat load

            // Inisialisasi DataTables
            var tablePembelian = $('#laporan-pembelian-table').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ajax: {
                    url: "{{ route('admin.laporan.pembelian.data') }}",
                    type: "GET",
                    data: function (d) { // Mengirim parameter filter tambahan
                        d.tanggal_mulai = $('#tanggal_mulai_filter_pembelian').val();
                        d.tanggal_akhir = $('#tanggal_akhir_filter_pembelian').val();
                        d.id_supplier = $('#supplier_filter').val();
                        d.status_pembelian = $('#status_pembelian_filter').val();
                        d.status_pembayaran = $('#status_pembayaran_filter').val();
                    },
                    error: function (xhr, error, thrown) {
                        // Menampilkan pesan error jika AJAX gagal
                        console.error("Error AJAX DataTables: ", xhr.responseText);
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal Memuat Data',
                            text: 'Terjadi kesalahan saat mengambil data pembelian. Silakan coba lagi atau hubungi administrator. (Detail: ' + (xhr.responseJSON ? xhr.responseJSON.message : thrown) + ')',
                        });
                        // Menghentikan indikator 'processing' DataTables
                        $('#laporan-pembelian-table_processing').css('display', 'none');
                    }
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, width: '5%' },
                    { data: 'nomor_pembelian', name: 'pembelian.nomor_pembelian' },
                    { data: 'nomor_faktur_supplier', name: 'pembelian.nomor_faktur_supplier', defaultContent: '-' },
                    { data: 'tanggal_pembelian_formatted', name: 'pembelian.tanggal_pembelian' },
                    { data: 'supplier_nama', name: 'supplier.nama', defaultContent: '-' }, // 'supplier.nama' untuk sorting jika pakai join
                    { data: 'pengguna_nama', name: 'pengguna.nama', defaultContent: '-' }, // 'pengguna.nama' untuk sorting
                    { data: 'total_harga_formatted', name: 'pembelian.total_harga', className: 'text-end' },
                    { data: 'status_pembelian_badge', name: 'pembelian.status_pembelian', className: 'text-center', orderable: true, searchable: true }, // Bisa disorting/search berdasarkan status asli
                    { data: 'status_pembayaran_badge', name: 'pembelian.status_pembayaran', className: 'text-center', orderable: true, searchable: true },
                    { data: 'action', name: 'action', orderable: false, searchable: false, width: '10%', className: 'text-center' }
                ],
                language: {
                    url: '{{ asset('js/i18n/id.json')}}',
                    processing: '<div class="d-flex justify-content-center align-items-center" style="height: 50px;"><div class="spinner-border text-primary spinner-border-sm" role="status"><span class="visually-hidden">Memuat...</span></div><span class="ms-2">Memuat data...</span></div>'
                },
                order: [[3, "desc"]], // Default order by tanggal pembelian (indeks kolom ke-3 di array `columns`)
                footerCallback: function (row, data, start, end, display) {
                    var api = this.api();
                    // Ambil data 'total_keseluruhan_pembelian' dari response JSON AJAX
                    var totalKeseluruhan = api.ajax.json() ? (api.ajax.json().total_keseluruhan_pembelian || 0) : 0;

                    // Update footer
                    // Pastikan fungsi formatRupiahGlobal sudah terdefinisi (biasanya di layouts.app.blade.php)
                    if (typeof formatRupiahGlobal === "function") {
                        $(api.column(6).footer()).html(formatRupiahGlobal(totalKeseluruhan));
                    } else {
                        $(api.column(6).footer()).html('Rp ' + (totalKeseluruhan || 0).toLocaleString('id-ID'));
                    }
                }
            });

            // Tombol Terapkan Filter
            $('#btn-apply-filter-pembelian').on('click', function() {
                tablePembelian.ajax.reload(); // Memuat ulang data DataTables dengan parameter filter baru
            });

            // Tombol Reset Filter
            $('#btn-reset-filter-pembelian').on('click', function() {
                $('#filter-form-pembelian')[0].reset(); // Reset form standar
                $('#supplier_filter').val(null).trigger('change'); // Reset Select2
                // Tidak perlu reset Litepicker secara manual jika setDefaultDateRange dipanggil lagi
                setDefaultDateRangePembelian(); // Set kembali ke default range
                tablePembelian.ajax.reload(); // Reload tabel
            });
        });
    </script>
@endpush
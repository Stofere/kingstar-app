{{-- admin/laporan/penjualan/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Laporan Penjualan')

@push('styles')
    <style>
        .filter-form .form-label { margin-bottom: 0.2rem; font-size: 0.85em;}
        .filter-form .form-select-sm, .filter-form .form-control-sm { font-size: 0.875em; }
    </style>
@endpush

@section('content')
<div class="container-fluid"> {{-- Ganti ke container-fluid agar lebih lebar --}}
    <h1 class="mb-4">Laporan Penjualan</h1>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0">Filter Laporan</h5>
        </div>
        <div class="card-body filter-form">
            <form id="filter-form-penjualan">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="tanggal_filter" class="form-label">Rentang Tanggal:</label>
                        <input type="text" class="form-control form-control-sm" id="tanggal_filter" placeholder="Pilih tanggal..." autocomplete="off">
                        {{-- Hidden inputs untuk tanggal mulai dan akhir --}}
                        <input type="hidden" id="tanggal_mulai_filter" name="tanggal_mulai">
                        <input type="hidden" id="tanggal_akhir_filter" name="tanggal_akhir">
                    </div>
                    <div class="col-md-2">
                        <label for="pelanggan_filter" class="form-label">Pelanggan:</label>
                        <select class="form-select form-select-sm select2-filter" id="pelanggan_filter" name="id_pelanggan" data-placeholder="Semua Pelanggan">
                            <option value=""></option>
                            @foreach($pelangganOptions as $id => $nama)
                                <option value="{{ $id }}">{{ $nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="kasir_filter" class="form-label">Kasir:</label>
                        <select class="form-select form-select-sm select2-filter" id="kasir_filter" name="id_kasir" data-placeholder="Semua Kasir">
                            <option value=""></option>
                             @foreach($kasirOptions as $id => $nama)
                                <option value="{{ $id }}">{{ $nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="tipe_transaksi_filter" class="form-label">Tipe Transaksi:</label>
                        <select class="form-select form-select-sm" id="tipe_transaksi_filter" name="tipe_transaksi">
                            <option value="">Semua Tipe</option>
                            @foreach($tipeTransaksiOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="status_penjualan_filter" class="form-label">Status Penjualan:</label>
                        <select class="form-select form-select-sm" id="status_penjualan_filter" name="status_penjualan">
                            <option value="">Semua Status</option>
                             @foreach($statusPenjualanOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                     <div class="col-md-2">
                        <label for="kanal_transaksi_filter" class="form-label">Kanal:</label>
                        <select class="form-select form-select-sm" id="kanal_transaksi_filter" name="kanal_transaksi">
                            <option value="">Semua Kanal</option>
                             @foreach($kanalTransaksiOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="button" class="btn btn-primary btn-sm w-100" id="btn-apply-filter">
                            <i class="bi bi-funnel-fill"></i> Terapkan Filter
                        </button>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                         <button type="button" class="btn btn-secondary btn-sm w-100" id="btn-reset-filter">
                            <i class="bi bi-arrow-clockwise"></i> Reset Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
           <h5 class="mb-0">Daftar Transaksi Penjualan</h5>
           {{-- Tombol Export bisa ditambahkan di sini --}}
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="laporan-penjualan-table" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>No. Penjualan</th>
                            <th>Tgl Penjualan</th>
                            <th>Pelanggan</th>
                            <th>Kasir</th>
                            <th class="text-end">Total</th>
                            <th>Tipe</th>
                            <th>Status Jual</th>
                            <th>Status Bayar</th>
                            <th>Kanal</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- Data diisi oleh DataTables AJAX --}}
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="5" style="text-align:right">Total Keseluruhan:</th>
                            <th id="total-penjualan-keseluruhan" style="text-align:right"></th>
                            <th colspan="5"></th> {{-- Sesuaikan colspan --}}
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
            $('.select2-filter').select2({
                theme: "bootstrap-5",
                width: $(this).data('width') ? $(this).data('width') : $(this).hasClass('w-100') ? '100%' : 'style',
                placeholder: $(this).data('placeholder'),
                allowClear: true
            });

            // Inisialisasi Litepicker
            const picker = new Litepicker({
                element: document.getElementById('tanggal_filter'),
                singleMode: false, 
                allowRepick: true,
                format: 'DD MMM YYYY',
                plugins: ['ranges'],
                tooltipText: {
                    one: 'hari',
                    other: 'hari'
                },
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
                            $('#tanggal_mulai_filter').val(date1.dateInstance.toISOString().split('T')[0]);
                            $('#tanggal_akhir_filter').val(date2.dateInstance.toISOString().split('T')[0]);
                        } else {
                            $('#tanggal_mulai_filter').val('');
                            $('#tanggal_akhir_filter').val('');
                        }
                    });
                }
            });
             // Set default tanggal ke 1 bulan terakhir
            const today = new Date();
            const oneMonthAgo = new Date(new Date().setDate(today.getDate() - 29));
            picker.setDateRange(oneMonthAgo, today);
             // Isi hidden input saat halaman pertama kali dimuat dengan default
            $('#tanggal_mulai_filter').val(oneMonthAgo.toISOString().split('T')[0]);
            $('#tanggal_akhir_filter').val(today.toISOString().split('T')[0]);


            var table = $('#laporan-penjualan-table').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ajax: {
                    url: "{{ route('admin.laporan.penjualan.data') }}",
                    type: "GET",
                    data: function (d) {
                        // Ambil nilai dari semua filter
                        d.tanggal_mulai = $('#tanggal_mulai_filter').val();
                        d.tanggal_akhir = $('#tanggal_akhir_filter').val();
                        d.id_pelanggan = $('#pelanggan_filter').val();
                        d.id_kasir = $('#kasir_filter').val();
                        d.tipe_transaksi = $('#tipe_transaksi_filter').val();
                        d.status_penjualan = $('#status_penjualan_filter').val();
                        d.kanal_transaksi = $('#kanal_transaksi_filter').val();
                    }
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, width: '5%' },
                    { data: 'nomor_penjualan', name: 'nomor_penjualan' },
                    { data: 'tanggal_penjualan_formatted', name: 'penjualan.tanggal_penjualan' }, // Sorting berdasarkan kolom DB 
                    { data: 'pelanggan_nama', name: 'pelanggan.nama' }, // relasi 'pelanggan' dan kolom 'nama'
                    { data: 'kasir_nama', name: 'pengguna.nama' }, //  relasi 'pengguna' dan kolom 'nama'
                    { data: 'total_harga_formatted', name: 'penjualan.total_harga', className: 'text-end' },
                    { data: 'tipe_transaksi', name: 'penjualan.tipe_transaksi' },
                    { data: 'status_penjualan_badge', name: 'penjualan.status_penjualan', orderable: false, searchable: false },
                    { data: 'status_pembayaran_badge', name: 'penjualan.status_pembayaran', orderable: false, searchable: false },
                    { data: 'kanal_transaksi', name: 'penjualan.kanal_transaksi' },
                    { data: 'action', name: 'action', orderable: false, searchable: false, width: '10%', className: 'text-center' }
                ],
                language: {
                    url: '{{ asset('js/i18n/id.json') }}',
                    processing: '<div class="spinner-border text-primary spinner-border-sm" role="status"><span class="visually-hidden">Memuat...</span></div>'
                },
                order: [[2, "desc"]], // Default order by tanggal penjualan (indeks kolom 2) descending
                // Footer callback untuk menampilkan total
                footerCallback: function (row, data, start, end, display) {
                    var api = this.api();
                    var total = api.ajax.json() ? (api.ajax.json().total_keseluruhan_penjualan || 0) : 0;
                    $('#total-penjualan-keseluruhan').html(formatRupiahGlobal(total)); // Gunakan fungsi global
                }
            });

            // Tombol Terapkan Filter
            $('#btn-apply-filter').on('click', function() {
                table.ajax.reload(); // Memuat ulang data DataTables dengan parameter filter baru
            });

            // Tombol Reset Filter
            $('#btn-reset-filter').on('click', function() {
                $('#filter-form-penjualan')[0].reset(); // Reset form
                $('.select2-filter').val(null).trigger('change'); // Reset Select2
                picker.clearSelection(); // Hapus pilihan tanggal
                 // Set default tanggal lagi ke 1 bulan terakhir setelah reset
                const today = new Date();
                const oneMonthAgo = new Date(new Date().setDate(today.getDate() - 29));
                picker.setDateRange(oneMonthAgo, today);
                $('#tanggal_mulai_filter').val(oneMonthAgo.toISOString().split('T')[0]);
                $('#tanggal_akhir_filter').val(today.toISOString().split('T')[0]);

                table.ajax.reload(); // Reload tabel
            });
        });
    </script>
@endpush
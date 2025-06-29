{{-- Navigasi khusus untuk Admin --}}
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
        <i class="bi bi-speedometer2 me-1"></i> Dashboard
    </a>
</li>

<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle {{ request()->routeIs('admin.produk.*', 'admin.merk.*', 'admin.supplier.*', 'admin.pelanggan.*', 'admin.pengguna.*') ? 'active' : '' }}" href="#" data-bs-toggle="dropdown">
        <i class="bi bi-stack me-1"></i> Data Master
    </a>
    <ul class="dropdown-menu">
        <li><a class="dropdown-item {{ request()->routeIs('admin.produk.*') ? 'active' : '' }}" href="{{ route('admin.produk.index') }}">Produk</a></li>
        <li><a class="dropdown-item {{ request()->routeIs('admin.merk.*') ? 'active' : '' }}" href="{{ route('admin.merk.index') }}">Merk</a></li>
        <li><a class="dropdown-item {{ request()->routeIs('admin.supplier.*') ? 'active' : '' }}" href="{{ route('admin.supplier.index') }}">Supplier</a></li>
        <li><a class="dropdown-item {{ request()->routeIs('admin.pelanggan.*') ? 'active' : '' }}" href="{{ route('admin.pelanggan.index') }}">Pelanggan</a></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item {{ request()->routeIs('admin.pengguna.*') ? 'active' : '' }}" href="{{ route('admin.pengguna.index') }}">Pengguna Sistem</a></li>
    </ul>
</li>

<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle {{ request()->routeIs('admin.pembelian.*', 'admin.retur_pembelian.*', 'admin.proses_retur_pelanggan.*') ? 'active' : '' }}" href="#" data-bs-toggle="dropdown">
        <i class="bi bi-receipt me-1"></i> Transaksi
    </a>
    <ul class="dropdown-menu">
        <li><a class="dropdown-item {{ request()->routeIs('admin.pembelian.*') ? 'active' : '' }}" href="{{ route('admin.pembelian.index')}}">Pembelian (PO)</a></li>
        <li><a class="dropdown-item {{ request()->routeIs('admin.retur_pembelian.*') ? 'active' : '' }}" href="{{ route('admin.retur_pembelian.index') }}">Retur ke Supplier</a></li>
        <li><a class="dropdown-item {{ request()->routeIs('admin.proses_retur_pelanggan.*') ? 'active' : '' }}" href="{{ route('admin.proses_retur_pelanggan.index') }}">Proses Retur Pelanggan</a></li>
    </ul>
</li>

<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle {{ request()->routeIs('gudang.stok-opname.*', 'perpindahan-stok.*', 'admin.pesan_barang_alokasi.*', 'admin.konsinyasi.*') ? 'active' : '' }}" href="#" data-bs-toggle="dropdown">
        <i class="bi bi-boxes me-1"></i> Manajemen Stok
    </a>
    <ul class="dropdown-menu">
        <li><a class="dropdown-item {{ request()->routeIs('gudang.stok-opname.*') ? 'active' : '' }}" href="{{ route('gudang.stok-opname.index') }}">Stok Opname</a></li>
        <li><a class="dropdown-item {{ request()->routeIs('perpindahan-stok.*') ? 'active' : '' }}" href="{{ route('perpindahan-stok.index') }}">Perpindahan Stok</a></li>
        <li><a class="dropdown-item {{ request()->routeIs('admin.pesan_barang_alokasi.*') ? 'active' : '' }}" href="{{ route('admin.pesan_barang_alokasi.index') }}">Alokasi Pesanan (DP)</a></li>
        <li><hr class="dropdown-divider"></li>
        {{-- INI MENU BARU UNTUK KONSINYASI --}}
        <li><a class="dropdown-item {{ request()->routeIs('admin.konsinyasi.input_harga.form') ? 'active' : '' }}" href="{{ route('admin.konsinyasi.input_harga.form') }}">Input Harga Konsinyasi</a></li>
        <li><a class="dropdown-item {{ request()->routeIs('admin.penyesuaian_stok.*') ? 'active' : '' }}" href="{{ route('admin.penyesuaian_stok.create') }}">Penyesuaian Stok</a></li>
    </ul>
</li>

<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle {{ request()->routeIs('admin.laporan.*') ? 'active' : '' }}" href="#" data-bs-toggle="dropdown">
        <i class="bi bi-file-earmark-bar-graph me-1"></i> Laporan
    </a>
    <ul class="dropdown-menu">
        <li><a class="dropdown-item {{ request()->routeIs('admin.laporan.penjualan.*') ? 'active' : '' }}" href="{{ route('admin.laporan.penjualan.index') }}">Laporan Penjualan</a></li>
        <li><a class="dropdown-item {{ request()->routeIs('admin.laporan.pembelian.*') ? 'active' : '' }}" href="{{ route('admin.laporan.pembelian.index') }}">Laporan Pembelian</a></li>
        <li><a class="dropdown-item {{ request()->routeIs('admin.laporan.stok.ringkasan_produk') ? 'active' : '' }}" href="{{ route('admin.laporan.stok.ringkasan_produk') }}">Laporan Stok</a></li>
        {{-- MENU BARU UNTUK LAPORAN KONSINYASI --}}
        <li><a class="dropdown-item {{ request()->routeIs('admin.laporan.konsinyasi.*') ? 'active' : '' }}" href="{{ route('admin.laporan.konsinyasi.index') }}">Laporan Konsinyasi</a></li>
        <li><a class="dropdown-item {{ request()->routeIs('admin.laporan.harga_beli.*') ? 'active' : '' }}" href="{{ route('admin.laporan.harga_beli.index') }}">Analisis Harga Beli</a></li>
        <li><a class="dropdown-item {{ request()->routeIs('admin.laporan.stok.lacak_nomor_seri.*') ? 'active' : '' }}" href="{{ route('admin.laporan.stok.lacak_nomor_seri.form') }}">Lacak Nomor Seri</a></li> 
    </ul>
</li>
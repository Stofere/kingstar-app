<?php 

namespace App\Helpers;

class ReturHelper {

    public static function formatAlasanRetur($key) { 
        $options = [
            'BARANG_RUSAK_PELANGGAN' => 'Barang Rusak Saat Diterima Pelanggan',
            'SALAH_BARANG_TERKIRIM' => 'Salah Kirim Barang',
            'BERUBAH_PIKIRAN' => 'Pelanggan Berubah Pikiran (Sesuai Kebijakan)',
            'TIDAK_SESUAI_SPESIFIKASI' => 'Tidak Sesuai Spesifikasi',
            'LAINNYA' => 'Lainnya',
        ];
        return $options[$key] ?? str_replace('_', ' ', ucwords(strtolower($key ?? '')));
    }

    // Untuk Retur PENJUALAN (Tindakan awal Kasir / Tindakan final Admin)
    public static function formatTindakanLanjut($key) {
        $options = [
            'DITERIMA_KEMBALI_PERLU_CEK' => 'Diterima Kembali (Perlu Pengecekan Admin)',
            'DITERIMA_LANGSUNG_RUSAK' => 'Diterima Kembali (Langsung Catat Rusak)',
            'KOMPLAIN_KE_SUPPLIER' => 'Disisihkan untuk Komplain ke Supplier',
            // Opsi tindakan Admin
            'KEMBALI_KE_STOK_BAIK_ADMIN' => 'Kembali ke Stok Aktif (Kondisi Baik)',
            'CATAT_SEBAGAI_STOK_RUSAK_FINAL' => 'Dicatat Sebagai Stok Rusak Final',
            'AKAN_DIRETUR_KE_SUPPLIER' => 'Akan Diretur ke Supplier',
        ];
        return $options[$key] ?? str_replace('_', ' ', ucwords(strtolower($key ?? '')));
    }

    // Untuk Retur PEMBELIAN (Alasan dari Admin ke Supplier)
    public static function formatAlasanReturPembelian($key) {
        $options = [
            'BARANG_RUSAK_DARI_SUPPLIER' => 'Barang Rusak dari Supplier',
            'SALAH_KIRIM_SUPPLIER' => 'Salah Kirim oleh Supplier',
            'KELEBIHAN_KIRIM_SUPPLIER' => 'Kelebihan Kirim oleh Supplier',
            'KUALITAS_TIDAK_SESUAI' => 'Kualitas Tidak Sesuai Pesanan',
            'RETUR_PELANGGAN_CACAT_PRODUKSI' => 'Retur Pelanggan (Cacat Produksi dari Supplier)',
            'LAINNYA' => 'Lainnya',
        ];
        return $options[$key] ?? str_replace('_', ' ', ucwords(strtolower($key ?? '')));
    }

    // Untuk Retur PEMBELIAN (Tindak lanjut yang diharapkan/status dari Supplier)
    public static function formatTindakanLanjutSupplier($key) {
        $options = [
            'MENUNGGU_RESPONS_SUPPLIER' => 'Menunggu Respons Supplier',
            'PROSES_PENGGANTIAN_BARANG' => 'Diajukan Penggantian Barang',
            'PROSES_REFUND_UANG' => 'Diajukan Refund Uang',
            'SELESAI_DIGANTI' => 'Selesai Diganti oleh Supplier',
            'SELESAI_DIREFUND' => 'Selesai Direfund oleh Supplier',
            'DITOLAK_SUPPLIER' => 'Ditolak oleh Supplier',
        ];
        return $options[$key] ?? str_replace('_', ' ', ucwords(strtolower($key ?? '')));
    }
}
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Produk;
use App\Models\StokBarang;
use App\Models\LogNomorSeri;

class StorePenjualanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->check() && in_array(auth()->user()->role, ['KASIR', 'ADMIN']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'id_pengguna' => ['required', 'exists:pengguna,id'], // Biasanya diisi otomatis dari Auth::id()
            'tanggal_penjualan' => ['required', 'date'],
            'id_pelanggan' => ['nullable', Rule::requiredIf(fn() => !$this->input('pelanggan_baru_nama')), 'exists:pelanggan,id'],
            'pelanggan_baru_nama' => ['nullable', Rule::requiredIf(fn() => !$this->input('id_pelanggan') && $this->filled('pelanggan_baru_nama')), 'string', 'max:255'],
            'pelanggan_baru_telepon' => ['nullable', 'string', 'max:20'],
            'pelanggan_baru_alamat' => ['nullable', 'string'],
            'kanal_transaksi' => ['required', Rule::in(['TOKO', 'TOKOPEDIA', 'SHOPEE'])],
            'tipe_transaksi' => ['required', Rule::in(['BIASA', 'PRE_ORDER'])],
            'metode_pembayaran' => ['required', 'string', 'max:50'], // Sesuaikan dengan daftar metode Anda
            'total_harga' => ['required', 'numeric', 'min:0'],
            'catatan' => ['nullable', 'string'],
            'diskon_nominal' => ['nullable', 'numeric', 'min:0'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.id_produk' => ['required', 'exists:produk,id'],
            'items.*.jumlah' => ['required', 'integer', 'min:1'],
            'items.*.harga_jual' => ['required', 'numeric', 'min:0'],
            'items.*.stok_allocations' => ['required', 'json'], // Validasi bahwa ini adalah string JSON
        ];

        if ($this->input('tipe_transaksi') === 'PRE_ORDER') {
            $rules['uang_muka'] = ['required', 'numeric', 'min:0', 'lte:total_harga']; // DP tidak boleh > total
            $rules['estimasi_kirim_at'] = ['nullable', 'date', 'after_or_equal:tanggal_penjualan'];
            // Uang bayar (pelunasan) untuk PO bisa opsional saat create, tergantung alur
            $rules['uang_bayar'] = ['nullable', 'numeric', 'min:0'];
        } else { // Transaksi BIASA
            $rules['uang_bayar'] = ['required', 'numeric', 'min:0']; // Uang bayar wajib
            // Bisa tambahkan validasi uang_bayar >= total_harga jika perlu di sini,
            // atau biarkan JS yang handle kembalian dan backend fokus ke pencatatan
        }

        // Validasi isi dari stok_allocations menggunakan custom rule atau after hook
        // karena validasi array di dalam JSON lebih kompleks dengan rule standar.

        return $rules;
    }

    public function messages()
    {
        $messages = [
            'id_pelanggan.required_if' => 'Pelanggan wajib dipilih jika tidak membuat pelanggan baru.',
            'pelanggan_baru_nama.required_if' => 'Nama pelanggan baru wajib diisi jika tidak memilih pelanggan yang sudah ada.',
            'items.required' => 'Minimal harus ada satu item dalam transaksi.',
            'items.min' => 'Minimal harus ada satu item dalam transaksi.',
            'items.*.id_produk.required' => 'Produk wajib dipilih untuk setiap item.',
            'items.*.jumlah.required' => 'Jumlah wajib diisi untuk setiap item.',
            'items.*.jumlah.min' => 'Jumlah item minimal 1.',
            'items.*.harga_jual.required' => 'Harga jual wajib diisi untuk setiap item.',
            'items.*.harga_jual.min' => 'Harga jual tidak boleh negatif.',
            'items.*.stok_allocations.required' => 'Alokasi batch/stok wajib ada untuk setiap item.',
            'items.*.stok_allocations.json' => 'Format data alokasi batch/stok tidak valid.',
            'uang_muka.lte' => 'Uang muka tidak boleh melebihi total harga.',
        ];
        // Tambahkan pesan custom lain jika perlu
        return $messages;
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $items = $this->input('items', []);
            $totalHargaDariForm = (float) $this->input('total_harga', 0);
            $calculatedTotalHarga = 0;
            $isPreOrder = $this->input('tipe_transaksi') === 'PRE_ORDER';
            $uangBayar = (float) $this->input('uang_bayar', 0);
            $uangMuka = (float) $this->input('uang_muka', 0);
            $subtotalSebelumDiskon = 0;

            foreach ($items as $key => $item) {
                $itemIndexForMessage = $key + 1; // Untuk pesan error
                $jumlahItem = (int)($item['jumlah'] ?? 0);
                $hargaJualItem = (float)($item['harga_jual'] ?? 0);
                $calculatedTotalHarga += ($jumlahItem * $hargaJualItem);
                $subtotalSebelumDiskon += ($jumlahItem * $hargaJualItem);

                // Validasi JSON stok_allocations
                $stokAllocationsJson = $item['stok_allocations'] ?? null;
                if (!$stokAllocationsJson) {
                    // Sudah divalidasi 'required' di rules(), tapi bisa dicek lagi
                    continue;
                }

                $allocations = json_decode($stokAllocationsJson, true);
                if (json_last_error() !== JSON_ERROR_NONE || !is_array($allocations)) {
                    $validator->errors()->add("items.{$key}.stok_allocations", "Data alokasi stok untuk item ke-{$itemIndexForMessage} tidak valid.");
                    continue;
                }
                if (empty($allocations) && $jumlahItem > 0) {
                     $validator->errors()->add("items.{$key}.stok_allocations", "Alokasi batch tidak boleh kosong untuk item ke-{$itemIndexForMessage} jika jumlah > 0.");
                     continue;
                }


                $totalQtyFromAllocations = 0;
                $produk = Produk::find($item['id_produk'] ?? null);

                if (!$produk) { // Seharusnya sudah dicek exists
                    continue;
                }

                foreach ($allocations as $allocKey => $alloc) {
                    $allocIndexForMessage = $allocKey + 1;
                    if (!isset($alloc['id_stok_barang'], $alloc['qty_allocated'])) {
                        $validator->errors()->add("items.{$key}.stok_allocations", "Data alokasi batch ke-{$allocIndexForMessage} untuk item ke-{$itemIndexForMessage} tidak lengkap (kurang ID batch atau qty).");
                        continue;
                    }

                    $qtyAllocated = (int)($alloc['qty_allocated'] ?? 0);
                    if ($qtyAllocated <= 0) {
                         $validator->errors()->add("items.{$key}.stok_allocations", "Kuantitas yang dialokasikan dari batch ke-{$allocIndexForMessage} (item ke-{$itemIndexForMessage}) harus lebih dari 0.");
                         continue;
                    }
                    $totalQtyFromAllocations += $qtyAllocated;

                    $stokBarang = StokBarang::where('id', $alloc['id_stok_barang'])
                                            ->where('id_produk', $produk->id) // Pastikan batch milik produk yg benar
                                            ->where('kondisi', 'BAIK')
                                            ->first();

                    if (!$stokBarang) {
                        $validator->errors()->add("items.{$key}.stok_allocations", "Batch (ID: {$alloc['id_stok_barang']}) untuk item ke-{$itemIndexForMessage} tidak ditemukan, tidak valid, atau bukan milik produk yang benar.");
                        continue;
                    }

                    if ($qtyAllocated > $stokBarang->jumlah) {
                        $validator->errors()->add("items.{$key}.stok_allocations", "Stok di Batch ID {$stokBarang->id} (item ke-{$itemIndexForMessage}) tidak mencukupi. Diminta: {$qtyAllocated}, Tersedia: {$stokBarang->jumlah}.");
                    }

                    if ($produk->memiliki_serial) {
                        if (!isset($alloc['serials_selected']) || !is_array($alloc['serials_selected'])) {
                            $validator->errors()->add("items.{$key}.stok_allocations", "Data nomor seri tidak valid untuk alokasi batch ke-{$allocIndexForMessage} (item ke-{$itemIndexForMessage}).");
                            continue;
                        }
                        if (count($alloc['serials_selected']) !== $qtyAllocated) {
                            $validator->errors()->add("items.{$key}.stok_allocations", "Jumlah nomor seri (".count($alloc['serials_selected']).") tidak sesuai dengan kuantitas yang dialokasikan ({$qtyAllocated}) dari Batch ID {$stokBarang->id} untuk item ke-{$itemIndexForMessage}.");
                        }
                        // Validasi keunikan dan ketersediaan serial
                        foreach($alloc['serials_selected'] as $serialKey => $serialNumber) {
                            if(empty(trim($serialNumber))) {
                                $validator->errors()->add("items.{$key}.stok_allocations", "Nomor seri ke-".($serialKey+1)." pada alokasi batch ID {$stokBarang->id} (item ke-{$itemIndexForMessage}) tidak boleh kosong.");
                                continue;
                            }
                            $logSerial = LogNomorSeri::where('id_produk', $produk->id)
                                                    ->where('nomor_seri', trim($serialNumber))
                                                    ->where('id_stok_barang_asal', $stokBarang->id) // Pastikan serial dari batch yang benar
                                                    ->where('status_log', 'DITERIMA') // Pastikan masih tersedia
                                                    ->first();
                            if (!$logSerial) {
                                $validator->errors()->add("items.{$key}.stok_allocations", "Nomor Seri '{$serialNumber}' tidak valid, tidak ditemukan di Batch ID {$stokBarang->id}, atau sudah tidak tersedia untuk item ke-{$itemIndexForMessage}.");
                            }
                        }
                    }
                }

                if ($totalQtyFromAllocations !== $jumlahItem) {
                    $validator->errors()->add("items.{$key}.stok_allocations", "Total kuantitas dari alokasi batch ({$totalQtyFromAllocations}) tidak cocok dengan jumlah item ({$jumlahItem}) untuk item ke-{$itemIndexForMessage}.");
                }
            }

            // Validasi total harga vs kalkulasi item (toleransi kecil untuk float)
            if (abs($calculatedTotalHarga - $totalHargaDariForm) > 0.01) {
                 $validator->errors()->add('total_harga', 'Total harga tidak cocok dengan kalkulasi item. Harap muat ulang halaman atau periksa input.');
            }

            // Validasi uang bayar vs total untuk transaksi biasa
            if (!$isPreOrder && $uangBayar < $totalHargaDariForm) {
                $validator->errors()->add('uang_bayar', 'Uang bayar kurang dari total belanja.');
            }

            // Validasi uang muka dan uang bayar untuk Pre-Order
            if ($isPreOrder) {
                $sisaPembayaranPO = $totalHargaDariForm - $uangMuka;
                if ($uangMuka < 0) {
                     $validator->errors()->add('uang_muka', 'Uang muka tidak boleh negatif.');
                }
                // Jika ada uang bayar (pelunasan), dan ada sisa PO, uang bayar tidak boleh kurang dari sisa
                if ($uangBayar > 0 && $sisaPembayaranPO > 0 && $uangBayar < $sisaPembayaranPO) {
                    $validator->errors()->add('uang_bayar', 'Uang bayar pelunasan kurang dari sisa pembayaran Pre-Order.');
                }
            }
            
            // Validasi diskon tidak melebihi subtotal
            $diskonNominal = $this->input('diskon_nominal', 0);
            if ($diskonNominal > $subtotalSebelumDiskon) {
                $validator->errors()->add('diskon_nominal', 'Diskon tidak boleh melebihi subtotal item');
            }

            // Validasi total harga setelah diskon
            $totalHarga = $this->input('total_harga');
            $expectedTotal = $subtotalSebelumDiskon - $diskonNominal;
            if (abs($totalHarga - $expectedTotal) > 0.01) { // Toleransi 0.01 untuk floating point
                $validator->errors()->add('total_harga', 'Total harga tidak sesuai dengan perhitungan (subtotal - diskon)');
            }
        });
    }
}
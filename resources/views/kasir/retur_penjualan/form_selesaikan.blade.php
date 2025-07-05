@extends('layouts.app')

@section('title', 'Selesaikan Penyerahan Retur: ' . $returPenjualan->nomor_retur)

@push('styles')
    <style> .select2-container--bootstrap-5 { z-index: 1060; } </style>
@endpush

@section('content')
<div class="container-fluid">
    <h1>Selesaikan Penyerahan Barang Retur</h1>
    <p>Nota Retur: <strong>{{ $returPenjualan->nomor_retur }}</strong> | Pelanggan: <strong>{{ $returPenjualan->penjualanAsal->pelanggan->nama ?? 'Umum' }}</strong></p>

    <form action="{{ route('kasir.retur_penjualan.selesaikan.store', $returPenjualan->id) }}" method="POST">
        @csrf
        @foreach($itemsSiapDiserahkan as $index => $detail)
            <div class="card mb-3 shadow-sm">
                <div class="card-header bg-success text-white">
                    Item #{{ $index + 1 }}: <strong>{{ $detail->detailPenjualanAsal->produk->nama }}</strong> ({{ $detail->jumlah_retur }} unit)
                    <br><small>Tindakan Admin: {{ ucwords(strtolower(str_replace('_', ' ', $detail->tindakan_lanjut))) }}</small>
                </div>
                <div class="card-body">
                    <input type="hidden" name="items_serah[{{$index}}][id_detail_retur]" value="{{ $detail->id }}">
                    
                    <div class="mb-3">
                        <label class="form-label required-label">Pilih Batch Pengganti:</label>
                        {{-- Data attribute untuk memicu AJAX --}}
                        <select name="items_serah[{{$index}}][id_stok_barang_pengganti]" class="form-select select-batch-pengganti" 
                                data-id_produk="{{ $detail->detailPenjualanAsal->id_produk }}" 
                                data-qty_dibutuhkan="{{ $detail->jumlah_retur }}" 
                                data-index="{{ $index }}"
                                data-detail-retur-id="{{ $detail->id }}"
                                required>
                            <option value="">Cari batch...</option>
                        </select>
                    </div>

                    @if($detail->detailPenjualanAsal->produk->memiliki_serial)
                    <div class="mb-3 area-pilih-serial" id="area-serial-{{$index}}" style="display:none;">
                        <label class="form-label required-label">Pilih Nomor Seri Pengganti:</label>
                        <select name="items_serah[{{$index}}][nomor_seri_pengganti][]" class="form-select select-serial-pengganti" 
                                data-index="{{ $index }}"
                                multiple="multiple" required>
                            {{-- Opsi diisi via AJAX --}}
                        </select>
                    </div>
                    @endif
                </div>
            </div>
        @endforeach
        
        <div class="card mt-4">
            <div class="card-body">
                <label for="catatan_penyerahan">Catatan Penyerahan (Opsional)</label>
                <textarea name="catatan_penyerahan" class="form-control" placeholder="Contoh: Barang diterima dalam kondisi baik oleh pelanggan."></textarea>
            </div>
            <div class="card-footer text-end">
                <a href="{{ route('kasir.retur_penjualan.index') }}" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i> Simpan Penyerahan</button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Data dari PHP Controller, key-nya adalah ID Detail Retur
        const itemsData = @json($itemsDataForJs ?? []);

        $('.select-batch-pengganti').each(function() {
            const selectBatchEl = $(this);
            const detailReturId = selectBatchEl.data('detail-retur-id');
            const itemInfo = itemsData[detailReturId];

            if (!itemInfo) return; // Lewati klo data tidak ditemukan

            selectBatchEl.select2({
                theme: "bootstrap-5",
                placeholder: 'Cari batch stok...',
                ajax: {
                    url: "{{ route('kasir.ajax.stok.available') }}",
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return { 
                            id_produk: itemInfo.id_produk, 
                            qty_dibutuhkan: itemInfo.jumlah_dibutuhkan 
                        };
                    },
                    processResults: function(data) {
                        const items = data.success ? data.batches_data.map(batch => ({
                            id: batch.id,
                            text: `Batch ID: ${batch.id} (Sisa: ${batch.jumlah_tersedia}, Lokasi: ${batch.lokasi})`
                        })) : [];
                        return { results: items };
                    }
                }
            }).on('select2:select', function(e) {
                const index = $(this).data('index');
                const detailReturId = $(this).data('detail-retur-id');
                const itemInfo = itemsData[detailReturId];

                // Safety check jika itemInfo tidak ditemukan
                if (!itemInfo) {
                    console.error(`Data untuk detail retur ID ${detailReturId} tidak ditemukan.`);
                    return;
                }

               // Cek lagi di dalam event handler apakah item ini memang berserial
                if (itemInfo.memiliki_serial) {
                    const idStokBarang = e.params.data.id;
                    // Ambil index dari data attribute untuk menargetkan elemen yang benar
                    const index = selectBatchEl.data('index'); 
                    loadSerialsForItem(index, idStokBarang, itemInfo.jumlah_dibutuhkan);
                }
            });
        });

        function loadSerialsForItem(index, idStokBarang, qtyDibutuhkan) {

            const serialArea = $(`#area-serial-${index}`);
            const selectSerialEl = serialArea.find('.select-serial-pengganti');
            
            // Tampilkan area dan loading state
            serialArea.show();
            selectSerialEl.empty().prop('disabled', true).select2({
                theme: "bootstrap-5",
                placeholder: 'Memuat nomor seri...'
            });

            $.ajax({
                url: "{{ route('kasir.ajax.stok.serials') }}",
                data: { id_stok_barang: idStokBarang },
                success: function(response) {
                    if (response.success && response.serials.length > 0) {
                        response.serials.forEach(serial => {
                            selectSerialEl.append(new Option(serial, serial, false, false));
                        });

                        // Inisialisasi ulang Select2 dengan opsi yang benar
                        selectSerialEl.prop('disabled', false).select2({
                            theme: "bootstrap-5",
                            placeholder: `Pilih ${qtyDibutuhkan} nomor seri...`,
                            maximumSelectionLength: qtyDibutuhkan,
                            closeOnSelect: false,
                        });
                    } else {
                        selectSerialEl.select2({ 
                            theme: "bootstrap-5", 
                            placeholder: 'Tidak ada serial tersedia'  
                        });
                    }
                },
                error: function() {
                     selectSerialEl.select2({ 
                        theme: "bootstrap-5", 
                        placeholder: 'Gagal memuat serial' 
                    });
                }
            });
        }
    });
</script>
@endpush
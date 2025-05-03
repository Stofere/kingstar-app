@extends('layouts.app')

@section('title', 'Edit Produk: ' . $produk->nama) {{-- Judul dinamis --}}

@section('content')
<div class="container">
    {{-- Judul Halaman Dinamis --}}
    <h1 class="mb-4">Edit Produk: <span class="fw-normal">{{ $produk->nama }}</span></h1>
     <div class="card shadow-sm">
        <div class="card-header bg-light">
             <h5 class="mb-0">Form Edit Produk</h5>
        </div>
        <div class="card-body">
            {{-- Form action ke route update, gunakan $produk->id --}}
            {{-- Method POST, tapi di-override dengan PUT --}}
            {{-- enctype diperlukan untuk file upload --}}
            <form action="{{ route('admin.produk.update', $produk->id) }}" method="POST" enctype="multipart/form-data">
                 @method('PUT') {{-- Override method form menjadi PUT --}}
                 {{-- Include partial form, pass $produk yang didapat dari controller --}}
                 @include('admin.produk.form', ['produk' => $produk])
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{{-- JS untuk edit form jika perlu (misal: preview gambar saat file dipilih) --}}
<script>
    // Script preview gambar (sama seperti di create view)
    const gambarInput = document.getElementById('gambar');
    if (gambarInput) {
        gambarInput.addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                // Cari img yang sudah ada (karena ini form edit, pasti ada elemen img atau placeholder)
                let imgElement = document.querySelector('img[alt^="Gambar"], div.text-muted:contains("(Tidak ada gambar)")');
                let imgPreview = document.querySelector('img.img-thumbnail[src^="data:image"]'); // Cari preview yg mungkin dibuat script

                // Jika belum ada elemen img sama sekali (kasus aneh) atau hanya placeholder
                if (!imgElement || imgElement.tagName === 'DIV') {
                    // Hapus placeholder jika ada
                    if(imgElement && imgElement.tagName === 'DIV') imgElement.remove();
                    // Buat elemen img baru untuk preview
                    imgElement = document.createElement('img');
                    imgElement.classList.add('img-thumbnail', 'mb-2', 'd-block');
                    imgElement.style.maxHeight = '150px';
                    imgElement.alt = 'Preview Gambar Baru';
                    this.parentNode.insertBefore(imgElement, this); // Sisipkan sebelum input file
                } else if(imgPreview) {
                     imgElement = imgPreview; // Gunakan preview yang sudah dibuat script
                }


                reader.onload = function(e) {
                    imgElement.src = e.target.result;
                    imgElement.style.display = 'block'; // Pastikan terlihat
                     // Jika ada placeholder text, sembunyikan
                    let placeholder = document.querySelector('div.text-muted:contains("(Tidak ada gambar)")');
                    if(placeholder) placeholder.style.display = 'none';
                }
                reader.readAsDataURL(file);
            }
        });
    }
</script>
@endpush
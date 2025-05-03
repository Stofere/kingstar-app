@extends('layouts.app')

@section('title', 'Tambah Produk Baru')

@section('content')
<div class="container">
    <h1 class="mb-4">Tambah Produk Baru</h1>
    <div class="card shadow-sm">
        <div class="card-header bg-light">
             <h5 class="mb-0">Form Tambah Produk</h5>
        </div>
        <div class="card-body">
            {{-- Form action ke route store, method POST, enctype untuk file --}}
            <form action="{{ route('admin.produk.store') }}" method="POST" enctype="multipart/form-data">
                {{-- Include partial form, pass $produk (instance baru dari controller) --}}
                @include('admin.produk.form', ['produk' => $produk])
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{{-- JS untuk create form jika perlu (misal: preview gambar) --}}
<script>
    // Optional: Image preview script
    const gambarInput = document.getElementById('gambar');
    if (gambarInput) {
        gambarInput.addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                let imgElement = document.querySelector('img[alt^="Gambar"]'); // Cari img yang ada
                if (!imgElement) { // Jika belum ada img (di form create), buat baru
                    imgElement = document.createElement('img');
                    imgElement.classList.add('img-thumbnail', 'mb-2', 'd-block');
                    imgElement.style.maxHeight = '150px';
                    imgElement.alt = 'Preview Gambar';
                    // Sisipkan sebelum input file
                    this.parentNode.insertBefore(imgElement, this);
                }

                reader.onload = function(e) {
                    imgElement.src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });
    }
</script>
@endpush
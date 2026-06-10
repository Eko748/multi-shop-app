@props([
    'title' => 'Data'
])

<div id="modal-form" class="modal fade bd-example-modal-lg" tabindex="-1" role="dialog"
    aria-labelledby="myLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                {{-- Menggunakan variabel title yang dinamis (bisa diisi $menu[0] nantinya) --}}
                <h5 class="modal-title" id="modalLabel">Tambah Data {{ $title }}</h5>
                <button type="button" class="btn-close reset-all close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fa fa-xmark"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="form-data">
                    {{-- Input form di-inject lewat JS renderModalForm() Anda --}}
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fa fa-times mr-1"></i>Tutup
                </button>
                <button type="submit" form="form-data" class="btn btn-success" id="save-btn">Simpan</button>
            </div>
        </div>
    </div>
</div>

@props([
    'title' => 'Data',
    'addModalFunction' => 'openAddModal()',
    'showAddButton' => true
])

<div class="card-header">
    <div class="row">
        @if($showAddButton)
            <div class="col-3 col-sm-12 col-md-3 col-xl-2 col-lg-2 mb-2">
                <button type="button" class="btn btn-primary w-100" id="btn-add-data" onclick="{{ $addModalFunction }}">
                    <i class="fa fa-circle-plus"></i><span> Tambah Data</span>
                </button>
            </div>
        @endif

        <div class="{{ $showAddButton ? 'col-9 col-sm-12 col-md-9 col-xl-10 col-lg-10' : 'col-12' }} mb-2">
            <div class="row justify-content-end">
                <div class="col-5 col-sm-4 col-md-3 col-xl-2 col-lg-2">
                    <select name="limitPage" id="limitPage" class="form-control mr-2 mb-2 mb-lg-0">
                        <option value="10">10</option>
                        <option value="20">20</option>
                        <option value="30">30</option>
                    </select>
                </div>
                <div class="col-7 col-sm-8 col-md-9 col-xl-4 col-lg-4 justify-content-end">
                    <input id="tb-search" class="tb-search form-control mb-2 mb-lg-0" type="search"
                        name="search" placeholder="Cari {{ $title }}" aria-label="search">
                </div>
            </div>
        </div>
    </div>
</div>

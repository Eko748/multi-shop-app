@extends('layouts.main')

@section('title')
    {{ $menu[0] }}
@endsection

@section('css')
    <link rel="stylesheet" href="{{ asset('css/button-action.css') }}">
    <link rel="stylesheet" href="{{ asset('css/table.css') }}">
    <link rel="stylesheet" href="{{ asset('css/sweetalert2.css') }}">
@endsection

@section('content')
    <x-content-container :breadcrumbs="$menu" :tableHeaders="[
        ['text' => 'No', 'class' => 'text-center text-wrap align-top'],
        ['text' => '<span>Hak Akses</span>', 'class' => 'text-wrap align-top text-nowrap'],
        ['text' => 'Level User', 'class' => 'text-wrap align-top text-nowrap'],
        ['text' => 'Informasi', 'class' => 'text-wrap align-top'],
        ['text' => '<span class=\'mr-2\'>Action</span>', 'class' => 'text-wrap align-top text-right'],
    ]">
        <x-slot:header>
            <x-content-header title="{{ $menu[0] }}" addModalFunction="openAddModal()" />
        </x-slot:header>
    </x-content-container>
@endsection

@section('asset_js')
    <script src="{{ asset('js/pagination.js') }}"></script>
    <script src="{{ asset('js/content-handler.js') }}"></script>
@endsection

@section('js')
    <script>
        let title = "{{ $menu[0] }}";
        let defaultLimitPage = 10;
        let currentPage = 1;
        let totalPage = 1;
        let defaultAscending = 0;
        let defaultSearch = '';
        let customFilter = {};
        let authUserID = {{ auth()->user()->id }};
        let authTokoID = {{ auth()->user()->toko_id }};

        function refreshTable() {
            getListData(defaultLimitPage, currentPage, defaultAscending, defaultSearch, customFilter);
        }

        async function getListData(limit = 10, page = 1, ascending = 0, search = '', customFilter = {}) {
            $('#listData').html(loadingData());

            let filterParams = {};

            let getDataRest = await CRUDHandler.get('{{ route('master.getleveluser') }}', {
                page: page,
                limit: limit,
                ascending: ascending,
                search: search,
                ...filterParams
            });

            if (getDataRest && getDataRest.status == 200 && Array.isArray(getDataRest.data.data)) {
                let handleDataArray = await Promise.all(
                    getDataRest.data.data.map(async item => await handleData(item))
                );
                await setListData(handleDataArray, getDataRest.data.pagination);
            } else {
                let errorMessage = getDataRest?.data?.message || 'Gagal memuat data.';
                let errorRow = `
                    <tr class="text-dark">
                        <th class="text-center" colspan="${$('.tb-head th').length}"> ${errorMessage} </th>
                    </tr>`;
                $('#listData').html(errorRow);
                $('#countPage').text("0 - 0");
                $('#totalPage').text("0");
            }
        }

        async function handleData(data) {
            let edit_button = ButtonBuilder.edit(data, title, 'nama_level');
            let delete_button = ButtonBuilder.delete(data, title, 'nama_level', 1);
            let access_button = `
                <a href='role/permission/${data.id}' class="btn btn-sm btn-light border akses-data d-inline-flex align-items-center"
                    data-container="body" data-toggle="tooltip" data-placement="top"
                    title="Atur Hak Akses untuk ${data.nama_level}"
                    data-id='${data.id}'>
                    <span class="text-dark mr-2">Atur</span>
                    <div class="icon text-success d-flex align-items-center">
                        <i class="fa fa-cog fa-spin"></i>
                    </div>
                </a>`;

            let action_buttons = ButtonBuilder.buildActionGroup([edit_button, delete_button]);

            return {
                id: data?.id ?? '-',
                nama_level: data?.nama_level ?? '-',
                informasi: data?.informasi ?? '-',
                access_button,
                action_buttons
            };
        }

        async function setListData(dataList, pagination) {
            totalPage = pagination.total_pages;
            currentPage = pagination.current_page;

            TableRenderer.render({
                dataList: dataList,
                pagination: pagination,
                limit: defaultLimitPage,
                columns: [
                    {
                        key: '_index',
                        class: 'align-center text-dark text-center text-nowrap'
                    },
                    {
                        key: 'access_button',
                        class: 'align-center text-dark text-left pl-3 text-nowrap'
                    },
                    {
                        key: 'nama_level',
                        class: 'align-center text-dark text-nowrap'
                    },
                    {
                        key: 'informasi',
                        class: 'align-center text-dark text-wrap w-100'
                    },
                    {
                        key: 'action_buttons',
                        class: 'align-center text-dark d-flex flex-nowrap justify-content-end text-nowrap'
                    }
                ]
            });
        }

        function openAddModal() {
            renderModalForm('add');
            $('#save-btn')
                .removeClass('btn-primary')
                .addClass('btn-success')
                .prop('disabled', false)
                .html('<i class="fa fa-save mr-1"></i>Simpan');

            $('#modal-form').modal('show');
        }

        function openEditModal(data) {
            try {
                let item = JSON.parse(decodeURIComponent(data));
                renderModalForm('edit', item);

                $('#save-btn')
                    .removeClass('btn-success')
                    .addClass('btn-primary')
                    .prop('disabled', false)
                    .html('<i class="fa fa-edit mr-1"></i>Update');

                $('#modal-form').modal('show');
            } catch (e) {
                notificationAlert('info', 'Pemberitahuan', 'Terjadi kesalahan saat memuat data untuk diedit.');
            }
        }

        async function renderModalForm(mode = 'add', data = {}) {
            const titleModal = mode === 'edit' ?
                `<i class="fa fa-edit mr-1"></i>Edit ${title}` :
                `<i class="fa fa-circle-plus mr-1"></i>Tambah ${title}`;

            $('#modalLabel').html(titleModal);

            const formContent = `
                <div class="row">
                    <div class="col-xl-12">
                        <div class="card-body">
                            <div class="row p-0">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="nama_level" class="form-control-label">Nama Level User<span style="color: red">*</span></label>
                                        <input type="text" id="nama_level" name="nama_level" placeholder="Contoh : Karyawan" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="informasi" class="form-control-label">Informasi<span style="color: red">*</span></label>
                                        <textarea id="informasi" name="informasi" placeholder="Contoh : Karyawan Toko" class="form-control"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>`;

            await $('#form-data').html(formContent);

            if (mode === 'edit') {
                $('#nama_level').val(data.nama_level);
                $('#informasi').val(data.informasi);

                if ($('#form-data input[name="id"]').length === 0) {
                    $('<input>').attr({
                        type: 'hidden',
                        name: 'id',
                        value: data.id
                    }).appendTo('#form-data');
                } else {
                    $('#form-data input[name="id"]').val(data.id);
                }
            }
        }

        async function initPageLoad() {
            CRUDHandler.save({
                title: title,
                routePost: '{{ route('role.post') }}',
                routeUpdate: '{{ route('role.update') }}',
                userId: authUserID,
                tokoId: authTokoID,
                onSuccess: refreshTable
            });

            CRUDHandler.delete({
                title: title,
                routeDelete: '{{ route('role.delete') }}',
                userId: authUserID,
                tokoId: authTokoID,
                onSuccess: refreshTable
            });

            await Promise.all([
                getListData(defaultLimitPage, currentPage, defaultAscending, defaultSearch, customFilter),
                searchList(),
            ]);
        }
    </script>
@endsection

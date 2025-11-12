@extends('layouts.main')

@section('title')
    Atur Hak Akses
@endsection

@section('css')
    <link rel="stylesheet" href="{{ asset('css/sweetalert2.css') }}">

    <style>
        .badge-method {
            font-size: 10px;
            padding: 2px 5px;
            border-radius: 4px;
            background-color: rgba(0, 0, 0, 0.05);
            color: #000;
        }

        .method-get {
            background-color: rgba(40, 167, 69, 0.15);
            color: #28a745;
        }

        .method-post {
            background-color: rgba(0, 123, 255, 0.15);
            color: #007bff;
        }

        .method-put {
            background-color: rgba(255, 193, 7, 0.15);
            color: #ffc107;
        }

        .method-delete {
            background-color: rgba(220, 53, 69, 0.15);
            color: #dc3545;
        }

        .permission-group-header {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            padding: 10px 15px;
            margin-bottom: 0;
            cursor: pointer;
            transition: background-color 0.2s ease;
            border-radius: 4px;
        }

        .permission-group-header:hover {
            background-color: #e9ecef;
        }

        .permission-group-title {
            font-weight: 600;
            font-size: 16px;
            color: #343a40;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .permission-text-ellipsis {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: inline-block;
            max-width: 75%;
        }

        @media (max-width: 768px) {
            .permission-text-ellipsis {
                max-width: 200px;
                display: inline-block;
            }
        }
    </style>
@endsection

@section('content')
    <div class="pcoded-main-container">
        <div class="pcoded-content pt-1 mt-1">
            @include('components.breadcrumbs')
            <div class="row">
                <div class="col-xl-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <a href="{{ route('master.leveluser.index') }}" class="btn btn-danger">
                                <i class="fa fa-arrow-left"></i> Kembali
                            </a>
                            <h5 class="mb-0 text-right">Pengaturan Hak Akses <span id="nama_level_user"></span></h5>
                        </div>
                        <x-adminlte-alerts />
                        <div class="card-body">
                            <form id="formHakAkses" method="POST">
                                @csrf
                                <div class="scroll-permission-wrapper"
                                    style="max-height: 500px; overflow-y: auto; padding-right: 10px;">
                                    <div id="list-permission" class="row"></div>
                                </div>
                                <div class="mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fa fa-save"></i> Simpan Hak Akses
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        const idLevelUser = `{{ $id }}`;

        async function getHakAksesUser() {
            $('#list-permission').html(`
            <div class="col-12 text-center">
                <div class="spinner-border text-primary mt-4 mb-4" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
                <div>Memuat data hak akses...</div>
            </div>
        `);
            $('#formHakAkses button[type="submit"]').hide(); // Hide submit while loading

            const getDataRest = await renderAPI(
                    'GET',
                    `{{ route('master.leveluser.permission.get', ['id' => '__ID__']) }}`.replace('__ID__',
                        idLevelUser), {}
                ).then(response => response)
                .catch(error => error.response);

            if (getDataRest.status === 200) {
                const data = getDataRest.data.data;
                $('#nama_level_user').html(
                    `<span class="font-weight-bold text-primary">${data.level.name || ''}</span>`);

                let html = '';
                data.permissions_grouped.forEach((group, index) => {
                    const collapseId = `collapseMenu${index}`;
                    const allChecked = group.permissions.every(perm =>
                        data.assigned_permissions.includes(perm.name)
                    );

                    const isGroupChecked = allChecked ? 'checked' : '';

                    html += `
                    <div class="col-12 mt-1 mb-2">
                        <div class="permission-group-header d-flex align-items-center justify-content-start">
                            <div class="form-check mr-2 ml-1">
                                <input type="checkbox" class="form-check-input check-all-group" id="check_all_group_${index}" data-group-index="${index}" ${isGroupChecked}>
                            </div>
                            <div class="toggle-collapse flex-grow-1" data-toggle="collapse" data-target="#${collapseId}" aria-expanded="true" aria-controls="${collapseId}">
                                <div class="permission-group-title d-flex justify-content-between align-items-center">
                                    <span><i class="fa fa-folder-open text-info mr-2"></i> ${group.menu_name}</span>
                                    <i class="fa fa-chevron-up collapse-icon ml-2" id="icon-${collapseId}"></i>
                                </div>
                            </div>
                        </div>
                        <div class="collapse show" id="${collapseId}">
                            <div class="row mt-1 ml-1">`;

                    group.permissions.forEach(perm => {
                        const isChecked = data.assigned_permissions.includes(perm.name) ? 'checked' :
                            '';
                        html += `
                        <div class="col-lg-4 col-md-6 col-sm-12 mt-1 mb-1">
                            <div class="form-check d-flex align-items-center">
                                <input class="form-check-input permission-checkbox" type="checkbox" value="${perm.name}" data-group-index="${index}" id="perm_${perm.id}" ${isChecked}>
                                <label class="form-check-label mb-0 permission-text-ellipsis" for="perm_${perm.id}" data-toggle="tooltip" data-placement="top" title="${perm.name}">
                                    <sup class="badge badge-method method-${(perm.method ?? '').toLowerCase()}">${perm.method ?? ''}</sup>
                                    ${perm.alias ? `<span class="permission-alias">${perm.alias}</span>` : perm.name}
                                </label>
                            </div>
                        </div>`;
                    });

                    html += `
                            </div>
                        </div>
                    </div>`;
                });

                $('#list-permission').html(html);
                $('#formHakAkses button[type="submit"]').show();
                $('[data-toggle="tooltip"]').tooltip();
            } else {
                $('#list-permission').html('<div class="col-12 text-danger">Gagal memuat data.</div>');
            }
        }

        async function createHakAksesUser() {
            $('#formHakAkses').on('submit', async function(e) {
                e.preventDefault();
                const $btn = $(this).find('button[type="submit"]');
                const originalHTML = $btn.html();

                $btn.prop('disabled', true).html(`
                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...
            `);

                const permissions = $('.permission-checkbox:checked').map(function() {
                    return $(this).val();
                }).get();

                const postDataRest = await renderAPI(
                        'POST',
                        `{{ route('master.leveluser.permission.store', ['id' => '__ID__']) }}`.replace(
                            '__ID__', idLevelUser), {
                            permissions
                        }
                    ).then(response => response)
                    .catch(error => error.response);

                if (postDataRest.status === 200) {
                    notificationAlert('success', 'Pemberitahuan', postDataRest.data.message);
                } else {
                    notificationAlert('error', 'Terjadi Kesalahan', postDataRest.data?.message ??
                        'Gagal menyimpan hak akses.');
                }

                $btn.prop('disabled', false).html(originalHTML); // restore button
            });
        }

        async function initHakAksesPage() {
            await getHakAksesUser();
            await createHakAksesUser();

            $(document).on('click', '.toggle-collapse', function() {
                const $icon = $(this).find('i');
                $icon.toggleClass('fa-chevron-down fa-chevron-up');
            });

            $(document).on('change', '.check-all-group', function() {
                const groupIndex = $(this).data('group-index');
                const isChecked = $(this).is(':checked');
                $(`.permission-checkbox[data-group-index="${groupIndex}"]`).prop('checked', isChecked);
            });

            $(document).on('change', '.permission-checkbox', function() {
                const groupIndex = $(this).data('group-index');
                const allGroupCheckboxes = $(`.permission-checkbox[data-group-index="${groupIndex}"]`);
                const allChecked = allGroupCheckboxes.length === allGroupCheckboxes.filter(':checked').length;
                $(`.check-all-group[data-group-index="${groupIndex}"]`).prop('checked', allChecked);
            });
        }

        $(document).ready(function() {
            initHakAksesPage();
        });
    </script>
@endsection

@extends('layouts.main')

@section('title')
    Edit Alias Hak Akses
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

        .form-control-alias {
            font-size: 14px;
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
                            <h5 class="mb-0">Edit Alias Hak Akses</h5>
                        </div>
                        <x-adminlte-alerts />
                        <div class="card-body">
                            <form id="formAliasPermission">
                                @csrf
                                <div class="scroll-permission-wrapper"
                                    style="max-height: 500px; overflow-y: auto; padding-right: 10px;">
                                    <div id="list-permission" class="row"></div>
                                </div>
                                <div class="mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fa fa-save"></i> Simpan Semua Alias
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
        async function getPermissionAliases() {
            $('#list-permission').html(`
                <div class="col-12 text-center">
                    <div class="spinner-border text-primary mt-4 mb-4" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                    <div>Memuat data hak akses...</div>
                </div>
            `);
            $('#formAliasPermission button[type="submit"]').hide();

            const getDataRest = await renderAPI('GET', `{{ route('master.permission.get') }}`, {})
                .then(response => response)
                .catch(error => error.response);

            if (getDataRest.status === 200) {
                const data = getDataRest.data.data;

                let html = '';
                data.forEach((group, index) => {
                    const collapseId = `collapseMenu${index}`;
                    html += `
                        <div class="col-12 mt-1">
                            <div class="permission-group-header toggle-collapse" data-toggle="collapse" data-target="#${collapseId}" aria-expanded="true" aria-controls="${collapseId}">
                                <div class="permission-group-title">
                                    <span><i class="fa fa-folder-open text-info mr-2"></i> ${group.menu_name}</span>
                                    <i class="fa fa-chevron-up collapse-icon" id="icon-${collapseId}"></i>
                                </div>
                            </div>
                            <div class="collapse show" id="${collapseId}">
                                <div class="row mt-1">`;

                    group.permissions.forEach(perm => {
                        html += `
                            <div class="col-lg-4 col-md-6 col-sm-12 mt-1 mb-1">
                                <div class="form-group">
                                    <label class="font-weight-bold" for="alias_${perm.id}" title="${perm.name}">
                                        <sup class="badge badge-method method-${(perm.method ?? '').toLowerCase()}">${perm.method ?? ''}</sup>
                                        ${perm.name}
                                    </label>
                                    <input type="text" placeholder="Isi Alias untuk ${perm.name}" class="form-control form-control-alias alias-input" id="alias_${perm.id}" data-id="${perm.id}" value="${perm.alias ?? ''}">
                                </div>
                            </div>`;
                    });

                    html += `
                                </div>
                            </div>
                        </div>`;
                });

                $('#list-permission').html(html);
                $('#formAliasPermission button[type="submit"]').show();
            } else {
                $('#list-permission').html('<div class="col-12 text-danger">Gagal memuat data permission.</div>');
            }
        }

        async function submitPermissionAliases() {
            $('#formAliasPermission').on('submit', async function(e) {
                e.preventDefault();
                const $btn = $(this).find('button[type="submit"]');
                const originalHTML = $btn.html();

                $btn.prop('disabled', true).html(`
                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...
                `);

                const permissions = $('.alias-input').map(function() {
                    return {
                        id: $(this).data('id'),
                        alias: $(this).val()
                    };
                }).get();

                const postData = await renderAPI(
                        'POST',
                        `{{ route('master.permission.update') }}`, {
                            permissions
                        }
                    ).then(response => response)
                    .catch(error => error.response);

                if (postData.status === 200) {
                    notificationAlert('success', 'Berhasil', postData.data.message);
                } else {
                    notificationAlert('error', 'Terjadi Kesalahan', postData.data?.message ??
                        'Gagal menyimpan data.');
                }

                $btn.prop('disabled', false).html(originalHTML);
            });
        }

        async function initPageAliasEditor() {
            await getPermissionAliases();
            await submitPermissionAliases();

            $(document).on('click', '.toggle-collapse', function() {
                const $icon = $(this).find('i');
                $icon.toggleClass('fa-chevron-down fa-chevron-up');
            });
        }

        $(document).ready(function() {
            initPageAliasEditor();
        });
    </script>
@endsection

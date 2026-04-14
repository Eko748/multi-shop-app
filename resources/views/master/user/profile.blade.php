@extends('layouts.main')

@section('title')
    Update Profile
@endsection

@section('css')
    <link rel="stylesheet" href="{{ asset('css/notyf.min.css') }}">
    <style>
        .card {
            transition: all 0.2s ease;
        }

        .card:hover {
            transform: translateY(-3px);
        }

        .form-control:focus {
            box-shadow: none;
            border-color: #4e73df;
        }

        .btn {
            transition: 0.2s;
        }

        .btn:hover {
            transform: scale(1.02);
        }
    </style>
@endsection

@section('content')
    <div class="pcoded-main-container">
        <div class="pcoded-content pt-3">

            <div class="row p-3">

                <!-- ===== LEFT: PROFILE ===== -->
                <div class="col-md-6 mb-3">
                    <div class="card shadow-sm border-0 rounded glossy-card bg-light h-100">
                        <div class="card-body">

                            <h5 class="mb-3 d-flex align-items-center">
                                <i class="fa fa-user mr-2 text-primary"></i>
                                Informasi Profile
                            </h5>

                            <form id="form-profile">

                                <div class="form-group">
                                    <label>Nama</label>
                                    <input type="text" name="nama" class="form-control"
                                        value="{{ auth()->user()->nama }}">
                                </div>

                                <div class="form-group">
                                    <label>Username</label>
                                    <input type="text" name="username" class="form-control"
                                        value="{{ auth()->user()->username }}">
                                </div>

                                <div class="form-group">
                                    <label>Alamat</label>
                                    <textarea name="alamat" class="form-control" rows="3">{{ auth()->user()->alamat }}</textarea>
                                </div>

                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fa fa-save mr-1"></i> Update Profile
                                </button>

                            </form>

                        </div>
                    </div>
                </div>

                <!-- ===== RIGHT: PASSWORD ===== -->
                <div class="col-md-6 mb-3">
                    <div class="card shadow-sm border-0 rounded glossy-card bg-light h-100">
                        <div class="card-body">

                            <h5 class="mb-3 d-flex align-items-center">
                                <i class="fa fa-lock mr-2 text-warning"></i>
                                Keamanan Akun
                            </h5>

                            <form id="form-password">

                                <div class="form-group">
                                    <label>Password Lama</label>
                                    <input type="password" name="old_password" class="form-control">
                                </div>

                                <div class="form-group">
                                    <label>Password Baru</label>
                                    <input type="password" name="new_password" class="form-control">
                                </div>

                                <div class="form-group">
                                    <label>Konfirmasi Password</label>
                                    <input type="password" name="confirm_password" class="form-control">
                                </div>

                                <button type="submit" class="btn btn-warning w-100">
                                    <i class="fa fa-key mr-1"></i> Update Password
                                </button>

                            </form>

                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>
@endsection

@section('asset_js')
    <script src="{{ asset('js/notyf.min.js') }}"></script>
@endsection

@section('js')
    <script>
        const userId = @json(auth()->user()->id);
        const notyf = new Notyf({
            duration: 3000,
            position: {
                x: 'right',
                y: 'top',
            }
        });
        const url = @json(route('user.updateProfile'));

        function handleError(error) {
            if (error.response) {
                const status = error.response.status;
                const data = error.response.data;

                if (status === 422) {
                    // validasi error
                    if (data.errors) {
                        Object.values(data.errors).forEach(errArr => {
                            errArr.forEach(msg => notyf.error(msg));
                        });
                    } else {
                        notyf.error(data.message || 'Validasi gagal');
                    }

                } else if (status === 404) {
                    notyf.error(data.message || 'Data tidak ditemukan');

                } else if (status === 403) {
                    notyf.error(data.message || 'Akses ditolak');

                } else {
                    notyf.error(data.message || 'Terjadi kesalahan server');
                }

            } else {
                notyf.error('Tidak dapat terhubung ke server');
            }
        }

        async function updateProfile() {
            $('#form-profile').off('submit').on('submit', async function(e) {
                e.preventDefault();

                const btn = $(this).find('button[type="submit"]');
                const originalText = btn.html();

                // 🔥 set loading
                btn.prop('disabled', true);
                btn.html(`
            <span class="spinner-border spinner-border-sm mr-1"></span>
            Menyimpan...
        `);

                const payload = {
                    id: userId,
                    nama: $('input[name="nama"]').val(),
                    username: $('input[name="username"]').val(),
                    alamat: $('textarea[name="alamat"]').val(),
                };

                try {
                    let res = await renderAPI(
                        'PATCH',
                        url,
                        payload
                    );

                    if (res.status === 200) {
                        notyf.success(res.data.message || 'Profile berhasil diupdate');
                    }

                } catch (error) {
                    handleError(error);
                } finally {
                    // 🔥 balikin tombol
                    btn.prop('disabled', false);
                    btn.html(originalText);
                }
            });
        }

        async function updatePassword() {
            $('#form-password').off('submit').on('submit', async function(e) {
                e.preventDefault();

                const btn = $(this).find('button[type="submit"]');
                const originalText = btn.html();

                // 🔥 loading state
                btn.prop('disabled', true);
                btn.html(`
            <span class="spinner-border spinner-border-sm mr-1"></span>
            Menyimpan...
        `);

                const payload = {
                    id: userId,
                    old_password: $('input[name="old_password"]').val(),
                    new_password: $('input[name="new_password"]').val(),
                    confirm_password: $('input[name="confirm_password"]').val(),
                };

                try {
                    let res = await renderAPI(
                        'PATCH',
                        url,
                        payload
                    );

                    if (res.status === 200) {
                        notyf.success(res.data.message || 'Password berhasil diupdate');
                        $('#form-password')[0].reset();
                    }

                } catch (error) {
                    handleError(error);
                } finally {
                    // 🔥 restore tombol
                    btn.prop('disabled', false);
                    btn.html(originalText);
                }
            });
        }

        function initPageLoad() {
            updateProfile();
            updatePassword();
        }

        $(document).ready(function() {
            initPageLoad();
        });
    </script>
@endsection

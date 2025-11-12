    @foreach ($kasir as $ksr)
        <div class="modal fade" id="mediumModal-{{ $ksr->id }}" tabindex="-1" role="dialog"
            aria-labelledby="mediumModalLabel-{{ $ksr->id }}" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-tile" id="mediumModalLabel-{{ $ksr->id }}">Detail Transaksi</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="tab-content" id="myTabContent-{{ $ksr->id }}">
                            <div class="tab-pane fade show active" id="home-{{ $ksr->id }}" role="tabpanel"
                                aria-labelledby="home-tab-{{ $ksr->id }}">
                                <div class="row">
                                    <div class="col-md-7 mb-4">
                                        <div class="info-wrapper">
                                            <div class="info-wrapper p-3 border rounded bg-light">
                                                <div class="row mb-0 pb-0">
                                                    <div class="col-12 col-sm-12 col-md-8 col-lg-8 col-xl-6">
                                                        <div class="info-row d-flex mb-2">
                                                            <p class="label mr-2"><i
                                                                    class="feather icon-file-text mr-1"></i>No Nota</p>
                                                            <p class="value">
                                                                :
                                                                @php
                                                                    $noNotaFormatted =
                                                                        substr($ksr->no_nota, 0, 6) .
                                                                        '-' .
                                                                        substr($ksr->no_nota, 6, 6) .
                                                                        '-' .
                                                                        substr($ksr->no_nota, 12);
                                                                @endphp
                                                                {{ $noNotaFormatted }}
                                                            </p>
                                                        </div>
                                                        <div class="info-row d-flex mb-2">
                                                            <p class="label mr-2"><i
                                                                    class="feather icon-calendar mr-1"></i>Tanggal
                                                                Transaksi</p>
                                                            <p class="value">:
                                                                {{ $ksr->created_at->setTimezone('Asia/Jakarta')->format('d-m-Y H:i:s') }}
                                                            </p>
                                                        </div>
                                                    </div>

                                                    <!-- Info Kasir -->
                                                    <div
                                                        class="col-12 col-sm-12 col-md-4 col-lg-4 col-xl-6 text-md-right text-left">
                                                        <div class="info-row d-flex justify-content-md-end">
                                                            <p class="label mr-2"><i
                                                                    class="feather icon-user mr-1"></i>Kasir</p>
                                                            <p class="value">: {{ $ksr->users->nama ?? '-' }}</p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <!-- Sisa Informasi -->
                                                <div class="info-row d-flex mb-2">
                                                    <p class="label mr-2"><i class="feather icon-layers mr-1"></i>Jumlah
                                                        Item</p>
                                                    <p class="value">: {{ $ksr->total_item }} Item</p>
                                                </div>
                                                <div class="info-row d-flex mb-2">
                                                    <p class="label mr-2"><i
                                                            class="feather icon-credit-card mr-1"></i>Nilai Transaksi</p>
                                                    <p class="value">: Rp.
                                                        {{ number_format($ksr->total_nilai, 0, '.', '.') }}</p>
                                                </div>
                                                <div class="info-row d-flex mb-2">
                                                    <p class="label mr-2"><i class="feather icon-percent mr-1"></i>Total
                                                        Potongan</p>
                                                    <p class="value">: Rp.
                                                        {{ number_format($ksr->total_diskon, 0, '.', '.') }}</p>
                                                </div>
                                                <div class="info-row d-flex mb-2">
                                                    <p class="label mr-2"><i
                                                            class="feather icon-credit-card mr-1"></i>Jumlah Bayar</p>
                                                    <p class="value">: Rp.
                                                        {{ number_format($ksr->jml_bayar, 0, '.', '.') }}</p>
                                                </div>
                                                <div class="info-row d-flex mb-2">
                                                    <p class="label mr-2"><i
                                                            class="feather icon-corner-down-left mr-1"></i>Kembalian</p>
                                                    <p class="value">: Rp.
                                                        {{ number_format($ksr->kembalian, 0, '.', '.') }}</p>
                                                </div>
                                                <div class="info-row d-flex mb-2">
                                                    <p class="label mr-2"><i class="feather icon-rotate-ccw mr-1"></i>Item
                                                        Retur</p>
                                                    <p class="value">: 0</p>
                                                </div>
                                                <div class="info-row d-flex">
                                                    <p class="label mr-2"><i class="feather icon-x-circle mr-1"></i>Nilai
                                                        Retur</p>
                                                    <p class="value">: 0</p>
                                                </div>
                                            </div>
                                        </div>


                                        <div class="table-responsive table-scroll-wrapper">
                                            <table class="table table-striped m-0" id="jsTable-{{ $ksr->id }}">
                                                <thead>
                                                    <tr>
                                                        <th class="text-center text-wrap align-top">Id trx</th>
                                                        <th class="text-wrap align-top">Nama Barang</th>
                                                        <th class="text-wrap align-top">Item</th>
                                                        <th class="text-wrap align-top">Harga</th>
                                                        <th class="text-wrap align-top">N.retur</th>
                                                        <th class="text-wrap align-top">QR Code Transaksi</th>
                                                        <th class="text-wrap align-top text-center">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @php
                                                        $groupedAndSorted = $detail_kasir
                                                            ->where('id_kasir', $ksr->id)
                                                            ->sortBy('id_barang') // urut berdasarkan id_barang
                                                            ->groupBy('id_barang')
                                                            ->flatten(1); // gabungkan kembali agar bisa di-foreach
                                                    @endphp

                                                    @foreach ($groupedAndSorted as $dtks)
                                                        <tr>
                                                            <td class="text-center text-wrap align-top">
                                                                {{ $dtks->id_kasir }}
                                                            </td>

                                                            @php
                                                                $namaBarang = $dtks->barang->nama_barang ?? '-';
                                                                $words = explode(' ', $namaBarang);
                                                                $shortWords = [];

                                                                foreach ($words as $word) {
                                                                    $shortWords[] =
                                                                        strlen($word) > 15
                                                                            ? substr($word, 0, 15) . '...'
                                                                            : $word;
                                                                }

                                                                $shortText = implode(' ', $shortWords);
                                                            @endphp

                                                            <td class="text-wrap align-top" title="{{ $namaBarang }}">
                                                                {{ $shortText }}</td>
                                                            <td class="text-wrap align-top">{{ $dtks->qty }}</td>
                                                            <td class="text-wrap align-top">
                                                                {{ number_format($dtks->harga, 0, '.', '.') }}</td>
                                                            <td class="text-wrap align-top">0</td>
                                                            <td class="text-wrap align-top">
                                                                <div
                                                                    class="d-flex flex-wrap align-items-center justify-content-between">
                                                                    <span class="mr-1 mb-1 text-break"
                                                                        id="qrcode-text-{{ $dtks->id }}">{{ $dtks->qrcode }}</span>
                                                                    <button type="button"
                                                                        class="btn btn-sm btn-outline-primary copy-btn"
                                                                        data-toggle="tooltip"
                                                                        title="Salin: {{ $dtks->qrcode }}"
                                                                        data-target="qrcode-text-{{ $dtks->id }}">
                                                                        <i class="fas fa-copy"></i>
                                                                    </button>
                                                                </div>
                                                            </td>
                                                            <td class="text-wrap align-top">
                                                                <div class="row pl-2 pr-4">
                                                                    <div
                                                                        class="col-12 col-xl-6 col-lg-12 p-0 m-0 mb-2 pl-2 justify-content-end">
                                                                        <a href="{{ asset('storage/' . $dtks->qrcode_path) }}"
                                                                            download
                                                                            class="w-100 btn btn-sm btn-outline-success"
                                                                            title="Download QR Code">
                                                                            <i class="fa fa-download"></i>
                                                                        </a>
                                                                    </div>
                                                                    <div
                                                                        class="col-12 col-xl-6 col-lg-12 p-0 m-0 mb-2 pl-2 justify-content-end">
                                                                        <button class="w-100 btn btn-sm btn-outline-info"
                                                                            title="Pengembalian Barang"
                                                                            onclick="pengembalianData({{ $dtks->id }}, '{{ $dtks->barang->nama_barang }}')">
                                                                            <i class="fa fa-rotate"></i>
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @endforeach

                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="col-md-5" style="background-color: rgb(250, 250, 250)">
                                        <button type="button" class="btn btn-primary btn-sm mb-3 btn-block mt-3"
                                            onclick="cetakStruk({{ $ksr->id }})">
                                            <i class="fa fa-print mr-2"></i>Cetak Struk
                                        </button>
                                        <div class="card text-center p-0" style="background-color: rgb(250, 250, 250)">
                                            <div class="card-body">
                                                <h5 class="card-subtitle">{{ $ksr->toko->nama_toko }}</h5>
                                                <p class="card-text">{{ $ksr->toko->alamat }}</p>
                                            </div>
                                        </div>
                                        <div class="info-wrapper">
                                            <div class="info-wrapper">
                                                <div class="info-row">
                                                    <p class="label">No Nota</p>
                                                    <p class="value">: @php
                                                        $noNotaFormatted =
                                                            substr($ksr->no_nota, 0, 6) .
                                                            '-' .
                                                            substr($ksr->no_nota, 6, 6) .
                                                            '-' .
                                                            substr($ksr->no_nota, 12);
                                                    @endphp
                                                        {{ $noNotaFormatted }}</p>
                                                </div>
                                                <div class="info-row">
                                                    <p class="label">Tgl Transaksi</p>
                                                    <p class="value">:
                                                        {{ $ksr->created_at->setTimezone('Asia/Jakarta')->format('d-m-Y H:i:s') }}
                                                    </p>
                                                </div>
                                                <div class="info-row">
                                                    <p class="label">Member</p>
                                                    <p class="value">:
                                                        {{ $ksr->id_member == 0 ? 'Guest' : $ksr->member->nama_member }}
                                                    </p>
                                                </div>
                                                <div class="info-row">
                                                    <p class="label">Kasir</p>
                                                    <p class="value">: {{ $ksr->users->nama ?? null }}</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="table-responsive-js">
                                            <table class="table-borderless" id="jsTable-{{ $ksr->id }}">
                                                <tbody>
                                                    @php
                                                        $groupedDetails = $detail_kasir
                                                            ->where('id_kasir', $ksr->id)
                                                            ->groupBy('id_barang')
                                                            ->map(function ($items) {
                                                                $first = $items->first();
                                                                return [
                                                                    'nama_barang' => $first->barang->nama_barang,
                                                                    'qty' => $items->sum('qty'),
                                                                    'harga' => $first->harga, // ambil dari satu item saja
                                                                    'diskon' => $first->diskon, // ambil dari satu item saja
                                                                    'total_harga' => $items->sum('total_harga'),
                                                                ];
                                                            });
                                                    @endphp

                                                    @foreach ($groupedDetails as $index => $item)
                                                        <tr>
                                                            <td class="narrow-column align-top">{{ $loop->iteration }}.
                                                            </td>
                                                            <td class="wide-column align-top">
                                                                ({{ $item['nama_barang'] }})
                                                                {{ $item['qty'] }}pcs
                                                                @.{{ number_format($item['harga'], 0, '.', '.') }}
                                                            </td>
                                                            <td class="price-column align-top">
                                                                -{{ number_format((float) $item['diskon'], 0, '.', '.') }}
                                                            </td>
                                                            <td class="price-column align-top">
                                                                {{ number_format($item['total_harga'], 0, '.', '.') }}
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                                <tfoot>
                                                    <tr>
                                                        <td></td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="3" style="text-align:left">Total Harga</td>
                                                        <td class="price-column">
                                                            {{ number_format($ksr->total_nilai, 0, '.', '.') }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="3" style="text-align:left">Total Potongan</td>
                                                        <td class="price-column">
                                                            {{ number_format($ksr->total_diskon, 0, '.', '.') }}</td>
                                                    </tr>
                                                    <tr style="background-color: rgba(145, 145, 145, 0.289);">
                                                        <th scope="col" colspan="3" style="text-align:left">Total
                                                        </th>
                                                        <th scope="col" class="price-column">
                                                            {{ number_format($ksr->total_nilai - $ksr->total_diskon, 0, '.', '.') }}
                                                        </th>
                                                    </tr>
                                                    <tr class="bg-success text-white">
                                                        <td colspan="3" style="text-align:left">Dibayar</td>
                                                        <td class="price-column">
                                                            {{ number_format($ksr->jml_bayar, 0, '.', '.') }}</td>
                                                    </tr>
                                                    @if ($ksr->kembalian != 0)
                                                        <tr class="bg-info text-white">
                                                            <td colspan="3" style="text-align:left">Kembalian</td>
                                                            <td class="price-column">
                                                                {{ number_format($ksr->kembalian, 0, '.', '.') }}</td>
                                                        </tr>
                                                    @endif
                                                    @if ($ksr->kasbon != null)
                                                        <tr class="bg-danger text-white">
                                                            <td colspan="3" style="text-align:left">Sisa Pembayaran
                                                            </td>
                                                            <td class="price-column">
                                                                {{ number_format($ksr->kasbon->utang, 0, '.', '.') }}
                                                            </td>
                                                        </tr>
                                                    @endif
                                                </tfoot>
                                            </table>
                                        </div>
                                        <p class="card-text" style="text-align: center">Terima Kasih</p>
                                        <hr>
                                        <button type="button" class="btn btn-primary btn-sm mb-3 btn-block"
                                            onclick="cetakStruk({{ $ksr->id }})">
                                            <i class="fa fa-print mr-2"></i>Cetak Struk
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="contact-{{ $ksr->id }}" role="tabpanel"
                                aria-labelledby="contact-tab-{{ $ksr->id }}">
                                Another Tab
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

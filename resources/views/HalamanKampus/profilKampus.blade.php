@include('navbar')

<link href="https://fonts.googleapis.com/css2?family=Alike&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/style.css') }}">

<header class="univ-header">
    <div class="univ-logo">USU</div>
    <div class="univ-info">
        <h1>Universitas Sumatera Utara</h1>
        <div class="univ-location">
            <svg viewBox="0 0 14 20" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M7 0C3.1 0 0.5 2.8 0.5 6.2c0 3.3 3.3 7 6.5 8.6c3.2-1.6 6.5-5.3 6.5-8.6C13.5 2.8 10.9 0 7 0z" fill="#000"/>
                <circle cx="7" cy="6" r="2.4" fill="#fff"/>
                <ellipse cx="7" cy="16.6" rx="4.2" ry="2.3" fill="none" stroke="#000" stroke-width="1.4"/>
            </svg>
            <span>Medan, Sumatera Utara</span>
        </div>
        <div class="univ-chips">
            <span class="univ-chip">PTN</span>
            <span class="univ-chip">Akreditasi Unggul</span>
        </div>
        <a class="univ-website" href >Website</a>
    </div>

    <div class="univ-actions">
        <a href="https://www.usu.ac.id" target="_blank" rel="noopener" class="btn-outline btn-website">Website Resmi</a>
        <button type="button" class="btn-outline btn-fav" aria-label="Simpan ke favorit">
            <svg viewBox="0 0 28 25" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">                    <path d="M14 23.2C6 17 1.5 12.7 1.5 8c0-3.4 2.6-6 5.9-6c2.6 0 5 1.5 6.6 3.8C15.600 3.500 18 2 20.600 2c3.300 0 5.900 2.600 5.900 6c0 4.700-4.500 9-12.500 15.200z" fill="none" stroke="#AEB6BF" stroke-width="2" stroke-linejoin="round"/>
            </svg>
        </button>
    </div>
</header>

<nav class="tabs">
    <a href="#profil">Profil</a>
    <a href="#fakultas">Fakultas &amp; Program Studi</a>
    <a href="#lokasi">Lokasi Kampus</a>
    <a href="#biaya">Biaya Kuliah</a>
</nav>

<section id="profil">
    <h2>Profil</h2>
    <p class="content">Universitas Sumatera Utara (USU) adalah salah satu perguruan tinggi negeri terbaik di Indonesia yang berlokasi di Medan, Sumatera Utara. Awalnya berdiri pada tahun 1952 dengan nama Yayasan Universitet Sumatera Utara, USU telah berkembang menjadi universitas unggulan yang menawarkan berbagai program studi yang berkualitas</p>
    <div class="meta">
        <div><small>Jenis Institusi</small>Universitas</div>
        <div><small>Tahun Berdiri</small>1952</div>
        <div><small>Akreditasi</small>Unggul</div>
        <div><small>Fakultas</small>10</div>
    </div>
</section>

<section id="fakultas">
    <h2>Fakultas &amp; Program Studi</h2>
    <input class="radio-hidden" type="radio" name="tampilan" id="tampil-fakultas" checked>
    <input class="radio-hidden" type="radio" name="tampilan" id="tampil-prodi">
    <div class="pills">
        <label class="pill" for="tampil-fakultas">Fakultas</label>
        <label class="pill" for="tampil-prodi">Program Studi</label>
    </div>

    <div class="listFakultas">
        <a class="row" href="{{ route('fakultas') }}">
            <div class="row-title">Fakultas Ilmu Komputer dan Teknologi Informasi</div>
            <div class="row-arrow">&gt;</div>
        </a>
        <a class="row" href="#">
            <div class="row-title">Fakultas Ilmu Budaya</div>
            <div class="row-arrow">&gt;</div>
        </a>
        <a class="row" href="#">
            <div class="row-title">Fakultas Teknik</div>
            <div class="row-arrow">&gt;</div>
        </a>
    </div>

    <div class="listProdi">
        <a class="row" href="{{ route('prodi') }}">
            <div class="row-title">S1 Teknologi Informasi
                <small>Fakultas Ilmu Komputer dan Teknologi Informasi</small>
            </div>
            <div class="row-arrow">&gt;</div>
        </a>
        <a class="row" href="#">
            <div class="row-title">S1 Ilmu Komputer
                <small>Fakultas Ilmu Komputer dan Teknologi Informasi</small>
            </div>
            <div class="row-arrow">&gt;</div>
        </a>
        <a class="row" href="#">
            <div class="row-title">S2 Kecerdasan Buatan
                <small>Fakultas Ilmu Komputer dan Teknologi Informasi</small>
            </div>
            <div class="row-arrow">&gt;</div>
        </a>
        <a class="row" href="#">
            <div class="row-title">S1 Teknik Sipil
                <small>Fakultas Teknik</small>
            </div>
            <div class="row-arrow">&gt;</div>
        </a>
  </div>
</section>

<section id="lokasi">
  <h2 style="text-align:center">Lokasi Kampus</h2>
  <div class="lokasi-list">
    <div class="lokasi-card">
        <h3>📍 USU Kampus 1</h3>
        <p><b>Medan, Sumatera Utara</b><br>Jl. Dr. T. Mansur No. 9</p>
        <a href="https://www.google.com/maps/search/?api=1&query=Universitas+Sumatera+Utara+Medan" target="_blank" rel="noopener">Lihat Peta</a>
    </div>
    <div class="lokasi-card">
        <h3>📍 USU Kampus 2</h3>
        <p><b>Medan, Sumatera Utara</b></p>
        <a href="#">Lihat Peta</a>
    </div>
    <div class="lokasi-card">
        <h3>📍 USU Kampus 3</h3>
        <p><b>Deli Serdang, Sumatera Utara</b></p>
        <a href="#">Lihat Peta</a>
    </div>
  </div>
</section>

<section id="biaya">
    <h2 style="text-align:center">Biaya UKT</h2>
    <input class="radio-hidden" type="radio" name="jenjang" id="j-d3">
    <input class="radio-hidden" type="radio" name="jenjang" id="j-d4">
    <input class="radio-hidden" type="radio" name="jenjang" id="j-s1" checked>
    <input class="radio-hidden" type="radio" name="jenjang" id="j-s2">
    <input class="radio-hidden" type="radio" name="jenjang" id="j-s3">

    <div class="tab-jenjang">
        <label class="pill" for="j-d3">Diploma 3</label>
        <label class="pill" for="j-d4">Diploma 4</label>
        <label class="pill" for="j-s1">Sarjana</label>
        <label class="pill" for="j-s2">Magister</label>
        <label class="pill" for="j-s3">Pendidikan Doktor</label>
    </div>

    <div class="panel panel-s1">
        <div class="tabel-wrapper">
            <table>
                <tr>
                    <th>Program Studi</th>
                    <th>UKT 1</th>
                    <th>UKT 2</th>
                    <th>UKT 3</th>
                    <th>UKT 4</th>
                    <th>UKT 5</th>
                    <th>UKT 6</th>
                </tr>
                
                <tr>
                    <td>Teknologi Informasi</td>
                    <td>Rp 500.000</td>
                    <td>Rp 1.000.000</td>
                    <td>Rp 1.500.000</td>
                    <td>Rp 2.000.000</td>
                    <td>Rp 3.000.000</td>
                    <td>Rp 3.600.000</td>
                </tr>
                
                <tr>
                    <td>Ilmu Komputer</td>
                    <td>Rp 550.000</td>
                    <td>Rp 1.100.000</td>
                    <td>Rp 1.650.000</td>
                    <td>Rp 2.200.000</td>
                    <td>Rp 3.300.000</td>
                    <td>Rp 3.960.000</td>
                </tr>
            </table>
        </div>
    </div>
    
    <div class="panel panel-d3">
        <div class="tabel-wrapper">
            <table>
                <tr>
                    <th>Program Studi</th>
                    <th>UKT 1</th>
                    <th>UKT 2</th>
                    <th>UKT 3</th>
                    <th>UKT 4</th>
                    <th>UKT 5</th>
                    <th>UKT 6</th>
                </tr>
                <tr>
                    <td colspan="7" class="kosong">Belum tersedia</td>
                </tr>
            </table>
        </div>
    </div>
    
    <div class="panel panel-d4">
        <div class="tabel-wrapper">
            <table>
                <tr>
                    <th>Program Studi</th>
                    <th>UKT 1</th>
                    <th>UKT 2</th>
                    <th>UKT 3</th>
                    <th>UKT 4</th>
                    <th>UKT 5</th>
                    <th>UKT 6</th>
                </tr>
                <tr>
                    <td colspan="7" class="kosong">Belum tersedia</td>
                </tr>
            </table>
        </div>
    </div>

    <div class="panel panel-s2">
        <div class="tabel-wrapper">
            <table>
                <tr>
                    <th>Program Studi</th>
                    <th>UKT 1</th>
                    <th>UKT 2</th>
                    <th>UKT 3</th>
                    <th>UKT 4</th>
                    <th>UKT 5</th>
                    <th>UKT 6</th>
                </tr>
                <tr>
                    <td colspan="7" class="kosong">Belum tersedia</td>
                </tr>
            </table>
        </div>
    </div>

    <div class="panel panel-s3">
        <div class="tabel-wrapper">
            <table>
                <tr>
                    <th>Program Studi</th>
                    <th>UKT 1</th>
                    <th>UKT 2</th>
                    <th>UKT 3</th>
                    <th>UKT 4</th>
                    <th>UKT 5</th>
                    <th>UKT 6</th>
                </tr>
                <tr>
                    <td colspan="7" class="kosong">Belum tersedia</td>
                </tr>
            </table>
        </div>
    </div>
</section>

<div class="copyright">
    © 2026 PTN Impian. Seluruh Hak Cipta Dilindungi Undang-Undang
</div>
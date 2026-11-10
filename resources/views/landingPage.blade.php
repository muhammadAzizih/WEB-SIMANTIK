<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Landing Page - PTN Impian</title>
    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <style>
        * {
            box-sizing: border-box;
            font-family: 'Georgia', serif, sans-serif;
        }

        body {
            background-color: #ffffff;
            color: #1a1a1a;
            margin: 0;
        }

        .search-container {
            display: flex;
            align-items: center;
            width: 100%;
            max-width: 650px;
            margin: 30px auto;
            padding: 6px 14px;
            border: 3px solid #ffbf5f;
            border-radius: 50px;
            background-color: #ffffff;
        }

        .search-wrapper {
            position: relative;
            width: 100%;
            max-width: 650px;
            margin: 30px auto;
        }

        .search-wrapper .search-container {
            margin: 0;
            max-width: 100%;
        }

        .search-suggestions {
            display: none;
            position: absolute;
            top: calc(100% + 8px);
            left: 0;
            width: 100%;
            background: #fff;
            border: 1px solid #f1d8b5;
            border-radius: 16px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.12);
            overflow: hidden;
            z-index: 1000;
        }

        .search-suggestions.show {
            display: block;
        }

        .suggestion-item {
            display: block;
            width: 100%;
            padding: 12px 18px;
            border: none;
            background: #fff;
            color: #1a1a1a;
            text-align: left;
            font-family: Georgia, serif;
            font-size: 15px;
            cursor: pointer;
        }

        .suggestion-item:hover {
            background: #fff3d6;
        }

        .suggestion-item small {
            display: block;
            margin-top: 4px;
            color: #888;
            font-size: 12px;
        }

        .search-icon-box {
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #ffbf5f;
            color: #000;
            padding: 8px 10px;
            border-radius: 12px;
            margin-right: 12px;
        }

        .search-container input {
            flex: 1;
            min-width: 0;
            border: none;
            outline: none;
            font-size: 15px;
            color: #1a1a1a;
            background: transparent;
        }

        .mic-btn {
            background: none;
            border: none;
            cursor: pointer;
            color: #1a1a1a;
        }

        .hero-section {
            text-align: left;
            padding: 0 6vw;
            margin: 30px auto;
        }

        .hero-title {
            font-size:  clamp(26px, 4vw, 38px);
            font-weight: normal;
            margin: 0 0 8px;
            color: #1a1a1a;
        }

        .hero-title strong {
            font-weight: bold;
        }

        .hero-desc {
            color: #555555;
            font-size: 18px;
            line-height: 1.6;
            margin: 0;
        }

        .category-group {
            display: flex;
            gap: 20px;
            padding: 0 6vw;
            margin: 30px auto;
            flex-wrap: wrap;
        }

        .cat-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            background-color: #C9D0D9;
            border: none;
            padding: 12px 24px;
            border-radius: 16px;
            font-size: 18px;
            font-weight: bold;
            color: #1a1a1a;
            cursor: pointer;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .cat-btn:hover {
            background-color: #ffbf5f;
            color: #ffffffff;
        }

        .filter-wrapper {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0 6vw;
            margin: 30px auto;
            flex-wrap: wrap;
        }

        .dropdown-group {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            border: 2px solid #ffbf5f;
            border-radius: 30px;
            padding: 6px;
            background-color: #ffffff;
        }

        .dropdown-item {
            position: relative;
        }

        .dropdown-btn {
           display: flex;
           align-items: center;
           justify-content: space-between;
           background: transparent;
           border: 1px solid #ffbf5f;
           border-radius: 20px;
           padding: 6px 10px;
           font-size: 16px;
           color: #1a1a1a;
           cursor: pointer;
           width: 160px;
           gap: 10px;
        }

        .dropdown-btn:focus,
        .dropdown-btn:active {
            font-family: 'Georgia', serif, sans-serif;
            font-size: 16px;
            font-weight: normal;
            color: #ee7f3e;
            padding: 6px 10px;
            width: 160px;
        }

        .dropdown-btn .arrow-box {
            border-left: 1px solid #ffbf5f;
            padding-left: 8px;
            color: #ffbf5f;
            font-weight: bold;
        }

        .dropdown-menu {
            display: none; 
            position: absolute;
            top: 110%;
            left: 0;
            background-color: #fcebd8;
            border-radius: 12px;
            width: 180px;
            max-height: 150px;
            overflow-y: auto;
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
            z-index: 100;
        }

        .dropdown-menu a {
            display: block;
            padding: 10px 14px;
            color: #1a1a1a;
            text-decoration: none;
            font-size: 14px;
            border-bottom: 1px solid #e3e3e3;
        }

        .dropdown-menu a:last-child {
            border-bottom: none;
        }

        .dropdown-menu a:hover {
            background-color: #ffbf5f;
            color: #1a1a1a;
        }

        .btn-filter {
            background-color: #ffbf5f;
            color: #1a1a1a;
            border: 2px solid #ffbf5f;
            padding: 8px 24px;
            border-radius: 25px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .btn-filter:hover {
            background-color: #ee7f3e;
            color: #ffffffff;
            border: 2px solid #ee7f3e;
            padding: 8px 24px;
            border-radius: 25px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .dropdown-menu::-webkit-scrollbar {
            width: 8px;
        }

        .dropdown-menu::-webkit-scrollbar-track {
            background: #fcebd8;
            border-radius: 10px;
        }

        .dropdown-menu::-webkit-scrollbar-thumb {
            background: #ffbf5f;
            border-radius: 10px;
        }

        .dropdown-menu::-webkit-scrollbar-thumb:hover {
            background: #ee7f3e;
        }

        .btn-filter:hover {
            background-color: #d96b32;
        }

        .btn-clear {
            background-color: #c9d0d9;
            color: #1a1a1a;
            border: 2px solid #c9d0d9;
            padding: 8px 24px;
            border-radius: 25px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        
        .btn-clear:hover {
            background-color: #aeb6bf;
        }

        #map {
            width: 100%;
            height: 550px;
            border-radius: 20px;
            overflow: hidden;
        }

        .cat-btn.active {
            background-color: #ffbf5f;
            color: #ffffff;
        }

        .dropdown-menu .dd-search {
            position: sticky;
            top: 0;
        }

        @media (max-width: 768px) {
            .search-container {
                width: 90%;
            }

            .hero-section,
            .category-group,
            .filter-wrapper {
                padding-left: 5vw;
                padding-right: 5vw;
            }

            .category-group {
                gap: 12px;
            }

            .cat-btn {
                font-size: 15px;
                padding: 10px 16px;
            }

            .dropdown-group {
                width: 100%;
            }

            .dropdown-item {
                flex: 1;
                min-width: 130px;
            }

            .dropdown-btn {
                width: 100%;
                font-size: 14px;
            }

            .btn-filter,
            .btn-clear {
                font-size: 16px;
            }

            #map {
                height: 350px;
            }
        }

        @media (max-width: 480px) {
            .hero-desc {
                font-size: 16px;
            }

            .hero-desc br {
                display: none;
            }

            .category-group {
                gap: 10px;
            }

            .cat-btn {
                font-size: 14px;
                padding: 10px 12px;
            }

            .dropdown-item {
                flex-basis: 100%;
            }
        }
    </style>
</head>
<body>

    @include('navbar')

    <div class="search-wrapper">
        <div class="search-container">
            <div class="search-icon-box">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2.5">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
            </div>

            <input type="text" id="search-input"
                placeholder="Cari nama kampus, jurusan, lokasi dan lainnya..."
                autocomplete="off">

            <button class="mic-btn">
                <!-- SVG microphone tetap gunakan yang sebelumnya -->
            </button>
        </div>

        <div id="search-suggestions" class="search-suggestions"></div>
    </div>

    <div class="hero-section">
        <h1 class="hero-title">Selamat datang di <strong>PTN impian</strong></h1>
        <p class="hero-desc">
            Teman yang membantu kamu mencari kampus terbaik untuk<br>
            kamu belajar, berkembang, dan menggapai mimpi
        </p>
    </div>

    <div class="category-group">
        <button class="cat-btn active" data-kategori="">🏛️ Semua Kampus</button>
        <button class="cat-btn" data-kategori="PTN_UMUM">🏛️ PTN Umum</button>
        <button class="cat-btn" data-kategori="PTKN">⚙️ PTKN</button>
        <!-- <button class="cat-btn">🏢 Institut</button> -->
    </div>

    <div class="filter-wrapper">        <div class="dropdown-group">
            <div class="dropdown-item" data-filter="jenis">
                <button type="button" class="dropdown-btn">
                    <span class="dd-label" data-default="Jenis Kampus">Jenis Kampus</span>
                    <span class="arrow-box">∨</span>
                </button>
                <div class="dropdown-menu"><div class="dd-options"></div></div>
            </div>

            <div class="dropdown-item" data-filter="provinsi">
                <button type="button" class="dropdown-btn">
                    <span class="dd-label" data-default="Provinsi">Provinsi</span>
                    <span class="arrow-box">∨</span>
                </button>
                <div class="dropdown-menu"><div class="dd-options"></div></div>
            </div>

            <div class="dropdown-item" data-filter="kota">
                <button type="button" class="dropdown-btn">
                    <span class="dd-label" data-default="Kota">Kota</span>
                    <span class="arrow-box">∨</span>
                </button>
                <div class="dropdown-menu"><div class="dd-options"></div></div>
            </div>

            <div class="dropdown-item" data-filter="jenjang">
                <button type="button" class="dropdown-btn">
                    <span class="dd-label" data-default="Jenjang">Jenjang</span>
                    <span class="arrow-box">∨</span>
                </button>
                <div class="dropdown-menu"><div class="dd-options"></div></div>
            </div>

            <div class="dropdown-item" data-filter="prodi">
                <button type="button" class="dropdown-btn">
                    <span class="dd-label" data-default="Prodi">Prodi</span>
                    <span class="arrow-box">∨</span>
                </button>
                <div class="dropdown-menu"><input type="text" class="dd-search" placeholder="Cari prodi..."><div class="dd-options"></div></div>
            </div>
        </div>

        <button type="button" class="btn-filter" id="btn-filter">Filter</button>
        <button type="button" class="btn-clear" id="btn-clear">Clear</button>
    </div>

    @include('chatbot')
    @include('content')
    @include('footer')

    <script src="{{ asset('js/kampus-filter.js') }}"></script>

    <!-- js unutk kalau user nyari, itu ada muncul dibawahnya itu -->
    <script>
        const searchInput = document.getElementById('search-input');
        const suggestionsBox = document.getElementById('search-suggestions');

        let searchTimer;
        let controller;
        let selectedSuggestion = false;

        async function loadSuggestions(keyword = '') {
            if (controller) controller.abort();

            controller = new AbortController();

            try {
                const response = await fetch(
                    `/api/kampus/suggestions?q=${encodeURIComponent(keyword)}`,
                    { signal: controller.signal }
                );

                if (!response.ok) throw new Error('Gagal mengambil rekomendasi');

                const data = await response.json();

                suggestionsBox.innerHTML = '';

                if (data.length === 0) {
                    suggestionsBox.classList.remove('show');
                    return;
                }

                data.forEach(kampus => {
                    const item = document.createElement('button');
                    item.type = 'button';
                    item.className = 'suggestion-item';

                    const nama = document.createElement('strong');
                    nama.textContent = kampus.nama;

                    const lokasi = document.createElement('small');
                    lokasi.textContent = `${kampus.kota}, ${kampus.provinsi}`;

                    item.append(nama, lokasi);

                    item.addEventListener('click', () => {
                        window.location.href = `/kampus?id=${encodeURIComponent(kampus.id)}`;
                    });

                    suggestionsBox.appendChild(item);
                });

                suggestionsBox.classList.add('show');

            } catch (error) {
                if (error.name !== 'AbortError') {
                    console.error(error);
                }
            }
        }

        searchInput.addEventListener('focus', () => {
            loadSuggestions(searchInput.value.trim());
        });

        searchInput.addEventListener('input', function () {
            if (selectedSuggestion) {
                selectedSuggestion = false;
                return;
            }

            clearTimeout(searchTimer);

            const keyword = this.value.trim();

            if (keyword.length === 0) {
                loadSuggestions('');
                return;
            }

            if (keyword.length < 2) {
                suggestionsBox.classList.remove('show');
                return;
            }

            searchTimer = setTimeout(() => {
                loadSuggestions(keyword);
            }, 200);
        });

        document.addEventListener('click', function (event) {
            if (!event.target.closest('.search-wrapper')) {
                suggestionsBox.classList.remove('show');
            }
        });
    </script>

    <!-- js untuk map -->
     <!-- js untuk map -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
        // 1. Inisialisasi peta Indonesia (fokus tengah Indonesia, zoom level 5)
        const map = L.map('map').setView([-2.5, 118], 5);

        // 2. Pasang tampilan peta dari OpenStreetMap
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        // 3. Ambil data asli dari API Fuseki (/api/kampus/peta)
        fetch('/api/kampus/peta')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Gagal mengambil data peta: ' + response.status);
                }
                return response.json();
            })
            .then(dataKampus => {
                // 4. Looping setiap kampus yang memiliki koordinat dari Fuseki
                dataKampus.forEach(kampus => {
                    if (kampus.lat && kampus.lng) {
                        // Desain pop-up info saat pin kampus di-klik
                        const popupContent = `
                            <div style="font-family: sans-serif; min-width: 180px; padding: 4px;">
                                <span style="background: #c9d0d9; font-size: 11px; font-weight: bold; padding: 2px 6px; border-radius: 4px;">
                                    ${kampus.singkatan || 'PTN'}
                                </span>
                                <h4 style="margin: 8px 0 4px; font-size: 14px; color: #1a1a1a; line-height: 1.3;">
                                    ${kampus.nama}
                                </h4>
                                <p style="margin: 0 0 10px; font-size: 12px; color: #666;">
                                     ${kampus.kota}, ${kampus.provinsi}
                                </p>
                                <a href="/kampus?id=${kampus.id}" style="display: inline-block; background: #ee7f3e; color: #ffffff; text-decoration: none; padding: 5px 12px; border-radius: 6px; font-size: 12px; font-weight: bold;">
                                    Lihat Kampus →
                                </a>
                            </div>
                        `;

                        // marker ke peta
                        L.marker([kampus.lat, kampus.lng])
                            .addTo(map)
                            .bindPopup(popupContent);
                    }
                });
            })
            .catch(error => {
                console.error('Error memuat peta:', error);
            });
    </script>
</body>
</html>
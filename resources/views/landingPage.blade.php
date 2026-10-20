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
           padding: 8px 12px;
           font-size: 16px;
           color: #1a1a1a;
           cursor: pointer;
           width: 170px;
           gap: 10px;
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
            background-color: #ee7f3e;
            color: #ffffff;
            border: 2px solid #ee7f3e;
            padding: 8px 24px;
            border-radius: 25px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s ease;
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

    <div class="search-container">
        <div class="search-icon-box">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
        </div>
        <input type="text" placeholder="Cari nama kampus, jurusan, lokasi dan lainnya...">
        <button class="mic-btn">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3z"></path>
                <path d="M19 10v2a7 7 0 0 1-14 0v-2"></path>
                <line x1="12" y1="19" x2="12" y2="22"></line>
            </svg>
        </button>
    </div>

    <div class="hero-section">
        <h1 class="hero-title">Selamat datang di <strong>PTN impian</strong></h1>
        <p class="hero-desc">
            Teman yang membantu kamu mencari kampus terbaik untuk<br>
            kamu belajar, berkembang, dan menggapai mimpi
        </p>
    </div>

    <div class="category-group">
        <button class="cat-btn">🏛️ Semua PTN</button>
        <button class="cat-btn">🏛️ Universitas</button>
        <button class="cat-btn">⚙️ Politeknik</button>
        <button class="cat-btn">🏢 Institut</button>
    </div>

    <div class="filter-wrapper">
        <div class="dropdown-group">
            
            <div class="dropdown-item">
                <button class="dropdown-btn">
                    <span>Provinsi</span>
                    <span class="arrow-box">∨</span>
                </button>
                <div class="dropdown-menu">
                    <a href="#">Sumatera Utara</a>
                    <a href="#">Sumatera Utara</a>
                    <a href="#">Sumatera Utara</a>
                    <a href="#">Sumatera Utara</a>
                    <a href="#">Sumatera Utara</a>
                    <a href="#">Sumatera Utara</a>
                    <a href="#">Sumatera Utara</a>
                    <a href="#">Sumatera Utara</a>
                    <a href="#">Sumatera Utara</a>
                    <a href="#">Sumatera Utara</a>
                </div>
            </div>

            <div class="dropdown-item">
                <button class="dropdown-btn">
                    <span>Kota</span>
                    <span class="arrow-box">∨</span>
                </button>
                <div class="dropdown-menu">
                    <a href="#">Medan</a>
                    <a href="#">Padang</a>
                    <a href="#">Padang</a>
                    <a href="#">Padang</a>
                    <a href="#">Padang</a>
                    <a href="#">Padang</a>
                    <a href="#">Padang</a>
                    <a href="#">Padang</a>
                </div>
            </div>

            <div class="dropdown-item">
                <button class="dropdown-btn">
                    <span>Jenjang</span>
                    <span class="arrow-box">∨</span>
                </button>
                <div class="dropdown-menu">
                    <a href="#">S1</a>
                    <a href="#">D3 / D4</a>
                    <a href="#">D3 / D4</a>
                    <a href="#">D3 / D4</a>
                    <a href="#">D3 / D4</a>
                    <a href="#">D3 / D4</a>
                </div>
            </div>

            <div class="dropdown-item">
                <button class="dropdown-btn">
                    <span>Prodi</span>
                    <span class="arrow-box">∨</span>
                </button>
                <div class="dropdown-menu">
                    <a href="#">Teknik Informatika</a>
                    <a href="#">Sistem Informasi</a>
                    <a href="#">Sistem Informasi</a>
                    <a href="#">Sistem Informasi</a>
                    <a href="#">Sistem Informasi</a>
                    <a href="#">Sistem Informasi</a>
                    <a href="#">Sistem Informasi</a>
                    <a href="#">Sistem Informasi</a>
                </div>
            </div>

        </div>

        <button class="btn-filter">Filter</button>
        <button class="btn-clear">Clear</button>
    </div>

    @include('chatbot')
    @include('content')
    @include('footer')

    <script>
        const dropdownButtons = document.querySelectorAll('.dropdown-btn');

        dropdownButtons.forEach(button => {
            button.addEventListener('click', function () {
                const menu = this.nextElementSibling;

                document.querySelectorAll('.dropdown-menu').forEach(item => {
                    if (item !== menu) {
                        item.style.display = 'none';
                    }
                });

                menu.style.display =
                    menu.style.display === 'block' ? 'none' : 'block';
            });
        });

        document.addEventListener('click', function (event) {
            if (!event.target.closest('.dropdown-item')) {
                document.querySelectorAll('.dropdown-menu').forEach(menu => {
                    menu.style.display = 'none';
                });
            }
        });
    </script>

    <!-- js untuk map -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
        const map = L.map('map').setView([-2.5, 118], 5);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        // data lokasi kordinat masing" ptn
        const ptn = [
            {
                nama: "Universitas Sumatera Utara",
                lat: 3.5619,
                lng: 98.6561
            },
            {
                nama: "Universitas Indonesia",
                lat: -6.3626,
                lng: 106.8249
            },
            {
                nama: "Universitas Gadjah Mada",
                lat: -7.7704,
                lng: 110.3777
            }
        ];

        // buat marker
        ptn.forEach(kampus => {
            L.marker([kampus.lat, kampus.lng])
                .addTo(map)
                .bindPopup(`<b>${kampus.nama}</b>`);
        });
    </script>
</body>
</html>
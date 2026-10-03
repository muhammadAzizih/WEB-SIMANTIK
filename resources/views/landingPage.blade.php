<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Landing Page - PTN Impian</title>
    <style>
        * {
            box-sizing: border-box;
            font-family: 'Georgia', serif, sans-serif;
        }

        body {
            background-color: #ffffff;
            margin: 0;
        }

        .search-container {
            display: flex;
            align-items: center;
            width: 100%;
            max-width: 650px;
            margin:30px auto;
            padding: 6px 14px;
            border: 3px solid #fcae49;
            border-radius: 50px;
            background-color: #ffffff;
        }

        .search-icon-box {
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #fcae49;
            color: #000;
            padding: 8px 10px;
            border-radius: 12px;
            margin-right: 12px;
        }

        .search-container input {
            flex: 1;
            border: none;
            outline: none;
            font-size: 15px;
            color: #333;
        }

        .mic-btn {
            background: none;
            border: none;
            cursor: pointer;
        }

        .hero-section {
            text-align: left;
            padding-left: 80px;
            margin: 30px auto;
        }

        .hero-title {
            font-size: 40px;
            font-weight: normal;
            margin-bottom: 8px;
            color: #111;
        }

        .hero-title strong {
            font-weight: bold;
        }

        .hero-desc {
            color: #555;
            font-size: 21px;
            line-height: 1.5;
            margin: 0;
        }

        .category-group {
            display: flex;
            gap: 115px;
            padding-left: 80px;
            margin: 30px auto;
            flex-wrap: wrap;
        }

        .cat-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            background-color: #cbd5e1;
            border: none;
            padding: 15px 30px;
            border-radius: 20px;
            font-size: 19px;
            font-weight: bold;
            color: #1e293b;
            cursor: pointer;
        }

        
        .cat-btn:hover {
            background-color: #fcae49;
            color: #ffffffff;
            transition: all 0.4s ease;
        }

        .filter-wrapper {
            display: flex;
            align-items: center;
            gap: 12px;
            padding-left: 80px;
            margin: 30px auto;
            flex-wrap: wrap;
        }

        .dropdown-group {
            display: flex;
            align-items: center;
            border: 2px solid #fcae49;
            border-radius: 30px;
            padding: 6px;
            gap: 8px;
            background-color: #fff;
        }

        .dropdown-item {
            position: relative;
        }

        .dropdown-btn {
           display: flex;
           align-items: center;
           justify-content: space-between;
           background: transparent;
           border: 1px solid #fcae49;
           border-radius: 20px;
           padding: 8px 12px;
           font-size: 20px;
           color: #64748b;
           cursor: pointer;
           width: 197px;
        }

        .dropdown-btn .arrow-box {
            border-left: 1px solid #fcae49;
            padding-left: 8px;
            color: #fcae49;
            font-weight: bold;
        }

        .dropdown-menu {
            display: none; 
            position: absolute;
            top: 110%;
            left: 0;
            background-color: #cbd5e1;
            border-radius: 15px;
            width: 180px;
            max-height: 150px;
            overflow-y: auto;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 100;
        }

        .dropdown-menu a {
            display: block;
            padding: 10px 14px;
            color: #475569;
            text-decoration: none;
            font-size: 15px;
            border-bottom: 1px solid #b3c1d1;
        }

        .dropdown-menu a:last-child {
            border-bottom: none;
        }

        .dropdown-menu a:hover {
            background-color: #b3c1d1;
            color: #000;
        }

        .btn-filter {
            background-color: #ff6b00;
            color: #ffffff;
            border: 2px solid #fcae49;
            padding: 8px 28px;
            border-radius: 25px;
            font-size: 25px;
            font-weight: bold;
            cursor: pointer;
        }

        .btn-clear {
            background-color: #cbd5e1;
            color: #1e293b;
            border: 2px solid #64748b;
            padding: 8px 24px;
            border-radius: 25px;
            font-size: 25px;
            font-weight: bold;
            cursor: pointer;
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
</body>
</html>
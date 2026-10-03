<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Profil Kampus</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Anton&family=Anton+SC&family=Bebas+Neue&family=Libre+Caslon+Condensed:ital,wght@0,400..700;1,400..700&family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #fff;
            font-family: 'Libre Caslon Condensed', serif;
            color: #111;
            min-height: 100vh;
        }

        .univ-header {
            height: 137px;
            padding: 24px 100px 0 100px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #E6E6E6;
        }

        .univ-left {
            display: flex;
            align-items: flex-start;
        }

        .univ-logo {
            width: 98px;
            height: 98px;
            border-radius: 50%;
            object-fit: contain;
            display: block;
        }

        .univ-info {
            margin-left: 30px;
            padding-top: 2px;
        }

        .univ-name {
            margin: 0;
            font-size: 22px;
            font-weight: 700;
            line-height: 26px;
        }

        .univ-location {
            margin-top: 2px;
            height: 18px;
            display: flex;
            align-items: center;
            font-size: 13px;
        }

        .univ-location svg {
            width: 13px;
            height: 20px;
            margin: 0 13px 0 2px;
        }

        .univ-badges {
            margin-top: 14px;
            display: flex;
            gap: 11px;
        }

        .badge {
            height: 21px;
            padding: 0 12px;
            background: #C5CDD8;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
        }

        .univ-actions {
            display: flex;
            align-items: center;
            gap: 13px;
        }

        .btn-outline {
            height: 37px;
            border: 2px solid #CCD3DA;
            border-radius: 8px;
            background: #fff;
            font-family: inherit;
            font-size: 13px;
            color: #111;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            text-decoration: none;
        }

        .btn-website {
            width: 119px;
        }

        .btn-fav {
            width: 47px;
        }

        .btn-fav svg {
            width: 28px;
            height: 25px;
        }

        .tabs {
            height: 48px;
            padding: 3px 100px 0 100px;
            display: flex;
            align-items: flex-start;
            border-bottom: 2px solid #E6E6E6;
        }

        .tab {
            height: 41px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 19px;
            font-weight: 700;
            color: #111;
            text-decoration: none;
            position: relative;
        }

        .tab.active {
            width: 93px;
            background: #FFEBD2;
            border-radius: 21px;
        }

        .tab.active::after {
            content: "";
            position: absolute;
            left: 5px;
            right: 6px;
            bottom: -5px;
            height: 3px;
            background: #FFBF5F;
        }

        .tab-fakultas { margin-left: 43px; }
        .tab-biaya    { margin-left: 44px; }

        .content {
            padding: 45px 100px 0 100px;
        }

        .content p {
            margin: 0;
            font-size: 13.5px;
            line-height: 21px;
            max-width: 970px;
        }

        .chat-fab {
            position: fixed;
            right: 41px;
            bottom: 36px;
            width: 82px;
            height: 70px;
            border-radius: 22px;
            background: #EEEEEE;
            border: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .chat-fab svg {
            width: 48px;
            height: 36px;
        }
    </style>
</head>
<body>

    
    @include('navbar')

    <section class="univ-header">
        <div class="univ-left">
            <img class="univ-logo" src="{{ asset('images/logo-usu.png') }}" alt="Logo Universitas Sumatera Utara">

            <div class="univ-info">
                <h1 class="univ-name">Universitas Sumatera Utara</h1>

                <div class="univ-location">
                    <svg viewBox="0 0 14 20" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M7 0C3.1 0 0.5 2.8 0.5 6.2c0 3.3 3.3 7 6.5 8.6c3.2-1.6 6.5-5.3 6.5-8.6C13.5 2.8 10.9 0 7 0z" fill="#000"/>
                        <circle cx="7" cy="6" r="2.4" fill="#fff"/>
                        <ellipse cx="7" cy="16.6" rx="4.2" ry="2.3" fill="none" stroke="#000" stroke-width="1.4"/>
                    </svg>
                    <span>Medan, Sumatera Utara</span>
                </div>

                <div class="univ-badges">
                    <span class="badge">PTN</span>
                    <span class="badge">Akreditasi Unggul</span>
                </div>
            </div>
        </div>

        <div class="univ-actions">
            <a href="https://www.usu.ac.id" target="_blank" rel="noopener" class="btn-outline btn-website">Website Resmi</a>
            <button type="button" class="btn-outline btn-fav" aria-label="Simpan ke favorit">
                <svg viewBox="0 0 28 25" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path d="M14 23.2C6 17 1.5 12.7 1.5 8c0-3.4 2.6-6 5.9-6c2.6 0 5 1.5 6.6 3.8C15.600 3.500 18 2 20.600 2c3.300 0 5.900 2.600 5.900 6c0 4.700-4.500 9-12.500 15.200z" fill="none" stroke="#AEB6BF" stroke-width="2" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>
    </section>

    <nav class="tabs">
        <a href="#profil" class="tab active">Profil</a>
        <a href="#fakultas" class="tab tab-fakultas">Fakultas &amp; Prodi</a>
        <a href="#biaya" class="tab tab-biaya">Biaya Kuliah</a>
    </nav>

    <main class="content" id="profil">
        <p>
            Universitas Sumatera Utara (USU) adalah salah satu perguruan tinggi negeri terbaik di Indonesia yang
            berlokasi di Medan, Sumatera Utara. Awalnya berdiri pada tahun 1952 dengan nama Yayasan Universitet
            Sumatera Utara, USU telah berkembang menjadi universitas unggulan yang menawarkan berbagai program
            studi yang berkualitas.
        </p>
    </main>

    <button type="button" class="chat-fab" aria-label="Buka chat">
        <svg viewBox="0 0 48 36" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path d="M3 17C3 9 10 3 20 3s17 6 17 14s-7 14-17 14c-2 0-4-.3-5.800-.8L7 33l1.600-6C5 24.500 3 21 3 17z" fill="#7C7C7C"/>
            <path d="M33 11c8 .6 13 5 13 11c0 3.500-1.800 6.500-4.800 8.500L42.500 35l-5.500-2.300c-1.200.3-2.500.5-3.800.5c-2.700 0-5.200-.7-7.200-1.900C31 29.500 36 26 36 21c0-3.800-1.200-7.800-3-10z" fill="#7C7C7C" opacity=".75"/>
            <circle cx="13" cy="17" r="2.300" fill="#EEE"/>
            <circle cx="20.500" cy="17" r="2.300" fill="#EEE"/>
            <circle cx="28" cy="17" r="2.300" fill="#EEE"/>
        </svg>
    </button>

</body>
</html>

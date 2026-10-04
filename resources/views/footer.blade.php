     <style>
        .footer-container {
        background-color: #ffffff;
        border-top: 2px solid #f97316;
        color: #4b5563;
        font-family: Georgia, serif;
        font-size: 14px;
        padding: 40px 32px 24px;
        position: relative;
    }
    .footer-content {
        max-width: 1100px;
        margin: 0 auto;
        display: grid;
        grid-template-columns: 1.2fr 1fr 1fr;
        gap: 32px;
    }
    .brand-logo {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 16px;
    }
    .logo-box {
        border: 2px solid #334155;
        border-radius: 8px;
        padding: 6px;
        width: 48px;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        font-size: 10px;
        font-weight: bold;
        color: #f97316;
    }
    .brand-title {
        font-size: 20px;
        font-weight: bold;
        color: #111827;
        margin: 0;
    }
    .brand-subtitle {
        font-size: 12px;
        color: #6b7280;
        margin: 0;
        font-family: sans-serif;
    }
    .section-title {
        color: #f97316;
        font-weight: 600;
        font-size: 16px;
        border-bottom: 2px solid #f97316;
        display: inline-block;
        padding-bottom: 2px;
        margin-bottom: 12px;
    }
    .footer-menu {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .footer-menu li {
        margin-bottom: 15px;
    }
    .footer-menu a {
        color: #4b5563;
        text-decoration: none;
    }
    .footer-menu a:hover {
        text-decoration: underline;
    }
    .contact-item {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .icon {
        color: #f97316;
    }
    .copyright {
        margin-top: 48px;
        text-align: center;
        font-size: 12px;
        color: #6b7280;
    }
    </style>
</head>
<body>
    <footer class="footer-container">
        <div class="footer-content">

            <div>
            <div class="brand-logo">
                <div class="logo-box">PTN Impian</div>
                <div>
                <h2 class="brand-title">PTN impian</h2>
                <p class="brand-subtitle">Gerbang Menuju Mimpi</p>
                </div>
            </div>
            <p style="line-height: 1.6; font-size: 13px;">
                website yang membantu calon mahasiswa menemukan dan mengenal berbagai <strong>Perguruan Tinggi Negeri (PTN) di Indonesia.</strong> Pengguna dapat mencari PTN berdasarkan lokasi, wilayah, jenis perguruan tinggi, akreditasi, serta informasi terkait lainnya. Dengan tampilan yang informatif dan mudah digunakan, PTN Impian hadir untuk membantu calon mahasiswa menemukan kampus yang sesuai dengan minat dan tujuan pendidikan mereka.
            </p>
            </div>

            <div>
            <div style="margin-bottom: 32px;">
                <h3 class="section-title">Navigasi Cepat</h3>
                <ul class="footer-menu">
                <li><a href="#">Beranda</a></li>
                <li><a href="#">Daftar 100+ PTN</a></li>
                <li><a href="#">Peta Lokasi Kampus</a></li>
                </ul>
            </div>

            <div>
                <h3 class="section-title">Kontak & Pengaduan</h3>
                <ul class="footer-menu">
                <li class="contact-item">
                    <span class="icon">✉</span>
                    <a href="mailto:halo@ptnimpian.id">halo@ptnimpian.id</a>
                </li>
                <li class="contact-item">
                    <span class="icon">📞</span>
                    <span>+62 21 8900 1234</span>
                </li>
                </ul>
            </div>
            </div>

            <div>
            <h3 class="section-title">Data & Akreditasi</h3>
            <ul class="footer-menu">
                <li><a href="#">BAN-PT Terkini</a></li>
                <li><a href="#">Kemendikbudristek RI</a></li>
                <li><a href="#">Portal SNPMB BPP</a></li>
                <li><a href="#">Standar UKT Golongan PTN</a></li>
            </ul>
            </div>

        </div>

        <div class="copyright">
            © 2026 PTN Impian. Seluruh Hak Cipta Dilindungi Undang-Undang
        </div>
    </footer>
<style>
    body {
        background-color: #f8fafc;
    }
    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 10px 16px;
        padding-left: 60px;
        padding-right: 60px;
    }
    .uni-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 15px;
    }
    .uni-card {
        background: #ffffff;
        border-radius: 20px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
        overflow: hidden;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        border: 1px solid #f1f5f9;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .uni-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.12);
    }
    .card-img {
        width: 100%;
        height: 150px;
        object-fit: cover;
    }
    .card-body {
        padding: 15px;
    }
    .card-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 16px;
    }
    .uni-name {
        font-size: 20px;
        font-weight: bold;
        color: #111827;
        margin: 0;
        line-height: 1.2;
    }
    .badge {
        background-color: #e5e7eb;
        color: #4b5563;
        font-family: sans-serif;
        font-size: 11px;
        font-weight: bold;
        padding: 4px 8px;
        border-radius: 4px;
    }
    .info-box {
        background-color: #f3f4f6;
        border-radius: 12px;
        padding: 12px;
        display: flex;
        justify-content: space-between;
        font-family: sans-serif;
        font-size: 12px;
        margin-bottom: 16px;
    }
    .info-left p, .info-right p {
        margin: 0;
    }
    .ukt-price {
        color: #f97316;
        font-weight: bold;
    }
    .card-desc {
        font-size: 12px;
        color: #6b7280;
        line-height: 1.5;
        margin-bottom: 2px;
    }
    .btn-detail {
        display: block;
        width: 100%;
        background-color: #f97316;
        color: #ffffff;
        text-align: center;
        padding: 10px 0;
        border-radius: 12px;
        text-decoration: none;
        font-family: sans-serif;
        font-weight: 600;
        box-sizing: border-box;
    }
    .btn-detail:hover {
        background-color: #ea580c;
    }
    .jelajahi-section {
        margin-top: 70px;
        margin-bottom: 40px;
        text-align: center;
    }

    .jelajahi-section h2 {
        font-size: 55px;
        font-family: Georgia, serif;
        font-weight: 700;
        margin: 0 0 20px;
        color: #000;
    }

    .jelajahi-section h2 span {
        color: #ff6b1a;
    }
    #map {
        width: 100%;
        height: 550px;
        border-radius: 20px;
        overflow: hidden;
    }
    </style>
    <div class="container">
        <div class="uni-grid">
            <!-- Card 1 -->
            <div class="uni-card">
            <div>
                <img 
                src="https://th.bing.com/th/id/OIP.NQv0n-_b6jXLsqnGPDtWOwHaE9?r=0&o=7rm=3&rs=1&pid=ImgDetMain" 
                alt="Universitas Padjadjaran" 
                class="card-img"
                />

                <div class="card-body">
                <div class="card-header">
                    <h3 class="uni-name">Universitas<br>Padjadjaran</h3>
                    <span class="badge">UNPAD</span>
                </div>

                <div class="info-box">
                    <div class="info-left">
                    <p style="font-weight: bold;">12 Fakultas</p>
                    <p style="color: #6b7280;">30 Prodi</p>
                    </div>

                    <div class="info-right" style="text-align: right;">
                    <p style="color: #6b7280;">Rentang UKT</p>
                    <p class="ukt-price">Rp. 500rb - 10Jt</p>
                    </div>
                </div>

                <p class="card-desc">
                    Universitas Padjadjaran adalah sebuah perguruan tinggi negeri di Kota Bandung dan Kabupaten ....
                </p>
                </div>
            </div>

            <div style="padding: 0 20px 20px;">
                <a href="/kampus" class="btn-detail">Lihat Detail</a>
            </div>
            </div>


            <!-- Card 2 -->
            <div class="uni-card">
            <div>
                <img 
                src="https://th.bing.com/th/id/OIP.NQv0n-_b6jXLsqnGPDtWOwHaE9?r=0&o=7rm=3&rs=1&pid=ImgDetMain" 
                alt="Universitas Padjadjaran" 
                class="card-img"
                />

                <div class="card-body">
                <div class="card-header">
                    <h3 class="uni-name">Universitas<br>Padjadjaran</h3>
                    <span class="badge">UNPAD</span>
                </div>

                <div class="info-box">
                    <div class="info-left">
                    <p style="font-weight: bold;">12 Fakultas</p>
                    <p style="color: #6b7280;">30 Prodi</p>
                    </div>

                    <div class="info-right" style="text-align: right;">
                    <p style="color: #6b7280;">Rentang UKT</p>
                    <p class="ukt-price">Rp. 500rb - 10Jt</p>
                    </div>
                </div>

                <p class="card-desc">
                    Universitas Padjadjaran adalah sebuah perguruan tinggi negeri di Kota Bandung dan Kabupaten ....
                </p>
                </div>
            </div>

            <div style="padding: 0 20px 20px;">
                <a href="/kampus" class="btn-detail">Lihat Detail</a>
            </div>
            </div>


            <!-- Card 3 -->
            <div class="uni-card">
            <div>
                <img 
                src="https://th.bing.com/th/id/OIP.NQv0n-_b6jXLsqnGPDtWOwHaE9?r=0&o=7rm=3&rs=1&pid=ImgDetMain" 
                alt="Universitas Padjadjaran" 
                class="card-img"
                />

                <div class="card-body">
                <div class="card-header">
                    <h3 class="uni-name">Universitas<br>Padjadjaran</h3>
                    <span class="badge">UNPAD</span>
                </div>

                <div class="info-box">
                    <div class="info-left">
                    <p style="font-weight: bold;">12 Fakultas</p>
                    <p style="color: #6b7280;">30 Prodi</p>
                    </div>

                    <div class="info-right" style="text-align: right;">
                    <p style="color: #6b7280;">Rentang UKT</p>
                    <p class="ukt-price">Rp. 500rb - 10Jt</p>
                    </div>
                </div>

                <p class="card-desc">
                    Universitas Padjadjaran adalah sebuah perguruan tinggi negeri di Kota Bandung dan Kabupaten ....
                </p>
                </div>
            </div>

            <div style="padding: 0 20px 20px;">
                <a href="/kampus" class="btn-detail">Lihat Detail</a>
            </div>
            </div>


            <!-- Card 4 -->
            <div class="uni-card">
            <div>
                <img 
                src="https://th.bing.com/th/id/OIP.NQv0n-_b6jXLsqnGPDtWOwHaE9?r=0&o=7rm=3&rs=1&pid=ImgDetMain" 
                alt="Universitas Padjadjaran" 
                class="card-img"
                />

                <div class="card-body">
                <div class="card-header">
                    <h3 class="uni-name">Universitas<br>Padjadjaran</h3>
                    <span class="badge">UNPAD</span>
                </div>

                <div class="info-box">
                    <div class="info-left">
                    <p style="font-weight: bold;">12 Fakultas</p>
                    <p style="color: #6b7280;">30 Prodi</p>
                    </div>

                    <div class="info-right" style="text-align: right;">
                    <p style="color: #6b7280;">Rentang UKT</p>
                    <p class="ukt-price">Rp. 500rb - 10Jt</p>
                    </div>
                </div>

                <p class="card-desc">
                    Universitas Padjadjaran adalah sebuah perguruan tinggi negeri di Kota Bandung dan Kabupaten ....
                </p>
                </div>
            </div>

            <div style="padding: 0 20px 20px;">
                <a href="/kampus" class="btn-detail">Lihat Detail</a>
            </div>
            </div>


            <!-- Card 5 -->
            <div class="uni-card">
            <div>
                <img 
                src="https://th.bing.com/th/id/OIP.NQv0n-_b6jXLsqnGPDtWOwHaE9?r=0&o=7rm=3&rs=1&pid=ImgDetMain" 
                alt="Universitas Padjadjaran" 
                class="card-img"
                />

                <div class="card-body">
                <div class="card-header">
                    <h3 class="uni-name">Universitas<br>Padjadjaran</h3>
                    <span class="badge">UNPAD</span>
                </div>

                <div class="info-box">
                    <div class="info-left">
                    <p style="font-weight: bold;">12 Fakultas</p>
                    <p style="color: #6b7280;">30 Prodi</p>
                    </div>

                    <div class="info-right" style="text-align: right;">
                    <p style="color: #6b7280;">Rentang UKT</p>
                    <p class="ukt-price">Rp. 500rb - 10Jt</p>
                    </div>
                </div>

                <p class="card-desc">
                    Universitas Padjadjaran adalah sebuah perguruan tinggi negeri di Kota Bandung dan Kabupaten ....
                </p>
                </div>
            </div>

            <div style="padding: 0 20px 20px;">
                <a href="/kampus" class="btn-detail">Lihat Detail</a>
            </div>
            </div>


            <!-- Card 6 -->
            <div class="uni-card">
            <div>
                <img 
                src="https://th.bing.com/th/id/OIP.NQv0n-_b6jXLsqnGPDtWOwHaE9?r=0&o=7rm=3&rs=1&pid=ImgDetMain" 
                alt="Universitas Padjadjaran" 
                class="card-img"
                />

                <div class="card-body">
                <div class="card-header">
                    <h3 class="uni-name">Universitas<br>Padjadjaran</h3>
                    <span class="badge">UNPAD</span>
                </div>

                <div class="info-box">
                    <div class="info-left">
                    <p style="font-weight: bold;">12 Fakultas</p>
                    <p style="color: #6b7280;">30 Prodi</p>
                    </div>

                    <div class="info-right" style="text-align: right;">
                    <p style="color: #6b7280;">Rentang UKT</p>
                    <p class="ukt-price">Rp. 500rb - 10Jt</p>
                    </div>
                </div>

                <p class="card-desc">
                    Universitas Padjadjaran adalah sebuah perguruan tinggi negeri di Kota Bandung dan Kabupaten ....
                </p>
                </div>
            </div>

            <div style="padding: 0 20px 20px;">
                <a href="/kampus" class="btn-detail">Lihat Detail</a>
            </div>
            </div>

        </div>

            <div class="jelajahi-section">
                <h2>Jelajahi <span>PTN</span> di Indonesia</h2>

                <div id="map"></div>
            </div>

    </div>
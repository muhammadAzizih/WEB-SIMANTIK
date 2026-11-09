<style>
    html {
        scroll-behavior: smooth;
    }

    body {
        background-color: #ffffff;
        color: #1a1a1a;
        font-family: 'Georgia', serif, sans-serif;
    }
    .container {
        width: 100%;
        max-width: 1200px;
        margin: 0 auto;
        padding: 44px 6vw;
    }
    .uni-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 15px;
    }
    .uni-card {
        background: #ffffff;
        border-radius: 16px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
        overflow: hidden;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        border: 1px solid #e3e3e3;
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
        color: #1a1a1a;
        margin: 0;
        line-height: 1.2;
    }
    .badge {
        background-color: #c9d0d9;
        color: #1a1a1a;
        font-family: sans-serif;
        font-size: 11px;
        font-weight: bold;
        padding: 4px 8px;
        border-radius: 4px;
    }
    .info-box {
        background-color: #fcebd8;
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
        color: #ee7f32;
        font-weight: bold;
    }
    .card-desc {
        font-size: 14px;
        color: #666;
        line-height: 1.5;
        margin-bottom: 2px;
    }
    .btn-detail {
        display: block;
        width: 100%;
        background-color: #ee7f3e;
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
        background-color: #d96b32;
    }
    .jelajahi-section {
        margin-top: 70px;
        margin-bottom: 40px;
        text-align: center;
    }

    .jelajahi-section h2 {
        font-size: 40px;
        font-family: Georgia, serif;
        font-weight: 700;
        margin: 0 0 20px;
        color: #000;
    }

    .jelajahi-section h2 span {
        color: #EE7F3E;
    }
    #map {
        width: 100%;
        height: 500px;
        border-radius: 20px;
        overflow: hidden;
    }

    #jelajahi-ptn {
        scroll-margin-top: 20px;
    }
    .dropdown-menu a.active { background:#fcebd8; font-weight:bold; }
    .dropdown-menu { max-height: 260px; overflow-y: auto; }
    .dropdown-menu input.dd-search { width:calc(100% - 16px); margin:8px; padding:6px 8px; border:1px solid #e3e3e3; border-radius:8px; box-sizing:border-box; }
    .dropdown-btn.has-value span:first-child { color:#ee7f32; font-weight:bold; }
    #uni-pagination { flex-wrap: wrap; align-items: center; }
    #uni-pagination .pg-btn {
        min-width: 44px; height: 44px; padding: 0 12px;
        display: inline-flex; align-items: center; justify-content: center;
        border: 1.5px solid #ffd9a8; background: #fffaf3; color: #1a1a1a;
        border-radius: 12px; font-size: 16px; font-weight: bold;
        cursor: pointer; transition: all .2s ease;
    }
    #uni-pagination .pg-btn:hover:not(:disabled):not(.active):not(.dots) {
        background: #fcebd8; border-color: #ffbf5f; transform: translateY(-2px);
    }
    #uni-pagination .pg-btn.active {
        background: #ee7f3e; border-color: #ee7f3e; color: #fff;
        box-shadow: 0 4px 10px rgba(238,127,62,.35); cursor: default;
    }
    #uni-pagination .pg-btn.dots { border-color: transparent; background: transparent; cursor: default; }
    #uni-pagination .pg-btn:disabled { opacity: .4; cursor: not-allowed; }
    @media (max-width: 480px) {
        #uni-pagination .pg-btn { min-width: 38px; height: 38px; padding: 0 8px; font-size: 14px; }
    }
    </style>
    <div class="container">
        <div class="uni-grid" id="uni-grid"></div>
        <p id="uni-empty" style="display:none;text-align:center;color:#666;font-family:sans-serif;margin-top:30px;">
            Tidak ada kampus yang cocok dengan filter kamu.
        </p>
        <div id="uni-pagination" style="display:flex;justify-content:center;gap:8px;margin:28px 0 8px;"></div>

        <div class="jelajahi-section">
                <h2 id="jelajahi-ptn">Jelajahi <span>PTN</span> di Indonesia</h2>

                <div id="map"></div>
            </div>

    </div>
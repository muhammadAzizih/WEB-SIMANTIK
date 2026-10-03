<style>
    .navbar {
        height: 70px;
        background: #FFBF5F;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 20px 100px;
        box-sizing: border-box;
        font-family: 'Libre Caslon Condensed', serif;
        color: #111;
    }

    .navbar-title {
        display: flex;
        align-items: center;
        text-decoration: none;
        color: inherit;
    }

    .navbar-title svg {
        width: 72px;
        height: 40px;
        display: block;
    }

    .navbar-title-text {
        margin-left: 12px;
        display: flex;
        flex-direction: column;
        line-height: 1;
    }

    .navbar-title-name {
        font-size: 21px;
        font-weight: 700;
        line-height: 22px;
        margin-top: -4px;
    }

    .navbar-title-sub {
        font-size: 18px;
        line-height: 22px;
        margin: 3px 0 0 3px;
    }

    .navbar-menu {
        list-style: none;
        margin: 0;
        padding: 0;
        display: flex;
        align-items: center;
    }

    .navbar-menu li a {
        text-decoration: none;
        color: #111;
        font-size: 16px;
        position: relative;
        top: 1px;
    }

    .navbar-menu li:nth-child(1) {
        margin-right: 27px;
    }

    .navbar-menu li:nth-child(2) {
        margin-right: 15px;
    }

    .navbar-menu li:nth-child(3) a {
        top: -2px;
        font-size: 16.5px;
    }
</style>

<header class="navbar">
    <a href="{{ url('/') }}" class="navbar-title">

        <svg viewBox="0 0 72 40" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <polygon points="36,3 71,17 36,31 1,17" fill="#1b1b1b"/>
            <path d="M15 24 V33 C15 40 57 40 57 33 V24 L36 32 Z" fill="#1b1b1b"/>
            <path d="M66 19 V34" stroke="#1b1b1b" stroke-width="2.5" stroke-linecap="round"/>
            <circle cx="66" cy="35" r="2.3" fill="#1b1b1b"/>
        </svg>

        <span class="navbar-title-text">
            <span class="navbar-title-name">PTN impian</span>
            <span class="navbar-title-sub">Gerbang Menuju Mimpi</span>
        </span>
    </a>

    <ul class="navbar-menu">
        <li><a href="{{ url('/') }}">Beranda</a></li>
        <li><a href="{{ url('/ptn') }}">Jelajahi PTN</a></li>
        <li><a href="{{ url('/tentang-kami') }}">Tentang Kami</a></li>
    </ul>
</header>

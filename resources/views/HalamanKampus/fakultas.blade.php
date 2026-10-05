<style>
    body {
        margin: 0;
        background: #fff;
        color: #111827;
    }
    .fk-page {
        min-height: 575px;
        padding: 34px 100px 40px;
        background: #fff;
        color: #111827;
        font-family: Georgia, serif;
    }
    .fk-title {
        margin: 0 0 20px 0;
        color: #111827;
        font-family: Georgia, serif;
        font-size: 22px;
        font-weight: 700;
        line-height: 1.3;
    }
    .fk-tabs {
        display: flex;
        gap: 27px;
        align-items: center;
        margin-bottom: 29px;
    }
    .fk-tab {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        height: 42px;
        padding: 0 20px;
        border-radius: 12px;
        border: 1px solid transparent;
        font-family: Georgia, serif;
        font-size: 15px;
        font-weight: 700;
        color: #111827;
        text-decoration: none;
        transition: transform 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
    }
    .fk-tab--active {
        background: #FFBD59;
        min-width: 120px;
    }
    .fk-tab--inactive {
        background: #C9D1DB;
        border-color: #BFC8D3;
        min-width: 190px;
    }
    .fk-tab:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 14px rgba(0, 0, 0, 0.12);
    }
    .fk-list {
        display: flex;
        flex-direction: column;
        gap: 16px;
        margin-right: 100px;
    }
    .fk-row {
        position: relative;
        display: flex;
        align-items: center;
        height: 58px;
        padding: 0 110px 0 28px;
        border-radius: 10px;
        font-size: 15px;
        font-weight: 700;
        font-family: Georgia, serif;
        color: var(--fk-ink);
        text-decoration: none;
        overflow: visible;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .fk-row:nth-child(odd) {
        background: #C9D1DB;
    }
    .fk-row:nth-child(even) {
        background: #FFBD59;
    }
    .fk-row__name {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .fk-row__go {
        position: absolute;
        top: 0;
        right: 0;
        width: 75px;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #FF7620;
        border-radius: 10px;
        transition: background-color 0.2s ease;
    }
    .fk-row__go svg {
        width: 24px;
        height: 24px;
        transition: transform 0.2s ease;
    }
    .fk-row:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 18px rgba(0, 0, 0, 0.14);
    }
    .fk-row:hover .fk-row__go {
        filter: brightness(1.06);
        background: #f56610;
    }
    .fk-row:hover .fk-row__go svg {
        transform: translateX(4px);
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
        transition: transform 0.2s ease,box-shadow 0.2s ease;
    }

    .chat-fab:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.12);
    }

    .chat-fab svg {
        width: 48px;
        height: 36px;
    }

    @media (max-width: 768px) {

        .fk-page {
            padding: 24px 16px 32px;
        }

        .fk-list {
            margin-right: 0;
        }

        .fk-tabs {
            gap: 12px;
        }

        .fk-tab {
            font-size: 15px;
            padding: 0 20px;
            border-radius: 12px;
            height: 42px;
        }

        .fk-title {
            font-size: 19px;
        }

        .fk-row {
            font-size: 16px;
            height: auto;
            min-height: 64px;
            padding: 10px 84px 10px 16px;
        }

        .fk-row__name {
            white-space: normal;
        }

        .fk-row__go {
            width: 70px;
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
    }
</style>

@include('navbar')

<main class="fk-page">
    <h1 class="fk-title">Fakultas &amp; Program Studi</h1>
    <nav class="fk-tabs" aria-label="Pilih tampilan">
        <a href="#" class="fk-tab fk-tab--active" aria-current="page">
            Fakultas
        </a>
        <a href="#" class="fk-tab fk-tab--inactive">
            Program Studi
        </a>
    </nav>
    <section class="fk-list" aria-label="Daftar fakultas">
        <a href="#" class="fk-row"
           title="Fakultas Ilmu Komputer dan Teknologi Informasi">

            <span class="fk-row__name">
                Fakultas Ilmu Komputer dan Teknologi Informasi
            </span>

            <span class="fk-row__go" aria-hidden="true">
                <svg viewBox="0 0 34 34" fill="none">
                    <polyline
                        points="9,4 26,17 9,30"
                        stroke="#FFD0AD"
                        stroke-width="3.2"
                        stroke-linecap="round"
                        stroke-linejoin="miter"
                    />
                </svg>
            </span>

        </a>

        <a href="#" class="fk-row" title="Fakultas Ilmu Budaya">

            <span class="fk-row__name">
                Fakultas Ilmu Budaya
            </span>

            <span class="fk-row__go" aria-hidden="true">
                <svg viewBox="0 0 34 34" fill="none">
                    <polyline
                        points="9,4 26,17 9,30"
                        stroke="#FFD0AD"
                        stroke-width="3.2"
                        stroke-linecap="round"
                        stroke-linejoin="miter"
                    />
                </svg>
            </span>

        </a>

        <a href="#" class="fk-row" title="Fakultas Teknik">

            <span class="fk-row__name">
                Fakultas Teknik
            </span>

            <span class="fk-row__go" aria-hidden="true">
                <svg viewBox="0 0 34 34" fill="none">
                    <polyline
                        points="9,4 26,17 9,30"
                        stroke="#FFD0AD"
                        stroke-width="3.2"
                        stroke-linecap="round"
                        stroke-linejoin="miter"
                    />
                </svg>
            </span>

        </a>

        <a href="#" class="fk-row" title="Fakultas Kedokteran">

            <span class="fk-row__name">
                Fakultas Kedokteran
            </span>

            <span class="fk-row__go" aria-hidden="true">
                <svg viewBox="0 0 34 34" fill="none">
                    <polyline
                        points="9,4 26,17 9,30"
                        stroke="#FFD0AD"
                        stroke-width="3.2"
                        stroke-linecap="round"
                        stroke-linejoin="miter"
                    />
                </svg>
            </span>

        </a>

        <a href="#" class="fk-row" title="Fakultas Psikologi">

            <span class="fk-row__name">
                Fakultas Psikologi
            </span>

            <span class="fk-row__go" aria-hidden="true">
                <svg viewBox="0 0 34 34" fill="none">
                    <polyline
                        points="9,4 26,17 9,30"
                        stroke="#FFD0AD"
                        stroke-width="3.2"
                        stroke-linecap="round"
                        stroke-linejoin="miter"
                    />
                </svg>
            </span>

        </a>

    </section>

    <button type="button" class="chat-fab" aria-label="Buka chat">
        <svg viewBox="0 0 48 36" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path d="M3 17C3 9 10 3 20 3s17 6 17 14s-7 14-17 14c-2 0-4-.3-5.800-.8L7 33l1.600-6C5 24.500 3 21 3 17z" fill="#7C7C7C"/>
            <path d="M33 11c8 .6 13 5 13 11c0 3.500-1.800 6.500-4.800 8.500L42.500 35l-5.500-2.300c-1.200.3-2.500.5-3.800.5c-2.700 0-5.200-.7-7.200-1.900C31 29.500 36 26 36 21c0-3.800-1.200-7.800-3-10z" fill="#7C7C7C" opacity=".75"/>
            <circle cx="13" cy="17" r="2.300" fill="#EEE"/>
            <circle cx="20.500" cy="17" r="2.300" fill="#EEE"/>
            <circle cx="28" cy="17" r="2.300" fill="#EEE"/>
        </svg>
    </button>
</main>


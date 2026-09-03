<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SYNCRO LAB</title>
    <!-- Core CSS at root level -->
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <!-- SECTION 1: HEADER NAVIGATION -->
    <header class="site-header">
        <!-- Brand Logo -->
        <a href="#home" class="brand-logo">
            <img src="assets/syncro-lab-light.svg" alt="SYNCRO LAB Logo" class="logo-img">
        </a>

        <!-- Navigation Links -->
        <nav class="main-nav">
            <ul>
                <li><a href="#home" class="nav-link">HOME</a></li>
                <li><a href="#services" class="nav-link">SERVICES</a></li>
                <li><a href="#catalog" class="nav-link active">CATALOG</a></li>
                <li><a href="#about" class="nav-link">ABOUT</a></li>
                <li><a href="#locations" class="nav-link">LOCATIONS</a></li>
                <li class="nav-search-item">
                    <button id="search-btn" class="icon-btn nav-search-btn" aria-label="Search">
                        <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M35 35L27.75 27.75M31.6667 18.3333C31.6667 25.6971 25.6971 31.6667 18.3333 31.6667C10.9695 31.6667 5 25.6971 5 18.3333C5 10.9695 10.9695 5 18.3333 5C25.6971 5 31.6667 10.9695 31.6667 18.3333Z"
                                  stroke="currentColor"
                                  stroke-width="3.5"
                                  stroke-linecap="round"
                                  stroke-linejoin="round"/>
                        </svg>
                    </button>
                </li>
            </ul>
        </nav>

        <!-- Header Right Actions -->
        <div class="header-actions">
            <!-- Book Service CTA -->
            <a href="#book" class="btn-book">BOOK SERVICE</a>

            <!-- Cart Drawer Trigger -->
            <button type="button" class="icon-btn cart-btn" aria-label="Open cart" aria-controls="cart-drawer" aria-expanded="false">
                <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <g clip-path="url(#clip0_172_18)">
                        <path d="M1.66663 1.66667H8.33329L12.8 23.9833C12.9524 24.7507 13.3698 25.4399 13.9792 25.9305C14.5886 26.4211 15.3511 26.6817 16.1333 26.6667H32.3333C33.1155 26.6817 33.878 26.4211 34.4874 25.9305C35.0968 25.4399 35.5142 24.7507 35.6666 23.9833L38.3333 10H9.99996M16.6666 35C16.6666 35.9205 15.9204 36.6667 15 36.6667C14.0795 36.6667 13.3333 35.9205 13.3333 35C13.3333 34.0795 14.0795 33.3333 15 33.3333C15.9204 33.3333 16.6666 34.0795 16.6666 35ZM35 35C35 35.9205 34.2538 36.6667 33.3333 36.6667C32.4128 36.6667 31.6666 35.9205 31.6666 35C31.6666 34.0795 32.4128 33.3333 33.3333 33.3333C34.2538 33.3333 35 34.0795 35 35Z"
                              stroke="currentColor"
                              stroke-width="3.5"
                              stroke-linecap="round"
                              stroke-linejoin="round"/>
                    </g>
                    <defs>
                        <clipPath id="clip0_172_18">
                            <rect width="40" height="40" fill="white"/>
                        </clipPath>
                    </defs>
                </svg>
                <span class="cart-count">[0]</span>
            </button>

            <!-- user icon -->
            <a href="#account" class="icon-btn account-btn" aria-label="Account">
                <svg width="45" height="45" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M40 42V38C40 35.8783 39.1571 33.8434 37.6569 32.3431C36.1566 30.8429 34.1217 30 32 30H16C13.8783 30 11.8434 30.8429 10.3431 32.3431C8.84285 33.8434 8 35.8783 8 38V42M32 14C32 18.4183 28.4183 22 24 22C19.5817 22 16 18.4183 16 14C16 9.58172 19.5817 6 24 6C28.4183 6 32 9.58172 32 14Z"
                          stroke="currentColor"
                          stroke-width="4"
                          stroke-linecap="round"
                          stroke-linejoin="round"/>
                </svg>
            </a>
        </div>
    </header>

    <!-- Cart Drawer -->
    <div class="cart-drawer-backdrop" data-cart-close></div>
    <aside id="cart-drawer" class="cart-drawer" aria-label="Shopping cart" aria-hidden="true">
        <div class="cart-drawer-header">
            <div>
                <p class="cart-drawer-eyebrow">YOUR SELECTION</p>
                <h2>SHOPPING CART</h2>
            </div>
            <button type="button" class="cart-drawer-close" data-cart-close aria-label="Close cart">&times;</button>
        </div>
        <div class="cart-drawer-content">
            <div class="cart-empty-state">
                <span class="cart-empty-icon" aria-hidden="true">+</span>
                <h3>Your cart is empty</h3>
                <p>Add precision parts, rider gear, or a service to get started.</p>
            </div>
        </div>
        <div class="cart-drawer-footer">
            <div class="cart-drawer-total"><span>SUBTOTAL</span><strong>PHP 0.00</strong></div>
            <a href="cart.php" class="cart-full-link">EXPLORE CART <span aria-hidden="true">&#8594;</span></a>
        </div>
    </aside>

    <!-- SECTION 2: HERO SECTION -->
    <section id="home" class="hero-section">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <h1 class="hero-title">PRECISION IS NOT<br>AN OPTION</h1>
            <p class="hero-subtext">High-end parts, custom bike builds, and<br>master-certified mechanics for road and trail.</p>
            <div class="hero-buttons">
                <a href="#catalog" class="btn btn-lime">SHOP NOW</a>
                <a href="#book" class="btn btn-outline">BOOK NOW</a>
            </div>
        </div>
    </section>

    <!-- SECTION 3: BRANDS & OFFERED SERVICES -->
    <!-- Brand Marquee Banner -->
    <section class="brand-banner">
        <span class="brand-item shimano">
            <svg width="164" height="22" viewBox="0 0 433 58" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path fill-rule="evenodd" clip-rule="evenodd" d="M81.1875 0H65.4912V58H81.1875V44.9907H105.002V58H121.24V0H105.002V34.1495H81.1875V0Z" fill="#12171B"/>
            <path fill-rule="evenodd" clip-rule="evenodd" d="M134.23 0V58H151.009V0H134.23Z" fill="#12171B"/>
            <path fill-rule="evenodd" clip-rule="evenodd" d="M215.418 9.21495C218.665 9.75701 222.995 10.2991 222.995 17.3458V58H238.691V18.4299C238.691 9.75701 239.233 0.542056 225.701 0H163.999V58H179.695V9.75701H193.768V58H208.923V9.21495H215.418Z" fill="#12171B"/>
            <path fill-rule="evenodd" clip-rule="evenodd" d="M353.436 19.514V58H369.674V15.7196C369.674 5.96262 363.72 0 353.978 0H315.008V58H331.245V9.75701H346.4C351.813 9.75701 353.436 15.1776 353.436 19.514Z" fill="#12171B"/>
            <path fill-rule="evenodd" clip-rule="evenodd" d="M12.4487 34.6916C12.4487 34.6916 12.99 34.6916 13.5312 34.6916H34.64C38.4287 34.6916 40.5938 37.9439 41.135 40.6542C41.6763 44.4486 39.5112 48.243 34.64 48.243H1.62375V58H35.7225C38.97 58 41.6763 58 43.3 58C44.3825 58 46.5475 57.4579 47.63 56.9159C53.0425 54.2056 55.2075 48.785 55.7487 43.3645C55.7487 41.7383 55.7487 40.1122 55.7487 38.486C55.2075 27.1028 51.96 22.2243 39.5112 22.2243H20.5675C16.2375 22.2243 15.155 18.4299 15.155 15.1776C15.155 9.21495 20.0262 9.75701 25.4387 9.75701H54.125V0H12.4487C11.3663 0 9.20125 0.542056 8.11875 0.542056C2.70625 2.71028 0 9.21495 0 16.2617C0 24.9346 2.70625 34.6916 12.4487 34.6916Z" fill="#12171B"/>
            <path fill-rule="evenodd" clip-rule="evenodd" d="M433 44.4486V13.5514C433 5.96262 427.046 0 419.469 0H392.406C384.829 0 378.875 5.96262 378.875 13.5514V44.4486C378.875 52.0374 384.829 58 392.406 58H419.469C427.046 58 433 52.0374 433 44.4486ZM401.066 9.75701H410.267C414.056 9.75701 416.763 12.4673 416.763 16.2617V42.2804C416.763 45.5327 414.056 48.785 410.267 48.785H401.066C397.819 48.785 394.571 45.5327 394.571 42.2804V16.2617C394.571 12.4673 397.819 9.75701 401.066 9.75701Z" fill="#12171B"/>
            <path fill-rule="evenodd" clip-rule="evenodd" d="M248.434 15.7196V58H264.13V45.5327H289.569V58H305.806V16.2617C305.806 5.42056 299.853 0 291.193 0H261.424C252.764 0 248.434 7.58879 248.434 15.7196ZM289.569 16.8037V34.6916H264.671V16.8037C264.671 11.9252 268.46 9.75701 270.084 9.75701H283.074C286.321 9.75701 289.569 12.4673 289.569 16.8037Z" fill="#12171B"/>
            </svg>
        </span>

        <span class="brand-item trek">
            <svg width="164" height="22" viewBox="0.5 73.9 600 71.5" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M145.5 74L5.4 73.9L0.5 87.6H48.8L27.8 145.3H75.6L96.4 87.6H140.6L145.5 74Z" fill="#12171B"/>
            <path d="M497.1 73.9H446.8L420.9 145.4H471.3L497.1 73.9Z" fill="#12171B"/>
            <path d="M488 107.2L517.9 145.4H576L542.6 107.6L600.5 73.9H541.4L488 107.2Z" fill="#12171B"/>
            <path d="M430.8 87.6L435.8 73.9H306.8L289 123C287.6 126.1 288 129.7 290 132.4L297.7 141.2C299.8 143.9 303 145.4 306.4 145.4H409.7L414.6 131.9H342.4C339.7 132.3 337.2 130.4 336.8 127.8C336.7 127 336.8 126.1 337.1 125.3L340.3 116.2H420.2L425.2 102.5H345.3L350.7 87.6H430.8Z" fill="#12171B"/>
            <path d="M282.7 73.9H154.5L128.6 145.4H177.7L198.5 87.6H235C237.3 87.3 239.5 88.9 239.8 91.2C239.9 91.9 239.8 92.6 239.6 93.3C238.7 96.3 237.4 99.7 236.6 101.9C235.1 105.3 231.7 107.4 228.1 107.2H193.8L223.6 145.3H280.1L254.7 116.7H269.9C275.7 117.1 281 113.6 283 108.2C285.2 102.8 289.8 89.6 291.4 85.3C293.9 78.2 290 73.9 282.7 73.9Z" fill="#12171B"/>
            </svg>
        </span>

        <span class="brand-item scott">
            <svg width="164" height="22" viewBox="43 14.2 196.8 21.2" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M165.8 20C167.8 16.6 165 14.3 162.3 14.3H135.3C130.4 14.3 128.6 17 127 19.8L121.3 29.7C119.3 33.1 122.1 35.4 124.8 35.4H151.8C156.7 35.4 158.5 32.7 160.1 29.9L165.8 20ZM146.6 30.2C146.3 30.7 145.7 31.5 144.3 31.5H136.7C133.6 31.5 134.7 29.4 135.2 28.7L140.4 19.7C140.7 19.2 141.3 18.4 142.7 18.4H150.3C153.4 18.4 152.3 20.5 151.8 21.2L146.6 30.2Z" fill="#12171B"/>
            <path d="M51.1 21.4C49.1 24.8 51.9 27.1 54.6 27.1H69.5C72.6 27.1 71.5 29.2 71 29.9C70.6 30.7 70.2 31.4 70.2 31.4H45.3L43 35.4H81.1L85.2 28.4C87.2 25 84.4 22.7 81.7 22.7H66.8C63.7 22.7 64.8 20.6 65.3 19.9L66.2 18.4H90L92.3 14.4H60.2C55.3 14.4 53.5 17.1 51.9 19.9L51.1 21.4Z" fill="#12171B"/>
            <path d="M106.7 19.6C107 19.1 107.6 18.3 109 18.3H124.1L126.4 14.2H101.6C96.7 14.2 94.9 16.9 93.3 19.7L87.6 29.6C85.6 33 88.4 35.3 91.1 35.3H114.3L116.6 31.3C112.2 31.3 105.3 31.3 103.1 31.3C100 31.3 101.1 29.2 101.6 28.5L106.7 19.6Z" fill="#12171B"/>
            <path d="M170.7 14.3L168.4 18.3H180.1L170.3 35.4H183.6L193.5 18.3H202.8L205.1 14.3H170.7Z" fill="#12171B"/>
            <path d="M208.1 14.3L205.8 18.3H214.8L205 35.4H218.3L228.2 18.3H237.5L239.8 14.3H208.1Z" fill="#12171B"/>
            </svg>
        </span>

        <span class="brand-item sram">
            <svg width="164" height="22" viewBox="0 0 189 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M75.5632 0.1474C75.5632 0.1474 53.9363 0.1474 53.8687 0.1474C46.2464 0.1474 45.1385 6.29321 44.9396 6.93612C44.6852 7.74856 41.4339 20.0269 40.4069 23.9159C44.8021 23.9159 53.5842 23.9327 53.5842 23.9327C53.5842 23.9327 56.1231 14.3173 56.1472 14.351C56.4305 14.6346 61.6142 20.95 63.2405 22.7285C64.5545 24.1467 65.4513 23.9255 70.5024 23.9327C73.738 23.9327 78.3335 23.9327 78.4878 23.9327L71.1258 15.2679H79.2725C79.8176 12.9473 80.5793 10.1943 81.2508 7.52391C82.2598 3.44618 78.9266 0.253098 75.5632 0.1474ZM67.1427 11.0343H57.0357L58.639 5.3149H68.6701L67.1427 11.0343Z" fill="#12171B"/>
            <path d="M40.0162 5.28858L41.4098 0.260264L12.9585 0.253113C6.97064 0.552415 5.24797 4.17334 4.34623 7.73776C4.02202 9.02375 3.69659 10.2363 3.42296 11.2806C2.92974 13.1528 2.70789 13.8691 2.70789 13.8691L24.658 13.8511L23.3162 18.9478H1.36629L0.000366211 23.9543C0.000366211 23.9543 25.878 23.9543 27.3596 23.9543C32.8603 23.9543 35.6077 21.6614 36.9471 16.6908C37.8656 13.1781 38.8529 9.71358 38.8529 9.71358H17.5082L18.6836 5.28858H40.0162Z" fill="#12171B"/>
            <path d="M95.7942 0.196579C93.699 0.196579 89.2072 -0.174758 86.906 6.84123C85.8656 10.6148 82.3407 23.9447 82.3407 23.9447C82.3407 23.9447 85.8789 23.9543 88.7625 23.9543C91.6785 23.9543 94.7524 24.0504 95.7266 22.1421C96.5993 20.4597 97.6939 15.9192 97.6939 15.9192H108.216L106.026 24H119.246C125.329 1.27815 125.165 1.83943 125.619 0.196579C118.236 0.196579 103.562 0.196579 95.7942 0.196579ZM109.132 11.1087L99.0731 11.1267L100.621 5.31385H110.648L109.132 11.1087Z" fill="#12171B"/>
            <path d="M167.238 23.9447L171.995 5.77168H164.074L159.277 23.9543H146.493L151.274 5.77168H143.296L138.479 23.9688H125.286L131.629 0.0415058C131.629 0.0415058 174.309 -0.052157 178.787 0.0415058C181.557 0.10168 185.547 2.85017 184.158 8.26779C182.514 14.6695 181.41 20.051 180.387 23.9447C175.898 23.9447 174.384 23.9447 167.238 23.9447Z" fill="#12171B"/>
            <path d="M184.231 21.1542C184.231 19.9308 185.164 18.9935 186.357 18.9935C187.543 18.9935 188.474 19.9308 188.474 21.1542C188.474 22.4161 187.543 23.3342 186.357 23.3342C185.164 23.3342 184.231 22.4161 184.231 21.1542ZM186.357 23.7764C187.787 23.7764 188.999 22.666 188.999 21.1542C188.999 19.6713 187.787 18.556 186.357 18.556C184.92 18.556 183.705 19.6713 183.705 21.1542C183.705 22.666 184.92 23.7764 186.357 23.7764ZM185.807 21.3633H186.333L187.129 22.666H187.64L186.786 21.3441C187.225 21.2937 187.563 21.0534 187.563 20.5149C187.563 19.9308 187.213 19.6713 186.499 19.6713H185.354V22.666H185.807V21.3633ZM185.807 20.9811V20.051H186.434C186.743 20.051 187.088 20.1134 187.088 20.4956C187.088 20.962 186.733 20.9811 186.357 20.9811H185.807Z" fill="#12171B"/>
            </svg>
        </span>

        <span class="brand-item mavic">
            <svg width="164" height="22" viewBox="12.8 66.7 131.8 23.9" fill="none" xmlns="http://www.w3.org/2000/svg" class="no-space-svg">
            <path d="M115.706 66.9448C113.546 66.9448 112.589 67.5337 111.46 69.6196L100.025 90.5276H109.104L121.988 66.9448" fill="#12171B"/>
            <path d="M101.939 66.9448C99.9263 66.9448 98.9202 67.2392 97.9386 69.2515L90.5521 84.1472L90.7975 66.9938H82.6993L82.9447 90.5767H95.0674L107.951 66.9938" fill="#12171B"/>
            <path d="M137.202 72.8098C138.086 72.8098 139.313 72.908 141.104 72.908L144.613 66.8957C142.748 66.7976 139.632 66.6994 138.159 66.6994C131.804 66.6994 128.245 67.2393 124.834 70.6503C122.159 73.3252 117.742 81.1534 117.742 84.9571C117.742 88.7117 120.098 90.6258 126.184 90.6258C128.442 90.6258 131.853 90.6258 134.331 90.4785L137.84 84.4663C135.264 84.5154 132.515 84.6135 130.503 84.6135C128.344 84.6135 126.798 84.1718 126.798 82.6994C126.798 80.6871 129.374 76.3436 131.092 74.6012C132.712 73.0552 134.503 72.8098 137.202 72.8098Z" fill="#12171B"/>
            <path d="M69.6933 66.9448C66.8712 66.9448 66.1841 67.2392 64.2699 69.9632L52.7362 86.0859L55.1411 66.9448H48.1227C46.1595 66.9448 44.9571 67.5337 43.9264 69.0552L36.589 79.7546L37.865 66.9202H28.6871L12.8098 90.503H21.5951L30.0859 76.3926L29.0061 90.503H34.2331C35.7546 90.503 37.0552 89.9141 37.8896 88.7362L46.2822 76.5153L44.4172 90.503H58.4294L61.1043 86.4785H70.5276L70.4785 90.5276H78.6258L78.3804 66.9448M70.6258 80.9325H64.8098L70.773 71.7055L70.6258 80.9325Z" fill="#12171B"/>
            </svg>
        </span>
        <span class="brand-item pinarello">
            <svg width="164" height="22" viewBox="0 0 205 17" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path fill-rule="evenodd" clip-rule="evenodd" d="M203.79 1.1403H203.964C203.996 1.1403 204.022 1.13111 204.042 1.11327C204.063 1.09598 204.073 1.07382 204.073 1.04733C204.073 1.02139 204.063 0.999226 204.042 0.98139C204.022 0.964093 203.996 0.954905 203.964 0.954905H203.79V1.1403ZM204.269 1.57324H204.095L203.931 1.28461H203.79V1.57324H203.627V0.810049H203.964C204.039 0.810049 204.104 0.834914 204.157 0.883559C204.21 0.932205 204.236 0.99058 204.236 1.0576C204.236 1.1003 204.223 1.1376 204.198 1.17111C204.173 1.20408 204.147 1.22624 204.122 1.23705L204.084 1.25381L204.269 1.57324ZM204.356 0.768972C204.232 0.652223 204.084 0.593307 203.91 0.593307C203.736 0.593307 203.588 0.652223 203.464 0.768972C203.341 0.885181 203.279 1.02625 203.279 1.19165C203.279 1.3565 203.341 1.49757 203.464 1.61432C203.588 1.73107 203.736 1.78945 203.91 1.78945C204.084 1.78945 204.232 1.73107 204.356 1.61432C204.479 1.49757 204.541 1.3565 204.541 1.19165C204.541 1.02625 204.479 0.885181 204.356 0.768972ZM204.483 1.7181C204.328 1.86188 204.137 1.93376 203.91 1.93376C203.682 1.93376 203.491 1.86188 203.337 1.7181C203.183 1.57487 203.105 1.3992 203.105 1.19165C203.105 0.984092 203.183 0.808969 203.337 0.665194C203.491 0.521419 203.682 0.450073 203.91 0.450073C204.137 0.450073 204.328 0.521419 204.483 0.665194C204.637 0.808969 204.714 0.984092 204.714 1.19165C204.714 1.3992 204.637 1.57487 204.483 1.7181Z" fill="#12171B"/>
            <path fill-rule="evenodd" clip-rule="evenodd" d="M192.082 2.79982C197.245 2.79982 199.223 4.64241 198.416 8.49027C197.606 12.3343 194.85 14.1807 189.687 14.1807C184.446 14.1807 182.583 12.3365 183.422 8.35406C183.818 6.47688 184.739 5.07751 186.206 4.15379L180.977 3.06791L189.033 3.07548V3.07656C189.94 2.89387 190.954 2.79982 192.082 2.79982ZM177.022 8.33082C175.77 14.2704 179.503 17.0016 189.094 17C198.562 17.0016 203.565 14.2704 204.782 8.48919C205.999 2.70794 202.143 -0.000540425 192.671 8.08815e-08C183.316 8.08815e-08 178.206 2.70794 177.022 8.33082Z" fill="#12171B"/>
            <path fill-rule="evenodd" clip-rule="evenodd" d="M175.909 13.961L168.998 13.9627C166.513 13.961 164.886 13.7589 163.827 12.893C163.03 12.2552 163.198 11.2763 163.479 9.93426C163.522 9.72887 163.565 9.52456 163.615 9.29863L165.5 0.329468H159.641L157.594 10.0499C157.139 12.2114 157.224 13.6448 159.081 14.9631C161.242 16.5111 163.885 16.6717 167.924 16.6717H175.34L175.909 13.961Z" fill="#12171B"/>
            <path fill-rule="evenodd" clip-rule="evenodd" d="M144.06 9.89979C144.103 9.69602 144.147 9.49224 144.196 9.26415L146.083 0.295532H140.221L138.175 10.016C137.718 12.1775 137.807 13.6114 139.665 14.9297C141.822 16.4772 144.466 16.6377 148.503 16.6377H155.918L156.489 13.9276H149.58C147.094 13.9276 145.467 13.7249 144.408 12.8591C143.612 12.2202 143.778 11.2419 144.06 9.89979Z" fill="#12171B"/>
            <path fill-rule="evenodd" clip-rule="evenodd" d="M139.067 0.319519H129.321C120.355 0.319519 115.638 3.00692 114.421 8.78438C113.795 11.7485 114.743 13.8851 117.335 15.2272C119.784 16.5017 122.349 16.6596 125.726 16.6596H135.627L136.225 13.8154H126.596C121.977 13.817 120.223 12.7468 120.804 9.80972H137.069L137.668 6.96504H121.439C122.215 4.39169 124.608 3.16258 128.84 3.16258H138.468L139.067 0.319519Z" fill="#12171B"/>
            <path fill-rule="evenodd" clip-rule="evenodd" d="M34.8625 3.06181L35.4396 0.319824H29.3062L25.8643 16.662H31.9999L34.5654 4.47523L29.1123 3.05533L34.8625 3.06181Z" fill="#12171B"/>
            <path fill-rule="evenodd" clip-rule="evenodd" d="M45.3406 0.320251L54.279 12.0638L56.159 2.3158L62.4623 0.320251L59.0215 16.6635L52.2244 16.6619L42.8333 4.30432L40.9898 14.135L34.4857 16.6619L37.9259 0.320251H45.3406Z" fill="#12171B"/>
            <path fill-rule="evenodd" clip-rule="evenodd" d="M70.8668 9.85651H79.175L76.2629 3.57421L70.8668 9.85651ZM79.7789 0.319824L88.2195 16.662H81.6949L79.9545 12.7704H67.8417L64.5378 16.662H58.3457L73.5697 0.319824H79.7789Z" fill="#12171B"/>
            <path fill-rule="evenodd" clip-rule="evenodd" d="M95.5747 7.35091L96.1922 4.43704L91.5295 3.22253L98.005 3.23063V3.23334H104.114C107.61 3.23334 109.24 3.59764 108.885 5.28185C108.537 6.94283 106.742 7.35091 103.249 7.35091H95.5747ZM93.5994 16.6622L94.9503 10.2426L101.863 10.2432L106.641 16.6622H113.242L107.727 9.83346C111.435 9.37889 113.793 7.7617 114.326 5.23483C115.05 1.80045 112.219 0.320007 105.619 0.320007H91.1782L87.734 16.6633L93.5994 16.6622Z" fill="#12171B"/>
            <path fill-rule="evenodd" clip-rule="evenodd" d="M7.67415 8.0582L8.47703 4.25142L4.54017 3.22608L11.0146 3.2331H16.7374C20.6212 3.23256 22.2047 3.82496 21.8215 5.64484C21.4737 7.28366 19.6045 8.0582 16.0725 8.0582H7.67415ZM7.06514 10.9477H14.1668C17.6629 10.9472 20.3948 10.8802 22.9683 9.90241C25.3342 8.99057 26.6742 7.60039 27.0842 5.64592C27.5113 3.62065 26.4797 2.07318 24.1931 1.1381C22.6176 0.501385 19.9786 0.319774 16.174 0.320314H3.43962L0 16.662H5.86025L7.06514 10.9477Z" fill="#12171B"/>
            </svg>
        </span>
    </section>

    <!-- Offered Services Section -->
    <section id="services" class="services-section">
        <div class="services-container">
            <h2 class="services-title">OFFERED SERVICES</h2>

            <div class="services-grid">
                <!-- Custom Build Card -->
                <article class="service-card">
                    <div class="card-image">
                        <img src="assets/custom-build.png" alt="Custom Bike Build Workshop">
                    </div>
                    <div class="card-content">
                        <h3>CUSTOM BUILD</h3>
                        <p>Frame-up bespoke assembly, electronic groupsets, and custom telemetry tunning.</p>
                        <a href="#custom-build" class="btn-explore">
                            EXPLORE SERVICE
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" 
                                    stroke="currentColor" 
                                    stroke-width="2.5"
                                    stroke-linecap="round" 
                                    stroke-linejoin="round">
                                <line x1="7" y1="17" x2="17" y2="7"></line>
                                <polyline points="7 7 17 7 17 17"></polyline>
                            </svg>
                        </a>
                    </div>
                </article>

                <!-- Repair & Maintenance Card -->
                <article class="service-card">
                    <div class="card-image">
                        <img src="assets/repair-maintenance.png" alt="Bike Repair and Maintenance">
                    </div>
                    <div class="card-content">
                        <h3>REPAIR & MAINTENANCE</h3>
                        <p>Complete ultrasonic cleaning, break bleeds, bearing check and torque calibration.</p>
                        <a href="#repair" class="btn-explore">
                            EXPLORE SERVICE
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" 
                                    stroke="currentColor" 
                                    stroke-width="2.5" stroke-linecap="round" 
                                    stroke-linejoin="round">
                                <line x1="7" y1="17" x2="17" y2="7"></line>
                                <polyline points="7 7 17 7 17 17"></polyline>
                            </svg>
                        </a>
                    </div>
                </article>

                <!-- Bike Fit Card -->
                <article class="service-card">
                    <div class="card-image">
                        <img src="assets/bike-fit.png" alt="Bike Fitting Analysis">
                    </div>
                    <div class="card-content">
                        <h3>BIKE FIT</h3>
                        <p>Motion analysis, cockpit alignment, and power-transfer telemetry adjustments.</p>
                        <a href="#bike-fit" class="btn-explore">
                            EXPLORE SERVICE
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" 
                                    stroke="currentColor" 
                                    stroke-width="2.5" stroke-linecap="round" 
                                    stroke-linejoin="round">
                                <line x1="7" y1="17" x2="17" y2="7"></line>
                                <polyline points="7 7 17 7 17 17"></polyline>
                            </svg>
                        </a>
                    </div>
                </article>
            </div>

            <!-- Syncro Lab Membership Card -->
            <div class="membership-card">
                <h2 class="membership-title">SYNCRO LAB MEMBERSHIP</h2>
                <p class="membership-desc">
                    Get 12 months of faster service, expert support, and priority access to rare gear.
                    Members enjoy guaranteed 24-hour repair turnarounds, an annual laser bike fit, and free deep cleans.
                    <a href="#membership-details" class="membership-link">
                        Learn more about member benefits
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="7" y1="17" x2="17" y2="7"></line>
                            <polyline points="7 7 17 7 17 17"></polyline>
                        </svg>
                    </a>
                </p>
                <a href="#register" class="btn-register">REGISTER NOW</a>
            </div>
        </div>
    </section>

    <section id="catalog" class="hardware-section" aria-label="Featured Hardware and Builds">
    <div class="hardware-container">
        <!-- Section Title -->
        <h2 class="hardware-title" id="featured-hardware-heading">FEATURED HARDWARE & BUILDS</h2>

        <!-- Hardware Products Grid -->
        <div class="hardware-grid" role="region" aria-labelledby="featured-hardware-heading">
        
        <!-- Card 1: S-Works Tarmac SL8 Custom -->
        <article class="hardware-card" aria-label="S-Works Tarmac SL8 Custom Product Card">
            <div class="card-header">
            <span class="category-tag" aria-label="Category">[FEATURED BUILD]</span>
            <span class="stock-tag" aria-label="Stock Status">IN STOCK: 1</span>
            </div>
            <div class="hardware-image">
            <img src="assets/s-works-tarmac.png" alt="S-Works Tarmac SL8 Custom Bicycle">
            </div>
            <h3 class="product-title">S-WORKS TARMAC SL8 CUSTOM</h3>
        </article>

        <!-- Card 2: Shimano Dura-Ace R9200 -->
        <article class="hardware-card" aria-label="Shimano Dura-Ace R9200 Product Card">
            <div class="card-header">
            <span class="category-tag" aria-label="Category">[NEW ARRIVAL COMPONENTS]</span>
            <span class="stock-tag" aria-label="Stock Status">IN STOCK: 14</span>
            </div>
            <div class="hardware-image">
            <img src="assets/shimano-dura-ace.png" alt="Shimano Dura-Ace R9200 Groupset">
            </div>
            <h3 class="product-title">SHIMANO DURA-ACE R9200</h3>
        </article>

        <!-- Card 3: Canyon Gorpcore Rain Jacket -->
        <article class="hardware-card" aria-label="Canyon Gorpcore Rain Jacket Product Card">
            <div class="card-header">
            <span class="category-tag" aria-label="Category">[RIDER GEAR]</span>
            <span class="stock-tag" aria-label="Stock Status">IN STOCK: 17</span>
            </div>
            <div class="hardware-image">
            <img src="assets/canyon-jacket.png" alt="Canyon Gorpcore Rain Jacket">
            </div>
            <h3 class="product-title">CANYON GORPCORE RAIN JACKET</h3>
        </article>

        <!-- Card 4: CeramicSpeed BB Unit -->
        <article class="hardware-card" aria-label="CeramicSpeed BB Unit Product Card">
            <div class="card-header">
            <span class="category-tag" aria-label="Category">[TELEMETRY TECH]</span>
            <span class="stock-tag" aria-label="Stock Status">IN STOCK: 20</span>
            </div>
            <div class="hardware-image">
            <img src="assets/ceramicspeed-bb.png" alt="CeramicSpeed Bottom Bracket Unit">
            </div>
            <h3 class="product-title">CERAMICSPEED BB UNIT</h3>
        </article>

        </div>
    </div>
    </section>

    <!-- ABOUT US SECTION -->
    <section id="about" class="about-section" aria-label="About Us">
    <div class="about-container">
        <!-- Section Title -->
        <h2 class="about-title">ABOUT US</h2>

        <!-- Hero Card Container -->
        <div class="about-card">
        <div class="about-card-overlay"></div>

        <!-- Left Text & CTA Content -->
        <div class="about-content">
            <h3 class="about-heading">PRECISION IS NOT AN OPTION.<br>IT IS OUR ONLY BENCHMARK.
            </h3>
            <p class="about-text">
            At SYNCRO LAB, we are a premier retail house that seamlessly blends elite component curation with technical bike fit and maintenance services. Driven by an obsessive dedication to mechanical precision and contemporary performance solutions, we engineer every build, tune, and overhaul to align with your exact vision. From custom frame-up assemblies to data-driven biomechanical calibrations, we approach every service with zero margin for error, ensuring maximum power transfer, optimal aerodynamics, and absolute reliability on every ride.
            </p>
            <a href="#learn-more" class="btn-learn-more">LEARN MORE</a>
        </div>

        <!-- Right Side Logo (SVG) -->
        <div class="about-logo-wrapper">
            <img src="assets/SL.svg" alt="SYNCRO LAB SL Mark" class="sl-logo-svg">
        </div>
        </div>

        <!-- Bottom 3 Feature Cards Bar -->
        <div class="about-features-bar">
        <!-- Feature 1: Torque Tolerance -->
        <div class="feature-box">
            <svg class="feature-icon" width="90" height="90" viewBox="0 0 90 90" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M89.9932 76.201C89.9823 79.8556 88.5257 83.3573 85.9415 85.9414C83.3574 88.5256 79.8556 89.9822 76.2011 89.9931C73.0612 89.8255 70.1027 88.4695 67.9259 86.2003C67.3351 85.4185 66.6389 84.7223 65.8571 84.1315L38.9626 57.237C35.8612 58.3389 32.5992 58.9214 29.3081 58.961C25.4585 58.9642 21.6459 58.2083 18.0887 56.7365C14.5314 55.2648 11.2993 53.1061 8.57714 50.3839C5.855 47.6618 3.6963 44.4296 2.22455 40.8724C0.752805 37.3151 -0.00313194 33.5026 4.23091e-05 29.6529C-0.0470569 23.7825 1.89778 18.0696 5.51688 13.4473L23.4465 31.3769L31.0321 23.7913L13.4473 6.20641C17.8957 2.42925 23.4776 0.245034 29.3081 0C35.6075 0.00732437 41.7374 2.04151 46.7914 5.80179C51.8454 9.56207 55.5553 14.8488 57.3725 20.8804C59.1897 26.9121 59.0178 33.3683 56.8822 39.2947C54.7467 45.2211 50.7608 50.3029 45.5138 53.789L70.6843 78.9595C71.3739 79.6491 74.4771 82.7523 75.8563 82.7523C77.6836 82.7468 79.4345 82.0185 80.7265 80.7264C82.0186 79.4344 82.7469 77.6835 82.7523 75.8563C82.7726 74.3609 82.2865 72.9027 81.3731 71.7186L79.6491 69.9946L63.0987 53.4442C62.7764 53.1284 62.52 52.7519 62.3442 52.3363C62.1684 51.9208 62.0767 51.4746 62.0744 51.0234C62.0721 50.5723 62.1593 50.1251 62.3309 49.7079C62.5025 49.2906 62.7551 48.9115 63.0741 48.5924C63.3932 48.2734 63.7723 48.0208 64.1896 47.8492C64.6068 47.6776 65.054 47.5904 65.5052 47.5927C65.9563 47.595 66.4025 47.6867 66.8181 47.8625C67.2336 48.0383 67.6101 48.2947 67.9259 48.617L84.4763 65.1674L86.2003 66.8914C88.5767 69.416 89.9287 72.7345 89.9932 76.201ZM52.065 29.6529C52.0709 26.708 51.4953 23.791 50.3711 21.0692C49.2469 18.3473 47.5962 15.8743 45.5139 13.792C43.4316 11.7097 40.9585 10.059 38.2367 8.93481C35.5149 7.81059 32.5978 7.23491 29.653 7.24082C28.7201 7.18486 27.7849 7.30175 26.8945 7.58562L38.273 18.9641C39.9312 20.6735 40.8585 22.9614 40.8585 25.3429C40.8585 27.7244 39.9312 30.0123 38.273 31.7217L31.7218 38.2729C30.0124 39.9311 27.7245 40.8585 25.3429 40.8585C22.9614 40.8585 20.6735 39.9311 18.9641 38.2729L7.58571 26.8945C7.58571 27.9289 7.24089 28.6185 7.24089 29.6529C7.24089 35.5969 9.60216 41.2975 13.8052 45.5006C18.0083 49.7037 23.7089 52.065 29.653 52.065C35.597 52.065 41.2976 49.7037 45.5007 45.5006C49.7038 41.2975 52.065 35.5969 52.065 29.6529Z" fill="#A6CE39"/>
            </svg>
            <div class="feature-text">
            <span class="feature-top">0.01MM</span>
            <span class="feature-bottom">TORQUE TOLERANCE</span>
            </div>
        </div>

        <!-- Feature 2: Direct Factory -->
        <div class="feature-box">
            <svg class="feature-icon" width="89" height="89" viewBox="0 0 89 89" fill="none" xmlns="http://www.w3.org/2000/svg">
            <g clip-path="url(#clip0_109_95)">
            <path d="M77.875 61.4656V47.2812C77.875 46.5436 77.582 45.8362 77.0604 45.3146C76.5388 44.793 75.8314 44.5 75.0938 44.5H58.4063C57.6686 44.5 56.9612 44.793 56.4396 45.3146C55.918 45.8362 55.625 46.5436 55.625 47.2812V61.1875H33.375V47.2812C33.375 46.5436 33.082 45.8362 32.5604 45.3146C32.0388 44.793 31.3314 44.5 30.5938 44.5H13.9063C13.1686 44.5 12.4612 44.793 11.9396 45.3146C11.4181 45.8362 11.125 46.5436 11.125 47.2812V61.4656C7.74538 62.1519 4.74129 64.0694 2.69593 66.846C0.650574 69.6226 -0.290122 73.0601 0.0564591 76.4913C0.403041 79.9225 2.01217 83.1024 4.57148 85.4139C7.13078 87.7254 10.4577 89.0034 13.9063 89H75.0938C78.5424 89.0034 81.8693 87.7254 84.4286 85.4139C86.9879 83.1024 88.597 79.9225 88.9436 76.4913C89.2902 73.0601 88.3495 69.6226 86.3041 66.846C84.2588 64.0694 81.2547 62.1519 77.875 61.4656ZM33.375 66.75C31.5347 69.1421 30.5369 72.0756 30.5369 75.0937C30.5369 78.1119 31.5347 81.0453 33.375 83.4375H24.9757C26.816 81.0453 27.8138 78.1119 27.8138 75.0937C27.8138 72.0756 26.816 69.1421 24.9757 66.75H33.375ZM36.1007 75.0937C36.1007 73.4459 36.5887 71.8349 37.5031 70.464C38.4174 69.0931 39.7173 68.0237 41.2387 67.3906C42.7601 66.7575 44.4348 66.589 46.0518 66.9065C47.6688 67.2239 49.1556 68.0131 50.3247 69.1744C51.4938 70.3358 52.2928 71.8173 52.621 73.4321C52.9492 75.047 52.7919 76.7228 52.1689 78.2484C51.5459 79.7739 50.4851 81.0809 49.1203 82.0043C47.7555 82.9278 46.1479 83.4265 44.5 83.4375C42.2871 83.4375 40.1649 82.5584 38.6001 80.9937C37.0354 79.4289 36.1563 77.3066 36.1563 75.0937H36.1007ZM55.625 66.75H64.08C62.2397 69.1421 61.2419 72.0756 61.2419 75.0937C61.2419 78.1119 62.2397 81.0453 64.08 83.4375H55.625C57.4654 81.0453 58.4632 78.1119 58.4632 75.0937C58.4632 72.0756 57.4654 69.1421 55.625 66.75ZM61.1875 50.0625H72.3125V61.1875H61.1875V50.0625ZM16.6875 50.0625H27.8125V61.1875H16.6875V50.0625ZM5.56253 75.0937C5.56253 73.4435 6.05188 71.8303 6.96871 70.4582C7.88553 69.0861 9.18864 68.0166 10.7133 67.3851C12.2379 66.7536 13.9155 66.5884 15.5341 66.9103C17.1526 67.2322 18.6393 68.0269 19.8062 69.1938C20.9731 70.3607 21.7678 71.8474 22.0897 73.4659C22.4117 75.0845 22.2464 76.7621 21.6149 78.2867C20.9834 79.8114 19.9139 81.1145 18.5418 82.0313C17.1697 82.9481 15.5565 83.4375 13.9063 83.4375C11.6934 83.4375 9.57112 82.5584 8.00636 80.9937C6.4416 79.4289 5.56253 77.3066 5.56253 75.0937ZM75.0938 83.4375C73.4435 83.4375 71.8304 82.9481 70.4582 82.0313C69.0861 81.1145 68.0167 79.8114 67.3852 78.2867C66.7536 76.7621 66.5884 75.0845 66.9103 73.4659C67.2323 71.8474 68.027 70.3607 69.1939 69.1938C70.3607 68.0269 71.8475 67.2322 73.466 66.9103C75.0845 66.5884 76.7622 66.7536 78.2868 67.3851C79.8114 68.0166 81.1145 69.0861 82.0314 70.4582C82.9482 71.8303 83.4375 73.4435 83.4375 75.0937C83.4375 77.3066 82.5585 79.4289 80.9937 80.9937C79.4289 82.5584 77.3067 83.4375 75.0938 83.4375ZM11.125 38.9375C11.8627 38.9375 12.5701 38.6445 13.0917 38.1229C13.6133 37.6013 13.9063 36.8939 13.9063 36.1562C13.9063 35.4186 13.6133 34.7112 13.0917 34.1896C12.5701 33.668 11.8627 33.375 11.125 33.375C10.0249 33.375 8.94942 33.0487 8.03467 32.4375C7.11992 31.8263 6.40696 30.9576 5.98595 29.9412C5.56494 28.9247 5.45478 27.8063 5.66941 26.7273C5.88404 25.6483 6.41382 24.6571 7.19175 23.8792C7.96968 23.1013 8.96082 22.5715 10.0398 22.3569C11.1189 22.1422 12.2373 22.2524 13.2537 22.6734C14.2701 23.0944 15.1389 23.8074 15.7501 24.7221C16.3613 25.6369 16.6875 26.7123 16.6875 27.8125C16.6875 28.5501 16.9806 29.2575 17.5021 29.7791C18.0237 30.3007 18.7311 30.5937 19.4688 30.5937C20.2064 30.5937 20.9138 30.3007 21.4354 29.7791C21.957 29.2575 22.25 28.5501 22.25 27.8125C22.25 24.8619 21.0779 22.0323 18.9916 19.9459C16.9053 17.8596 14.0756 16.6875 11.125 16.6875V11.125H33.375C33.375 14.0755 34.5471 16.9052 36.6335 18.9915C38.7198 21.0779 41.5495 22.25 44.5 22.25C47.4506 22.25 50.2802 21.0779 52.3666 18.9915C54.4529 16.9052 55.625 14.0755 55.625 11.125H77.875V16.6875C74.9245 16.6875 72.0948 17.8596 70.0085 19.9459C67.9221 22.0323 66.75 24.8619 66.75 27.8125C66.75 28.5501 67.0431 29.2575 67.5646 29.7791C68.0862 30.3007 68.7936 30.5937 69.5313 30.5937C70.2689 30.5937 70.9763 30.3007 71.4979 29.7791C72.0195 29.2575 72.3125 28.5501 72.3125 27.8125C72.3125 26.7123 72.6388 25.6369 73.25 24.7221C73.8612 23.8074 74.7299 23.0944 75.7463 22.6734C76.7628 22.2524 77.8812 22.1422 78.9602 22.3569C80.0392 22.5715 81.0304 23.1013 81.8083 23.8792C82.5862 24.6571 83.116 25.6483 83.3306 26.7273C83.5453 27.8063 83.4351 28.9247 83.0141 29.9412C82.5931 30.9576 81.8801 31.8263 80.9654 32.4375C80.0506 33.0487 78.9752 33.375 77.875 33.375C77.1374 33.375 76.43 33.668 75.9084 34.1896C75.3868 34.7112 75.0938 35.4186 75.0938 36.1562C75.0938 36.8939 75.3868 37.6013 75.9084 38.1229C76.43 38.6445 77.1374 38.9375 77.875 38.9375C80.3029 38.9127 82.656 38.0943 84.5753 36.6072C86.4947 35.1201 87.8748 33.0459 88.5051 30.7011C89.1354 28.3563 88.9813 25.8697 88.0663 23.6207C87.1514 21.3717 85.5257 19.4838 83.4375 18.245V8.34373C83.4375 7.6061 83.1445 6.89868 82.6229 6.37709C82.1013 5.8555 81.3939 5.56248 80.6563 5.56248H54.0675C53.0825 3.90238 51.6824 2.52715 50.005 1.57197C48.3275 0.616779 46.4304 0.114502 44.5 0.114502C42.5697 0.114502 40.6725 0.616779 38.9951 1.57197C37.3176 2.52715 35.9176 3.90238 34.9325 5.56248H8.34378C7.60615 5.56248 6.89873 5.8555 6.37714 6.37709C5.85555 6.89868 5.56253 7.6061 5.56253 8.34373V18.245C3.47431 19.4838 1.84869 21.3717 0.933723 23.6207C0.0187524 25.8697 -0.135331 28.3563 0.494985 30.7011C1.1253 33.0459 2.5054 35.1201 4.42471 36.6072C6.34402 38.0943 8.69714 38.9127 11.125 38.9375ZM44.5 5.56248C45.6002 5.56248 46.6756 5.88872 47.5904 6.49993C48.5051 7.11115 49.2181 7.97989 49.6391 8.9963C50.0601 10.0127 50.1703 11.1312 49.9556 12.2102C49.741 13.2892 49.2112 14.2803 48.4333 15.0583C47.6554 15.8362 46.6642 16.366 45.5852 16.5806C44.5062 16.7952 43.3878 16.6851 42.3714 16.2641C41.3549 15.843 40.4862 15.1301 39.875 14.2153C39.2638 13.3006 38.9375 12.2251 38.9375 11.125C38.9375 9.64971 39.5236 8.23487 40.5667 7.1917C41.6099 6.14853 43.0248 5.56248 44.5 5.56248Z" fill="#A6CE39"/>
            </g>
            <defs>
            <clipPath id="clip0_109_95">
            <rect width="89" height="89" fill="white"/>
            </clipPath>
            </defs>
            </svg>
            <div class="feature-text">
            <span class="feature-top">100%</span>
            <span class="feature-bottom">DIRECT FACTORY</span>
            </div>
        </div>

        <!-- Feature 3: Turnaround -->
        <div class="feature-box">
            <svg class="feature-icon" width="87" height="87" viewBox="0 0 87 87" fill="none" xmlns="http://www.w3.org/2000/svg">
            <g clip-path="url(#clip0_109_93)">
            <path d="M32.625 86.9999H3.625C2.66359 86.9999 1.74156 86.618 1.06174 85.9381C0.381919 85.2583 0 84.3363 0 83.3749C0 82.4135 0.381919 81.4914 1.06174 80.8116C1.74156 80.1318 2.66359 79.7499 3.625 79.7499H32.625C33.5864 79.7499 34.5084 80.1318 35.1883 80.8116C35.8681 81.4914 36.25 82.4135 36.25 83.3749C36.25 84.3363 35.8681 85.2583 35.1883 85.9381C34.5084 86.618 33.5864 86.9999 32.625 86.9999ZM25.375 72.4999H3.625C2.66359 72.4999 1.74156 72.118 1.06174 71.4381C0.381919 70.7583 0 69.8363 0 68.8749C0 67.9135 0.381919 66.9914 1.06174 66.3116C1.74156 65.6318 2.66359 65.2499 3.625 65.2499H25.375C26.3364 65.2499 27.2584 65.6318 27.9383 66.3116C28.6181 66.9914 29 67.9135 29 68.8749C29 69.8363 28.6181 70.7583 27.9383 71.4381C27.2584 72.118 26.3364 72.4999 25.375 72.4999ZM18.125 57.9999H3.625C2.66359 57.9999 1.74156 57.618 1.06174 56.9381C0.381919 56.2583 0 55.3363 0 54.3749C0 53.4135 0.381919 52.4914 1.06174 51.8116C1.74156 51.1318 2.66359 50.7499 3.625 50.7499H18.125C19.0864 50.7499 20.0084 51.1318 20.6883 51.8116C21.3681 52.4914 21.75 53.4135 21.75 54.3749C21.75 55.3363 21.3681 56.2583 20.6883 56.9381C20.0084 57.618 19.0864 57.9999 18.125 57.9999ZM47.125 86.8368C46.1636 86.8795 45.2246 86.5387 44.5145 85.8891C43.8044 85.2395 43.3815 84.3345 43.3387 83.3731C43.2959 82.4117 43.6368 81.4726 44.2864 80.7626C44.9359 80.0525 45.841 79.6295 46.8024 79.5868C53.6625 78.9591 60.2018 76.3896 65.6543 72.1794C71.1068 67.9691 75.2467 62.2924 77.5892 55.8141C79.9317 49.3358 80.3798 42.3241 78.8809 35.6003C77.382 28.8765 73.9983 22.719 69.1261 17.849C64.2538 12.979 58.0948 9.59815 51.3703 8.10238C44.6458 6.60662 37.6344 7.0579 31.1571 9.40336C24.6799 11.7488 19.0051 15.8914 14.7973 21.3458C10.5896 26.8002 8.02316 33.3407 7.39862 40.2011C7.35578 40.6753 7.21997 41.1363 6.99894 41.558C6.77791 41.9796 6.476 42.3536 6.11044 42.6586C5.74488 42.9636 5.32283 43.1935 4.86839 43.3354C4.41395 43.4773 3.93601 43.5282 3.46187 43.4854C2.98774 43.4425 2.52668 43.3067 2.10503 43.0857C1.68338 42.8647 1.30939 42.5628 1.00442 42.1972C0.699452 41.8316 0.46947 41.4096 0.327607 40.9551C0.185745 40.5007 0.134781 40.0228 0.177625 39.5486C1.1886 28.3956 6.46194 18.0622 14.8999 10.6993C23.3379 3.33646 34.2905 -0.488519 45.4776 0.0206032C56.6648 0.529726 67.2246 5.33372 74.959 13.4326C82.6933 21.5314 87.0062 32.3012 87 43.4999C87.0556 54.3689 83.0177 64.8605 75.6896 72.8878C68.3616 80.9151 58.2803 85.8898 47.4513 86.8223C47.3425 86.8331 47.2301 86.8368 47.125 86.8368ZM43.5 21.7499C42.5386 21.7499 41.6166 22.1318 40.9367 22.8116C40.2569 23.4914 39.875 24.4135 39.875 25.3749V43.4999C39.8752 44.4612 40.2573 45.3831 40.9371 46.0628L51.8121 56.9378C52.4958 57.5981 53.4115 57.9635 54.362 57.9552C55.3124 57.9469 56.2216 57.5657 56.8937 56.8936C57.5658 56.2215 57.9471 55.3123 57.9553 54.3618C57.9636 53.4114 57.5982 52.4957 56.9379 51.812L47.125 41.9991V25.3749C47.125 24.4135 46.7431 23.4914 46.0633 22.8116C45.3834 22.1318 44.4614 21.7499 43.5 21.7499Z" fill="#A6CE39"/>
            </g>
            <defs>
            <clipPath id="clip0_109_93">
            <rect width="87" height="87" fill="white"/>
            </clipPath>
            </defs>
            </svg>
            <div class="feature-text">
            <span class="feature-top">24 - 48 HR</span>
            <span class="feature-bottom">TURNAROUND</span>
            </div>
        </div>
        </div>
    </div>
    </section>

    <!-- LAB LOCATIONS SECTION -->
    <section id="locations" class="locations-section" aria-label="Lab Locations">
    <div class="locations-container">

        <h2 class="locations-title">LAB LOCATIONS</h2>

        <!-- Search & Filter Bar -->
        <div class="search-bar">
        <input type="text" class="search-input" placeholder="FIND A LAB" aria-label="Find a lab location">
        <div class="search-icons">
            <!-- Search Icon (SVG) -->
            <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="#7e8c99" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"></circle>
            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <!-- Filter Icon (SVG) -->
            <svg class="filter-icon" viewBox="0 0 24 24" fill="none" stroke="#7e8c99" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <line x1="4" y1="21" x2="4" y2="14"></line>
            <line x1="4" y1="10" x2="4" y2="3"></line>
            <line x1="12" y1="21" x2="12" y2="12"></line>
            <line x1="12" y1="8" x2="12" y2="3"></line>
            <line x1="20" y1="21" x2="20" y2="16"></line>
            <line x1="20" y1="12" x2="20" y2="3"></line>
            <line x1="1" y1="14" x2="7" y2="14"></line>
            <line x1="9" y1="8" x2="15" y2="8"></line>
            <line x1="17" y1="16" x2="23" y2="16"></line>
            </svg>
        </div>
        </div>

        <!-- Locations List Container -->
        <div class="locations-list">
        <!-- Location Card 1 -->
        <div class="location-card">
            <div class="location-info">
            <h3 class="location-name">NORTH HQ</h3>
            <p class="location-address">
                E.J. Blanco Drive, Brgy. Piapi, Dumaguete City<br>
                <span class="address-sub">6200 Negros Oriental</span>
            </p>
            </div>
            <div class="location-actions">
            <a href="#directions" class="btn-directions">SHOW DIRECTIONS</a>
            <a href="#book" class="btn-book">BOOK LAB</a>
            </div>
        </div>

        <!-- Location Card 2 -->
        <div class="location-card">
            <div class="location-info">
            <h3 class="location-name">DOWNTOWN WORKSHOP</h3>
            <p class="location-address">
                Real Street, Brgy. 3, Dumaguete City<br>
                <span class="address-sub">6200 Negros Oriental</span>
            </p>
            </div>
            <div class="location-actions">
            <a href="#directions" class="btn-directions">SHOW DIRECTIONS</a>
            <a href="#book" class="btn-book">BOOK LAB</a>
            </div>
        </div>

        <!-- Location Card 3 -->
        <div class="location-card">
            <div class="location-info">
            <h3 class="location-name">METRO MANILA METROLOGY</h3>
            <p class="location-address">
                Greenhills Shopping Center, Ortigas Ave, San Juan<br>
                <span class="address-sub">1500 Metro Manila</span>
            </p>
            </div>
            <div class="location-actions">
            <a href="#directions" class="btn-directions">SHOW DIRECTIONS</a>
            <a href="#book" class="btn-book">BOOK LAB</a>
            </div>
        </div>

        <!-- Location Card 4 -->
        <div class="location-card">
            <div class="location-info">
            <h3 class="location-name">NUVALI TRAIL & ROAD STATION</h3>
            <p class="location-address">
                Solenad 3, Nuvali Boulevard, Santa Rosa<br>
                <span class="address-sub">4026 Laguna</span>
            </p>
            </div>
            <div class="location-actions">
            <a href="#directions" class="btn-directions">SHOW DIRECTIONS</a>
            <a href="#book" class="btn-book">BOOK LAB</a>
            </div>
        </div>

        <!-- Location Card 5 -->
        <div class="location-card">
            <div class="location-info">
            <h3 class="location-name">GIRONA PELOTON BASE</h3>
            <p class="location-address">
                Carrer de les Ballesteries, 28, Barri Vell<br>
                <span class="address-sub">17004 Girona, Spain</span>
            </p>
            </div>
            <div class="location-actions">
            <a href="#directions" class="btn-directions">SHOW DIRECTIONS</a>
            <a href="#book" class="btn-book">BOOK LAB</a>
            </div>
        </div>
        </div>
    </div>
    </section>

<!-- FOOTER SECTION -->
<footer class="site-footer" aria-label="Site Footer">
  <div class="footer-container">
    <div class="footer-content">
      
      <!-- Column 1: Brand & Contact Info -->
      <div class="footer-brand-col">
        <!-- Logo SVG -->
        <div class="footer-logo">
          <img src="assets/syncro-lab-dark.svg" alt="SYNCRO LAB Logo" class="footer-logo-img">
        </div>
        <!-- Address & Email -->
        <p class="footer-address">
          Dumaguete City,<br>
          6200 Negros Oriental
        </p>
        <a href="mailto:contact@syncrolab.com" class="footer-email">contact@syncrolab.com</a>
      </div>

      <!-- Column 2: Shop Links -->
      <div class="footer-links-col">
        <h4 class="footer-column-title">SHOP</h4>
        <ul class="footer-links">
          <li><a href="#bikes">Bikes</a></li>
          <li><a href="#components">Components</a></li>
          <li><a href="#wheels">Wheels</a></li>
          <li><a href="#apparel">Apparel</a></li>
          <li><a href="#frames">Bike frames</a></li>
        </ul>
      </div>

      <!-- Column 3: Services Links -->
      <div class="footer-links-col">
        <h4 class="footer-column-title">SERVICES</h4>
        <ul class="footer-links">
          <li><a href="#tune-ups">Tune-ups</a></li>
          <li><a href="#custom-builds">Custom Builds</a></li>
          <li><a href="#diagnostics">Diagnostics</a></li>
          <li><a href="#warranty">Warranty</a></li>
          <li><a href="#faqs">FAQs</a></li>
        </ul>
      </div>

      <!-- Column 4: Social Links with SVG Icons -->
      <div class="footer-links-col">
        <h4 class="footer-column-title">SOCIALS</h4>
        <ul class="footer-social-links">
          <li>
            <a href="#instagram">
              <svg class="social-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line></svg>
              <span>Instagram</span>
            </a>
          </li>
          <li>
            <a href="#youtube">
              <svg class="social-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22.54 6.42a2.78 2.78 0 0 0-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 0 0-1.94 2A29 29 0 0 0 1 11.75a29 29 0 0 0 .46 5.33A2.78 2.78 0 0 0 3.4 19c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 0 0 1.94-2 29 29 0 0 0 .46-5.25 29 29 0 0 0-.46-5.33z"></path><polygon points="9.75 15.02 15.5 11.75 9.75 8.48 9.75 15.02"></polygon></svg>
              <span>YouTube</span>
            </a>
          </li>
          <li>
            <a href="#strava">
              <svg class="social-icon" viewBox="0 0 24 24" fill="currentColor"><path d="M15.387 17.944l-2.089-4.116h-3.065L15.387 24l5.15-10.172h-3.066m-7.008-5.599l2.836 5.598h4.172L10.463 0l-7.925 15.599h4.173"/></svg>
              <span>Strava</span>
            </a>
          </li>
          <li>
            <a href="#komoot">
              <svg class="social-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v10M8 10l4-3 4 3M8 14l4 3 4-3"></path></svg>
              <span>Komoot</span>
            </a>
          </li>
        </ul>
      </div>

    </div>

    <!-- Copyright Bar -->
    <div class="footer-copyright">
      <p>© 2026 SYNCHRO LAB. All rights reserved</p>
    </div>
  </div>
</footer>

<script src="javascript.js"></script>
</body>
</html>
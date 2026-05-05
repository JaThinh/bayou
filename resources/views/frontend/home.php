<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đường bay đẹp & giá tốt</title>
    
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/components/header/header.css">
    <link rel="stylesheet" href="/components/hero/hero.css">
    <link rel="stylesheet" href="/components/destinations/destinations.css">
    <link rel="stylesheet" href="/components/partners/partners.css">
    <link rel="stylesheet" href="/components/footer/footer.css">

    <link rel="icon" href="/assets/logos/favicon.png.png" type="image/png">
</head>
<body>
    <!-- Global Loader -->
    <div id="global-loader" class="loader-overlay">
        <!-- Các đám mây bay -->
        <img src="/assets/logos/cloud.png" alt="Cloud" class="flying-cloud cloud-1">
        <img src="/assets/logos/cloud.png" alt="Cloud" class="flying-cloud cloud-2">
        <img src="/assets/logos/cloud.png" alt="Cloud" class="flying-cloud cloud-3">

        <div class="flight-loader">
            <div class="earth-container">
                <img src="/assets/logos/earth.png" alt="Earth" class="earth-svg">
            </div>
            
            <div class="orbit">
                <div class="plane">
                    <!-- Máy bay -->
                    <img src="/assets/logos/plane.png" alt="Plane" style="width: 24px; height: 24px;">
                </div>
            </div>
        </div>
    </div>

    <div id="app">
        <div id="header-container"></div>
        <div id="hero-container"></div>
        <div id="destinations-container"></div>
        <div id="news-container"></div>
        <div id="partners-container"></div>
        <div id="footer-container"></div>
        <div id="modals-container"></div>
    </div>
    
    <script src="/js/main.js" type="module"></script>
</body>
</html>

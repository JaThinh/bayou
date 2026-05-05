const https = require('https');
const fs = require('fs');
const path = require('path');

const logosDir = path.join(__dirname, 'assets', 'logos');
if (!fs.existsSync(logosDir)) {
    fs.mkdirSync(logosDir, { recursive: true });
}

const urls = {
    "earth.png": "https://img.icons8.com/color/96/000000/earth-planet.png",
    "cloud.png": "https://img.icons8.com/color/96/000000/cloud.png",
    "plane.png": "https://img.icons8.com/color/96/000000/airplane-mode-on.png",
    "dest-newyork.png": "https://images.unsplash.com/photo-1496442226666-8d4d0e62e6e9?w=500&q=80",
    "dest-paris.png": "https://images.unsplash.com/photo-1431274172761-fca41d930114?w=500&q=80",
    "dest-tokyo.png": "https://images.unsplash.com/photo-1540959733332-eab4deabeeaf?w=500&q=80",
    "dest-bali.png": "https://images.unsplash.com/photo-1537996194471-e657df975ab4?w=500&q=80",
    "news-1.png": "https://images.unsplash.com/photo-1436491865332-7a61a109cc05?w=500&q=80",
    "news-2.png": "https://images.unsplash.com/photo-1530521954074-e64f6810b32d?w=500&q=80",
    "news-3.png": "https://images.unsplash.com/photo-1488085061387-422e29b40080?w=500&q=80",
    "news-4.png": "https://images.unsplash.com/photo-1542296332-2e4473faf563?w=500&q=80",
    "qr-website.png": "https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=Bayouticket.com",
    "qr-fb.png": "https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=Facebook",
    "qr-zalo.png": "https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=Zalo",
    "qr-viber.png": "https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=Viber"
};

console.log('Bắt đầu tải ảnh PNG thật về thư mục assets/logos...');

for (const [filename, url] of Object.entries(urls)) {
    const dest = path.join(logosDir, filename);
    const file = fs.createWriteStream(dest);
    https.get(url, (response) => {
        response.pipe(file);
        file.on('finish', () => {
            file.close();
            console.log(`✅ Đã tải thành công: ${filename}`);
        });
    }).on('error', (err) => {
        fs.unlink(dest, () => {});
        console.error(`❌ Lỗi tải ${filename}:`, err.message);
    });
}

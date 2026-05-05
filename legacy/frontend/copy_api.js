const fs = require('fs');
const path = require('path');

const sourceDir = path.resolve(__dirname, '../../api-bayou');
const destDir = 'D:\\laragon\\www\\api-bayou';

function copyFolderSync(from, to) {
    if (!fs.existsSync(to)) {
        fs.mkdirSync(to, { recursive: true });
    }
    
    const items = fs.readdirSync(from);
    for (const item of items) {
        const srcPath = path.join(from, item);
        const destPath = path.join(to, item);
        
        const stat = fs.statSync(srcPath);
        if (stat.isDirectory()) {
            copyFolderSync(srcPath, destPath);
        } else {
            fs.copyFileSync(srcPath, destPath);
            console.log(`Đã copy: ${item}`);
        }
    }
}

try {
    console.log('Đang copy API sang Laragon...');
    copyFolderSync(sourceDir, destDir);
    console.log('\n✅ COPY THÀNH CÔNG! API đã được cập nhật vào D:\\laragon\\www\\api-bayou');
    console.log('Bây giờ bạn hãy ra web F5 và tìm kiếm lại nhé!');
} catch (err) {
    console.error('❌ Lỗi khi copy:', err.message);
}

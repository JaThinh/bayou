document.addEventListener('DOMContentLoaded', () => {
    // Xử lý Form Đăng Nhập
    const loginForm = document.getElementById('login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', (e) => {
            e.preventDefault();
            // Cập nhật trạng thái loading hoặc chuyển hướng sang trang admin
            const btn = loginForm.querySelector('.btn-submit-login');
            const originalText = btn.textContent;
            btn.textContent = 'Đang đăng nhập...';
            
            setTimeout(() => {
                alert('Đăng nhập thành công!');
                window.location.href = 'admin.html';
            }, 1000);
        });
    }

    // Xử lý Form Đăng Ký
    const registerForm = document.getElementById('register-form');
    if (registerForm) {
        registerForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const btn = registerForm.querySelector('.btn-submit-login');
            btn.textContent = 'Đang xử lý...';
            
            setTimeout(() => {
                alert('Đăng ký thành công! Vui lòng đăng nhập.');
                window.location.href = 'login.html';
            }, 1000);
        });
    }
});

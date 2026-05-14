// ==========================================
// ANIMATED PARTICLES BACKGROUND
// ==========================================

export function initParticles() {
    const particlesContainer = document.createElement('div');
    particlesContainer.className = 'particles-container';
    document.body.insertBefore(particlesContainer, document.body.firstChild);

    const particleCount = 30;
    const colors = [
        'rgba(102, 126, 234, 0.6)',
        'rgba(118, 75, 162, 0.6)',
        'rgba(240, 147, 251, 0.6)',
        'rgba(0, 212, 255, 0.5)'
    ];

    for (let i = 0; i < particleCount; i++) {
        createParticle(particlesContainer, colors);
    }
}

function createParticle(container, colors) {
    const particle = document.createElement('div');
    particle.className = 'particle';
    
    // Random properties
    const size = Math.random() * 4 + 2;
    const startX = Math.random() * window.innerWidth;
    const duration = Math.random() * 15 + 10;
    const delay = Math.random() * 5;
    const color = colors[Math.floor(Math.random() * colors.length)];
    
    particle.style.width = `${size}px`;
    particle.style.height = `${size}px`;
    particle.style.left = `${startX}px`;
    particle.style.animationDuration = `${duration}s`;
    particle.style.animationDelay = `${delay}s`;
    particle.style.background = `radial-gradient(circle, ${color} 0%, transparent 70%)`;
    
    container.appendChild(particle);
    
    // Recreate particle after animation ends
    particle.addEventListener('animationiteration', () => {
        particle.style.left = `${Math.random() * window.innerWidth}px`;
    });
}

// ==========================================
// PARALLAX EFFECT ON MOUSE MOVE
// ==========================================

export function initParallax() {
    const parallaxElements = document.querySelectorAll('.parallax-layer');
    
    if (parallaxElements.length === 0) return;
    
    document.addEventListener('mousemove', (e) => {
        const mouseX = e.clientX / window.innerWidth;
        const mouseY = e.clientY / window.innerHeight;
        
        parallaxElements.forEach((element, index) => {
            const speed = (index + 1) * 10;
            const x = (mouseX - 0.5) * speed;
            const y = (mouseY - 0.5) * speed;
            
            element.style.transform = `translate(${x}px, ${y}px)`;
        });
    });
}

// ==========================================
// SCROLL REVEAL ANIMATIONS
// ==========================================

export function initScrollReveal() {
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry, index) => {
            if (entry.isIntersecting) {
                setTimeout(() => {
                    entry.target.classList.add('animate-slide-up');
                }, index * 100);
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);
    
    // Observe elements that should animate on scroll
    const animateElements = document.querySelectorAll('.article-card, .partners__logo, .destination-card');
    animateElements.forEach(el => {
        el.style.opacity = '0';
        observer.observe(el);
    });
}

// ==========================================
// SMOOTH SCROLL WITH EASING
// ==========================================

export function initSmoothScroll() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            if (href === '#') return;
            
            e.preventDefault();
            const target = document.querySelector(href);
            
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}

// ==========================================
// RIPPLE EFFECT ON BUTTONS
// ==========================================

export function initRippleEffect() {
    const buttons = document.querySelectorAll('.btn-modern, .search-widget__submit-btn, .modal__submit-btn');
    
    buttons.forEach(button => {
        button.addEventListener('click', function(e) {
            const ripple = document.createElement('span');
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;
            
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';
            ripple.classList.add('ripple-effect');
            
            this.appendChild(ripple);
            
            setTimeout(() => ripple.remove(), 600);
        });
    });
}

// ==========================================
// CARD TILT EFFECT (3D)
// ==========================================

export function initCardTilt() {
    const cards = document.querySelectorAll('.article-card');
    
    cards.forEach(card => {
        card.addEventListener('mousemove', (e) => {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            const centerX = rect.width / 2;
            const centerY = rect.height / 2;
            
            const rotateX = (y - centerY) / 10;
            const rotateY = (centerX - x) / 10;
            
            card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale3d(1.02, 1.02, 1.02)`;
        });
        
        card.addEventListener('mouseleave', () => {
            card.style.transform = 'perspective(1000px) rotateX(0) rotateY(0) scale3d(1, 1, 1)';
        });
    });
}

// ==========================================
// TYPING EFFECT FOR HERO TEXT
// ==========================================

export function initTypingEffect(element, texts, speed = 100) {
    if (!element) return;
    
    let textIndex = 0;
    let charIndex = 0;
    let isDeleting = false;
    
    function type() {
        const currentText = texts[textIndex];
        
        if (isDeleting) {
            element.textContent = currentText.substring(0, charIndex - 1);
            charIndex--;
        } else {
            element.textContent = currentText.substring(0, charIndex + 1);
            charIndex++;
        }
        
        let typeSpeed = speed;
        
        if (isDeleting) {
            typeSpeed /= 2;
        }
        
        if (!isDeleting && charIndex === currentText.length) {
            typeSpeed = 2000;
            isDeleting = true;
        } else if (isDeleting && charIndex === 0) {
            isDeleting = false;
            textIndex = (textIndex + 1) % texts.length;
            typeSpeed = 500;
        }
        
        setTimeout(type, typeSpeed);
    }
    
    type();
}

// ==========================================
// LOADING PROGRESS BAR
// ==========================================

export function showLoadingProgress() {
    const progressBar = document.createElement('div');
    progressBar.className = 'loading-progress';
    progressBar.innerHTML = '<div class="loading-progress-bar"></div>';
    document.body.appendChild(progressBar);
    
    let progress = 0;
    const interval = setInterval(() => {
        progress += Math.random() * 30;
        if (progress > 100) progress = 100;
        
        const bar = progressBar.querySelector('.loading-progress-bar');
        bar.style.width = progress + '%';
        
        if (progress === 100) {
            clearInterval(interval);
            setTimeout(() => {
                progressBar.style.opacity = '0';
                setTimeout(() => progressBar.remove(), 300);
            }, 200);
        }
    }, 200);
}

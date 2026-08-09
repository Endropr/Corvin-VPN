// ===== БУРГЕР-МЕНЮ =====
document.addEventListener('DOMContentLoaded', function() {
    const burgerBtn = document.getElementById('burgerBtn');
    const mobileMenu = document.getElementById('mobileMenu');
    const mobileOverlay = document.getElementById('mobileOverlay');
    const header = document.querySelector('.header');
    
    if (!burgerBtn || !mobileMenu) {
        console.error(' Элементы меню не найдены!');
        return;
    }
    
    function openMenu() {
        mobileMenu.classList.add('open');
        mobileOverlay.classList.add('show');
        burgerBtn.classList.add('active');
        header.classList.add('menu-open');
        document.body.style.overflow = 'hidden';
    }
    
    function closeMenu() {
        mobileMenu.classList.remove('open');
        mobileOverlay.classList.remove('show');
        burgerBtn.classList.remove('active');
        header.classList.remove('menu-open');
        document.body.style.overflow = '';
    }
    
    burgerBtn.addEventListener('click', function() {
        if (mobileMenu.classList.contains('open')) {
            closeMenu();
        } else {
            openMenu();
        }
    });
    
    mobileOverlay.addEventListener('click', closeMenu);
    
    // Закрыть по Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && mobileMenu.classList.contains('open')) {
            closeMenu();
        }
    });
    
    // Закрыть при клике на ссылку в меню
    const menuLinks = mobileMenu.querySelectorAll('a');
    menuLinks.forEach(function(link) {
        link.addEventListener('click', function() {
            setTimeout(closeMenu, 200);
        });
    });
    
    console.log(' Бургер-меню инициализировано');
});

// ===== КАСТОМНОЕ ВИДЕО =====
(function() {
    const video = document.getElementById('heroVideo');
    const overlay = document.getElementById('playOverlay');
    const container = document.getElementById('customVideo');
    
    if (!video || !overlay || !container) return;
    
    // Единый обработчик клика на весь контейнер
    container.addEventListener('click', function(e) {
        e.preventDefault();
        
        if (video.paused) {
            video.play();
            overlay.style.opacity = '0';
        } else {
            video.pause();
            overlay.style.opacity = '1';
        }
    });
    
    // Когда видео закончилось — показать кнопку
    video.addEventListener('ended', function() {
        overlay.style.opacity = '1';
    });
    
    // На всякий случай — touch события для мобильных
    container.addEventListener('touchend', function(e) {
        e.preventDefault();
        
        if (video.paused) {
            video.play();
            overlay.style.opacity = '0';
        } else {
            video.pause();
            overlay.style.opacity = '1';
        }
    });
})();

// ===== ЧАСТИЦЫ (плавное хаотичное движение) =====
(function() {
    const canvas = document.getElementById('particles-canvas');
    if (!canvas) {
        console.error('Canvas #particles-canvas не найден!');
        return;
    }
    
    const ctx = canvas.getContext('2d');
    let width, height;
    let particles = [];
    
    const MAX_PARTICLES = 80;
    const CONNECTION_DISTANCE = 130;
    
    class Particle {
        constructor() {
            this.reset(true);
        }
        
        reset(randomY = false) {
            this.x = Math.random() * width;
            this.y = randomY ? Math.random() * height : height + 20;
            
            // Скорость: очень маленькая для плавности
            this.vx = (Math.random() - 0.5) * 0.3;  // от -0.15 до +0.15
            this.vy = (Math.random() - 0.5) * 0.3;  // от -0.15 до +0.15
            
            this.size = Math.random() * 2 + 0.8;
            this.baseOpacity = Math.random() * 0.35 + 0.15;
            this.opacity = this.baseOpacity;
            this.opacityPhase = Math.random() * Math.PI * 2;
            this.opacitySpeed = 0.008 + Math.random() * 0.012;
        }

        
        
        update() {
            // Плавное хаотичное движение (без привязки к скроллу)
            this.x += this.vx;
            this.y += this.vy;
            
            // Лёгкое изменение направления для хаотичности
            if (Math.random() < 0.005) {
                this.vx += (Math.random() - 0.5) * 0.08;
                this.vy += (Math.random() - 0.5) * 0.08;
            }
            
            // Ограничение скорости, чтобы не разгонялись
            const maxSpeed = 0.5;
            const speed = Math.sqrt(this.vx * this.vx + this.vy * this.vy);
            if (speed > maxSpeed) {
                this.vx = (this.vx / speed) * maxSpeed;
                this.vy = (this.vy / speed) * maxSpeed;
            }
            
            // Плавное мерцание
            this.opacityPhase += this.opacitySpeed;
            this.opacity = this.baseOpacity + Math.sin(this.opacityPhase) * 0.12;
            if (this.opacity < 0.08) this.opacity = 0.08;
            if (this.opacity > 0.6) this.opacity = 0.6;
            
            // Зацикливание по краям (плавное)
            const margin = 30;
            if (this.x < -margin) this.x = width + margin;
            if (this.x > width + margin) this.x = -margin;
            if (this.y < -margin) this.y = height + margin;
            if (this.y > height + margin) this.y = -margin;
        }
        
        draw() {
            ctx.beginPath();
            ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
            ctx.fillStyle = `rgba(0, 170, 255, ${this.opacity})`;
            ctx.fill();
            
            // Лёгкое свечение вокруг
            ctx.beginPath();
            ctx.arc(this.x, this.y, this.size * 2.5, 0, Math.PI * 2);
            ctx.fillStyle = `rgba(0, 150, 255, ${this.opacity * 0.12})`;
            ctx.fill();
        }
    }
    
    function resize() {
        width = window.innerWidth;
        height = window.innerHeight;
        canvas.width = width;
        canvas.height = height;
    }
    
    function createParticles() {
        particles = [];
        for (let i = 0; i < MAX_PARTICLES; i++) {
            particles.push(new Particle());
        }
    }
    
    function drawConnections() {
        for (let i = 0; i < particles.length; i++) {
            for (let j = i + 1; j < particles.length; j++) {
                const dx = particles[i].x - particles[j].x;
                const dy = particles[i].y - particles[j].y;
                const dist = Math.sqrt(dx * dx + dy * dy);
                
                if (dist < CONNECTION_DISTANCE) {
                    const opacity = (1 - dist / CONNECTION_DISTANCE) * 0.08;
                    ctx.beginPath();
                    ctx.moveTo(particles[i].x, particles[i].y);
                    ctx.lineTo(particles[j].x, particles[j].y);
                    ctx.strokeStyle = `rgba(0, 150, 255, ${opacity})`;
                    ctx.lineWidth = 0.4;
                    ctx.stroke();
                }
            }
        }
    }
    
    function animate() {
        ctx.clearRect(0, 0, width, height);
        
        particles.forEach(p => {
            p.update();
            p.draw();
        });
        
        drawConnections();
        requestAnimationFrame(animate);
    }
    
    // Запуск
    resize();
    createParticles();
    animate();
    
    // Плавный ресайз
    let resizeTimeout;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => {
            resize();
            createParticles();
        }, 200);
    });
    
    console.log(' Частицы запущены! (хаотичное движение)');
})();

document.addEventListener('DOMContentLoaded', () => {
    // Плавная прокрутка
    document.querySelectorAll('a[href^="#"]').forEach(link => {
        link.addEventListener('click', e => {
            e.preventDefault();
            const id = link.getAttribute('href');
            const target = document.querySelector(id);
            if (target) target.scrollIntoView({ behavior: 'smooth' });
        });
    });

    // FAQ аккордеон
    document.querySelectorAll('.faq-question').forEach(btn => {
        btn.addEventListener('click', () => {
            const item = btn.parentElement;
            document.querySelectorAll('.faq-item').forEach(el => {
                if (el !== item) el.classList.remove('active');
            });
            item.classList.toggle('active');
        });
    });

    // Мерцание логотипа
    const logo = document.querySelector('.logo-icon');
    if (logo) {
        setInterval(() => {
            logo.style.boxShadow = '0 0 24px rgba(0,200,255,0.9)';
            setTimeout(() => logo.style.boxShadow = '0 0 14px rgba(0,120,255,0.5)', 400);
        }, 2000);
    }
});
// скролл
const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('visible');
        }
    });
}, { threshold: 0.5 });

document.querySelectorAll('.feature-item-h, .faq-item').forEach(el => {
    el.classList.add('fade-in');
    observer.observe(el);
});
// Плавное появление
document.querySelectorAll('.feature-item-h, .faq-item, .benefit-item, .step').forEach(el => {
    el.classList.add('fade-in');
    observer.observe(el);
});
// ===== КАРУСЕЛЬ ТАРИФОВ =====
(function() {
    const track = document.getElementById('carouselTrack');
    if (!track) return;
    
    // Клонируем карточки для бесшовности (если ещё не клонированы)
    const cards = track.querySelectorAll('.plan-card');
    const originalCount = cards.length / 2; // У нас уже есть дубликаты в HTML
    
    // Если дубликатов нет — создаём
    if (originalCount < 4) {
        cards.forEach(card => {
            const clone = card.cloneNode(true);
            track.appendChild(clone);
        });
    }
    
    // Устанавливаем скорость
    const totalCards = track.children.length;
    const duration = totalCards * 2.5;
    track.style.animationDuration = duration + 's';
    
    console.log(' Карусель запущена!');
})();

// ===== ПЛАВНОЕ ПОЯВЛЕНИЕ ПРИ СКРОЛЛЕ =====
(function() {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                observer.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.15,
        rootMargin: '0px 0px -30px 0px'
    });

    const elementsToAnimate = document.querySelectorAll(
        '.benefit-item, .step, .tariff-hero, .tariff-footer-cta, .tariff-description h2, .how-it-works h2'
    );

    elementsToAnimate.forEach(el => {
        el.classList.add('fade-in');
        observer.observe(el);
    });

    console.log('✅ Анимация появления активирована');
})();

// ===== ДАННЫЕ ТАРИФОВ =====
const tariffData = {
    '1month': {
        title: 'Тариф «Попробовать»',
        subtitle: '1 месяц свободного интернета',
        badge: '',
        oldPrice: '',
        priceRaw: '249',
        priceDisplay: '249 ₽',
        period: '/ месяц',
    },
    '3months': {
        title: 'Тариф «Обойти блокировку»',
        subtitle: '3 месяца доступа ко всем сервисам',
        badge: 'ВЫГОДНО',
        oldPrice: '1 200 ₽',
        priceRaw: '699',
        priceDisplay: '699 ₽',
        period: '/ 3 месяца',
    },
    '6months': {
        title: 'Тариф «Эксперт по ВПН»',
        subtitle: '6 месяцев полной свободы',
        badge: 'ПОПУЛЯРНЫЙ',
        oldPrice: '1 799 ₽',
        priceRaw: '1349',
        priceDisplay: '1 349 ₽',
        period: '/ 6 месяцев',
    },
    '12months': {
        title: 'Тариф «Забудь о Чебурнете»',
        subtitle: '12 месяцев без ограничений',
        badge: 'ЛУЧШАЯ ЦЕНА',
        oldPrice: '4 200 ₽',
        priceRaw: '2499',
        priceDisplay: '2 499 ₽',
        period: '/ 12 месяцев',
    }
};

// ===== ОПРЕДЕЛЕНИЕ ТАРИФА ИЗ URL =====
function getPlanFromURL() {
    const params = new URLSearchParams(window.location.search);
    return params.get('plan') || '6months';
}

// ===== ЗАПОЛНЕНИЕ СТРАНИЦЫ =====
function fillTariffPage() {
    const plan = getPlanFromURL();
    const data = tariffData[plan];
    
    if (!data) return;
    
    // Заголовок
    document.getElementById('tariffTitle').textContent = data.title;
    document.getElementById('tariffSubtitle').textContent = data.subtitle;
    
    // Бейдж
    const badge = document.getElementById('tariffBadge');
    if (data.badge) {
        badge.textContent = data.badge;
        badge.style.visibility = 'visible';
    }
    
    // Цены
    document.getElementById('tariffPrice').textContent = data.priceDisplay;
    document.getElementById('tariffPeriod').textContent = data.period;
    
    const oldPriceEl = document.getElementById('tariffOldPrice');
    if (data.oldPrice) {
        oldPriceEl.textContent = data.oldPrice;
        oldPriceEl.style.display = 'inline';
    } else {
        oldPriceEl.style.display = 'none';
    }
    
    // Кнопки оплаты
    const payButtons = document.querySelectorAll('#payButton, #payButtonBottom');
    payButtons.forEach(btn => {
        btn.innerHTML = `ОПЛАТИТЬ <span class="price-nowrap">${data.priceDisplay}</span>`;
        btn.href = `pay.php?plan=${plan}&price=${data.priceRaw}`;
    });
    
    // Заголовок страницы
    document.title = `Corvin VPN — ${data.title}`;
}

// ===== ЧАСТИЦЫ =====
(function() {
    const canvas = document.getElementById('particles-canvas');
    if (!canvas) return;
    
    const ctx = canvas.getContext('2d');
    let width, height;
    let particles = [];
    
    const MAX_PARTICLES = 40;
    
    function resize() {
        width = window.innerWidth;
        height = window.innerHeight;
        canvas.width = width;
        canvas.height = height;
    }
    
    function createParticles() {
        particles = [];
        for (let i = 0; i < MAX_PARTICLES; i++) {
            particles.push({
                x: Math.random() * width,
                y: Math.random() * height,
                vx: (Math.random() - 0.5) * 0.4,
                vy: (Math.random() - 0.5) * 0.4,
                size: Math.random() * 2 + 0.8,
                opacity: Math.random() * 0.35 + 0.15
            });
        }
    }
    
    function draw() {
        ctx.clearRect(0, 0, width, height);
        particles.forEach(p => {
            p.x += p.vx;
            p.y += p.vy;
            
            if (p.x < -20) p.x = width + 20;
            if (p.x > width + 20) p.x = -20;
            if (p.y < -20) p.y = height + 20;
            if (p.y > height + 20) p.y = -20;
            
            ctx.beginPath();
            ctx.arc(p.x, p.y, p.size, 0, Math.PI * 2);
            ctx.fillStyle = `rgba(0, 170, 255, ${p.opacity})`;
            ctx.fill();
        });
        requestAnimationFrame(draw);
    }
    
    resize();
    createParticles();
    draw();
    
    window.addEventListener('resize', () => {
        resize();
        createParticles();
    });
})();

// ===== ЗАПУСК =====
fillTariffPage();

// ===== ЧЕКБОКС ОФЕРТЫ =====
(function() {
    const checkbox1 = document.getElementById('ofertaAgree');
    const checkbox2 = document.getElementById('ofertaAgreeBottom');
    const payButton1 = document.getElementById('payButton');
    const payButton2 = document.getElementById('payButtonBottom');
    
    if (!checkbox1 || !payButton1) return;
    
    function syncCheckboxes(source, target) {
        target.checked = source.checked;
        updateButtons();
    }
    
    function updateButtons() {
        const isChecked = checkbox1.checked;
        
        [payButton1, payButton2].forEach(btn => {
            if (!btn) return;
            if (isChecked) {
                btn.classList.remove('btn-disabled');
                btn.classList.add('btn-active');
            } else {
                btn.classList.add('btn-disabled');
                btn.classList.remove('btn-active');
            }
        });
    }
    
    checkbox1.addEventListener('change', function() {
        if (checkbox2) syncCheckboxes(checkbox1, checkbox2);
    });
    
    if (checkbox2) {
        checkbox2.addEventListener('change', function() {
            syncCheckboxes(checkbox2, checkbox1);
        });
    }
    
    [payButton1, payButton2].forEach(btn => {
        if (!btn) return;
        btn.addEventListener('click', function(e) {
            if (!checkbox1.checked) {
                e.preventDefault();
                alert('Для продолжения необходимо принять условия Публичной оферты.');
            }
        });
    });
    
    console.log('✅ Оферта подключена');
})();
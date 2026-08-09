// Аккордеоны
document.querySelectorAll('.accordion-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const parent = btn.parentElement;
        parent.classList.toggle('open');
    });
});

// FAQ
document.querySelectorAll('.faq-question').forEach(btn => {
    btn.addEventListener('click', () => {
        const item = btn.parentElement;
        item.classList.toggle('active');
    });
});

// ===== КАСТОМНОЕ ВИДЕО =====
(function() {
    const video = document.getElementById('setupVideo');
    const overlay = document.getElementById('playOverlay');
    const container = document.getElementById('customVideo');
    
    if (!video || !overlay || !container) return;
    
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
    
    video.addEventListener('ended', function() {
        overlay.style.opacity = '1';
    });
    
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
// Кнопка во весь экран
const fullscreenBtn = document.getElementById('fullscreenBtn');
const videoContainer = document.getElementById('customVideo');

if (fullscreenBtn && videoContainer) {
    fullscreenBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        
        if (document.fullscreenElement) {
            // Если уже в полноэкранном режиме — выходим
            document.exitFullscreen();
        } else {
            // Иначе — входим
            if (videoContainer.requestFullscreen) {
                videoContainer.requestFullscreen();
            } else if (videoContainer.webkitRequestFullscreen) {
                videoContainer.webkitRequestFullscreen();
            } else if (videoContainer.msRequestFullscreen) {
                videoContainer.msRequestFullscreen();
            }
        }
    });
    
    // Меняем иконку при входе/выходе из фуллскрина
    document.addEventListener('fullscreenchange', updateFullscreenIcon);
    document.addEventListener('webkitfullscreenchange', updateFullscreenIcon);
    
    function updateFullscreenIcon() {
        const icon = fullscreenBtn.querySelector('svg');
        if (document.fullscreenElement) {
            // Иконка "свернуть"
            icon.innerHTML = `
                <polyline points="4 8 4 3 9 3"></polyline>
                <polyline points="20 16 20 21 15 21"></polyline>
                <line x1="4" y1="3" x2="11" y2="10"></line>
                <line x1="20" y1="21" x2="13" y2="14"></line>
            `;
        } else {
            // Иконка "расширить"
            icon.innerHTML = `
                <polyline points="15 3 21 3 21 9"></polyline>
                <polyline points="9 21 3 21 3 15"></polyline>
                <line x1="21" y1="3" x2="14" y2="10"></line>
                <line x1="3" y1="21" x2="10" y2="14"></line>
            `;
        }
    }
}
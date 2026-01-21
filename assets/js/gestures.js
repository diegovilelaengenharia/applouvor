/**
 * gestures.js
 * Adiciona suporte a swipe (arrastar) em elementos da lista.
 * Inspirado em interações nativas iOS/Android.
 */

document.addEventListener('DOMContentLoaded', () => {
    const listItems = document.querySelectorAll('.list-item, .member-card, .song-card, .swipe-item');

    listItems.forEach(item => {
        let touchStartX = 0;
        let touchEndX = 0;

        item.addEventListener('touchstart', e => {
            touchStartX = e.changedTouches[0].screenX;
        }, { passive: true });

        item.addEventListener('touchend', e => {
            touchEndX = e.changedTouches[0].screenX;
            handleSwipe(item);
        }, { passive: true });

        function handleSwipe(element) {
            const threshold = 50; // Pixels mínimos para considerar swipe
            const diff = touchStartX - touchEndX;

            if (Math.abs(diff) > threshold) {
                if (diff > 0) {
                    // Swipe Left (<<) - Geralmente Ações (Excluir/Editar)
                    // Dispara evento customizado 'swipe-left'
                    element.dispatchEvent(new CustomEvent('swipe-left', { bubbles: true }));

                    // Visual feedback (opcional, pode ser uma classe CSS)
                    element.style.transform = 'translateX(-20px)';
                    setTimeout(() => element.style.transform = 'translateX(0)', 200);
                } else {
                    // Swipe Right (>>)
                    element.dispatchEvent(new CustomEvent('swipe-right', { bubbles: true }));
                }
            }
        }
    });
});

document.addEventListener('DOMContentLoaded', () => {
    const cards = document.querySelectorAll('.gender-card');
    
    cards.forEach(card => {
        card.addEventListener('click', () => {
            // Сначала снимаем выделение со всех карточек
            cards.forEach(c => {
                c.setAttribute('data-selected', 'false');
                c.style.backgroundColor = '';
                c.style.border = '1px solid var(--color-border)';
            });
            
            // Затем выделяем текущую карточку
            card.setAttribute('data-selected', 'true');
            
            // Устанавливаем соответствующие стили в зависимости от типа карточки
            if (card.classList.contains('gender-card__boys')) {
                card.style.backgroundColor = '#F8FDFF';
                card.style.border = '1px solid rgba(93, 106, 255, 0.4)';
            } else if (card.classList.contains('gender-card__girls')) {
                card.style.backgroundColor = '#FFF8FC';
                card.style.border = '1px solid rgba(255, 93, 182, 0.3)';
            } else if (card.classList.contains('gender-card__either')) {
                card.style.backgroundColor = '#F8FFF8';
                card.style.border = '1px solid rgba(46, 212, 74, 0.4)';
            }
        });
    });
}); 
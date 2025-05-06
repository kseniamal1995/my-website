document.addEventListener('DOMContentLoaded', () => {
    const styleCards = document.querySelectorAll('.cardStyle');
    
    styleCards.forEach(card => {
        card.addEventListener('click', () => {
            // Сначала снимаем выделение со всех карточек
            styleCards.forEach(c => {
                c.classList.remove('selected');
            });
            
            // Затем выделяем текущую карточку
            card.classList.add('selected');
        });
    });
}); 
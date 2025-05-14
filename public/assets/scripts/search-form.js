document.addEventListener('DOMContentLoaded', function() {
    let selectedGender = null;
    const selectedStyles = new Set();

    // Обработка выбора пола
    const genderCards = document.querySelectorAll('.gender-card');
    genderCards.forEach(card => {
        card.addEventListener('click', function() {
            // Убираем выделение со всех карточек
            genderCards.forEach(c => {
                c.dataset.selected = 'false';
            });
            
            // Если кликнули на уже выбранную карточку, снимаем выбор
            if (selectedGender === this.querySelector('.gender-card__title').textContent.toLowerCase()) {
                selectedGender = null;
            } else {
                // Иначе выбираем новую карточку
                this.dataset.selected = 'true';
                selectedGender = this.querySelector('.gender-card__title').textContent.toLowerCase();
            }
        });
    });

    // Обработка выбора стилей
    const styleCards = document.querySelectorAll('.cardStyle');
    styleCards.forEach(card => {
        card.addEventListener('click', function() {
            const styleId = this.dataset.slug;
            if (styleId === undefined || this.querySelector('.body1').textContent === 'All') {
                // Если кликнули на "All" или карточка без ID, сбрасываем все выделения
                selectedStyles.clear();
                styleCards.forEach(c => c.classList.remove('selected'));
                if (this.querySelector('.body1').textContent === 'All') {
                    this.classList.add('selected');
                }
            } else {
                // Убираем выделение с "All"
                styleCards[0].classList.remove('selected');
                
                // Переключаем выделение текущей карточки
                this.classList.toggle('selected');
                
                if (this.classList.contains('selected')) {
                    selectedStyles.add(styleId);
                } else {
                    selectedStyles.delete(styleId);
                }
            }
        });
    });

    // Обработка нажатия на кнопку Generate names
    const generateButton = document.querySelector('.buttonPrimary');
    generateButton.addEventListener('click', function() {
        const params = new URLSearchParams();
        
        // Добавляем параметр пола
        if (selectedGender) {
            params.set('gender', selectedGender === 'boys' ? 'm' : (selectedGender === 'girls' ? 'f' : 'n'));
        }
        
        // Добавляем параметр стилей
        if (selectedStyles.size > 0) {
            params.set('styles', Array.from(selectedStyles).join(','));
        }
        
        // Переходим на страницу поиска с параметрами
        window.location.href = '/search/?' + params.toString();
    });
}); 
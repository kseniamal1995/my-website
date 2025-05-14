document.addEventListener('DOMContentLoaded', () => {
    const originSelectors = document.querySelectorAll('.origin-selector');
    
    originSelectors.forEach(originSelector => {
        const button = originSelector.querySelector('.origin-selector__button');
        const menu = originSelector.querySelector('.origin-selector__menu');
        const options = originSelector.querySelectorAll('.origin-selector__option');
        const selectedText = button.querySelector('span');

        button.addEventListener('click', (e) => {
            // Закрываем все другие селекторы
            originSelectors.forEach(otherSelector => {
                if (otherSelector !== originSelector) {
                    otherSelector.querySelector('.origin-selector__button').classList.remove('open');
                    otherSelector.querySelector('.origin-selector__menu').classList.remove('open');
                }
            });

            const isOpen = menu.classList.contains('open');
            button.classList.toggle('open');
            menu.classList.toggle('open');

            if (!isOpen) {
                // При открытии прокручиваем к выбранному элементу
                const selectedOption = menu.querySelector('.selected');
                if (selectedOption) {
                    selectedOption.scrollIntoView({ block: 'nearest' });
                }
            }

            e.stopPropagation();
        });

        options.forEach(option => {
            option.addEventListener('click', () => {
                // Снимаем выделение со всех опций
                options.forEach(opt => opt.classList.remove('selected'));
                // Выделяем выбранную опцию
                option.classList.add('selected');
                // Обновляем текст в кнопке
                selectedText.textContent = option.textContent;
                // Закрываем меню
                button.classList.remove('open');
                menu.classList.remove('open');
            });
        });
    });

    // Закрытие при клике вне селекторов
    document.addEventListener('click', () => {
        originSelectors.forEach(selector => {
            const button = selector.querySelector('.origin-selector__button');
            const menu = selector.querySelector('.origin-selector__menu');
            button.classList.remove('open');
            menu.classList.remove('open');
        });
    });
}); 
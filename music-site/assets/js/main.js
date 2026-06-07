/**
 * Основной JavaScript файл
 */

document.addEventListener('DOMContentLoaded', function() {
    // Инициализация плеера
    initAudioPlayer();
    
    // Обработка форм
    initForms();
    
    
});

/**
 * Инициализация аудио плеера
 */
function initAudioPlayer() {
    const audioElements = document.querySelectorAll('audio');
    
    audioElements.forEach(audio => {
        // При воспроизведении одного трека, остановить другие
        audio.addEventListener('play', function() {
            audioElements.forEach(otherAudio => {
                if (otherAudio !== audio && !otherAudio.paused) {
                    otherAudio.pause();
                }
            });
        });
        
        // Отправка статистики прослушивания
        audio.addEventListener('ended', function() {
            const trackId = this.dataset.trackId;
            if (trackId) {
                sendPlayStatistic(trackId);
            }
        });
    });
}

/**
 * Отправка статистики прослушивания
 */
function sendPlayStatistic(trackId) {
    fetch(window.BASE_PATH + '/api/track-play.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ track_id: trackId })
    }).catch(error => console.error('Ошибка отправки статистики:', error));
}

/**
 * Инициализация форм
 */
function initForms() {
    const forms = document.querySelectorAll('form[data-ajax]');
    
    forms.forEach(form => {
        form.addEventListener('submit', async function(e) {
            const ajaxType = this.dataset.ajax;
            
            if (ajaxType === 'true') {
                e.preventDefault();
                await handleAjaxSubmit(this);
            }
        });
    });
}

/**
 * Обработка AJAX отправки форм
 */
async function handleAjaxSubmit(form) {
    const formData = new FormData(form);
    const action = form.action || window.location.href;
    const method = form.method || 'POST';
    
    try {
        const response = await fetch(action, {
            method: method,
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showAlert(result.message || 'Успешно!', 'success');
            
            if (result.redirect) {
                window.location.href = result.redirect;
            }
        } else {
            showAlert(result.error || 'Ошибка!', 'error');
        }
    } catch (error) {
        showAlert('Произошла ошибка при отправке формы', 'error');
        console.error('Error:', error);
    }
}

/**
 * Инициализация функционала избранного
 */
/* function initFavorites() {
    const favoriteButtons = document.querySelectorAll('[data-favorite-action]');
    
    favoriteButtons.forEach(button => {
        button.addEventListener('click', async function() {
            const trackId = this.dataset.trackId;
            const action = this.dataset.favoriteAction;
            
            if (!trackId) return;
            
            try {
                const response = await fetch(window.BASE_PATH + '/api/favorite.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ 
                        track_id: parseInt(trackId),
                        action: action || 'toggle'
                    })
                });
                
                const text = await response.text();
                console.log('Response text:', text);
                
                let result;
                try {
                    result = JSON.parse(text);
                } catch (e) {
                    console.error('Invalid JSON response:', text.substring(0, 200));
                    throw new Error('Сервер вернул некорректный ответ');
                }
                
                if (result.success) {
                    this.classList.toggle('active');
                    const icon = this.querySelector('i');
                    if (icon) {
                        icon.classList.toggle('fa-heart');
                        icon.classList.toggle('fa-heart-o');
                    }
                } else {
                    console.error('API error:', result.error);
                }
            } catch (error) {
                console.error('Ошибка:', error.message);
            }
        });
    });
} */

/**
 * Показ уведомления
 */
function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.textContent = message;
    
    const container = document.querySelector('.container') || document.body;
    container.insertBefore(alertDiv, container.firstChild);
    
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

/**
 * Загрузка файла с прогрессом
 */
async function uploadFileWithProgress(file, uploadUrl, onProgress) {
    const formData = new FormData();
    formData.append('file', file);
    
    return new Promise((resolve, reject) => {
        const xhr = new XMLHttpRequest();
        
        xhr.upload.addEventListener('progress', function(e) {
            if (e.lengthComputable && onProgress) {
                const percent = Math.round((e.loaded / e.total) * 100);
                onProgress(percent);
            }
        });
        
        xhr.addEventListener('load', function() {
            if (xhr.status === 200) {
                resolve(JSON.parse(xhr.responseText));
            } else {
                reject(new Error('Ошибка загрузки файла'));
            }
        });
        
        xhr.addEventListener('error', function() {
            reject(new Error('Ошибка сети'));
        });
        
        xhr.open('POST', uploadUrl);
        xhr.send(formData);
    });
}

/**
 * Дебаунс функция
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

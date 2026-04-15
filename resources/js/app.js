import './bootstrap';
import './echo';

import Alpine from 'alpinejs';
import { initQuizPlayer } from './quiz-player';
import { initQuizStream } from './stream-show';

window.Alpine = Alpine;
Alpine.start();

document.addEventListener('DOMContentLoaded', () => {
    // Player initialisieren, nur wenn DOM vorhanden
    initQuizPlayer();

    // Stream initialisieren, optional
    if (typeof initQuizStream === 'function') {
        initQuizStream();
    }
});
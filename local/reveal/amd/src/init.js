define([], function() {
    return {
        init: function() {
            if (typeof Reveal !== 'undefined') {
                try {
                    Reveal.initialize({
                        hash: true,
                        slideNumber: false,
                        transition: 'fade',
                        transitionSpeed: 'slow',
                        loop: true,
                        keyboard: true,
                        touch: true,
                        center: true,
                        progress: true,
                        autoSlide: 3000,
                        autoSlideStoppable: false,
                        /*dependencies: [
                            { src: 'plugin/zoom-js/zoom.js', async: true },
                            { src: 'plugin/notes/notes.js', async: true }
                        ]*/
                    });

                    Reveal.addEventListener('slidechanged', function(event) {
                        window.console.log('Slide changed to: ', event.indexh, event.indexv);
                    });

                    Reveal.addEventListener('overviewhidden', function() {
                        window.console.log('Overview mode hidden');
                    });

                } catch (e) {
                    window.console.error('Error initializing Reveal.js:', e);
                }
            } else {
                window.console.error("Reveal.js is not loaded! Please ensure Reveal.js is available.");
            }
        }
    };
});

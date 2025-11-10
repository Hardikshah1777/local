define([], function() {
    return {
        init: function() {
            if (typeof Reveal !== 'undefined') {
                Reveal.initialize({
                    hash: true,
                    slideNumber: true,
                    transition: 'fade',
                    loop: true
                });
            } else {
                window.console.error("Reveal.js not loaded!");
            }
        }
    };
});

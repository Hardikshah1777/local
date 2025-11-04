define(['jquery'], function() {
    return {
        init: function() {
            require(['local_reveal/revealjs/reveal'], function(Reveal) {
                Reveal.initialize({
                    hash: true,
                    slideNumber: true,
                    transition: 'fade'
                });
            });
        }
    };
});

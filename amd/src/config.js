define([], function () {
    window.requirejs.config({
        paths: {
            "block_exaport/jquery-json": M.cfg.wwwroot + '/blocks/exaport/javascript/jquery-json',
            "block_exaport/touchpunch": M.cfg.wwwroot + '/blocks/exaport/javascript/jquery.ui.touch-punch',
            "block_exaport/popover": M.cfg.wwwroot + '/blocks/exaport/javascript/popover',
        },
        shim: {
            'block_exaport/jquery-json': {
                deps: ['jquery'],
                exports: 'json'
            },
            'block_exaport/touchpunch': {
                deps: ['jquery', 'jqueryui'],
                exports: 'touchpunch'
            },
            'block_exaport/popover': {
                deps: ['jquery'],
                exports: 'fu_popover',
            },
        }
    });
});
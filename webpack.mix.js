const mix = require('laravel-mix');

mix.sass('resource/scss/admin.scss', 'www/wp-content/themes/original/css/')
    .sass('resource/scss/style.scss', 'www/wp-content/themes/original/css/')
    .js('resource/js/admin.js', 'www/wp-content/themes/original/js/')
    .js('resource/js/public.js', 'www/wp-content/themes/original/js/')
    .sourceMaps()

module.exports = {
    main: {
        files: [{
            expand: true,
            cwd: 'src/js/',
            src: ['**/*.js'],
            dest: 'build/www/src/js/'
        }, {
            expand: true,
            cwd: 'src/js/',
            src: ['**/*.min.js'],
            dest: 'build/www/dist/js/'
        }, {
            expand: true,
            cwd: 'src/css/',
            src: [
                '**/*.scss',
                '**/*.css'
            ],
            dest: 'build/www/src/css/'
        }, {
            expand: true,
            cwd: 'src/images/',
            src: ['**/*.{png,jpg,jpeg,gif,svg}'],
            dest: 'build/www/dist/images/'
        }, {
            expand: true,
            cwd: 'src/fonts/',
            src: [
                '**/*.dfont',
                '**/*.eot',
               
                '**/*.pfa',
                '**/*.pfb',
                '**/*.pfm',
                
                '**/*.otf',
                '**/*.ttc',
                '**/*.ttf',

                '**/*.svg',
                '**/*.woff',
                '**/*.woff2'
            ],
            dest: 'build/www/dist/fonts/'
        }, {
            expand: true,
            cwd: 'src/templates',
            src: ['**/*.*'],
            dest: 'build/www/'
        }, {
            expand: true,
            cwd: 'src/gatekeeper',
            src: ['**/*.*'],
            dest: 'build/gatekeeper/'
        }]
    },
    cbc: {
        files: [{
            expand: true,
            cwd: 'src/templates',
            src: ['**/*.php'],
            dest: 'build/www/'
        }]
    }
};

module.exports = {
    main: {
        files: [{
            expand: true,
            cwd: 'src/js/',
            src: ['**/*.js'],
            dest: 'build/src/js/'
        }, {
            expand: true,
            cwd: 'src/js/',
            src: ['**/*.min.js'],
            dest: 'build/dist/js/'
        }, {
            expand: true,
            cwd: 'src/css/',
            src: [
                '**/*.scss',
                '**/*.css'
            ],
            dest: 'build/src/css/'
        }, {
            expand: true,
            cwd: 'src/images/',
            src: ['**/*.{png,jpg,jpeg,gif,svg}'],
            dest: 'build/dist/images/'
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
            dest: 'build/dist/fonts/'
        }, {
            expand: true,
            cwd: 'src/templates',
            src: ['**/*.*'],
            dest: 'build/'
        }]
    },
    cbc: {
        files: [{
            expand: true,
            cwd: 'src/templates',
            src: ['**/*.php'],
            dest: 'build/'
        }]
    }
};

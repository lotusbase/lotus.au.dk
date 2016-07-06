module.exports = {
    // Development settings
    dev: {
        options: {
            map: true,
            processors: [
                require('autoprefixer')({browsers: 'last 2 versions'})
            ]
        },
        files: [{
            expand: true,
            cwd: 'build/dist/css',
            src: ['*.css'],
            dest: 'build/dist/css',
            ext: '.css'
        }]
    },
    // Production settings
    prod: {
        options: {
            map: true,
            processors: [
                require('autoprefixer')({browsers: 'last 2 versions'}),
                require('cssnano')({zindex: false})
            ]
        },
        files: [{
            expand: true,
            cwd: 'build/dist/css',
            src: ['*.css'],
            dest: 'build/dist/css',
            ext: '.min.css'
        }]
    }
};
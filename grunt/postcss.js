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
            cwd: 'build/www/dist/css',
            src: ['*.css'],
            dest: 'build/www/dist/css',
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
            cwd: 'build/www/dist/css',
            src: ['*.css'],
            dest: 'build/www/dist/css',
            ext: '.min.css'
        }]
    },

    // Jekyll AMP styles
    jekyll_amp: {
        options: {
            map: false,
            processors: [
                require('autoprefixer')({browsers: 'last 2 versions'}),
                require('cssnano')({zindex: false})
            ]
        },
        files: [{
            expand: true,
            cwd: 'src/blog/_includes/amp',
            src: ['*.css'],
            dest: 'src/blog/_includes/amp',
            ext: '.min.css'
        }]
    }
};
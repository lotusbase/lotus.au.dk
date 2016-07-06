module.exports = {
    main: {
        files: [{
            expand: true,
            cwd: 'src/css',
            src: ['**/*.css', '!**/*.min.css'],
            dest: 'build/dist/css',
            ext: '.min.css'
        }]
    }
};

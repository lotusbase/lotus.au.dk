module.exports = {
    all: {
        files: [{
            expand: true,
            cwd: 'src/images',
            src: '**/*.{png,jpg,jpeg,gif}',
            dest: 'dist/images'
        }]
    }
};
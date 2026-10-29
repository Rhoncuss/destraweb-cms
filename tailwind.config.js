/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        './app/Views/frontend/**/*.php',
        './themes/**/*.php',
        './public/assets/frontend/js/**/*.js',
    ],
    corePlugins: {
        // The public design system ships its own reset in resources/css/frontend.css.
        preflight: false,
    },
    theme: {
        extend: {
            colors: {
                dst: {
                    primary: 'var(--dst-primary)',
                    secondary: 'var(--dst-secondary)',
                    accent: 'var(--dst-accent)',
                    ink: 'var(--dst-ink)',
                    muted: 'var(--dst-muted)',
                    line: 'var(--dst-line)',
                    soft: 'var(--dst-soft)',
                },
            },
            borderRadius: {
                dst: 'var(--dst-radius)',
            },
        },
    },
    plugins: [],
};

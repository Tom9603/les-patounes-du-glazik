import { defineConfig } from 'vite'

export default defineConfig({
    build: {
        outDir: 'public/build',
        rollupOptions: {
            input: {
                animations: './assets/js/animations.js',
                app: './assets/styles/app.scss',
                'admin-calendar': './assets/js/admin-calendar.js',
                'admin-stats': './assets/js/admin-stats.js',
            },
            output: {
                entryFileNames: '[name].js',
                chunkFileNames: '[name].js',
                assetFileNames: '[name][extname]',
            }
        }
    }
})

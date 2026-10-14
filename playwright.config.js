import { defineConfig } from '@playwright/test';

const extensionDirectory = process.env.PHP_TEST_EXTENSION_DIR;
const extensionFlags = extensionDirectory
    ? `-d extension_dir="${extensionDirectory}" -d extension=openssl -d extension=mbstring -d extension=fileinfo -d extension=pdo_sqlite`
    : '';

export default defineConfig({
    testDir: './tests/Browser',
    workers: 1,
    use: { baseURL: 'http://127.0.0.1:8765', browserName: 'chromium', trace: 'retain-on-failure' },
    reporter: 'list',
    webServer: {
        command: `php ${extensionFlags} -S 127.0.0.1:8765 -t . ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php`,
        cwd: 'public',
        url: 'http://127.0.0.1:8765',
        reuseExistingServer: false,
        env: { APP_ENV: 'testing', APP_URL: 'http://localhost:8765', SESSION_DRIVER: 'file', CACHE_STORE: 'array', DB_CONNECTION: 'sqlite', DB_DATABASE: ':memory:' },
    },
});

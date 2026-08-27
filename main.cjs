const { app, BrowserWindow, dialog } = require('electron');
const { spawn, spawnSync } = require('child_process');
const path = require('path');
const http = require('http');
const fs = require('fs');

function copyFolderSync(from, to) {
    if (!fs.existsSync(to)) {
        fs.mkdirSync(to, { recursive: true });
    }
    fs.readdirSync(from).forEach(element => {
        const fromPath = path.join(from, element);
        const toPath = path.join(to, element);
        if (fs.lstatSync(fromPath).isDirectory()) {
            copyFolderSync(fromPath, toPath);
        } else {
            try {
                fs.copyFileSync(fromPath, toPath);
            } catch (e) {
                // ignore write errors
            }
        }
    });
}

/**
 * Lock down file permissions on sensitive app data.
 * macOS/Linux: chmod to restrict to owner only.
 * Windows: icacls to remove inherited permissions and grant only current user.
 */
function lockDownPermissions(appDataDir, databasePath, logPath) {
    const configPath = path.join(appDataDir, 'server_config.json');
    const checksumPath = path.join(appDataDir, 'database.checksum');

    if (process.platform === 'win32') {
        // Windows: use icacls to restrict access to current user only
        const username = process.env.USERNAME || process.env.USER || '';
        if (!username) {
            fs.appendFileSync(logPath, `[SECURITY] WARNING: Could not determine Windows username for ACL lockdown.\n`);
            return;
        }

        const targets = [
            { path: appDataDir, flags: ['/inheritance:r', '/grant:r', `${username}:(OI)(CI)F`] },
            { path: databasePath, flags: ['/inheritance:r', '/grant:r', `${username}:F`] },
        ];

        // Also lock config and checksum files if they exist
        if (fs.existsSync(configPath)) {
            targets.push({ path: configPath, flags: ['/inheritance:r', '/grant:r', `${username}:F`] });
        }
        if (fs.existsSync(checksumPath)) {
            targets.push({ path: checksumPath, flags: ['/inheritance:r', '/grant:r', `${username}:F`] });
        }

        targets.forEach(target => {
            if (fs.existsSync(target.path)) {
                try {
                    const result = spawnSync('icacls', [target.path, ...target.flags], { timeout: 10000 });
                    fs.appendFileSync(logPath, `[SECURITY] icacls ${path.basename(target.path)}: ${result.stdout ? result.stdout.toString().trim() : 'OK'}\n`);
                } catch (e) {
                    fs.appendFileSync(logPath, `[SECURITY] WARNING: Failed to set ACL on ${path.basename(target.path)}: ${e.message}\n`);
                }
            }
        });
    } else {
        // macOS / Linux: use chmod
        const targets = [
            { path: appDataDir, mode: 0o700 },
            { path: databasePath, mode: 0o600 },
        ];

        if (fs.existsSync(configPath)) {
            targets.push({ path: configPath, mode: 0o600 });
        }
        if (fs.existsSync(checksumPath)) {
            targets.push({ path: checksumPath, mode: 0o600 });
        }

        targets.forEach(target => {
            if (fs.existsSync(target.path)) {
                try {
                    fs.chmodSync(target.path, target.mode);
                    fs.appendFileSync(logPath, `[SECURITY] chmod ${target.mode.toString(8)} on ${path.basename(target.path)}\n`);
                } catch (e) {
                    fs.appendFileSync(logPath, `[SECURITY] WARNING: Failed to chmod ${path.basename(target.path)}: ${e.message}\n`);
                }
            }
        });
    }

    fs.appendFileSync(logPath, `[SECURITY] File permission lockdown complete.\n`);
}

let mainWindow;
let phpProcess;
let PORT = 8000;
let serverReady = false;
let timeoutId;

const net = require('net');

function getFreePort(startingPort, callback) {
    const server = net.createServer();
    server.once('error', (err) => {
        if (err.code === 'EADDRINUSE') {
            getFreePort(startingPort + 1, callback);
        } else {
            callback(startingPort);
        }
    });
    server.once('listening', () => {
        const port = server.address().port;
        server.close(() => {
            callback(port);
        });
    });
    server.listen(startingPort, '127.0.0.1');
}

function startPhpServer() {
    const artisanPath = path.join(__dirname, 'artisan');
    const logDir = app.getPath('userData');
    const logPath = path.join(logDir, 'php_server.log');

    // Ensure user data log directory exists
    if (!fs.existsSync(logDir)) {
        fs.mkdirSync(logDir, { recursive: true });
    }

    const storagePath = path.join(logDir, 'storage');
    const databasePath = path.join(logDir, 'database.sqlite');

    // Copy storage folder if it doesn't exist
    if (!fs.existsSync(storagePath)) {
        const sourceStorage = path.join(__dirname, 'storage');
        try {
            copyFolderSync(sourceStorage, storagePath);
        } catch (e) {
            fs.appendFileSync(logPath, `[ERROR] Failed to copy storage folder: ${e.message}\n`);
        }
    }

    // Ensure essential Laravel framework storage directories exist
    const frameworkDirs = [
        path.join(storagePath, 'framework', 'sessions'),
        path.join(storagePath, 'framework', 'views'),
        path.join(storagePath, 'framework', 'cache')
    ];
    frameworkDirs.forEach(dir => {
        if (!fs.existsSync(dir)) {
            fs.mkdirSync(dir, { recursive: true });
        }
    });

    // Ensure database.sqlite exists
    if (!fs.existsSync(databasePath)) {
        try {
            fs.writeFileSync(databasePath, '');
        } catch (e) {
            fs.appendFileSync(logPath, `[ERROR] Failed to create database.sqlite: ${e.message}\n`);
        }
    }

    let phpCommand = 'php';
    const binaryName = process.platform === 'win32' ? 'php.exe' : 'php';
    let vendorPhpPath = path.join(__dirname, 'vendor', 'nativephp', 'nativephp', 'electron', 'resources', 'js', 'resources', 'php', binaryName);
    
    if (vendorPhpPath.includes('app.asar')) {
        vendorPhpPath = vendorPhpPath.replace('app.asar', 'app.asar.unpacked');
    }
    
    if (fs.existsSync(vendorPhpPath)) {
        phpCommand = vendorPhpPath;
    }

    // Write startup log header
    fs.writeFileSync(logPath, `--- LIMS PHP Server Startup Log ---\nDate: ${new Date().toISOString()}\nPHP Executable: ${phpCommand}\nArtisan Path: ${artisanPath}\n\n`);

    const env = Object.assign({}, process.env, {
        NATIVEPHP_RUNNING: 'true',
        NATIVEPHP_STORAGE_PATH: storagePath,
        NATIVEPHP_DATABASE_PATH: databasePath,
        DB_CONNECTION: 'sqlite',
        DB_DATABASE: databasePath,
        LARAVEL_STORAGE_PATH: storagePath,
        APP_URL: `http://127.0.0.1:${PORT}`,
        HOME: process.env.HOME || require('os').homedir()
    });

    // Log all critical env vars for debugging
    fs.appendFileSync(logPath, `ENV HOME: ${env.HOME}\n`);
    fs.appendFileSync(logPath, `ENV NATIVEPHP_DATABASE_PATH: ${env.NATIVEPHP_DATABASE_PATH}\n`);
    fs.appendFileSync(logPath, `ENV DB_DATABASE: ${env.DB_DATABASE}\n`);

    // Run migrations
    fs.appendFileSync(logPath, `Running database migrations...\n`);
    try {
        const migrateProcess = spawnSync(phpCommand, [artisanPath, 'migrate', '--force'], {
            env,
            cwd: __dirname,
            timeout: 30000
        });
        fs.appendFileSync(logPath, `Migrations stdout: ${migrateProcess.stdout ? migrateProcess.stdout.toString() : ''}\n`);
        fs.appendFileSync(logPath, `Migrations stderr: ${migrateProcess.stderr ? migrateProcess.stderr.toString() : ''}\n`);
    } catch (e) {
        fs.appendFileSync(logPath, `[ERROR] Migrations process failed: ${e.message}\n`);
    }

    // Lock down file permissions on sensitive files (cross-platform)
    fs.appendFileSync(logPath, `Applying file permission security lockdown...\n`);
    lockDownPermissions(logDir, databasePath, logPath);

    // Run database integrity verification
    fs.appendFileSync(logPath, `Running database integrity check...\n`);
    try {
        const integrityProcess = spawnSync(phpCommand, [artisanPath, 'desktop:verify-integrity'], {
            env,
            cwd: __dirname,
            timeout: 15000
        });
        const integrityOut = integrityProcess.stdout ? integrityProcess.stdout.toString().trim() : '';
        const integrityErr = integrityProcess.stderr ? integrityProcess.stderr.toString().trim() : '';
        fs.appendFileSync(logPath, `Integrity stdout: ${integrityOut}\n`);
        if (integrityErr) fs.appendFileSync(logPath, `Integrity stderr: ${integrityErr}\n`);

        if (integrityProcess.status === 1) {
            // Tampering detected — show a soft warning (non-blocking)
            fs.appendFileSync(logPath, `[SECURITY] WARNING: Database integrity check FAILED.\n`);
            dialog.showMessageBox({
                type: 'warning',
                title: 'Security Warning',
                message: 'Database Integrity Check Failed',
                detail: 'The local database may have been modified outside the application. ' +
                    'This could indicate unauthorized tampering.\n\n' +
                    'If you did not make external changes, please sync with your server to restore the database.\n\n' +
                    'The application will continue to run, but please review your data.',
                buttons: ['Continue'],
                defaultId: 0,
            });
        } else {
            fs.appendFileSync(logPath, `[SECURITY] Database integrity verified OK.\n`);
        }
    } catch (e) {
        fs.appendFileSync(logPath, `[SECURITY] Integrity check process error: ${e.message}\n`);
    }

    // Ensure PHP binary is executable (fixes EACCES / Gatekeeper issues on some external drives)
    if (phpCommand !== 'php') {
        try {
            fs.chmodSync(phpCommand, 0o755);
        } catch (e) {
            fs.appendFileSync(logPath, `[WARNING] Failed to chmod PHP binary: ${e.message}\n`);
        }
    }

    // Spawn PHP built-in server directly (bypasses artisan serve which loses env vars)
    try {
        phpProcess = spawn(phpCommand, ['-S', `127.0.0.1:${PORT}`, '-t', path.join(__dirname, 'public'), 'server.php'], {
            detached: true,
            stdio: ['ignore', 'pipe', 'pipe'],
            env,
            cwd: __dirname
        });

        phpProcess.stdout.on('data', (data) => {
            fs.appendFileSync(logPath, `[STDOUT] ${data.toString()}`);
        });

        phpProcess.stderr.on('data', (data) => {
            fs.appendFileSync(logPath, `[STDERR] ${data.toString()}`);
        });

        phpProcess.on('error', (err) => {
            fs.appendFileSync(logPath, `[PROCESS_ERROR] Failed to start PHP server: ${err.message}\n`);
            if (!serverReady) {
                dialog.showErrorBox(
                    'LIMS Background Service Error',
                    `Failed to launch the background database service: ${err.message}\n\n` +
                    `If you are on an Apple Silicon Mac, you may need to install Rosetta 2 to run the background service.`
                );
                app.quit();
            }
        });

        phpProcess.on('close', (code) => {
            fs.appendFileSync(logPath, `[PROCESS_CLOSE] PHP process exited with code ${code}\n`);
            if (!serverReady) {
                dialog.showErrorBox(
                    'LIMS Background Service Terminated',
                    `The background database service exited unexpectedly (Code: ${code}).\n\n` +
                    `Log file path: ${logPath}\n\n` +
                    `If you are running the application on Windows for the first time, you may need to install the Microsoft Visual C++ Redistributable (VC++ 2015-2022).`
                );
                app.quit();
            }
        });

        phpProcess.unref();
    } catch (e) {
        fs.appendFileSync(logPath, `[FATAL] PHP Spawn Error: ${e.message}\n`);
        dialog.showErrorBox(
            'LIMS Startup Error',
            `Could not start the application backend because the PHP engine failed to launch.\n\nError: ${e.message}\n\n` +
            `This usually happens if you are running the app from an external drive (like USB/exFAT) that restricts executable permissions, or if there is an architecture mismatch (e.g. running an Intel app on Apple Silicon without Rosetta 2).\n\nPlease try moving the Kipit.app to your Applications folder and open it again.`
        );
        app.quit();
    }
}

function checkServerReady(callback) {
    const request = http.request({
        host: '127.0.0.1',
        port: PORT,
        method: 'GET',
        path: '/'
    }, (response) => {
        serverReady = true;
        if (timeoutId) clearTimeout(timeoutId);
        callback(true);
    });

    request.on('error', () => {
        if (!serverReady) {
            setTimeout(() => checkServerReady(callback), 250);
        }
    });

    request.end();
}

function createWindow() {
    mainWindow = new BrowserWindow({
        width: 1280,
        height: 800,
        title: 'Kipit Desktop Client',
        icon: path.join(__dirname, 'build', 'icon.png'),
        webPreferences: {
            nodeIntegration: false,
            contextIsolation: true
        }
    });

    mainWindow.loadURL(`http://127.0.0.1:${PORT}/`);

    // Toggle developer tools with Cmd+Shift+I (macOS) or Ctrl+Shift+I (Windows)
    mainWindow.webContents.on('before-input-event', (event, input) => {
        if ((input.control || input.meta) && input.shift && input.key.toLowerCase() === 'i') {
            mainWindow.webContents.toggleDevTools();
            event.preventDefault();
        }
    });

    mainWindow.on('closed', () => {
        mainWindow = null;
    });
}

app.on('ready', () => {
    getFreePort(8000, (freePort) => {
        PORT = freePort;
        startPhpServer();
        
        // Set 60-second startup timeout
        timeoutId = setTimeout(() => {
            if (!serverReady) {
                dialog.showErrorBox(
                    'LIMS Startup Timeout',
                    `The LIMS desktop background service took too long to respond (60 seconds timeout).\n\n` +
                    `This usually happens if port ${PORT} is occupied by another application or if the PHP executable lacks necessary system dependencies (like Visual C++ Redistributable on Windows).`
                );
                app.quit();
            }
        }, 60000);

        checkServerReady(() => {
            createWindow();
        });
    });
});

app.on('window-all-closed', () => {
    if (process.platform !== 'darwin') {
        app.quit();
    }
});

app.on('quit', () => {
    if (phpProcess) {
        try {
            process.kill(-phpProcess.pid);
        } catch (e) {
            phpProcess.kill();
        }
    }
});

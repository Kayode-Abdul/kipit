const { app, BrowserWindow, dialog, safeStorage } = require('electron');
const { spawn, spawnSync } = require('child_process');
const path = require('path');
const http = require('http');
const fs = require('fs');
const crypto = require('crypto');

const DB_AT_REST_MAGIC = Buffer.from('KIPITDB1');
const DB_KEY_FILENAME = 'db_at_rest.key';

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

function appendSecurityLog(logPath, message) {
    try {
        fs.appendFileSync(logPath, `[SECURITY] ${message}\n`);
    } catch (e) {
        // ignore logging failures during shutdown
    }
}

/**
 * AES-256-GCM key wrapped by OS keychain/DPAPI via Electron safeStorage.
 * Protects the offline SQLite file when the app is not running.
 */
function getOrCreateDbAtRestKey(appDataDir, logPath) {
    if (!safeStorage || typeof safeStorage.isEncryptionAvailable !== 'function' || !safeStorage.isEncryptionAvailable()) {
        appendSecurityLog(logPath, 'OS secure storage unavailable — skipping DB at-rest encryption.');
        return null;
    }

    const keyPath = path.join(appDataDir, DB_KEY_FILENAME);
    try {
        if (fs.existsSync(keyPath)) {
            const wrapped = fs.readFileSync(keyPath);
            const hex = safeStorage.decryptString(wrapped);
            return Buffer.from(hex, 'hex');
        }

        const key = crypto.randomBytes(32);
        fs.writeFileSync(keyPath, safeStorage.encryptString(key.toString('hex')));
        if (process.platform !== 'win32') {
            try {
                fs.chmodSync(keyPath, 0o600);
            } catch (e) {
                // ignore
            }
        }
        appendSecurityLog(logPath, 'Created new DB at-rest encryption key in OS secure storage.');
        return key;
    } catch (e) {
        appendSecurityLog(logPath, `DB at-rest key error: ${e.message}`);
        return null;
    }
}

function isDbAtRestEncrypted(buffer) {
    return buffer.length >= 8 && buffer.subarray(0, 8).equals(DB_AT_REST_MAGIC);
}

function encryptDbFileAtRest(filePath, key, logPath) {
    if (!fs.existsSync(filePath) || !key) {
        return;
    }

    const data = fs.readFileSync(filePath);
    if (data.length === 0 || isDbAtRestEncrypted(data)) {
        return;
    }

    const iv = crypto.randomBytes(12);
    const cipher = crypto.createCipheriv('aes-256-gcm', key, iv);
    const encrypted = Buffer.concat([cipher.update(data), cipher.final()]);
    const tag = cipher.getAuthTag();
    fs.writeFileSync(filePath, Buffer.concat([DB_AT_REST_MAGIC, iv, tag, encrypted]));
    if (process.platform !== 'win32') {
        try {
            fs.chmodSync(filePath, 0o600);
        } catch (e) {
            // ignore
        }
    }
    appendSecurityLog(logPath, `Encrypted at rest: ${path.basename(filePath)}`);
}

function decryptDbFileAtRest(filePath, key, logPath) {
    if (!fs.existsSync(filePath) || !key) {
        return;
    }

    const data = fs.readFileSync(filePath);
    if (!isDbAtRestEncrypted(data)) {
        return;
    }

    const iv = data.subarray(8, 20);
    const tag = data.subarray(20, 36);
    const encrypted = data.subarray(36);
    const decipher = crypto.createDecipheriv('aes-256-gcm', key, iv);
    decipher.setAuthTag(tag);
    const decrypted = Buffer.concat([decipher.update(encrypted), decipher.final()]);
    fs.writeFileSync(filePath, decrypted);
    if (process.platform !== 'win32') {
        try {
            fs.chmodSync(filePath, 0o600);
        } catch (e) {
            // ignore
        }
    }
    appendSecurityLog(logPath, `Decrypted for use: ${path.basename(filePath)}`);
}

function collectLocalSqlitePaths(appDataDir, databasePath) {
    const paths = new Set();
    if (databasePath) {
        paths.add(databasePath);
    }

    try {
        fs.readdirSync(appDataDir).forEach((name) => {
            if (name === 'database.sqlite' || name.startsWith('kipit_') || name.endsWith('.sqlite')) {
                paths.add(path.join(appDataDir, name));
            }
        });
    } catch (e) {
        // ignore
    }

    const nestedDbDir = path.join(appDataDir, 'database');
    try {
        if (fs.existsSync(nestedDbDir)) {
            fs.readdirSync(nestedDbDir).forEach((name) => {
                if (name.startsWith('kipit_') || name.endsWith('.sqlite')) {
                    paths.add(path.join(nestedDbDir, name));
                }
            });
        }
    } catch (e) {
        // ignore
    }

    return Array.from(paths);
}

function unlockLocalDatabases(appDataDir, databasePath, logPath) {
    const key = getOrCreateDbAtRestKey(appDataDir, logPath);
    if (!key) {
        return;
    }

    collectLocalSqlitePaths(appDataDir, databasePath).forEach((filePath) => {
        try {
            decryptDbFileAtRest(filePath, key, logPath);
        } catch (e) {
            appendSecurityLog(logPath, `Failed to decrypt ${path.basename(filePath)}: ${e.message}`);
            dialog.showErrorBox(
                'Database Unlock Failed',
                'Kipit could not decrypt the local offline database. If this persists after reinstall, contact support.'
            );
            app.quit();
        }
    });
}

function lockLocalDatabases(appDataDir, databasePath, logPath) {
    const key = getOrCreateDbAtRestKey(appDataDir, logPath);
    if (!key) {
        return;
    }

    collectLocalSqlitePaths(appDataDir, databasePath).forEach((filePath) => {
        try {
            encryptDbFileAtRest(filePath, key, logPath);
        } catch (e) {
            appendSecurityLog(logPath, `Failed to encrypt ${path.basename(filePath)}: ${e.message}`);
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
    const dbKeyPath = path.join(appDataDir, DB_KEY_FILENAME);

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
        if (fs.existsSync(dbKeyPath)) {
            targets.push({ path: dbKeyPath, flags: ['/inheritance:r', '/grant:r', `${username}:F`] });
        }

        targets.forEach(target => {
            if (fs.existsSync(target.path)) {
                try {
                    const result = spawnSync('icacls', [target.path, ...target.flags], { timeout: 10000, windowsHide: true });
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
        if (fs.existsSync(dbKeyPath)) {
            targets.push({ path: dbKeyPath, mode: 0o600 });
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
let appDataDirForSecurity = null;
let databasePathForSecurity = null;
let isShuttingDown = false;

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
    const legacyDatabasePath = path.join(logDir, 'database.sqlite');
    const sealedPath = path.join(logDir, 'database.sealed');
    const runtimeDir = path.join(logDir, 'runtime');
    const runtimeDbPath = path.join(runtimeDir, 'database.sqlite');
    appDataDirForSecurity = logDir;
    databasePathForSecurity = runtimeDbPath;

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

    if (!fs.existsSync(runtimeDir)) {
        fs.mkdirSync(runtimeDir, { recursive: true });
    }

    // Migrate legacy database.sqlite → sealed layout (cipher at rest; runtime plaintext only while app runs).
    fs.appendFileSync(logPath, `Preparing sealed/runtime database layout...\n`);
    if (fs.existsSync(legacyDatabasePath) && !fs.existsSync(sealedPath)) {
        try {
            unlockLocalDatabases(logDir, legacyDatabasePath, logPath);
            fs.copyFileSync(legacyDatabasePath, runtimeDbPath);
            fs.renameSync(legacyDatabasePath, sealedPath);
            encryptDbFileAtRest(sealedPath, getOrCreateDbAtRestKey(logDir, logPath), logPath);
            fs.appendFileSync(logPath, `[SECURITY] Migrated legacy database.sqlite to database.sealed\n`);
        } catch (e) {
            fs.appendFileSync(logPath, `[SECURITY] WARNING: legacy DB migrate failed: ${e.message}\n`);
        }
    }

    // Unlock sealed ciphertext into runtime/database.sqlite for this session only.
    if (fs.existsSync(sealedPath)) {
        try {
            const key = getOrCreateDbAtRestKey(logDir, logPath);
            let sealed = fs.readFileSync(sealedPath);
            if (key && isDbAtRestEncrypted(sealed)) {
                const iv = sealed.subarray(8, 20);
                const tag = sealed.subarray(20, 36);
                const encrypted = sealed.subarray(36);
                const decipher = crypto.createDecipheriv('aes-256-gcm', key, iv);
                decipher.setAuthTag(tag);
                sealed = Buffer.concat([decipher.update(encrypted), decipher.final()]);
            }
            fs.writeFileSync(runtimeDbPath, sealed);
            if (process.platform !== 'win32') {
                fs.chmodSync(runtimeDir, 0o700);
                fs.chmodSync(runtimeDbPath, 0o600);
            }
            fs.appendFileSync(logPath, `[SECURITY] Unlocked sealed DB into runtime/database.sqlite\n`);
        } catch (e) {
            fs.appendFileSync(logPath, `[SECURITY] Failed to unlock sealed DB: ${e.message}\n`);
            dialog.showErrorBox('Database Unlock Failed', 'Kipit could not unlock the local offline database.');
            app.quit();
            return;
        }
    }

    if (!fs.existsSync(runtimeDbPath)) {
        try {
            fs.writeFileSync(runtimeDbPath, '');
        } catch (e) {
            fs.appendFileSync(logPath, `[ERROR] Failed to create runtime database.sqlite: ${e.message}\n`);
        }
    }

    let phpCommand = 'php';
    const binaryName = process.platform === 'win32' ? 'php.exe' : 'php';
    let vendorPhpPath = path.join(__dirname, 'vendor', 'nativephp', 'electron', 'resources', 'js', 'resources', 'php', binaryName);
    
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
        NATIVEPHP_DATABASE_PATH: runtimeDbPath,
        DB_CONNECTION: 'sqlite',
        DB_DATABASE: runtimeDbPath,
        LARAVEL_STORAGE_PATH: storagePath,
        APP_URL: `http://127.0.0.1:${PORT}`,
        APP_ENV: 'production',
        APP_DEBUG: 'false',
        HOME: process.env.HOME || require('os').homedir()
    });

    // Log all critical env vars for debugging
    fs.appendFileSync(logPath, `ENV HOME: ${env.HOME}\n`);
    fs.appendFileSync(logPath, `ENV NATIVEPHP_DATABASE_PATH: ${env.NATIVEPHP_DATABASE_PATH}\n`);
    fs.appendFileSync(logPath, `ENV DB_DATABASE: ${env.DB_DATABASE}\n`);

    // Run pending migrations (central + tenant). Additive only — existing SQLite shop data is kept.
    // If app version changed, refresh integrity after migrate so upgrades never false-trigger cloud-wins.
    fs.appendFileSync(logPath, `Running database migrations...\n`);
    let versionFile = '0';
    try {
        const vf = path.join(__dirname, 'version.txt');
        if (fs.existsSync(vf)) {
            versionFile = fs.readFileSync(vf, 'utf8').trim() || '0';
        }
    } catch (e) {
        // ignore
    }
    const versionFlagPath = `${runtimeDbPath}.migrated_version`;
    let lastMigratedVersion = '';
    try {
        if (fs.existsSync(versionFlagPath)) {
            lastMigratedVersion = fs.readFileSync(versionFlagPath, 'utf8').trim();
        }
    } catch (e) {
        // ignore
    }
    const isAppUpgrade = lastMigratedVersion !== versionFile;

    const migratePaths = ['database/migrations', 'database/migrations/tenant'];
    migratePaths.forEach((migratePath) => {
        try {
            fs.appendFileSync(logPath, `Migrating path: ${migratePath}\n`);
            const migrateProcess = spawnSync(
                phpCommand,
                [artisanPath, 'migrate', '--force', `--path=${migratePath}`],
                {
                    env,
                    cwd: __dirname,
                    timeout: 120000,
                    windowsHide: true
                }
            );
            fs.appendFileSync(logPath, `Migrations stdout (${migratePath}): ${migrateProcess.stdout ? migrateProcess.stdout.toString() : ''}\n`);
            fs.appendFileSync(logPath, `Migrations stderr (${migratePath}): ${migrateProcess.stderr ? migrateProcess.stderr.toString() : ''}\n`);
        } catch (e) {
            fs.appendFileSync(logPath, `[ERROR] Migrations failed for ${migratePath}: ${e.message}\n`);
        }
    });

    if (isAppUpgrade) {
        try {
            fs.writeFileSync(versionFlagPath, versionFile);
            fs.appendFileSync(logPath, `[SECURITY] App upgrade ${lastMigratedVersion || '(none)'} → ${versionFile}; refreshing integrity checksum (no cloud purge).\n`);
            const refreshProcess = spawnSync(phpCommand, [artisanPath, 'desktop:verify-integrity', '--refresh'], {
                env,
                cwd: __dirname,
                timeout: 60000,
                windowsHide: true
            });
            fs.appendFileSync(logPath, `Integrity refresh: ${refreshProcess.stdout ? refreshProcess.stdout.toString().trim() : ''}\n`);
        } catch (e) {
            fs.appendFileSync(logPath, `[SECURITY] Post-upgrade integrity refresh error: ${e.message}\n`);
        }
    }

    // Lock down file permissions on sensitive files (cross-platform)
    fs.appendFileSync(logPath, `Applying file permission security lockdown...\n`);
    lockDownPermissions(logDir, runtimeDbPath, logPath);

    // Integrity verify: auto cloud-wins only when this is NOT an upgrade boot
    // (upgrade already refreshed the fingerprint above).
    fs.appendFileSync(logPath, `Running database integrity check...\n`);
    try {
        const integrityArgs = isAppUpgrade
            ? [artisanPath, 'desktop:verify-integrity', '--no-repair']
            : [artisanPath, 'desktop:verify-integrity'];
        const integrityProcess = spawnSync(phpCommand, integrityArgs, {
            env,
            cwd: __dirname,
            timeout: 180000,
            windowsHide: true
        });
        const integrityOut = integrityProcess.stdout ? integrityProcess.stdout.toString().trim() : '';
        const integrityErr = integrityProcess.stderr ? integrityProcess.stderr.toString().trim() : '';
        fs.appendFileSync(logPath, `Integrity status: ${integrityProcess.status === 0 ? 'OK' : 'REPAIR/FAIL'} - ${integrityOut}\n`);
        if (integrityErr) {
            fs.appendFileSync(logPath, `Integrity stderr: ${integrityErr}\n`);
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
            stdio: ['ignore', 'pipe', 'pipe'],
            env,
            cwd: __dirname,
            windowsHide: true
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
                    'Application Error',
                    'A critical background service failed to start. Please restart the application.'
                );
                app.quit();
            }
        });

        phpProcess.on('close', (code) => {
            fs.appendFileSync(logPath, `[PROCESS_CLOSE] PHP process exited with code ${code}\n`);
            if (!serverReady) {
                dialog.showErrorBox(
                    'Service Terminated',
                    'The background service stopped unexpectedly. Please restart the application.'
                );
                app.quit();
            }
        });

        phpProcess.unref();
    } catch (e) {
        fs.appendFileSync(logPath, `[FATAL] PHP Spawn Error: ${e.message}\n`);
        dialog.showErrorBox(
            'Startup Error',
            'Could not start the application. Please ensure you have the necessary permissions and try opening the app again.'
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
                    'Startup Timeout',
                    'The application took too long to start. Please try restarting.'
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

app.on('before-quit', (event) => {
    if (isShuttingDown) {
        return;
    }

    isShuttingDown = true;
    event.preventDefault();

    const logDir = appDataDirForSecurity || app.getPath('userData');
    const logPath = path.join(logDir, 'php_server.log');
    const databasePath = databasePathForSecurity || path.join(logDir, 'database.sqlite');

    if (phpProcess) {
        try {
            process.kill(-phpProcess.pid);
        } catch (e) {
            try {
                phpProcess.kill();
            } catch (e2) {
                // ignore
            }
        }
        phpProcess = null;
    }

    // Give PHP a moment to release SQLite file locks, then seal runtime DB and wipe plaintext.
    setTimeout(() => {
        try {
            const sealedPath = path.join(logDir, 'database.sealed');
            const runtimeDbPath = databasePath || path.join(logDir, 'runtime', 'database.sqlite');
            if (fs.existsSync(runtimeDbPath)) {
                fs.copyFileSync(runtimeDbPath, sealedPath);
                encryptDbFileAtRest(sealedPath, getOrCreateDbAtRestKey(logDir, logPath), logPath);
                try {
                    fs.unlinkSync(runtimeDbPath);
                    appendSecurityLog(logPath, 'Removed runtime plaintext database after sealing.');
                } catch (e) {
                    // If unlink fails, still encrypt in place as fallback
                    encryptDbFileAtRest(runtimeDbPath, getOrCreateDbAtRestKey(logDir, logPath), logPath);
                }
            }
            lockDownPermissions(logDir, sealedPath, logPath);
        } catch (e) {
            appendSecurityLog(logPath, `Shutdown encrypt error: ${e.message}`);
        }
        app.exit(0);
    }, 400);
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

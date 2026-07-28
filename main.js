// main.js
// Electron main process. This is the ONLY new runtime layer added on top of the
// existing web terminal. It creates a native desktop window and loads the
// unchanged front-end (index.html + existing assets/ and public/ files).
// No UI, CSS, or terminal logic is modified here — this file only wraps the app.

const { app, BrowserWindow, shell } = require('electron');
const path = require('path');

let mainWindow = null;

function createWindow() {
  mainWindow = new BrowserWindow({
    width: 1280,
    height: 860,
    minWidth: 900,
    minHeight: 640,
    // Matches the terminal's <meta name="theme-color"> so there is no white
    // flash before the page paints.
    backgroundColor: '#03070c',
    autoHideMenuBar: true,
    title: 'EcoCart Traffic Control',
    webPreferences: {
      preload: path.join(__dirname, 'preload.js'),
      // Security defaults: the renderer stays a plain web page with no Node access,
      // exactly like it ran in the browser. app.js only uses standard web APIs.
      contextIsolation: true,
      nodeIntegration: false,
      sandbox: true,
    },
  });

  // Load the existing front-end from disk. Relative paths inside index.html
  // (public/output.css, assets/app.js, assets/traffic-control-icon.svg) resolve
  // against this file location, so no asset paths need to change.
  mainWindow.loadFile(path.join(__dirname, 'index.html'));

  // Open any external links (e.g. the lucide CDN if a link were clicked) in the
  // user's real browser instead of inside the app window.
  mainWindow.webContents.setWindowOpenHandler(({ url }) => {
    shell.openExternal(url);
    return { action: 'deny' };
  });

  mainWindow.on('closed', () => {
    mainWindow = null;
  });
}

app.whenReady().then(() => {
  createWindow();

  // macOS: re-create a window when the dock icon is clicked and none are open.
  app.on('activate', () => {
    if (BrowserWindow.getAllWindows().length === 0) {
      createWindow();
    }
  });
});

// Quit when all windows are closed, except on macOS where apps stay active.
app.on('window-all-closed', () => {
  if (process.platform !== 'darwin') {
    app.quit();
  }
});

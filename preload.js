// preload.js
// Runs in an isolated, sandboxed context BEFORE the web page loads.
//
// The existing terminal (index.html + assets/app.js) only uses standard browser
// APIs (DOM, localStorage, Fullscreen, timers), all of which Electron's renderer
// already provides. So there is nothing to bridge and this file deliberately
// stays empty of exposed APIs — it exists to keep the secure default
// (contextIsolation on, nodeIntegration off) and as the place to add a
// contextBridge API later if the app ever needs native features.
//
// Keeping it a no-op guarantees the front-end behaves byte-for-byte like the
// original web version.

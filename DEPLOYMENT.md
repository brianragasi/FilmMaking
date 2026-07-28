# EcoCart Deployment

## Production Address

EcoCart is moving to:

```text
https://ecocart.whf.bz
```

If SSL is not ready yet, use:

```text
http://ecocart.whf.bz
```

The GoogieHost FTP account in the control panel should point to the domain root, with the public website files inside `/public_html/`.

## GitHub Secrets

Open the `brianragasi/FilmMaking` repository:

**Settings > Secrets and variables > Actions > New repository secret**

Create these secrets:

| Secret | Value |
| --- | --- |
| `GOOGIEHOST_FTP_SERVER` | GoogieHost FTP server, usually the server host shown in the panel such as `cloud3.googiehost.com` |
| `GOOGIEHOST_FTP_USERNAME` | FTP username shown in GoogieHost, for example `admin@ecocart.whf.bz` |
| `GOOGIEHOST_FTP_PASSWORD` | The FTP password after you rotate it |

Never commit the FTP password to GitHub or paste it into a PHP file.

## First Deployment

The workflow at `.github/workflows/deploy-googiehost.yml` runs after every push to `main`. It builds `public/output.css`, validates the JavaScript, and synchronizes the public runtime files to `/public_html/` over FTPS.

The deployment intentionally excludes local filming and development files:

- `attacker-terminal.php`
- `index.html`
- `main.js`
- `preload.js`
- Electron build folders
- docs, story files, database scripts, logs, and local config

Use the attacker/traffic-control prop locally in XAMPP for filming. Do not upload it to free public hosting.

## Database Setup

The database is not created by FTP deployment:

1. Create a MySQL database in GoogieHost.
2. Open phpMyAdmin, select the new database, and import `database/schema.sql`.
3. In GoogieHost File Manager, create `includes/config.local.php`.
4. Use `includes/config.local.example.php` as the shape of the file and fill in the exact MySQL hostname, database name, username, and password from GoogieHost.

`includes/config.local.php` is excluded from Git and automatic deployment, so future pushes will not expose or overwrite the production password.

## Normal Update Flow

```bash
git add .
git commit -m "Describe the change"
git push
```

The GitHub **Actions** tab shows whether the GoogieHost deployment succeeded.

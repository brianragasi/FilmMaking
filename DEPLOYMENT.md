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
| `GOOGIEHOST_DB_HOST` | MySQL hostname shown by GoogieHost, for example `localhost` |
| `GOOGIEHOST_DB_NAME` | Database name shown by GoogieHost |
| `GOOGIEHOST_DB_USER` | Database username shown by GoogieHost |
| `GOOGIEHOST_DB_PASSWORD` | Database password after you rotate it |

Never commit the FTP password to GitHub or paste it into a PHP file.

## First Deployment

The workflow at `.github/workflows/deploy-googiehost.yml` runs after every push to `main`. It builds `public/output.css`, validates the JavaScript, creates `includes/config.local.php` from GitHub secrets, and synchronizes the public runtime files to `/public_html/` over FTPS.

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
3. Add the database values as GitHub Actions secrets.
4. Push to `main` and let the workflow upload the generated `includes/config.local.php`.

`includes/config.local.php` is generated during deployment and is still excluded from Git, so future pushes will not expose the production password.

## Normal Update Flow

```bash
git add .
git commit -m "Describe the change"
git push
```

The GitHub **Actions** tab shows whether the GoogieHost deployment succeeded.

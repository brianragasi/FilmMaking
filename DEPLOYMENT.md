# EcoCart Deployment

## Production address

EcoCart is deployed to:

```text
https://ecocart-mnl.site.je
```

The InfinityFree document root is `/ecocart-mnl.site.je/htdocs/`. If the domain
health check says the directory is missing, use **Recreate Directory** before
running the deployment workflow.

## 2. Add GitHub deployment secrets

Open the `brianragasi/FilmMaking` repository and go to:

**Settings > Secrets and variables > Actions > New repository secret**

Create both secrets:

| Secret | Value |
| --- | --- |
| `INFINITYFREE_FTP_USERNAME` | Username shown under InfinityFree **FTP Details** |
| `INFINITYFREE_FTP_PASSWORD` | Password shown under InfinityFree **FTP Details** |

Never put the FTP password directly in the workflow or any PHP file committed
to GitHub.

## 3. First deployment

The workflow at `.github/workflows/deploy-infinityfree.yml` runs after every
push to `main`. It can also be started from the repository's **Actions** tab
using **Run workflow**.

It builds `public/output.css`, validates `assets/app.js`, and synchronizes the
runtime files to `/ecocart-mnl.site.je/htdocs/` over FTPS.

## 4. Configure the InfinityFree database

The database is not created by FTP deployment:

1. Create a MySQL database in InfinityFree.
2. Open its phpMyAdmin and import `database/schema.sql`.
3. In InfinityFree File Manager, copy `includes/config.local.example.php` to
   `includes/config.local.php`.
4. Replace the example values with the exact MySQL hostname, database name,
   username, and password from the InfinityFree panel.

`includes/config.local.php` is excluded from Git and automatic deployment, so
future pushes will not expose or overwrite the production password.

## 5. Normal update flow

```bash
git add .
git commit -m "Describe the change"
git push
```

The GitHub **Actions** tab shows whether the InfinityFree deployment succeeded.

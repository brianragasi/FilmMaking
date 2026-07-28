# EcoCart Deployment

## 1. Connect highlandfresh.codes

In Name.com, open `highlandfresh.codes`, choose **Manage Nameservers**, and
replace the current nameservers with:

```text
ns1.infinityfree.com
ns2.infinityfree.com
```

Do not enter these in the A-record form. Nameserver changes can take from a few
hours to a few days to propagate.

In InfinityFree, return to **Add Domain**:

1. Enter `highlandfresh.codes`.
2. Select `highlandfresh.infinityfree.io/htdocs` as the directory alias.
3. Click **Add Domain**.

Using the alias makes the free subdomain and custom domain serve the same
`/htdocs` files.

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
runtime files to `/htdocs/` over FTPS.

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


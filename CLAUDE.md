# Sena Crafted website — notes for Claude

- Live site: https://www.senacrafted.com, WordPress + WooCommerce on Bluehost. The static site in `legacy-static-site/` is retired; do not edit it expecting changes to go live.
- `theme/sena-crafted/` and `plugin/sena-studio/` are the sources that matter. Keep each folder's README accurate when behaviour changes.
- `*.zip` files are build artifacts and are git-ignored. Rebuild them from the source folders when a full upload is needed.
- Never commit anything from `.ssh/` (Bluehost deploy key) or any wp-config / credentials.
- WooPayments on the live site is connected to a real bank account: never place real test checkouts.
- Bluehost caches pages for 2 hours; purge the cache after deploying theme/CSS changes.

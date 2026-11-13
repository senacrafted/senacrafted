# Sena Crafted — senacrafted.com

Source for the Sena Crafted website. The live site is WordPress + WooCommerce on Bluehost (cut over 12 Nov 2026); the previous static GitHub Pages site is kept for reference.

| Folder | What it is |
| --- | --- |
| `theme/sena-crafted/` | WordPress theme (classic PHP + WooCommerce templates). See `theme/README.md`. |
| `plugin/sena-studio/` | "Studio" admin plugin: phone-first orders / pieces / requests / posts / events screen. See `plugin/README.md`. |
| `migration/` | Migration kit: `woocommerce-products.csv` used to import the original products. |
| `legacy-static-site/` | The old one-page static site that ran on GitHub Pages (products in `works.js`). No longer deployed. |

## Deploying

Theme and plugin live on the server under `wp-content/themes/sena-crafted` and `wp-content/plugins/sena-studio`. Copy changed files over with `scp` (or rebuild the zips with `zip -r sena-crafted.zip sena-crafted` inside `theme/`, same for the plugin, and upload through wp-admin). Zips are git-ignored; they are build output.

After deploying CSS or template changes, purge the Bluehost page cache (wp-admin toolbar → Caching → Purge All).

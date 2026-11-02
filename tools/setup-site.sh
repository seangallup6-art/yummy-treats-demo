#!/usr/bin/env bash
# Assembles ./site (a runnable WordPress) from GitHub only — no wordpress.org access needed.
# Run once, then `npm run dev`. Re-run to upgrade; the SQLite database in site/ is kept.
set -euo pipefail
cd "$(dirname "$0")/.."
WP_TAG="${WP_TAG:-7.1.4}"
WC_TAG="${WC_TAG:-11.2.1}"
mkdir -p dl site
if [ ! -d dl/wp ]; then
  echo "• WordPress $WP_TAG from GitHub"
  git clone --depth 1 --branch "$WP_TAG" https://github.com/WordPress/WordPress dl/wp
fi
if [ ! -f dl/woocommerce.zip ]; then
  echo "• WooCommerce $WC_TAG release zip"
  curl -fL -o dl/woocommerce.zip "https://github.com/woocommerce/woocommerce/releases/download/$WC_TAG/woocommerce.zip"
fi
if [ ! -d dl/omise ]; then
  echo "• Opn (Omise) WooCommerce plugin"
  git clone --depth 1 https://github.com/omise/omise-woocommerce dl/omise
fi
echo "• Copying into site/ (database and uploads are kept)"
rsync -a --exclude .git --exclude wp-content/uploads --exclude wp-content/database dl/wp/ site/ 2>/dev/null || cp -rn dl/wp/. site/
mkdir -p site/wp-content/plugins
rm -rf site/wp-content/plugins/woocommerce && unzip -qo dl/woocommerce.zip -d site/wp-content/plugins/
rm -rf site/wp-content/plugins/omise && mkdir -p site/wp-content/plugins/omise && cp -r dl/omise/. site/wp-content/plugins/omise/ && rm -rf site/wp-content/plugins/omise/.git
for z in vendor/*.zip; do [ -f "$z" ] && unzip -qo "$z" -d site/wp-content/plugins/ && echo "• unzipped $z"; done
echo "Done. Now: npm run dev  →  http://127.0.0.1:9400"

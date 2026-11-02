<?php
/**
 * WordPress Playground compatibility. Playground stores the site in SQLite, and a few plugin queries are written for
 * MySQL only. Each shim below switches off one MySQL-only feature when (and only when) the database is SQLite;
 * on a normal MySQL host nothing here does anything.
 *
 * @package YummyTreats
 */

defined( 'ABSPATH' ) || exit;

/**
 * Is this site running on SQLite (WordPress Playground)?
 *
 * @return bool
 */
function yt_is_sqlite() {
	return defined( 'DB_ENGINE' ) && 'sqlite' === DB_ENGINE;
}

/**
 * WooCommerce holds stock for 60 minutes while a customer pays, with an INSERT … SELECT … ON DUPLICATE KEY UPDATE into
 * wc_reserved_stock. SQLite rejects it and checkout then says "Not enough units … in stock" for every item.
 *
 * @param bool $hold Hold stock during checkout.
 * @return bool
 */
function yt_sqlite_skip_stock_hold( $hold ) {
	return yt_is_sqlite() ? false : $hold;
}
add_filter( 'woocommerce_hold_stock_for_checkout', 'yt_sqlite_skip_stock_hold' );

/**
 * TranslatePress logs its MySQL-only queries as "SQL errors" and shows a red admin notice. The features those queries
 * serve are already off (gettext through TranslatePress, see seed/seed.php), so on SQLite the notice is noise.
 *
 * @param bool $disabled Error manager disabled.
 * @return bool
 */
function yt_sqlite_quiet_translatepress( $disabled ) {
	return yt_is_sqlite() ? true : $disabled;
}
add_filter( 'trp_disable_error_manager', 'yt_sqlite_quiet_translatepress' );

/**
 * TranslatePress's dynamic translation re-translates text that JavaScript adds after load, through an AJAX lookup.
 * On SQLite that lookup cannot write, so it blanks text it does not know and slows the block cart. Everything the
 * theme and WooCommerce add with JavaScript already arrives in Thai (wp.i18n overrides and the theme's .mo), so on
 * Playground it is switched off.
 *
 * @param bool $enabled Dynamic translation on.
 * @return bool
 */
function yt_sqlite_skip_dynamic_translation( $enabled ) {
	return yt_is_sqlite() ? false : $enabled;
}
add_filter( 'trp_enable_dynamic_translation', 'yt_sqlite_skip_dynamic_translation' );

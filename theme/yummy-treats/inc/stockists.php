<?php
/**
 * Stockists: a tiny custom post type with province, district and type taxonomies.
 * The Stockists page lists them with a Query Loop and a small filter script.
 *
 * @package YummyTreats
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register the post type and its three taxonomies.
 */
function yt_register_stockists() {
	register_post_type(
		'yt_stockist',
		array(
			'labels'       => array(
				'name'          => __( 'Stockists', 'yummy-treats' ),
				'singular_name' => __( 'Stockist', 'yummy-treats' ),
				'add_new_item'  => __( 'Add stockist', 'yummy-treats' ),
				'edit_item'     => __( 'Edit stockist', 'yummy-treats' ),
			),
			'public'       => true,
			'has_archive'  => false,
			'show_in_rest' => true,
			'menu_icon'    => 'dashicons-store',
			'supports'     => array( 'title', 'editor', 'custom-fields' ),
			'rewrite'      => array( 'slug' => 'stockist' ),
		)
	);

	$tax = function ( $slug, $singular, $plural ) {
		register_taxonomy(
			$slug,
			'yt_stockist',
			array(
				'labels'            => array(
					'name'          => $plural,
					'singular_name' => $singular,
				),
				'public'            => true,
				'hierarchical'      => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
			)
		);
	};
	$tax( 'yt_province', __( 'Province', 'yummy-treats' ), __( 'Provinces', 'yummy-treats' ) );
	$tax( 'yt_district', __( 'District', 'yummy-treats' ), __( 'Districts', 'yummy-treats' ) );
	$tax( 'yt_stockist_type', __( 'Type', 'yummy-treats' ), __( 'Types', 'yummy-treats' ) );

	// Address and phone as plain meta, shown in the REST API so the editor can bind to them.
	foreach ( array( 'address', 'phone', 'maps_url' ) as $key ) {
		register_post_meta(
			'yt_stockist',
			'yt_' . $key,
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_text_field',
			)
		);
	}
}
add_action( 'init', 'yt_register_stockists' );

/**
 * [yt_stockists] — the full list, grouped by district, with type chips to filter (shop.js).
 * A shortcode rather than a Query Loop because the Query Loop cannot print post meta (address, phone) without extra blocks.
 *
 * @return string
 */
function yt_stockists_shortcode() {
	$posts = get_posts(
		array(
			'post_type'      => 'yt_stockist',
			'posts_per_page' => 200,
			'orderby'        => 'title',
			'order'          => 'ASC',
		)
	);
	if ( ! $posts ) {
		return '<p class="yt-placeholder">' . esc_html__( '[No stockists added yet]', 'yummy-treats' ) . '</p>';
	}

	$types  = get_terms( array( 'taxonomy' => 'yt_stockist_type', 'hide_empty' => true ) );
	$groups = array();
	foreach ( $posts as $p ) {
		$district = get_the_terms( $p, 'yt_district' );
		$label    = $district && ! is_wp_error( $district ) ? $district[0]->name : __( 'Other', 'yummy-treats' );
		$groups[ $label ][] = $p;
	}
	ksort( $groups );

	ob_start();
	?>
	<div class="yt-stockists" data-yt-stockists>
		<?php if ( $types && ! is_wp_error( $types ) ) : ?>
		<div class="yt-filterbar yt-stockists__filters" role="group" aria-label="<?php esc_attr_e( 'Filter by type', 'yummy-treats' ); ?>">
			<button type="button" class="yt-chip" data-type="" aria-pressed="true"><?php esc_html_e( 'All', 'yummy-treats' ); ?></button>
			<?php foreach ( $types as $t ) : ?>
			<button type="button" class="yt-chip" data-type="<?php echo esc_attr( $t->slug ); ?>" aria-pressed="false"><?php echo esc_html( $t->name ); ?> <span><?php echo (int) $t->count; ?></span></button>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>
		<?php $n = 0; foreach ( $groups as $district => $items ) : ?>
		<section class="yt-stockists__group">
			<h2><?php echo esc_html( $district ); ?></h2>
			<?php foreach ( $items as $p ) : ?>
				<?php
				++$n;
				$type  = get_the_terms( $p, 'yt_stockist_type' );
				$tslug = $type && ! is_wp_error( $type ) ? $type[0]->slug : '';
				$tname = $type && ! is_wp_error( $type ) ? $type[0]->name : '';
				$addr  = get_post_meta( $p->ID, 'yt_address', true );
				$phone = get_post_meta( $p->ID, 'yt_phone', true );
				$maps  = get_post_meta( $p->ID, 'yt_maps_url', true );
				?>
				<article class="wp-block-post" data-type="<?php echo esc_attr( $tslug ); ?>">
					<span class="yt-num" aria-hidden="true"><?php echo (int) $n; ?></span>
					<div>
						<h3 class="wp-block-post-title"><?php echo esc_html( get_the_title( $p ) ); ?></h3>
						<p class="yt-stockists__meta"><?php echo esc_html( $addr ); ?><?php if ( $phone ) : ?> · <a href="tel:<?php echo esc_attr( preg_replace( '/\D/', '', $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a><?php endif; ?><?php if ( $maps ) : ?> · <a href="<?php echo esc_url( $maps ); ?>" rel="noopener"><?php esc_html_e( 'Map', 'yummy-treats' ); ?></a><?php endif; ?></p>
					</div>
					<?php if ( $tname ) : ?><span class="taxonomy-yt_stockist_type"><?php echo esc_html( $tname ); ?></span><?php endif; ?>
				</article>
			<?php endforeach; ?>
		</section>
		<?php endforeach; ?>
	</div>
	<?php
	// No whitespace between tags: WordPress's wpautop would otherwise wrap stray <p> tags around it and break the card grid.
	return preg_replace( '/>\s+</', '><', ob_get_clean() );
}
add_shortcode( 'yt_stockists', 'yt_stockists_shortcode' );

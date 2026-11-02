<?php
/**
 * Bank slip upload.
 *
 * Top-down:
 *  1. Customer side: a box on the order-received page and on My Account → View order (bank transfer orders only).
 *     It posts the file to admin-post.php, which checks the order key, the file type and size, and stores it.
 *  2. Storage: wp-content/uploads/yt-slips/, random file names, direct web access denied (.htaccess on Apache;
 *     random names elsewhere). Files are streamed through admin-post.php to staff, or to the customer holding the order key.
 *  3. Shop side: a "Payment slip" box on the order screen with a one-click "mark as paid", a Slip column in the
 *     orders list, and a link to the upload box in the on-hold email.
 *
 * Nothing is added to the Media Library: slips hold bank details and names (PDPA), so they stay attached to the order only.
 *
 * @package YTSlipUpload
 */

defined( 'ABSPATH' ) || exit;

/**
 * Plugin controller. Stateless; every method is static.
 */
final class YT_Slip_Upload {

	const META   = '_yt_slip';
	const FOLDER = 'yt-slips';

	/**
	 * Hook everything up.
	 */
	public static function init() {
		load_plugin_textdomain( 'yt-slip-upload', false, dirname( plugin_basename( YT_SLIP_UPLOAD_FILE ) ) . '/languages' );

		// Customer side.
		foreach ( self::methods() as $method ) {
			add_action( 'woocommerce_thankyou_' . $method, array( __CLASS__, 'render_for_order_id' ), 20 );
		}
		add_action( 'woocommerce_view_order', array( __CLASS__, 'render_for_order_id' ), 5 );
		add_filter( 'woocommerce_my_account_my_orders_actions', array( __CLASS__, 'my_orders_action' ), 10, 2 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'assets' ) );

		add_action( 'admin_post_yt_slip_upload', array( __CLASS__, 'handle_upload' ) );
		add_action( 'admin_post_nopriv_yt_slip_upload', array( __CLASS__, 'handle_upload' ) );
		add_action( 'admin_post_yt_slip_view', array( __CLASS__, 'handle_view' ) );
		add_action( 'admin_post_nopriv_yt_slip_view', array( __CLASS__, 'handle_view' ) );

		// Shop side.
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_metabox' ) );
		add_action( 'admin_post_yt_slip_mark_paid', array( __CLASS__, 'handle_mark_paid' ) );
		add_filter( 'manage_woocommerce_page_wc-orders_columns', array( __CLASS__, 'add_column' ) );
		add_action( 'manage_woocommerce_page_wc-orders_custom_column', array( __CLASS__, 'render_column' ), 10, 2 );
		add_filter( 'manage_edit-shop_order_columns', array( __CLASS__, 'add_column' ) );
		add_action( 'manage_shop_order_posts_custom_column', array( __CLASS__, 'render_column' ), 10, 2 );
		add_action( 'woocommerce_email_before_order_table', array( __CLASS__, 'email_link' ), 20, 4 );
	}

	/* --------------------------------------------------------------------- settings (filters) */

	/**
	 * Payment methods that use slips. Default: WooCommerce's "Direct bank transfer".
	 *
	 * @return string[]
	 */
	public static function methods() {
		return (array) apply_filters( 'yt_slip_upload_methods', array( 'bacs' ) );
	}

	/**
	 * Order statuses in which a slip can still be sent.
	 *
	 * @return string[]
	 */
	public static function open_statuses() {
		return (array) apply_filters( 'yt_slip_upload_statuses', array( 'pending', 'on-hold' ) );
	}

	/**
	 * Largest accepted file, in bytes. Default 5 MB: a phone photo of a slip is 1–3 MB.
	 *
	 * @return int
	 */
	public static function max_bytes() {
		return (int) apply_filters( 'yt_slip_upload_max_bytes', 5 * MB_IN_BYTES );
	}

	/**
	 * Accepted types. Thai banking apps save slips as JPG or PNG; some customers send a PDF statement.
	 *
	 * @return array<string,string>
	 */
	public static function mimes() {
		return (array) apply_filters(
			'yt_slip_upload_mimes',
			array(
				'jpg|jpeg' => 'image/jpeg',
				'png'      => 'image/png',
				'webp'     => 'image/webp',
				'pdf'      => 'application/pdf',
			)
		);
	}

	/* --------------------------------------------------------------------- model */

	/**
	 * Does this order pay by a slip method?
	 *
	 * @param WC_Order $order Order.
	 * @return bool
	 */
	public static function applies( $order ) {
		return $order instanceof WC_Order && in_array( $order->get_payment_method(), self::methods(), true );
	}

	/**
	 * Can the customer still send (or replace) a slip?
	 *
	 * @param WC_Order $order Order.
	 * @return bool
	 */
	public static function is_open( $order ) {
		return self::applies( $order ) && in_array( $order->get_status(), self::open_statuses(), true );
	}

	/**
	 * The stored slip, or null.
	 *
	 * @param WC_Order $order Order.
	 * @return array|null
	 */
	public static function get_slip( $order ) {
		if ( ! $order instanceof WC_Order ) {
			return null;
		}
		$slip = $order->get_meta( self::META, true );
		return ( is_array( $slip ) && ! empty( $slip['file'] ) && file_exists( self::dir() . '/' . $slip['file'] ) ) ? $slip : null;
	}

	/**
	 * Storage folder, created on first use with web access denied.
	 *
	 * @return string
	 */
	public static function dir() {
		$uploads = wp_upload_dir( null, false );
		$dir     = trailingslashit( $uploads['basedir'] ) . self::FOLDER;
		if ( ! is_dir( $dir ) ) {
			wp_mkdir_p( $dir );
		}
		if ( ! file_exists( $dir . '/.htaccess' ) ) {
			// Apache 2.4 and 2.2. On nginx add: location ~ /uploads/yt-slips/ { deny all; }
			file_put_contents( $dir . '/.htaccess', "Require all denied\n<IfModule !mod_authz_core.c>\n\tDeny from all\n</IfModule>\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		}
		if ( ! file_exists( $dir . '/index.php' ) ) {
			file_put_contents( $dir . '/index.php', "<?php\n// Silence is golden.\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		}
		return $dir;
	}

	/**
	 * URL that streams the slip: staff can always open it; the customer needs the order key.
	 *
	 * @param WC_Order $order    Order.
	 * @param bool     $with_key Include the order key (customer-facing links).
	 * @return string
	 */
	public static function view_url( $order, $with_key = false ) {
		$args = array(
			'action' => 'yt_slip_view',
			'order'  => $order->get_id(),
		);
		if ( $with_key ) {
			$args['key'] = $order->get_order_key();
		}
		return add_query_arg( $args, admin_url( 'admin-post.php' ) );
	}

	/**
	 * Save a file as the order's slip, replacing any earlier one.
	 *
	 * @param WC_Order $order         Order.
	 * @param string   $source        Path of the incoming file.
	 * @param string   $ext           Checked extension.
	 * @param string   $type          Checked MIME type.
	 * @param string   $original_name Name the customer's device gave it.
	 * @param bool     $is_upload     True for a fresh HTTP upload (move_uploaded_file), false for a local copy.
	 * @return bool
	 */
	private static function store( $order, $source, $ext, $type, $original_name, $is_upload ) {
		$dir  = self::dir();
		$name = $order->get_id() . '-' . strtolower( wp_generate_password( 24, false ) ) . '.' . $ext;
		$dest = $dir . '/' . $name;

		$ok = $is_upload ? @move_uploaded_file( $source, $dest ) : @copy( $source, $dest ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		if ( ! $ok ) {
			return false;
		}

		$previous = self::get_slip( $order );
		if ( $previous ) {
			wp_delete_file( $dir . '/' . $previous['file'] );
		}

		$slip = array(
			'file'     => $name,
			'type'     => $type,
			'size'     => (int) filesize( $dest ),
			'uploaded' => time(),
			'name'     => sanitize_file_name( $original_name ),
		);
		$order->update_meta_data( self::META, $slip );
		$order->add_order_note(
			sprintf(
				/* translators: %s: file name */
				__( 'Payment slip received (%s). Check it against your bank statement, then mark the order as Processing.', 'yt-slip-upload' ),
				$slip['name']
			)
		);
		$order->save();

		/**
		 * A slip was stored. Hook here to notify staff (LINE Notify, email, Slack…).
		 *
		 * @param WC_Order $order Order.
		 * @param array    $slip  Stored slip data.
		 */
		do_action( 'yt_slip_uploaded', $order, $slip );
		return true;
	}

	/**
	 * Attach a local file (seeding and tests). Same checks as an upload, minus the HTTP part.
	 *
	 * @param WC_Order $order Order.
	 * @param string   $path  File path.
	 * @return bool
	 */
	public static function attach_file( $order, $path ) {
		if ( ! self::applies( $order ) || ! is_readable( $path ) ) {
			return false;
		}
		$check = wp_check_filetype_and_ext( $path, basename( $path ), self::mimes() );
		if ( empty( $check['ext'] ) || empty( $check['type'] ) ) {
			return false;
		}
		return self::store( $order, $path, $check['ext'], $check['type'], basename( $path ), false );
	}

	/* --------------------------------------------------------------------- customer side */

	/**
	 * Hook target for the thank-you and view-order pages.
	 *
	 * @param int $order_id Order ID.
	 */
	public static function render_for_order_id( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( self::applies( $order ) ) {
			self::render_box( $order );
		}
	}

	/**
	 * The upload box. Markup is plain and class-based so any theme can style it.
	 *
	 * @param WC_Order $order Order.
	 */
	public static function render_box( $order ) {
		$slip   = self::get_slip( $order );
		$open   = self::is_open( $order );
		$notice = isset( $_GET['yt-slip'] ) ? sanitize_key( wp_unslash( $_GET['yt-slip'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		if ( ! $slip && ! $open ) {
			return;
		}

		$messages = array(
			'ok'          => array( 'status', __( 'Slip received. Thank you!', 'yt-slip-upload' ) ),
			'no-file'     => array( 'alert', __( 'Choose a photo or screenshot of your slip first.', 'yt-slip-upload' ) ),
			/* translators: %s: size limit, e.g. 5 MB */
			'too-big'     => array( 'alert', sprintf( __( 'That file is larger than %s. A screenshot of the slip is enough.', 'yt-slip-upload' ), size_format( self::max_bytes() ) ) ),
			'bad-type'    => array( 'alert', __( 'Send the slip as a JPG, PNG or PDF.', 'yt-slip-upload' ) ),
			'expired'     => array( 'alert', __( 'This page was open for a long time. Reload it and send the slip again.', 'yt-slip-upload' ) ),
			'not-allowed' => array( 'alert', __( 'This order no longer accepts a slip. Contact us if you have already paid.', 'yt-slip-upload' ) ),
			'failed'      => array( 'alert', __( 'The upload did not go through. Try again, or send the slip to us directly.', 'yt-slip-upload' ) ),
		);

		/**
		 * Text under the form: where else a slip can go (e.g. a LINE account).
		 *
		 * @param string   $text  Default text.
		 * @param WC_Order $order Order.
		 */
		$alt = apply_filters(
			'yt_slip_upload_alt_text',
			/* translators: %s: order number */
			sprintf( __( 'Or send it to us with your order number, #%s.', 'yt-slip-upload' ), $order->get_order_number() ),
			$order
		);
		/**
		 * When slips are checked, shown after a slip arrives.
		 *
		 * @param string $text Default text.
		 */
		$check_text = apply_filters( 'yt_slip_upload_check_text', __( 'We check slips within the hour on working days, 9:00 to 18:00.', 'yt-slip-upload' ) );
		$accept     = implode( ',', array_unique( array_values( self::mimes() ) ) );
		?>
		<section class="yt-slip<?php echo $slip ? ' yt-slip--received' : ''; ?>" id="yt-slip" aria-labelledby="yt-slip-title">
			<h2 class="yt-slip__title" id="yt-slip-title">
				<?php echo $slip ? esc_html__( 'Your payment slip', 'yt-slip-upload' ) : esc_html__( 'Send your payment slip', 'yt-slip-upload' ); ?>
			</h2>

			<?php if ( isset( $messages[ $notice ] ) ) : ?>
				<p class="yt-slip__notice yt-slip__notice--<?php echo 'status' === $messages[ $notice ][0] ? 'ok' : 'error'; ?>" role="<?php echo esc_attr( $messages[ $notice ][0] ); ?>">
					<?php echo esc_html( $messages[ $notice ][1] ); ?>
				</p>
			<?php endif; ?>

			<?php if ( $slip ) : ?>
				<p class="yt-slip__received">
					<?php
					printf(
						/* translators: 1: file name, 2: time since upload, e.g. "5 mins" */
						esc_html__( 'Received %2$s ago: %1$s.', 'yt-slip-upload' ),
						'<a href="' . esc_url( self::view_url( $order, true ) ) . '" target="_blank" rel="noopener">' . esc_html( $slip['name'] ) . '</a>', // phpcs:ignore WordPress.Security.EscapeOutput
						esc_html( human_time_diff( (int) $slip['uploaded'] ) )
					);
					?>
					<?php echo esc_html( $check_text ); ?>
				</p>
			<?php endif; ?>

			<?php if ( $open ) : ?>
				<form class="yt-slip__form" method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-max-bytes="<?php echo esc_attr( self::max_bytes() ); ?>">
					<input type="hidden" name="action" value="yt_slip_upload">
					<input type="hidden" name="order_id" value="<?php echo esc_attr( $order->get_id() ); ?>">
					<input type="hidden" name="key" value="<?php echo esc_attr( $order->get_order_key() ); ?>">
					<input type="hidden" name="return" value="<?php echo esc_attr( is_wc_endpoint_url( 'view-order' ) ? 'account' : 'received' ); ?>">
					<?php wp_nonce_field( 'yt_slip_upload_' . $order->get_id(), '_ytslip' ); ?>
					<label class="yt-slip__label" for="yt-slip-file">
						<?php echo $slip ? esc_html__( 'Sent the wrong one? Choose another photo or screenshot', 'yt-slip-upload' ) : esc_html__( 'Photo or screenshot of your transfer slip', 'yt-slip-upload' ); ?>
					</label>
					<input class="yt-slip__file" type="file" id="yt-slip-file" name="yt_slip" accept="<?php echo esc_attr( $accept ); ?>" required aria-describedby="yt-slip-hint">
					<p class="yt-slip__hint" id="yt-slip-hint">
						<?php
						/* translators: %s: size limit */
						printf( esc_html__( 'JPG, PNG or PDF, up to %s.', 'yt-slip-upload' ), esc_html( size_format( self::max_bytes() ) ) );
						?>
					</p>
					<button type="submit" class="yt-slip__submit wp-element-button"><?php echo $slip ? esc_html__( 'Replace slip', 'yt-slip-upload' ) : esc_html__( 'Send slip', 'yt-slip-upload' ); ?></button>
				</form>
				<p class="yt-slip__alt"><?php echo wp_kses_post( $alt ); ?></p>
			<?php endif; ?>
		</section>
		<?php
	}

	/**
	 * "Send slip" button in My Account → Orders for orders still waiting.
	 *
	 * @param array    $actions Actions.
	 * @param WC_Order $order   Order.
	 * @return array
	 */
	public static function my_orders_action( $actions, $order ) {
		if ( self::is_open( $order ) && ! self::get_slip( $order ) ) {
			$actions['yt-slip'] = array(
				'url'  => $order->get_view_order_url() . '#yt-slip',
				'name' => __( 'Send slip', 'yt-slip-upload' ),
			);
		}
		return $actions;
	}

	/**
	 * Small script (file name, size check, preview) only on the pages that show the box.
	 */
	public static function assets() {
		if ( ! function_exists( 'is_wc_endpoint_url' ) || ! ( is_wc_endpoint_url( 'order-received' ) || is_wc_endpoint_url( 'view-order' ) ) ) {
			return;
		}
		$url = plugins_url( 'assets/', YT_SLIP_UPLOAD_FILE );
		wp_enqueue_style( 'yt-slip-upload', $url . 'slip-upload.css', array(), YT_SLIP_UPLOAD_VERSION );
		wp_enqueue_script( 'yt-slip-upload', $url . 'slip-upload.js', array(), YT_SLIP_UPLOAD_VERSION, array( 'strategy' => 'defer' ) );
		wp_localize_script(
			'yt-slip-upload',
			'ytSlip',
			array(
				/* translators: %s: size limit */
				'tooBig'  => sprintf( __( 'That file is larger than %s. A screenshot of the slip is enough.', 'yt-slip-upload' ), size_format( self::max_bytes() ) ),
				'sending' => __( 'Sending…', 'yt-slip-upload' ),
			)
		);
	}

	/**
	 * Where to send the customer after an upload.
	 *
	 * @param WC_Order $order  Order.
	 * @param string   $result Result code.
	 * @return string
	 */
	private static function return_url( $order, $result ) {
		$return = isset( $_POST['return'] ) && 'account' === $_POST['return'] ? $order->get_view_order_url() : $order->get_checkout_order_received_url(); // phpcs:ignore WordPress.Security.NonceVerification
		return add_query_arg( 'yt-slip', $result, $return ) . '#yt-slip';
	}

	/**
	 * admin-post.php?action=yt_slip_upload
	 */
	public static function handle_upload() {
		$order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
		$key      = isset( $_POST['key'] ) ? wc_clean( wp_unslash( $_POST['key'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$order    = $order_id ? wc_get_order( $order_id ) : false;

		// The order key is the customer's proof of ownership (the same secret the order-received URL carries).
		if ( ! $order || ! $key || ! hash_equals( $order->get_order_key(), $key ) ) {
			wp_safe_redirect( home_url( '/' ) );
			exit;
		}

		$nonce = isset( $_POST['_ytslip'] ) ? sanitize_text_field( wp_unslash( $_POST['_ytslip'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'yt_slip_upload_' . $order_id ) ) {
			self::finish( $order, 'expired' );
		}
		if ( ! self::is_open( $order ) ) {
			self::finish( $order, 'not-allowed' );
		}

		$file = $_FILES['yt_slip'] ?? null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		if ( ! $file || ! isset( $file['error'] ) || UPLOAD_ERR_NO_FILE === $file['error'] ) {
			self::finish( $order, 'no-file' );
		}
		if ( in_array( $file['error'], array( UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE ), true ) || (int) $file['size'] > self::max_bytes() ) {
			self::finish( $order, 'too-big' );
		}
		if ( UPLOAD_ERR_OK !== $file['error'] || ! is_uploaded_file( $file['tmp_name'] ) ) {
			self::finish( $order, 'failed' );
		}

		// Checks the real content (not just the name) against the allowed list.
		$check = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'], self::mimes() );
		if ( empty( $check['ext'] ) || empty( $check['type'] ) ) {
			self::finish( $order, 'bad-type' );
		}

		$stored = self::store( $order, $file['tmp_name'], $check['ext'], $check['type'], $file['name'], true );
		self::finish( $order, $stored ? 'ok' : 'failed' );
	}

	/**
	 * Redirect back to the order page with a result code.
	 *
	 * @param WC_Order $order  Order.
	 * @param string   $result Result code.
	 */
	private static function finish( $order, $result ) {
		wp_safe_redirect( self::return_url( $order, $result ) );
		exit;
	}

	/**
	 * admin-post.php?action=yt_slip_view: stream the file to staff, or to the customer holding the order key.
	 */
	public static function handle_view() {
		$order = isset( $_GET['order'] ) ? wc_get_order( absint( $_GET['order'] ) ) : false; // phpcs:ignore WordPress.Security.NonceVerification
		$key   = isset( $_GET['key'] ) ? wc_clean( wp_unslash( $_GET['key'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$ok    = $order && ( current_user_can( 'edit_shop_orders' ) || ( $key && hash_equals( $order->get_order_key(), $key ) ) );
		$slip  = $ok ? self::get_slip( $order ) : null;
		if ( ! $slip ) {
			wp_die( esc_html__( 'Slip not found.', 'yt-slip-upload' ), '', array( 'response' => $ok ? 404 : 403 ) );
		}
		$path = self::dir() . '/' . $slip['file'];
		$ext  = pathinfo( $slip['file'], PATHINFO_EXTENSION );
		nocache_headers();
		header( 'Content-Type: ' . $slip['type'] );
		header( 'Content-Length: ' . filesize( $path ) );
		header( 'Content-Disposition: inline; filename="slip-' . sanitize_file_name( $order->get_order_number() ) . '.' . $ext . '"' );
		header( 'X-Content-Type-Options: nosniff' );
		readfile( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		exit;
	}

	/* --------------------------------------------------------------------- shop side */

	/**
	 * "Payment slip" box on the order screen (HPOS and classic).
	 */
	public static function add_metabox() {
		$screen = function_exists( 'wc_get_page_screen_id' ) ? wc_get_page_screen_id( 'shop-order' ) : 'shop_order';
		add_meta_box( 'yt-slip-upload', __( 'Payment slip', 'yt-slip-upload' ), array( __CLASS__, 'render_metabox' ), $screen, 'side', 'high' );
	}

	/**
	 * Metabox body.
	 *
	 * @param WP_Post|WC_Order $object Post (classic) or order (HPOS).
	 */
	public static function render_metabox( $object ) {
		$order = $object instanceof WC_Order ? $object : wc_get_order( $object->ID );
		if ( ! self::applies( $order ) ) {
			echo '<p>' . esc_html__( 'Not a bank transfer order.', 'yt-slip-upload' ) . '</p>';
			return;
		}
		$slip = self::get_slip( $order );
		if ( ! $slip ) {
			echo '<p>' . esc_html__( 'No slip yet. The customer can send one from the order page or their account.', 'yt-slip-upload' ) . '</p>';
			return;
		}
		$url = self::view_url( $order );
		if ( str_starts_with( $slip['type'], 'image/' ) ) {
			printf( '<p><a href="%1$s" target="_blank" rel="noopener"><img src="%1$s" alt="%2$s" style="display:block;max-width:100%%;height:auto;border:1px solid #dcdcde;border-radius:4px"></a></p>', esc_url( $url ), esc_attr__( 'Payment slip', 'yt-slip-upload' ) );
		} else {
			printf( '<p><a class="button" href="%s" target="_blank" rel="noopener">%s</a></p>', esc_url( $url ), esc_html__( 'Open PDF slip', 'yt-slip-upload' ) );
		}
		printf(
			'<p>%s<br><small>%s · %s</small></p>',
			esc_html( $slip['name'] ),
			/* translators: %s: time since upload */
			esc_html( sprintf( __( '%s ago', 'yt-slip-upload' ), human_time_diff( (int) $slip['uploaded'] ) ) ),
			esc_html( size_format( (int) $slip['size'] ) )
		);
		if ( self::is_open( $order ) ) {
			$mark = wp_nonce_url( add_query_arg( array( 'action' => 'yt_slip_mark_paid', 'order' => $order->get_id() ), admin_url( 'admin-post.php' ) ), 'yt_slip_mark_paid_' . $order->get_id() );
			printf( '<p><a class="button button-primary" href="%s">%s</a></p>', esc_url( $mark ), esc_html__( 'Slip checked: mark as paid', 'yt-slip-upload' ) );
		}
	}

	/**
	 * admin-post.php?action=yt_slip_mark_paid
	 */
	public static function handle_mark_paid() {
		$order_id = isset( $_GET['order'] ) ? absint( $_GET['order'] ) : 0;
		check_admin_referer( 'yt_slip_mark_paid_' . $order_id );
		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_die( esc_html__( 'You cannot edit orders.', 'yt-slip-upload' ), '', array( 'response' => 403 ) );
		}
		$order = wc_get_order( $order_id );
		if ( $order && self::is_open( $order ) ) {
			$order->payment_complete();
			$order->add_order_note(
				/* translators: %s: staff display name */
				sprintf( __( 'Payment slip checked by %s.', 'yt-slip-upload' ), wp_get_current_user()->display_name )
			);
		}
		wp_safe_redirect( $order ? $order->get_edit_order_url() : admin_url() );
		exit;
	}

	/**
	 * "Slip" column after the status column.
	 *
	 * @param array $columns Columns.
	 * @return array
	 */
	public static function add_column( $columns ) {
		$out = array();
		foreach ( $columns as $key => $label ) {
			$out[ $key ] = $label;
			if ( 'order_status' === $key ) {
				$out['yt_slip'] = __( 'Slip', 'yt-slip-upload' );
			}
		}
		if ( ! isset( $out['yt_slip'] ) ) {
			$out['yt_slip'] = __( 'Slip', 'yt-slip-upload' );
		}
		return $out;
	}

	/**
	 * Column cell.
	 *
	 * @param string       $column Column ID.
	 * @param int|WC_Order $order  Order or post ID.
	 */
	public static function render_column( $column, $order ) {
		if ( 'yt_slip' !== $column ) {
			return;
		}
		$order = $order instanceof WC_Order ? $order : wc_get_order( $order );
		if ( ! self::applies( $order ) ) {
			echo '<span aria-hidden="true">–</span>';
			return;
		}
		if ( self::get_slip( $order ) ) {
			$label = self::is_open( $order ) ? __( 'To check', 'yt-slip-upload' ) : __( 'Checked', 'yt-slip-upload' );
			printf( '<mark class="order-status %s"><span>%s</span></mark>', self::is_open( $order ) ? 'status-on-hold' : 'status-completed', esc_html( $label ) );
		} elseif ( self::is_open( $order ) ) {
			echo '<mark class="order-status status-pending"><span>' . esc_html__( 'Waiting', 'yt-slip-upload' ) . '</span></mark>';
		}
	}

	/**
	 * In the customer's "on hold" email, a link straight to the upload box.
	 *
	 * @param WC_Order $order         Order.
	 * @param bool     $sent_to_admin Admin copy.
	 * @param bool     $plain_text    Plain-text email.
	 * @param WC_Email $email         Email object.
	 */
	public static function email_link( $order, $sent_to_admin, $plain_text = false, $email = null ) {
		if ( $sent_to_admin || ! self::is_open( $order ) || self::get_slip( $order ) ) {
			return;
		}
		$url  = $order->get_checkout_order_received_url() . '#yt-slip';
		$text = __( 'Paid already? Send us the slip here:', 'yt-slip-upload' );
		if ( $plain_text ) {
			echo esc_html( $text ) . ' ' . esc_url_raw( $url ) . "\n\n";
		} else {
			printf( '<p>%s <a href="%s">%s</a></p>', esc_html( $text ), esc_url( $url ), esc_html__( 'upload your slip', 'yt-slip-upload' ) );
		}
	}
}

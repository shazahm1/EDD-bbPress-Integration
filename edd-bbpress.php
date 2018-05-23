<?php
/**
 * Plugin Name: Easy Digital Downloads Integration For bbPress
 * Plugin URI: http://connections-pro.com/
 * Description: Adds customer info and purchase records beneath their bbPress topics and topic replies.
 * Version: 1.0
 * Author: Steven A. Zahm
 * Author URI: http://connections-pro.com/
 * Text Domain: edd_bbress
 * Domain Path: languages
 *
 * Copyright 2015  Steven A. Zahm  ( email : helpdesk@connections-pro.com )
 *
 * Easy Digital Downloads Integration For bbPress is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Easy Table of Contents; if not, see <http://www.gnu.org/licenses/>.
 *
 * @package  Easy Digital Downloads Integration For bbPress
 * @category Plugin
 * @author   Steven A. Zahm
 * @version  1.0
 */

// Add CSS to <head>.
add_action( 'wp_head', array( 'EDD_bbPress', 'css' ), 99 );

// Enqueue the JS.
add_action( 'wp_enqueue_scripts', array( 'EDD_bbPress', 'enqueueJS' ) );

// Handle ajax requests for site license deactivation and resending purchase receipts.
add_action( 'wp_ajax_nopriv_edd_bbress_action', array( 'EDD_bbPress', 'doAJAX' ) );
add_action( 'wp_ajax_edd_bbress_action', array( 'EDD_bbPress', 'doAJAX' ) );

// Render EDD Customer Info and Purchase Records to bbPress topic or topic reply.
add_action( 'bbp_theme_after_reply_content', 'bbpress_edd_customer_detail' );

function bbpress_edd_customer_detail() {

	// Left this filter name the same as EDD core on purpose.
	$customer_view_role = apply_filters( 'edd_view_customers_role', 'view_shop_reports' );

	if ( current_user_can( $customer_view_role ) ) {

		new EDD_bbPress();
	}
}

class EDD_bbPress {

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	const VERSION = '1.0';

	/**
	 * @var array
	 */
	private $customer_email = '';

	/**
	 * @var array
	 */
	private $customer_payments = array();

	/**
	 *
	 */
	public function __construct() {

		// Customer email.
		$this->customer_email = bbp_get_reply_author_email();

		// Get customer payment(s).
		$this->customer_payments = $this->getPayments();

		// Render HTML for custom record and purchase records.
		echo $this->buildHTML();
	}

	/**
	 * Query all payments belonging to the customer (by email)
	 *
	 * @return array
	 */
	private function getPayments() {

		/** @var wpdb $wpdb */
		global $wpdb;

		$payments = array();

		/**
		 * Allows you to perform your own search for customer payments, based on given data.
		 *
		 * @since 1.0
		 */
		$payments = apply_filters( 'edd_bbress_customer_payments', $payments, $this->customer_email );

		// Allow filter to short circuit the function to return its own results.
		if ( ! empty( $payments ) ) {

			return $payments;
		}

		// Query by email(s)
		$sql = "SELECT p.ID, p.post_status, p.post_date ";
		$sql .= "FROM {$wpdb->posts} p, {$wpdb->postmeta} pm ";
		$sql .= "WHERE pm.meta_key = '_edd_payment_user_email' ";

		if ( count( $this->customer_email ) > 1 ) {

			$in_clause = rtrim( str_repeat( "'%s', ", count( $this->customer_email ) ), ", " );
			$sql .= "AND pm.meta_value IN( $in_clause ) ";

		} else {

			$sql .= "AND pm.meta_value = '%s' ";
		}

		$sql .= "AND p.ID = pm.post_id GROUP BY p.ID  ORDER BY p.ID DESC";

		$query   = $wpdb->prepare( $sql, $this->customer_email );
		$results = $wpdb->get_results( $query );

		if ( is_array( $results ) ) {

			return $results;
		}

		return array();
	}

	/**
	 * @return string
	 */
	private function buildHTML() {

		if ( $this->customer_email == wp_get_current_user()->user_email ) {
			return '';
		}

		// No purchase data was found.
		if ( count( $this->customer_payments ) === 0 ) {

			$html = '<div class="edd-bbpress">';
			$html .= esc_html__( 'No payments found.', 'edd_bbress' );
			$html .= '</div>';

			return $html;
		}

		$customer = new EDD_Customer( $this->customer_email );

		// Build array of purchases.
		$orders   = array();

		foreach ( $this->customer_payments as $payment ) {

			$order                        = array();
			$order['customer']            = $customer;
			$order['payment_id']          = $payment->ID;
			$order['date']                = $payment->post_date;
			$order['amount']              = edd_get_payment_amount( $payment->ID );
			$order['status']              = $payment->post_status;
			$order['payment_method']      = $this->get_payment_method( $payment->ID );
			$order['downloads']           = array();
			$order['resend_receipt_link'] = '';
			$order['is_renewal']          = FALSE;
			$order['is_completed']        = ( $payment->post_status === 'publish' );

			if ( $payment->post_status === 'publish' ) {

				$url = wp_nonce_url(
					add_query_arg(
						array(
							'action'     => 'edd_bbress_action',
							'do'         => 'resend-purchase-receipt',
							'payment_id' => $order['payment_id'],
						),
						admin_url( 'admin-ajax.php' )
					),
					'edd_resend_purchase_receipt_nonce'
				);

				$order['resend_receipt_link'] = $url;
			}

			// Get purchased downloads.
			$order['downloads'] = (array) edd_get_payment_meta_downloads( $payment->ID );

			// For each download, find license and sites.
			if ( function_exists( 'edd_software_licensing' ) ) {

				/**
				 * @var EDD_Software_Licensing $licensing
				 */
				$licensing = edd_software_licensing();

				// was this order a renewal?
				$order['is_renewal'] = ( (string) get_post_meta( $payment->ID, '_edd_sl_is_renewal', TRUE ) !== '' );

				if ( $order['is_completed'] ) {

					foreach ( $order['downloads'] as $key => $download ) {

						// Only proceed if this download has EDD Software Licensing enabled.
						if ( '' === (string) get_post_meta( $download['id'], '_edd_sl_enabled', TRUE ) ) {
							continue;
						}

						// Find license that was generated for this download purchase.
						/** @var EDD_SL_License $license */
						$license = $licensing->get_license_by_purchase( $payment->ID, $download['id'] );

							if ( is_a( $license, 'EDD_SL_License' ) ) {

								$lkey = (string) get_post_meta( $license->ID, '_edd_sl_key', TRUE );

								// Add support for "lifetime" licenses.
								if ( method_exists( $licensing, 'is_lifetime_license' ) && $licensing->is_lifetime_license( $license->ID ) ) {

									$is_expired = FALSE;

								} else {

									$expires    = (string) get_post_meta( $license->ID, '_edd_sl_expiration', TRUE );
									$is_expired = $expires < time();
								}

								// Is the download a bundle?
								$is_bundle = edd_is_bundled_product( $download['id'] );

								$order['downloads'][ $key ]['license'] = array(
									'limit'          => 0,
									'key'            => $lkey,
									'is_expired'     => $is_expired,
									'is_bundle'      => $is_bundle,
									'child_licenses' => array(),
									'sites'          => array(),
								);

								// Look up active sites if license is not expired.
								if ( ! $is_expired ) {

									// Get license limit.
									$order['downloads'][ $key ]['license']['limit'] = $licensing->get_license_limit( $download['id'], $license->ID );

									$sites = (array) $licensing->get_sites( $license->ID );

									foreach ( $sites as $site ) {

										$url = wp_nonce_url(
											add_query_arg(
												array(
													'action'     => 'edd_bbress_action',
													'do'         => 'deactivate-license',
													'license_id' => $license->ID,
													'site_url'   => $site,
												),
												admin_url( 'admin-ajax.php' )
											),
											'edd_deactivate_site_nonce'
										);

										$order['downloads'][ $key ]['license']['sites'][] = array(
											'url'             => self::prefix( $site ),
											'deactivate_link' => $url,
										);

									}

									// If download is a bundle, get the bundle items license key data.
									if ( $is_bundle ) {

										$child_licenses = get_children(
											array(
												'post_type'      => 'edd_license',
												'post_status'    => array( 'publish', 'future' ),
												'posts_per_page' => -1,
												'post_parent'    => $license->ID,
											)
										);

										if ( ! empty( $child_licenses ) ) {

											$child_licenses_data = array();

											foreach ( $child_licenses as $child_license ) {

												$child_license_sites      = (array) $licensing->get_sites( $child_license->ID );
												$child_license_sites_data = array();

												foreach ( $child_license_sites as $site ) {

													$url = wp_nonce_url(
														add_query_arg(
															array(
																'action'     => 'edd_bbress_action',
																'do'         => 'deactivate-license',
																'license_id' => $child_license->ID,
																'site_url'   => $site,
															),
															admin_url( 'admin-ajax.php' )
														),
														'edd_deactivate_site_nonce'
													);

													$child_license_sites_data[] = array(
														'url'             => self::prefix( $site ),
														'deactivate_link' => $url,
													);

												}

												$child_licenses_data[] = array(
													'id'          => $child_license->ID,
													//'title'       => get_the_title( $child_license->ID ),
													'key'         => $licensing->get_license_key( $child_license->ID ),
													'limit'       => $licensing->get_license_limit( $download['id'], $child_license->ID ),
													'sites'       => $child_license_sites_data,
													'download_id' => get_post_meta(
														$child_license->ID,
														'_edd_sl_download_id',
														TRUE
													),
												);

											}
										} //end is child license data

										$order['downloads'][ $key ]['license']['child_licenses'] = $child_licenses_data;
									} //end is bundle.

								} //endif not expired
							} // endif license found
						//} // end foreach licenses of purchase
					} // end foreach downloads
				} // endif order completed
			}

			$orders[] = $order;
		}

		// Generate HTML.
		$html = '<div class="edd-bbpress">';
		$html .= $this->customer( $customer );

		foreach ( $orders as $order ) {

			$html .= str_replace( '\t', '', $this->purchase( $order ) );
		}

		$html .= '</div>';

		return $html;
	}

	public function customer( EDD_Customer $customer ) {

		ob_start();
		include dirname( __FILE__ ) . '/views/customer.php';
		$html = ob_get_clean();
		return $html;
	}

	/**
	 * @param $purchase
	 *
	 * @return string
	 */
	public function purchase( array $purchase ) {

		ob_start();
		include dirname( __FILE__ ) . '/views/order-row.php';
		$html = ob_get_clean();
		return $html;
	}

	/**
	 * Get the payment method used for the given $payment_id. Returns a link to the transaction in Stripe or PayPal if possible.
	 *
	 * @param int $payment_id
	 *
	 * @return string
	 */
	private function get_payment_method( $payment_id ) {

		$payment_method = edd_get_payment_gateway( $payment_id );
		$notes          = edd_get_payment_notes( $payment_id );

		switch ( $payment_method ) {

			case 'paypal':

				foreach ( $notes as $note ) {

					if ( preg_match( '/^PayPal Transaction ID: ([^\s]+)/', $note->comment_content, $match ) ) {

						$transaction_id = $match[1];
						$payment_method = '<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_view-a-trans&id=' . esc_attr( $transaction_id ) . '" target="_blank">PayPal</a>';
						break 2;
					}
				}

				break;

			case 'stripe':

				foreach ( $notes as $note ) {

					if ( preg_match( '/^Stripe Charge ID: ([^\s]+)/', $note->comment_content, $match ) ) {

						$transaction_id = $match[1];
						$payment_method = '<a href="https://dashboard.stripe.com/payments/' . esc_attr( $transaction_id ) . '" target="_blank">Stripe</a>';
						break 2;
					}
				}

				break;

			case 'manual_purchases':

				$payment_method = 'Manual';
				break;
		}

		return $payment_method;
	}

	/**
	 * Gets the URL to switch to the user
	 * if the User Switching plugin is active
	 *
	 * @access public
	 * @since  1.0
	 *
	 * @param int $id
	 *
	 * @return bool|string
	 */
	public function get_switch_to_url( $id ) {

		if ( ! class_exists( 'user_switching' ) ) {

			return FALSE;
		}

		$link = user_switching::maybe_switch_url( new WP_User( $id ) );

		if ( $link ) {

			$link = add_query_arg( 'redirect_to', urlencode( home_url() ), $link );

			return $link;

		} else {

			return FALSE;
		}
	}

	public static function doAJAX() {

		$action = $_REQUEST['do'];

		switch ( $action ) {

			case 'deactivate-license':
				self::deactivateLicense();
				break;

			case 'resend-purchase-receipt':
				self::resendPurchaseReceipt();
				break;

			default:
				break;
		}
	}

	/**
	 * Deactivates a site
	 *
	 * Function code copied from @see edd_sl_process_deactivate_site() in EDD-SL
	 */
	private static function deactivateLicense() {

		$response = -1;

		if ( function_exists( 'edd_software_licensing' ) ) {

			if ( is_admin() && ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'edd_deactivate_site_nonce' ) ) {
				wp_send_json( $response );
			}

			$license_id = absint( $_REQUEST['license_id'] );
			$user_id    = get_post_meta( $license_id, '_edd_sl_user_id', TRUE );

			if ( ! current_user_can( 'edit_shop_payments' ) && $user_id != get_current_user_id() ) {
				wp_send_json( $response );
			}

			$site_url = urldecode( $_REQUEST['site_url'] );

			$response = edd_software_licensing()->delete_site( $license_id, $site_url );
		}

		wp_send_json( $response );
	}

	/**
	 * Handle resending the purchase email.
	 *
	 * Function code copied from @see edd_resend_purchase_receipt() in EDD.
	 */
	private static function resendPurchaseReceipt() {

		if ( is_admin() && ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'edd_resend_purchase_receipt_nonce' ) ) {

			wp_send_json( -1 );
		}

		$payment_id = absint( $_REQUEST['payment_id'] );

		edd_email_purchase_receipt( $payment_id, FALSE );

		// Grab all downloads of the purchase and update their file download limits, if needed
		// This allows admins to resend purchase receipts to grant additional file downloads
		$downloads = edd_get_payment_meta_downloads( $payment_id );

		if ( is_array( $downloads ) ) {

			foreach ( $downloads as $download ) {

				$limit = edd_get_file_download_limit( $download['id'] );

				if ( ! empty( $limit ) ) {

					edd_set_file_download_limit_override( $download['id'], $payment_id );
				}
			}
		}

		wp_send_json( TRUE );
	}

	public static function css() {

		?>
<style type="text/css">
/* EDD bbPress Integration Custom Styles */
#bbpress-forums div.bbp-reply-content ul.edd-bbpress-customer-details {
	margin: 0;
	padding: 0;
}
#bbpress-forums div.bbp-reply-content ul.edd-bbpress-customer-details li {
	list-style: none;
	margin: 0;
	padding: 0;
}
#bbpress-forums div.bbp-reply-content .edd-bbpress-purchase-record ul {
		margin: 0;
	padding: 0;
}
#bbpress-forums div.bbp-reply-content .edd-bbpress-purchase-record ul li {
	list-style: none;
	margin: 0;
	padding: 0;
}
#bbpress-forums div.bbp-reply-content .edd-bbpress-purchase-record ol li {
	list-style: decimal;
}
#bbpress-forums hr.edd-bbpress-divider {
	margin: 8px 0;
}
#bbpress-forums div.bbp-reply-content .edd-bbpress p {
	margin: 0 0 12px;
}
#bbpress-forums div.bbp-reply-content .edd-bbpress a {
	border: none;
	text-decoration: none;
}
#bbpress-forums div.bbp-reply-content .edd-bbpress .edd-bbpress-purchase-indent {
	margin-left: 24px;
}
</style>
		<?php
	}

	/**
	 * Enqueue the plugin's JS file only on bbPress pages.
	 */
	public static function enqueueJS() {

		// Check if bbPress exists
		if ( ! function_exists( 'is_bbpress' ) ) {
			return;
		}

		if ( is_bbpress() ) {

			// If SCRIPT_DEBUG is set and TRUE load the non-minified JS files, otherwise, load the minified files.
			$min = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
			$url = plugin_dir_url( __FILE__ );

			wp_enqueue_script( 'edd-bbpress', $url . "assets/js/edd-bbpress$min.js", array( 'jquery' ), self::VERSION, TRUE );
		}

	}

	/**
	 * Take a URL and see if it's prefixed with a protocol, if it's not then it will add the default prefix to the
	 * start of the string.
	 *
	 * @access public
	 * @since
	 * @static
	 *
	 * @param  string $url
	 * @param  string $protocol
	 *
	 * @return string
	 */
	private static function prefix( $url, $protocol = 'http://' ) {

		return parse_url( $url, PHP_URL_SCHEME ) === NULL ? $protocol . $url : $url;
	}

}

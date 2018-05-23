<?php defined( 'ABSPATH' ) or exit;?>
<div class="edd-bbpress-purchase-record">

	<?php do_action( 'edd_bbress_before_order', $purchase ); ?>

	<strong>
		<i class="dashicons dashicons-cart"></i>
		<a target="_blank" href="<?php echo esc_attr( admin_url( 'edit.php?post_type=download&page=edd-payment-history&view=view-order-details&id='. $purchase['payment_id'] ) ); ?>">#<?php echo $purchase['payment_id']; ?></a>
	</strong>

	<?php do_action( 'edd_bbpress_before_order_status', $purchase ); ?>

	<?php if ( $purchase['is_completed'] ) : ?>
		<a href="javascript:;" rel="<?php echo esc_url( $purchase['resend_receipt_link'] ); ?>" class="edd-bbpress-purchase-resend-receipt" title="<?php echo esc_attr( __( 'Resend Purchase Receipt', 'edd_bbress' ) ); ?>" target="_blank">
			<i class="dashicons dashicons-media-text"></i>
		</a>
	<?php else : ?>
		<span style="color:orange;font-weight:bold;"> <?php echo esc_html( $purchase['status'] ); ?></span>
	<?php endif; ?>

	<?php if ( $purchase['is_renewal'] ) : ?>
		<span style="color:green;font-weight:bold;"> (renewal)</span>
	<?php endif; ?>

	<a href="javascript:;" class="edd-bbpress-purchase-toggle" title="<?php echo esc_attr( __( 'Show Purchase Details', 'edd_bbress' ) ); ?>"><i class="dashicons dashicons-arrow-right"></i></a>

	<?php do_action( 'edd_bbpress_after_order_status', $purchase ); ?>

	<div class="edd-bbpress-purchase-indent edd-bbpress-purchase-details closed">

		<?php do_action( 'edd_bbpress_before_order_details', $purchase ); ?>

		<p>
			<span><?php echo date_i18n( get_option( 'date_format' ), strtotime( $purchase['date'] ) ); ?></span><br/>
			<?php echo trim( edd_currency_filter( $purchase['amount'] ) ) . ( ( isset( $purchase['payment_method'] ) && '' !== $purchase['payment_method'] ) ?  ' with ' . $purchase['payment_method'] : '' ); ?>
		</p>

		<?php if ( ! empty( $purchase['downloads'] ) ) : ?>
			<?php do_action( 'edd_bbpress_before_order_downloads', $purchase ); ?>

			<ul>
				<?php foreach( $purchase['downloads'] as $key => $download ) : ?>
					<li>
						<i class="dashicons dashicons-download"></i><strong><?php echo get_the_title( $download['id'] ); ?></strong>

						<div class="edd-bbpress-purchase-indent">

							<?php do_action( 'edd_bbpress_before_order_download_details', $purchase, $download ); ?>

							<?php if ( ! empty( $download['license'] ) ) : $license = $download['license']; ?>

								<?php do_action( 'edd_bbpress_before_order_download_license', $purchase, $download, $license ); ?>

								<i class="dashicons dashicons-admin-network"></i>

								<?php if ( isset( $download['options'] ) && isset( $download['options']['price_id'] ) ) echo edd_get_price_option_name( $download['id'], $download['options']['price_id'] ); ?>

								<a href="<?php echo admin_url( 'edit.php?post_type=download&page=edd-licenses&s=' . $license['key'] ); ?>" target="_blank"><?php echo $license['key']; ?></a>

								<?php if ( $license['is_expired'] ) : ?>
									<span style="color:red; font-weight:bold;"> <?php esc_html_e( 'expired', 'edd_bbress' ); ?></span>
								<?php endif; ?>

								<?php if ( ! empty( $license['sites'] ) ) : ?>
									<div class="manage-sites">
										<span class="active-sites"><i class="dashicons dashicons-admin-site"></i> Active sites <?php printf( '(<span class="count" data-active_site_count="%1$d">%1$d</span>/%d)', count( $license['sites'] ), $license['limit'] ); ?></span>
										<div class="edd-bbpress-purchase-indent">
											<ol>
												<?php foreach( $license['sites'] as $site ) : ?>
													<li>
														<a href="<?php echo esc_url( $site['url'] ); ?>" target="_blank"><?php echo esc_html( $site['url'] ); ?></a>
														<a href="javascript:;" rel="<?php echo esc_url( $site['deactivate_link'] ); ?>" class="edd-bbpress-deactivate-site" title="<?php echo esc_attr( __( 'Deactivate Site', 'edd_bbress' ) ); ?>" target="_blank"> <i class="dashicons dashicons-dismiss" style="color: red; vertical-align: text-bottom;"></i></a>
													</li>
												<?php endforeach; // end foreach sites ?>
											</ol>
										</div>
									</div>
								<?php endif; // end if sites not empty ?>

								<?php if ( $license['is_bundle'] ) : ?>

									<?php if ( ! empty( $license['child_licenses'] ) ) : ?>

										<div class="edd-bbpress-purchase-indent">
											<ul>

											<?php foreach( $license['child_licenses'] as $child_license ) : ?>
												<li>
													<i class="dashicons dashicons-download"></i><strong><?php echo get_the_title( $child_license['download_id'] ); ?></strong>

													<div class="edd-bbpress-purchase-indent">
														<i class="dashicons dashicons-admin-network"></i>
														<a href="<?php echo admin_url( 'edit.php?post_type=download&page=edd-licenses&s=' . $child_license['key'] ); ?>" target="_blank"><?php echo $child_license['key']; ?></a>
													</div>

													<?php if ( ! empty( $child_license['sites'] ) ) : ?>
														<div class="edd-bbpress-purchase-indent">
															<div class="manage-sites">
																<span class="active-sites"><i class="dashicons dashicons-admin-site"></i> Active sites <?php printf( '(<span class="count" data-active_site_count="%1$d">%1$d</span>/%d)', count( $child_license['sites'] ), $child_license['limit'] ); ?></span>
																<div class="edd-bbpress-purchase-indent">
																	<ol>
																		<?php foreach( $child_license['sites'] as $site ) : ?>
																			<li>
																				<a href="<?php echo esc_url( $site['url'] ); ?>" target="_blank"><?php echo esc_html( $site['url'] ); ?></a>
																				<a href="javascript:;" rel="<?php echo esc_url( $site['deactivate_link'] ); ?>" class="edd-bbpress-deactivate-site" title="<?php echo esc_attr( __( 'Deactivate Site', 'edd_bbress' ) ); ?>" target="_blank"> <i class="dashicons dashicons-dismiss" style="color: red; vertical-align: text-bottom;"></i></a>
																			</li>
																		<?php endforeach; // end foreach sites ?>
																	</ol>
																</div>
															</div>
														</div>
													<?php endif; // end if sites not empty ?>

												</li>
											<?php endforeach; // end child license ?>

											</ul>
										</div>

									<?php endif; // end if bundle has child licenses ?>

								<?php endif; // end if is a bundle ?>

								<?php do_action( 'edd_bbpress_after_order_download_license', $purchase, $download, $license ); ?>

							<?php endif; //end if has license ?>

							<?php do_action( 'edd_bbpress_after_order_download_details', $purchase, $download ); ?>
						</div>
					</li>
				<?php endforeach; ?>
			</ul>

			<?php do_action( 'edd_bbpress_after_order_downloads', $purchase ); ?>

		<?php endif; // endif downloads ?>

		<?php do_action( 'edd_bbpress_after_order_details', $purchase ); ?>

	</div>

	<?php do_action( 'edd_bbpress_after_order', $purchase ); ?>

</div>

<hr class="edd-bbpress-divider">

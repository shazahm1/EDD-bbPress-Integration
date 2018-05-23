<?php defined( 'ABSPATH' ) or exit;?>

<hr class="edd-bbpress-divider">

<a href="javascript:;" class="edd-bbpress-customer-toggle" title="<?php echo esc_attr( __( 'Show Customer Details', 'edd_bbress' ) ); ?>"><?php esc_html_e( 'Customer Details', 'edd_bbress' ); ?> <i class="dashicons dashicons-arrow-right"></i></a>

<ul class="edd-bbpress-customer-details closed">
	<li data-customer_name="customer_name">
		<a href="<?php echo admin_url( 'edit.php?post_type=download&page=edd-customers&view=overview&id=' . $customer->id ); ?>" target="_blank" title="<?php echo esc_attr( __( 'View Customer Record', 'edd_bbress' ) ); ?>"><?php echo esc_html( $customer->name ); ?></a>
	</li>
	<li data-customer_email="customer_email"><a href="mailto:<?php echo esc_attr( $customer->email ); ?>" target="_blank" title="<?php echo esc_attr( __( 'Email Customer', 'edd_bbress' ) ); ?>"><?php echo esc_html( $customer->email ); ?></a></li>
	<li data-customer_email="user_id">
		<span class="dashicons dashicons-admin-users"></span>
		<?php if ( intval( $customer->user_id ) > 0 ) : ?>
			<span data-user_id="user_id"><a href="<?php echo esc_url( admin_url( add_query_arg( array( 'user_id' => $customer->user_id ), 'user-edit.php' ) ) ); ?>" target="_blank" title="<?php echo esc_attr( __( 'View User Profile', 'edd_bbress' ) ); ?>">#<?php echo esc_html( $customer->user_id ); ?></a></span>

			<?php $url = $this->get_switch_to_url( $customer->user_id ); ?>

			<?php if ( FALSE !== $url ) : ?>

				<?php
				$link = esc_html__( 'Switch&nbsp;To', 'edd_bbress' );
				?>

				| <span class="edd-bbpress-user-switching"><a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $link ); ?></a></span>

			<?php endif; ?>

		<?php else : ?>
			<span data-key="user_id"><?php _e( 'none', 'edd_bbress' ); ?></span>
		<?php endif; ?>
	</li>
</ul>

<hr class="edd-bbpress-divider">

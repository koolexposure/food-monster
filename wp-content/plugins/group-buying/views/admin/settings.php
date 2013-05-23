<?php require ABSPATH . 'wp-admin/options-head.php'; // not a general options page, so it must be included here ?>
<div class="wrap">
	<?php screen_icon(); ?>
	<h2 class="nav-tab-wrapper">
		<?php self::display_admin_tabs(); ?>
	</h2>

	<h3><?php echo esc_html( $title ); ?></h3>
	<?php do_action( 'gb_settings_page_sub_heading_'.$page, $page ); ?>

	<?php if ( isset( $_GET['tab'] ) && $_GET['tab'] == 'gb_shop' ): // TODO use a callback for shop theme ?>
		<?php do_action( 'gb_options_shop' ) ?>
	<?php else: ?>
		<form method="post" enctype="multipart/form-data" action="<?php echo admin_url( 'options.php' ); ?>">
			<?php settings_fields( $page ); ?>
			<table class="form-table">
				<?php do_settings_fields( $page, 'default' ); ?>
			</table>
			<?php do_settings_sections( $page ); ?>
			<?php submit_button(); ?>
			<?php if ( $reset ): ?>
				<?php submit_button( gb__( 'Reset Defaults' ), 'secondary', $page.'-reset', false ); ?>
			<?php endif ?>
		</form>
	<?php endif ?>


	<?php do_action( 'gb_settings_page', $page ) ?>
	<?php do_action( 'gb_settings_page_'.$page, $page ) ?>
</div>

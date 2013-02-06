<?php do_action( 'gb_voucher_pre_header' ) ?>
<!DOCTYPE html>
<html>
	<head>
		<title><?php the_title(); ?> <?php gb_e( 'Printing...' ); ?></title>
		<style>
			#coupon-wrap {width:800px}
			.clearfix:after {clear: both;content: ' ';display: block;font-size: 0;line-height: 0;visibility: hidden;width: 0;height: 0;}
			.clearfix {display: inline-block;}
			* html .clearfix {height: 1%;}
			.clearfix {display: block;}
			.left {float: left;width: 45%;}
			.right {float: right;width: 50%;}
			p.title {font-weight: bold;padding: 0;margin: 15px 0 0;}
			body {margin: 20px;font-family: "Helvetica Neue", Arial, Helvetica, Geneva, sans-serif;}
			#voucher-top {border: 2px solid #000;padding: 15px;margin-bottom: 20px;}
			#voucher-top #title-section p.title {margin-top: 0;}
			#title-section {border-bottom-width: 2px;border-bottom-style: solid;padding-bottom: 10px;}
			#how_to {font-size: small;}
			#how-column-right iframe {width: 100%;height: 250px;overflow: hidden;}
			#how-column-right small a {display: none;}
			#support {margin-top: 20px;margin-bottom: 20px;padding: 10px 50px;background-color: silver; font-size: small;text-align: center;}
			#support span {margin-right: 50px;}
			#univ-fine-print, #legal-stuff {font-size: x-small;}
			#merch_voucher_logo {margin-right: 20px;}
			#qr_code {margin: -45px;float: right}
			<?php do_action( 'gb_voucher_css' ) ?>
		</style>
		<script type="text/javascript">
			<!--
				function printpage() {
					window.print();
				}
			//-->
		</script>
		<?php do_action( 'gb_voucher_head' ) ?>
	</head>
	<body onload="printpage()">
		<div id="coupon-wrap" class="clearfix">
			<div id="voucher-top" class="clearfix">
				<div id="title-section" class="clearfix">
					<div id="title" class="left">
						<?php
							if ( gb_has_voucher_logo() ) {
								gb_voucher_logo_image();
							}
							elseif ( gb_has_univ_voucher_logo() ) {
								gb_univ_voucher_logo();
							} else { ?>
								<p class="title">
									<?php bloginfo( 'name' ) ?>
								</p><span><?php bloginfo( 'description' ) ?></span>
								<?php
							} ?>
					</div>
					<div id="voucher-id" class="right">
						#<?php gb_voucher_code(); ?>
						<div id="qr_code" class="clearfix">
							<img src="https://chart.googleapis.com/chart?cht=qr&amp;chs=120x120&amp;chl=<?php echo urlencode( gb_get_voucher_claim_url( gb_get_voucher_security_code(), FALSE ) ) ?>">
						</div>
					</div>
				</div>
				<div id="deal" class="clearfix">
					<div class="clearfix">
						<p class="title">
							<?php echo str_replace( gb__( 'Voucher for' ), '', get_the_title() ); ?>
						</p>
					</div>
					<div id="colomn-left" class="left">
						<p class="title">
							<?php gb_e( 'Recipient:' ); ?>
						</p><span class="value"><?php esc_attr_e( gb_get_name() ); ?></span>
						<p class="title">
							<?php gb_e( 'Expires On:' ); ?>
						</p><span class="value"><?php gb_voucher_expiration_date(); ?></span>
						<p class="title">
							<?php gb_e( 'Fine Print:' ); ?>
						</p><span class="value"><?php gb_voucher_fine_print() ?></span>
					</div>
					<div id="colomn-right" class="right">
						<p class="title">
							<?php gb_e( 'Voucher Code:' ); ?>
						</p><span class="value"><?php gb_voucher_code(); ?></span>
						<p class="title">
							<?php gb_e( 'Reference:' ); ?>
						</p><span class="value"><?php gb_voucher_security_code(); ?></span>
						<p class="title">
							<?php gb_e( 'Redeem at:' ); ?>
						</p>
						<?php gb_voucher_locations() ?>
					</div>
				</div>
				<div id="univ-fine-print" class="clearfix">
					<p class="title">
						<?php gb_e( 'Universal Fine Print' ); ?>
					</p><?php  gb_univ_voucher_fine_print(); ?>
				</div>
			</div>
			<div id="how_to" class="clearfix">
				<div id="how-column-left" class="left">
					<p class="title">
						<?php gb_e( 'How to use this:' ); ?>
					</p><span class="value"><?php gb_voucher_usage_instructions() ?></span>
				</div>
				<div id="how-column-right" class="right">

					<p class="title">
						<?php gb_e( 'Map:' ); ?>
					</p><?php gb_voucher_map() ?>

				</div>
			</div>
			<div id="support" class="clearfix">
				<span><?php gb_voucher_support1() ?></span><span><?php gb_voucher_support2() ?></span>
			</div>
			<div id="legal-stuff" class="clearfix">
				<?php gb_voucher_legal(); ?>
			</div>
		</div>
		<?php do_action( 'gb_voucher_footer' ) ?>
	</body>
	<?php do_action( 'gb_voucher_post_body' ) ?>
</html>
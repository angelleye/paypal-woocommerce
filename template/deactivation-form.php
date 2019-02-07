<?php
defined( 'ABSPATH' ) || die( 'Cheatin&#8217; uh?' );
$deactivation_url = wp_nonce_url( 'plugins.php?action=deactivate&amp;plugin=' . rawurlencode( 'paypal-for-woocommerce/paypal-for-woocommerce.php' ), 'deactivate-plugin_paypal-for-woocommerce/paypal-for-woocommerce.php' );
?>
<div class="deactivation-Modal">
	<div class="deactivation-Modal-header">
		<div>
			<button class="deactivation-Modal-return deactivation-icon-chevron-left"><?php _e( 'Return', 'paypal-for-woocommerce' ); ?></button>
			<h2><?php _e( 'PayPal for WooCommerce feedback', 'paypal-for-woocommerce' ); ?></h2>
		</div>
		<button class="deactivation-Modal-close deactivation-icon-close"><?php _e( 'Close', 'paypal-for-woocommerce' ); ?></button>
	</div>
	<div class="deactivation-Modal-content">
		<div class="deactivation-Modal-question deactivation-isOpen">
			<h3><?php _e( 'May we have a little info about why you are deactivating?', 'paypal-for-woocommerce' ); ?></h3>
			<ul>
				<li>
					<input type="radio" name="reason" id="reason-temporary" value="Temporary Deactivation">
					<label for="reason-temporary"><?php _e( '<strong>It is a temporary deactivation.</strong> I am just debugging an issue.', 'paypal-for-woocommerce' ); ?></label>
				</li>
				<li>
					<input type="radio" name="reason" id="reason-broke" value="Broken Layout">
					<label for="reason-broke"><?php _e( 'The plugin <strong>broke my layout</strong> or some functionality.', 'paypal-for-woocommerce' ); ?></label>
				</li>
				<li>
					<input type="radio" name="reason" id="reason-score" value="Score">
					<label for="reason-score"><?php _e( 'My PageSpeed or GTMetrix <strong>score did not improve.</strong>', 'paypal-for-woocommerce' ); ?></label>
				</li>
				<li>
					<input type="radio" name="reason" id="reason-loading" value="Loading Time">
					<label for="reason-loading"><?php _e( 'I did not notice a difference in loading time.', 'paypal-for-woocommerce' ); ?></label>
				</li>
				<li>
					<input type="radio" name="reason" id="reason-complicated" value="Complicated">
					<label for="reason-complicated"><?php _e( 'The plugin is <strong>too complicated to configure.</strong>', 'paypal-for-woocommerce' ); ?></label>
				</li>
				<li>
					<input type="radio" name="reason" id="reason-host" value="Host">
					<label for="reason-host"><?php _e( 'My host already has its own caching system.', 'paypal-for-woocommerce' ); ?></label>
					<div class="deactivation-Modal-fieldHidden">
						<input type="text" name="reason-hostname" id="reason-hostname" value="" placeholder="<?php _e( 'What is the name of your web host?', 'paypal-for-woocommerce' ); ?>">
					</div>
				</li>
				<li>
					<input type="radio" name="reason" id="reason-other" value="Other">
					<label for="reason-other"><?php _e( 'Other', 'paypal-for-woocommerce' ); ?></label>
					<div class="deactivation-Modal-fieldHidden">
						<textarea name="reason-other-details" id="reason-other-details" placeholder="<?php _e( 'Let us know why you are deactivating PayPal for WooCommerce so we can improve the plugin', 'paypal-for-woocommerce' ); ?>"></textarea>
					</div>
				</li>
			</ul>
			<input id="deactivation-reason" type="hidden" value="">
			<input id="deactivation-details" type="hidden" value="">
		</div>
		<div id="reason-broke-panel" class="deactivation-Modal-hidden">
			<h3><?php _e( 'The plugin broke my layout or some functionality', 'paypal-for-woocommerce' ); ?></h3>
			<p><?php _e( 'This type of issue can usually be fixed by deactivating some options in PayPal for WooCommerce.', 'paypal-for-woocommerce' ); ?></p>
			<p><?php _e( 'Click "Apply Safe Mode" to quickly disable LazyLoad, File Optimization, Embeds and CDN options. Then check your site to see if the issue has resolved.', 'paypal-for-woocommerce' ); ?></p>
			<div class="text-center">
				<button id="deactivation-action-safe_mode" class="deactivation-button"><?php _e( 'Apply safe mode', 'paypal-for-woocommerce' ); ?></button>
			</div>
			<div class="deactivation-Modal-safeMode deactivation-icon-check show-if-safe-mode">
				<div class="deactivation-Modal-safeMode-title deactivation-title3"><?php _e( 'Safe mode applied.', 'paypal-for-woocommerce' ); ?></div>
				<?php _e( 'Review your site in a private/logged out browser window.', 'paypal-for-woocommerce' ); ?>
			</div>
			<p class="show-if-safe-mode"><?php _e( 'Is the issue fixed? Now you can reactivate options one at a time to determine which one caused the problem. <a href="https://docs.wp-angelleye.me/article/19-resolving-issues-with-file-optimization/?utm_source=wp_plugin&utm_medium=wp_angelleye" target="_blank">More info</a>', 'paypal-for-woocommerce' ); ?></p>
		</div>
		<div id="reason-score-panel" class="deactivation-Modal-hidden">
			<h3><?php _e( 'My PageSpeed or GT Metrix score did not improve', 'paypal-for-woocommerce' ); ?></h3>
			<p><?php _e( 'PayPal for WooCommerce makes your site faster. The PageSpeed grade or GTMetrix score are not indicators of speed.  Neither your real visitors, nor Google will ever see your website’s “grade”. Speed is the only metric that matters for SEO and conversions.', 'paypal-for-woocommerce' ); ?></p>
			<p><?php _e( 'Yoast, the expert on all things related to SEO for WordPress states:', 'paypal-for-woocommerce' ); ?></p>
			<blockquote cite="https://yoast.com/ask-yoast-google-page-speed/"><?php _e( '[Google] just looks at how fast your website loads for users, so you don’t have to obsess over that specific score. You have to make sure your website is as fast as you can get it.', 'paypal-for-woocommerce' ); ?></blockquote>
			<cite><a href="https://yoast.com/ask-yoast-google-page-speed/" target="_blank">https://yoast.com/ask-yoast-google-page-speed/</a></cite>

			<p><?php _e( 'How to measure the load time of your site:<br><a href="https://wp-angelleye.me/blog/correctly-measure-websites-page-load-time/?utm_source=wp_plugin&utm_medium=wp_angelleye" target="_blank">https://wp-angelleye.me/blog/correctly-measure-websites-page-load-time/</a>', 'paypal-for-woocommerce' ); ?></p>
			<p><?php _e( 'Why you should not be chasing a PageSpeed score:<br><a href="https://wp-angelleye.me/blog/the-truth-about-google-pagespeed-insights/?utm_source=wp_plugin&utm_medium=wp_angelleye" target="_blank">https://wp-angelleye.me/blog/the-truth-about-google-pagespeed-insights/</a>', 'paypal-for-woocommerce' ); ?></p>
		</div>
		<div id="reason-loading-panel" class="deactivation-Modal-hidden">
			<h3><?php _e( 'I did not notice a difference in loading time', 'paypal-for-woocommerce' ); ?></h3>
			<p><?php _e( 'Make sure you look at your site while logged out to see the fast, cached pages!', 'paypal-for-woocommerce' ); ?>
			<p><?php _e( 'The best way to see the improvement PayPal for WooCommerce provides is to perform speed tests. Follow this guide to correctly measure the load time of your website:<br><a href="https://wp-angelleye.me/blog/correctly-measure-websites-page-load-time/?utm_source=wp_plugin&utm_medium=wp_angelleye" target="_blank">https://wp-angelleye.me/blog/correctly-measure-websites-page-load-time/</a>', 'paypal-for-woocommerce' ); ?>
		</div>
		<div id="reason-complicated-panel" class="deactivation-Modal-hidden">
			<h3><?php _e( 'The plugin is too complicated to configure', 'paypal-for-woocommerce' ); ?></h3>
			<p><?php _e( 'We are sorry to hear you are finding it difficult to use PayPal for WooCommerce.', 'paypal-for-woocommerce' ); ?></p>
			<p><?php _e( 'PayPal for WooCommerce is the only caching plugin that provides 80% of best practices in speed optimization, by default. That means you do not have to do anything besides activate PayPal for WooCommerce and your site will already be faster!', 'paypal-for-woocommerce' ); ?></p>
			<p><?php _e( 'The additional options are not required for a fast site, they are for fine-tuning.', 'paypal-for-woocommerce' ); ?></p>
			<p><?php _e( 'To see the benefit PayPal for WooCommerce is already providing, measure the speed of your site using a tool like Pingdom:<br><a href="https://wp-angelleye.me/blog/correctly-measure-websites-page-load-time/?utm_source=wp_plugin&utm_medium=wp_angelleye" target="_blank">https://wp-angelleye.me/blog/correctly-measure-websites-page-load-time/</a>', 'paypal-for-woocommerce' ); ?></p>
		</div>
	</div>
	<div class="deactivation-Modal-footer">
		<div>
			<a href="<?php echo esc_attr( $deactivation_url ); ?>" class="button button-primary deactivation-isDisabled" disabled id="mixpanel-send-deactivation"><?php _e( 'Send & Deactivate', 'paypal-for-woocommerce' ); ?></a>
			<button class="deactivation-Modal-cancel"><?php _e( 'Cancel', 'paypal-for-woocommerce' ); ?></button>
		</div>
		<a href="<?php echo esc_attr( $deactivation_url ); ?>" class="button button-secondary"><?php _e( 'Skip & Deactivate', 'paypal-for-woocommerce' ); ?></a>
	</div>
</div>
<div class="deactivation-Modal-overlay"></div>

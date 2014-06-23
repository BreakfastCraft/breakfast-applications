<div class="wrap">
	<div class="icon32 icon32-posts-post" id="icon-edit"><br/></div>
	<h2><?php _e( 'Application Questions', 'breakfast-application' ) ?></h2>
	<?php if ( ! empty( $notice ) ): ?>
		<div id="notice" class="error"><p><?php echo $notice ?></p></div>
	<?php endif; ?>
	<?php if ( ! empty( $message ) ): ?>
		<div id="message" class="updated"><p><?php echo $message ?></p></div>
	<?php endif; ?>
	<form method="POST">
		<input type="hidden" name="nonce" value="<?php echo wp_create_nonce( basename( __FILE__ ) ) ?>"/>
		<?php do_meta_boxes( 'questions', 'normal', $questions ); ?>
	</form>
</div>
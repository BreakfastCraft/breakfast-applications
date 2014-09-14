<div class="wrap">
	<div class="icon32 icon32-posts-post" id="icon-edit"><br/></div>
	<h2><?php _e( 'Applications', 'breakfast-application' ) ?></h2>

	<?php if ( ! empty( $notice ) ): ?>
		<div id="notice" class="error"><p><?php echo $notice ?></p></div>
	<?php endif; ?>
	<?php if ( ! empty( $message ) ): ?>
		<div id="message" class="updated"><p><?php echo $message ?></p></div>
	<?php endif; ?>

	<form method="POST">
		<input type="hidden" name="nonce" value="<?php echo wp_create_nonce( 'view_application' ) ?>"/>
		<input type="hidden" name="id" value="<?php echo $application['id'] ?>"/>
		<dl>
			<dt>Username:</dt>
			<dd><?php echo $user->data->user_login; ?></dd>
			<dt>Display Name:</dt>
			<dd><?php echo $user->data->display_name; ?></dd>
			<dt>Email:</dt>
			<dd><?php echo $user->data->user_email; ?></dd>
			<dt>Age:</dt>
			<dd><?php echo $application['age']; ?></dd>
			<dt>Minecraft Name:</dt>
			<dd><?php echo $application['minecraft_name']; ?></dd>

			<?php foreach ( $answers as $answer ): ?>
				<dt><?php echo $answer['question']; ?></dt>
				<dd><?php echo $answer['answer']; ?></dd>
			<?php endforeach; ?>
		</dl>

		<h2>Fishbans Info</h2>

		<div>
			<?php if ( $ban_info['success'] ): ?>
				<dl>
					<dt>Total Bans: <?php echo $bans; ?></dt>
					<?php foreach ( $ban_info['bans']['service'] as $service => $values ): ?>
						<dt><?php echo $service; ?>: <?php echo $values['bans']; ?></dt>
						<?php foreach ( $values['ban_info'] as $server => $info ): ?>
							<dd><?php echo $server; ?> - <?php echo $info; ?></dd>
						<?php endforeach; ?>
					<?php endforeach; ?>
				</dl>
			<?php else: ?>
				<?php echo $ban_info['error']; ?>
			<?php endif; ?>
		</div>
		<?php if ( $application['status'] == 'pending' ): ?>
			<div><dl>
					<dt>Deny Reason:</dt>
			</dl>
				<dd>
				<textarea cols="70" rows="5" class="input"
				          name="reason"><?php echo $application['reason']; ?></textarea></dd>
			</div>
			<div style="margin-top: 15px;">
				<button name="op" value="approve" id="op_approve" class="button">Approve</button>
				<button name="op" value="deny" id="op_deny" class="button">Deny</button>
			</div>
		<?php else: ?>
			<div>
				<dl>
					<dt>Status:</dt>
					<dd><?php echo $application['status']; ?></dd>
					<?php if ( $application['status'] == 'denied' ): ?>
						<dt>Reason:</dt>
						<dd><?php echo $application['reason']; ?></dd>
					<?php endif; ?>
				</dl>
			</div>
		<?php endif; ?>
	</form>
</div>


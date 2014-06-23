<?php // $servers is available in scope ?>
<div id="breakfast-servers">
	<?php for ( $i = 0; $i < count( $servers ); $i ++ ): ?>
		<div class="breakfast-server" id="breakfast-<?php echo $i; ?>">
			<label for="host-<?php echo $i; ?>">Host:</label>
			<input type="text" name="servers[<?php echo $i; ?>][host]" id="host-<?php echo $i; ?>"
			       value="<?php echo isset( $_POST['servers'][ $i ] ) ? $_POST['servers'][ $i ]['host'] : $servers[ $i ]['host']; ?>"/>

			<label for="port-<?php echo $i; ?>">Port:</label>
			<input type="text" name="servers[<?php echo $i; ?>][port]" id="port-<?php echo $i; ?>"
			       value="<?php echo isset( $_POST['servers'][ $i ] ) ? $_POST['servers'][ $i ]['port'] : $servers[ $i ]['port']; ?>"/>

			<label type="text" for="pass-<?php echo $i; ?>">Password:</label>
			<input type="text" name="servers[<?php echo $i; ?>][pass]" id="pass-<?php echo $i; ?>"
			       value="<?php echo isset( $_POST['servers'][ $i ] ) ? $_POST['servers'][ $i ]['pass'] : $servers[ $i ]['pass']; ?>"/>

			<label for="host-<?php echo $i; ?>">Master:</label>
			<input type="hidden" name="servers[<?php echo $i; ?>][master]" value="0"/>
			<input type="checkbox" name="servers[<?php echo $i; ?>][master]" id="host-<?php echo $i; ?>"
			       value="1"
				<?php if ( ( isset( $_POST['servers'][ $i ] ) && $_POST['servers'][ $i ]['master'] ) || ( ! isset( $_POST['servers'][ $i ] ) && $servers[ $i ]['master'] ) ): ?>
					checked
				<?php endif; ?>>

			<button class="button remove">Remove</button>
			<hr/>
		</div>
	<?php endfor; ?>
</div>
<button id="breakfast-add-server" class="button">Add Server</button>
<input type="submit" name="submit" class="button" value="Save Servers"/>
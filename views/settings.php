<?php //$settings is available in scope ?>

	<table class="form-table">
		<tr>
			<th scope="row"><label for="notify-users">Notify New Applications</label></th>
			<td>
				<input type="text" id="notify-users" name="settings[notify_new]" value="<?php echo $settings['notify_new']; ?>">
				<p class="description">Comma separated list of users to notify about new applications.</p>
			</td>
		</tr>

		<tr>
			<th scope="row"><label for="notify-users">Notify Approved Applications</label></th>
			<td>
				<input type="text" id="notify-users" name="settings[notify_approved]" value="<?php echo $settings['notify_approved']; ?>">
				<p class="description">Comma separated list of users to notify about approved applications.</p>
			</td>
		</tr>
	</table>
<p class="submit">
	<input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes"/>
</p>

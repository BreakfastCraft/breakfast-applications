<?php //$settings is available in scope ?>

<table class="form-table">
	<tr>
		<th scope="row"><label for="notify-users">Notify New Applications</label></th>
		<td>
			<input type="text" id="notify-users" name="settings[notify_new]"
				   value="<?php echo $settings['notify_new']; ?>">

			<p class="description">Comma separated list of users to notify about new applications.</p>
		</td>
	</tr>

	<tr>
		<th scope="row"><label for="notify-users">Notify Approved Applications</label></th>
		<td>
			<input type="text" id="notify-users" name="settings[notify_approved]"
				   value="<?php echo $settings['notify_approved']; ?>">

			<p class="description">Comma separated list of users to notify about approved applications.</p>
		</td>
	</tr>
	<tr>
		<th scope="row"><label for="admin-notify-new-subject">Admin Notify New Applicant Subject</label></th>
		<td>
			<input type="text" id="admin-notify-new-subject" name="settings[admin_notify_new_subject]"
				   value="<?php if ( isset($settings['admin_notify_new_subject']) ) echo $settings['admin_notify_new_subject']; ?>">
		</td>
	</tr>
	<tr>
		<th scope="row"><label for="admin-notify-new-email">Admin Notify New Applicant Email</label></th>
		<td>
			<textarea id="admin-notify-new-email" name="settings[admin_notify_new_email]"><?php if ( isset ( $settings['admin_notify_new_email'] ) ) echo stripslashes($settings['admin_notify_new_email']); ?></textarea>
			<p class="description">Email template for notifying admin of new application. {{name}} </p>
		</td>
	</tr>
	<tr>
		<th scope="row"><label for="admin-notify-approval-subject">Admin Notify New Approval Subject</label></th>
		<td>
			<input type="text" id="admin-notify-approval-subject" name="settings[admin_notify_approval_subject]"
				   value="<?php if ( isset($settings['admin_notify_approval_subject']) ) echo $settings['admin_notify_approval_subject']; ?>">
		</td>
	</tr>
	<tr>
		<th scope="row"><label for="admin-notify-approved-email">Admin Notify New Approval Email</label></th>
		<td>
			<textarea id="admin-notify-approved-email" name="settings[admin_notify_approval_email]"><?php if ( isset ( $settings['admin_notify_approval_email'] ) ) echo stripslashes($settings['admin_notify_approval_email']); ?></textarea>
			<p class="description">Email template for notifying admin of new application. {{name}}</p>
		</td>
	</tr>
	<tr>
		<th scope="row"><label for="user-notify-approval-subject">User Notify Approval Subject</label></th>
		<td>
			<input type="text" id="user-notify-approval-subject" name="settings[user_notify_approval_subject]"
				   value="<?php if ( isset($settings['user_notify_approval_subject']) ) echo $settings['user_notify_approval_subject']; ?>">
		</td>
	</tr>
	<tr>
		<th scope="row"><label for="user-notify-approval-email">User Notify Approval Email</label></th>
		<td>
			<textarea id="user-notify-approval-email" name="settings[user_notify_approval_email]"><?php if ( isset ( $settings['user_notify_approval_email'] ) ) echo stripslashes( $settings['user_notify_approval_email']); ?></textarea>
			<p class="description">Email template for notifying user of application approval. {{note}}</p>
		</td>
	</tr>
	<tr>
		<th scope="row"><label for="user-notify-denial-subject">User Notify Denial Subject</label></th>
		<td>
			<input type="text" id="user-notify-denial-subject" name="settings[user_notify_denial_subject]"
				   value="<?php if ( isset($settings['user_notify_denial_subject'] ) ) echo $settings['user_notify_denial_subject']; ?>">
		</td>
	</tr>
	<tr>
		<th scope="row"><label for="user-notify-denial-email">User Notify Denial Email</label></th>
		<td>
			<textarea id="user-notify-denial-email" name="settings[user_notify_denial_email]"><?php if ( isset ( $settings['user_notify_denial_email'] ) ) echo stripslashes ( $settings['user_notify_denial_email']); ?></textarea>
			<p class="description">Email template for notifying user of application denial. {{reason}}</p>
		</td>
	</tr>
</table>
<p class="submit">
	<input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes"/>
</p>

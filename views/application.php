<?php if ( ! empty( $message ) ): ?>
	<div class="alert alert-success">
		<?php echo $message; ?>
	</div>
<?php endif; ?>
<div class="well">
	<form method="POST" class="form-vertical">
		<input type="hidden" name="nonce" value="<?php echo wp_create_nonce(); ?>"/>
		<fieldset>
			<div class="form-group">
				<label for="minecraft_name">Minecraft Username</label>

				<input type="text" class="form-control" id="minecraft_name"
				       value="<?php echo ( isset( $_POST['minecraft_name'] ) ) ? $_POST['minecraft_name'] : $application['minecraft_name']; ?>"
				       name="minecraft_name"/>

			</div>
			<div class="form-group">
				<label for="age">Age</label>


				<input type="text" class="form-control" id="age" name="age"
				       value="<?php echo ( isset( $_POST['age'] ) ) ? $_POST['age'] : $application['age']; ?>"/>

			</div>
			<?php foreach ( $questions as $question ): ?>

				<?php if ( $application == null ) {
					$answer = '';
				} else {
					$answer = $wpdb->get_var( "SELECT answer FROM " . self::$answer_table . " WHERE question_id={$question['id']} AND application_id={$application['id']}" );
				} ?>

				<div class="form-group">
					<label
						for="question-<?php echo $question['id']; ?>"><?php echo $question['question']; ?></label>
					<?php if ( $question['format'] == 'textarea' ): ?>
						<textarea class="form-control" id="question-<?php echo $question['id']; ?>"
						          name="question-<?php echo $question['id']; ?>"><?php echo ( isset( $_POST[ 'question-' . $question['id'] ] ) ) ? $_POST[ 'question-' . $question['id'] ] : $answer; ?></textarea>
					<?php else: ?>
						<input type="text" class="form-control" id="question-<?php echo $question['id']; ?>"
						       name="question-<?php echo $question['id']; ?>"
						       value="<?php echo ( isset( $_POST[ 'question-' . $question['id'] ] ) ) ? $_POST[ 'question-' . $question['id'] ] : $answer; ?>"/>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
			<div class="form-group">
				<input type="submit" value="Submit" name="submit" class="btn btn-primary"/>
			</div>
		</fieldset>
	</form>
</div>
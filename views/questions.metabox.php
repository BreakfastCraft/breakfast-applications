<?php //$questions is available in scope ?>
<div id="breakfast-questions">
	<?php foreach ( $questions as $question ): ?>
		<div class="breakfast-question" id="breakfast-<?php echo $question['id']; ?>">
			<input type="hidden" value="<?php echo $question['id']; ?>"
			       name="questions[<?php echo $question['id']; ?>][id]"/>
			<textarea cols="70" rows="5" class="input"
			          name="questions[<?php echo $question['id']; ?>][question]"><?php echo $question['question']; ?></textarea><br/>
			<select name="questions[<?php echo $question['id']; ?>][format]">
				<option value="text" <?php echo ( $question['format'] == 'text' ) ? 'selected' : ''; ?>>Text
				</option>
				<option
					value="textarea" <?php echo ( $question['format'] == 'textarea' ) ? 'selected' : ''; ?>>
					Textarea
				</option>

			</select><br/>
			<button class="button remove">Remove</button>
			<hr/>
		</div>
	<?php endforeach; ?>
</div>
<button id="breakfast-add-question" class="button">Add Question</button>
<input type="submit" name="submit" class="button" value="Save Questions"/>
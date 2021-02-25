<div class="box panel">
	<div class="tbl-ctrls panel-body">
		<?=form_open(ee('CP/URL')->make('addons/settings/emoji_support/convert'))?>
			<h1><?=lang('emoji_support_module_name')?></h1>

			<?=ee('CP/Alert')->getAllInlines()?>

			<p><?=lang('convert')?></p>

			<textarea><?=implode("\n", $sql)?></textarea>

			<br />

			<div class="progress-bar">
				<div class="progress" style="width: 0%;"></div>
			</div>

			<br />

			<fieldset class="form-ctrls">
				<?=cp_form_submit('btn_update_db', lang('btn_update_db_working'), 'convert')?>
			</fieldset>

		<?=form_close();?>
	</div>
</div>

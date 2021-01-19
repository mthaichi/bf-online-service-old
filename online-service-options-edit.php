<div class="wrap" id="custom-logo">
<?php $options = $this->options; ?>
<h2>オンライン礼拝設定</h2>
<?php if ( !empty($_POST) ) : ?>
<div id="message" class="updated fade"><p><strong><?php _e('Options saved.') ?></strong></p></div>
<?php endif; ?>
<?php if ( !$this->is_acf_installed ): ?>
<div id="message" class="notice notice-error"><p><strong>プラグイン Advanced Custom Field を有効にしてください。</strong></p></div>

<?php endif; ?>

<form method="post" action="options.php">
<?php settings_fields( $this->setting_group_name ); ?>
<?php do_settings_sections( $this->setting_group_name ); ?>

<h3>各種設定</h3>
<table class="form-table">
<tr>
<th>Youtubeの待機画像</th>
<td><?php $this->generate_upload_image_tag('online_service_wait_image_url', $options['online_service_wait_image_url']); ?></td>
</tr>
<tr>
<th>バックナンバーURL</th>
<td><input type="url" name="online_service_setting[online_service_backnumber_url]" value="<?php echo $options['online_service_backnumber_url']; ?>" size="70"></td></tr>
</table>

<h3>テンプレート（上級者向け）</h3>
<table class="form-table">
<tr>
<th>タイトルHTML</th>
<td><textarea rows="4" cols="70" name="online_service_setting[title_html]"><?php echo $options['title_html']; ?></textarea></td>
</tr>
<tr>
<th>Youtube画面埋め込みHTML</th>
<td><textarea rows="4" cols="70" name="online_service_setting[embed_youtube_html]"><?php echo $options['embed_youtube_html']; ?></textarea></td>
</tr>
<tr>
<th>Youtube待機画面HTML</th>
<td><textarea rows="4" cols="70" name="online_service_setting[wait_youtube_html]"><?php echo $options['wait_youtube_html']; ?></textarea></td>
</tr>
<tr>
<th>説教要旨PDFリンクHTML</th>
<td><textarea rows="4" cols="70" name="online_service_setting[message_pdf_html]"><?php echo $options['message_pdf_html']; ?></textarea></td>
</tr>
<tr>
<th>週報表面HTML</th>
<td><textarea rows="4" cols="70" name="online_service_setting[shuho1_html]"><?php echo $options['shuho1_html']; ?></textarea></td>
</tr>
<tr>
<th>週報裏面HTML</th>
<td><textarea rows="4" cols="70" name="online_service_setting[shuho2_html]"><?php echo $options['shuho2_html']; ?></textarea></td>
</tr>
<th>説教音声HTML</th>
<td><textarea rows="4" cols="70" name="online_service_setting[shuho2_html]"><?php echo $options['message_mp3_html']; ?></textarea></td>
</tr>
</table>
<?php submit_button(); ?>
</form>
</div>
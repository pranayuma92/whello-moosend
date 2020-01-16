<style type="text/css">
	.form-table tr input.settings-input, .form-table tr textarea {
	    width: 50%;
	}
</style>
<div class="wrap whello-moosend-setting">
	<h2>Whello Moosend Settings</h2>
	<?php 
		settings_errors(); 

		if(empty(get_option('api_key'))){
			echo '<div class="notice notice-warning">';
			echo '<p>Moosend API key is not available. Please enter the API key for this plugin to work. If you don\'t have it please visit <a href="https://moosend.com" target="_blank">https://moosend.com</a> to create one</p>';
			echo '</div>';
		}
	?>

	<form method="post" action="options.php" class="postbox" style="padding: 10px 20px;">
  	<?php
	  	
        settings_fields('wm_field');
        do_settings_sections('wm_settings');
        
        submit_button(); 
    ?>   
	</form>
	<?php if(!empty(get_option('api_key'))){ ?>
		<div class="postbox" style="padding: 10px 20px;">
			<h2>Cache settings</h2>
			<p>Last update: <?php echo (new WHMoosendApi())->getLastUpdate()?></p>
			<p class="submit">
				<input type="submit" id="update-cache" class="button button-primary" value="Update cache data">
			</p>
		</div>
	<?php } ?>
</div>
<script type="text/javascript">
	(function($){
		$(document).ready(function(){
			console.log('init');
			$('#update-cache').on('click', function(){
				var _this = $(this);
				_this.val('Fecthing data...');
				$.get('<?php echo admin_url('admin-ajax.php?action=update_cache') ?>', function(data){
					if(data){
						_this.val('Cache updatetd');
						window.location.reload();
					}
				})
			})
		})
	})(jQuery)
</script>

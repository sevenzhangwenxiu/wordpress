<?php 
if (! defined ( 'ABSPATH' ))
    exit (); // Exit if accessed directly

$channels = XH_Social::instance()->channel->get_social_channels(array('login'));
global $current_user;
if(!is_user_logged_in()){
    return;
}
?>
<div class="xh-regbox" style="width:100%;border:0;padding:0;">
<div class="um-account-heading uimob340-hide uimob500-hide" ><?php echo __('Account binding/unbundling',XH_SOCIAL)?></div>
<?php 
if($channels){
    foreach ($channels as $channel){
        ?>
        <div class="um-field um-field-text" >
            	<span class="xh-text"><img src="<?php echo $channel->icon?>" style="width:25px;vertical-align:middle;"/> <?php echo $channel->title?></span>
            	<span style="float:right;">
            	<?php echo $channel->bindinfo($current_user->ID);?>
				</span>
		</div>
        <?php 
    }
}
?> </div>
<?php 
?>
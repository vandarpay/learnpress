<?php
defined( 'ABSPATH' ) || exit();
?>
<?php $settings = LP()->settings; ?>
<p><?php echo $this->get_description(); ?></p>
<div id="learn-press-vandar-form" class="<?php if(is_rtl()) echo ' learn-press-form-vandar-rtl'; ?>">
    <p class="learn-press-form-row">
        <label><?php echo wp_kses( __( 'ایمیل', 'vandar-learnpress' ), array( 'span' => array() ) ); ?></label>
        <input type="text" name="learn-press-vandar[email]" id="learn-press-vandar-payment-email" value=""
		placeholder="لطفا ایمیل خود را وارد نمایید..." required/>
		<div class="learn-press-vandar-form-clear"></div>
    </p>
	<div class="learn-press-vandar-form-clear"></div>
    <p class="learn-press-form-row">
        <label><?php echo wp_kses( __( 'موبایل', 'vandar-learnpress' ), array( 'span' => array() ) ); ?></label>
        <input type="text" name="learn-press-vandar[mobile]" id="learn-press-vandar-payment-mobile" value=""
               placeholder="لطفا شماره موبایل خود را وارد نمایید..." required/>
		<div class="learn-press-vandar-form-clear"></div>
    </p>
	<div class="learn-press-vandar-form-clear"></div>
</div>

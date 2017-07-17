<?php /* Template Name: tpl-moneris-en */?>

<?php get_header(); ?>

<div id="content" class="section">

<?php arras_above_content() ?>

<?php 
if ( arras_get_option('single_meta_pos') == 'bottom' ) add_filter('arras_postfooter', 'arras_postmeta');
else add_filter('arras_postheader', 'arras_postmeta');
?>

<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
	<?php arras_above_post() ?>
	<div id="post-<?php the_ID() ?>" <?php arras_single_post_class() ?>>
        <?php arras_postheader() ?>
      
		<div class="entry-content">
            <p>A&eacute;roport de Qu&eacute;bec inc., responsible for the management of Jean Lesage International Airport is pleased to offer a secure, free online bill pay service. Pay your bills anytime (maximum $5000) by Visa, Mastercard or American Express. To continue, please complete the form below: </p>
            
            <FORM name="monerisForm" onsubmit="return validateFormEn()" METHOD="POST" ACTION="https://www3.moneris.com/HPPDP/index.php">
                <INPUT TYPE="HIDDEN" NAME="ps_store_id" VALUE="HEJGN34601">
                <INPUT TYPE="HIDDEN" NAME="hpp_key" VALUE="hpGNT7XXC84H">
                <INPUT TYPE="HIDDEN" NAME="lang" VALUE="en-ca">
            
                <!--MORE OPTIONAL VARIABLES CAN BE DEFINED HERE -->
                <label><strong>Last Name</strong></label><br />
                <INPUT type="text" NAME="bill_last_name" VALUE="" required><br /><br />
                
                <label><strong>First Name</strong></label><br />
                <INPUT type="text" NAME="bill_first_name" VALUE="" required><br /><br />    
                
                <label><strong>Company Name</strong></label><br />
                <INPUT type="text" NAME="bill_company_name" VALUE="" required><br /><br />
            
                <label><strong>Invoice Number</strong></label><br />
                <INPUT type="text" NAME="order_id" VALUE="" required><br /><br />
            
                <label><strong>Client ID</strong></label><br />
                <INPUT type="text" NAME="cust_id" VALUE="" required><br /><br />
            
                <label><strong>Amount Due</strong></label><br />
                <INPUT type="text" NAME="charge_total" VALUE="" required> 
                (Please enter decimals i.e. : 20.00)<br /><br />
                <INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="Click here to make your secure payment">
                <!-- <INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="Click to proceed to Secure Page"> -->
            </FORM>
            <br />
            <p><span style="font-size:10px; color:#999;">*You will be redirected to our partner, a secure site where you can pay by Visa, Master Card or American Express. </span></p>
		</div>
        
        <!-- <?php trackback_rdf() ?> -->
		<?php arras_postfooter() ?>

        <?php if ( arras_get_option('display_author') ) : ?>
        <div class="about-author clearfix">
        	<h4><?php _e('About the Author', 'arras') ?></h4>
            <?php echo get_avatar(the_author_ID, 48); ?>
            <?php the_author_description(); ?>
        </div>
        <?php endif; ?>
    </div>
    
	<?php arras_below_post() ?>
<?php displayComments() ?>
    
<?php endwhile; else: ?>

<?php arras_post_notfound() ?>

<?php endif; ?>

<?php arras_below_post() ?>
<?php if ( get_post_custom_values('comments') ) comments_template('', true) ?>
<?php arras_below_comments() ?>

<?php arras_below_content() ?>
</div><!-- #content -->

<?php

	if(ICL_LANGUAGE_CODE=='en'){
		if(is_page('105')){
		get_sidebar('blog-en');
		}
	else {
		get_sidebar('en');
	}
	}
	
	if(ICL_LANGUAGE_CODE=='fr'){
		if(is_page('103')){
		get_sidebar('blog-fr');
		}
	else {
		get_sidebar();
	}
	}
?>

<?php get_footer(); ?>
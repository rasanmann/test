<?php /* Template Name: tpl-moneris */?>

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
            <p>A&eacute;roport de Qu&eacute;bec inc. (AQi), gestionnaire de l'a&eacute;roport international Jean-Lesage de Qu&eacute;bec, met &agrave; votre disposition un service gratuit de paiement en ligne qui vous permet d'acquitter  vos factures en tout temps et en toute s&eacute;curit&eacute; (maximum de 5000$) par Visa, MasterCard ou American Express. Pour utiliser le service, il vous suffit de compl&eacute;ter les champs suivants :</p>
            
            <FORM name="monerisForm" onsubmit="return validateForm()" METHOD="POST" ACTION="https://www3.moneris.com/HPPDP/index.php">
                <INPUT TYPE="HIDDEN" NAME="ps_store_id" VALUE="HEJGN34601">
                <INPUT TYPE="HIDDEN" NAME="hpp_key" VALUE="hpOUJB91ZLFF">
                <INPUT TYPE="HIDDEN" NAME="lang" VALUE="fr-ca">
            
                <!--MORE OPTIONAL VARIABLES CAN BE DEFINED HERE -->
                <label><strong>Nom</strong></label><br />
                <INPUT type="text" NAME="bill_last_name" VALUE="" required><br /><br />
                
                <label><strong>Pr&eacute;nom</strong></label><br />
                <INPUT type="text" NAME="bill_first_name" VALUE="" required><br /><br />    
                
                <label><strong>Nom de votre entreprise</strong></label><br />
                <INPUT type="text" NAME="bill_company_name" VALUE="" required><br /><br />
            
                <label><strong>Num&eacute;ro de facture</strong></label><br />
                <INPUT type="text" NAME="order_id" VALUE="" required><br /><br />
            
                <label><strong>Num&eacute;ro de client</strong></label><br />
                <INPUT type="text" NAME="cust_id" VALUE="" required><br /><br />
            
                <label><strong>Montant &agrave; d&eacute;bourser</strong></label><br />
                <INPUT type="text" NAME="charge_total" VALUE="" required> (Entrer les d&eacute;cimales Ex.: 20.00)<br /><br />
                <INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="Cliquez ici pour faire votre paiement s&eacute;curis&eacute;">
                <!-- <INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="Click to proceed to Secure Page"> -->
            </FORM>
            <br />
            <p><span style="font-size:10px; color:#999;">* Notez que vous serez redirig&eacute; chez notre partenaire pour effectuer votre paiement de facon s&eacute;curitaire. Le paiement s'effectue au moyen de votre carte de cr&eacute;dit Visa, MasterCard ou American Express.</span></p>
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
jQuery(document).ready(function($){

    var masterBlock = $('#block-yqbblockalert');

    //get the block
    var block = $('#block-yqbblockalert .form-group');
   
    //get the block innerhtml
    var blockText = block.html();

    //get the link
    var link = $('#block-yqbblockalert .form-group ~ .link');

    //get the link
    var linkText = $('#block-yqbblockalert .form-group ~ .link').html();

    //add them to the block innerhtml

    block.html(blockText + " " + linkText);
    link.remove();
    
    masterBlock.css({
	"display":"block",
    });
    
    

});

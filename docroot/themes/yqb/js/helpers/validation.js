var Drupal = Drupal || {};

var Validation = (function ($, Drupal, Bootstrap) {
	var self = this;
	
	self.validate = function($container, parent){
		var valid = true;
	
		//Validate empty
		$container.find('input.required, select.required, textarea.required').each(function(){
			var $this = $(this);
			
			if($this.val() == ''){
				$this.parents(parent).addClass('error error-empty');
				valid = false;
				
			}else if($this.parents(parent).find('input.needed').length != 0){
				var vide = false;
				
				$this.parents(parent).find('.needed').each(function(){
					if($(this).val() == ''){
						vide = true;
					}
				})
				
				if(vide){
					$this.parents(parent).addClass('error error-empty');
					valid = false;	
				}else{
					$this.parents(parent).removeClass('error error-empty');
				}
			}else{
				$this.parents(parent).removeClass('error error-empty');
			}
		});
		
		//Validate checkbox
		$container.find('.checkbox.required input[type="checkbox"]').each(function(){
			var $this = $(this);
			
			if($('input[name="'+$this.attr('name')+'"]').is(':checked')){
				$this.parents(parent).removeClass('error error-checkbo');
			}else{
				$this.parents(parent).addClass(' error error-checkbox');
				valid = false;
			}
		});
		
		//Validate radio button
		$container.find('.radio.required').each(function(){
			var $this = $(this),
				$input_name = $this.find('input[type="radio"]').attr('name');
			
			if($('input[name="'+$input_name+'"]').is(':checked')){
				$this.removeClass('error error-checkbox');
			}else{
				$this.addClass('error error-checkbox');
				valid = false;
			}
		});
		
//		if(valid){
			//Validate minLength
			$container.find('.minLength').each(function(){
				var $this = $(this);
				
				if(!self.validator.minLength($this.val(), $this.data('limit'))){
					valid = false;	
					$(this).parents(parent).addClass('error error-length');	
				}else{
					$(this).parents(parent).removeClass('error error-length');
				}
			});
			
			//Validate maxLength
			$container.find('.maxLength').each(function(){
				var $this = $(this);
				
				if(!self.validator.maxLength($this.val(), $this.data('limit'))){
					valid = false;	
					$(this).parents(parent).addClass('error error-length');	
				}else{
					$(this).parents(parent).removeClass('error error-length');
				}
			});
		
			//Validate email
			$container.find('input.validEmail').each(function(){
				var $this = $(this);
				
				if(!self.validator.email($this.val())){
					$this.parents(parent).addClass('error error-email');
					valid = false;
				}else{
					$this.parents(parent).removeClass('error error-email');
				}
			});
		
			//Validate numeric
			$container.find('input.validNumber').each(function(){
				var $this = $(this);
				
				if(!self.validator.numeric($this.val())){
					$this.parents(parent).addClass('error error-numeric');
					valid = false;
				}else{
					$this.parents(parent).removeClass('error error-numeric');
				}
			});
		
			//Validate date past today
			$container.find('input.validUpcomingDate').each(function(){
				var $this = $(this);
				
				if(!self.validator.upcoming($this.val())){
					$this.parents(parent).addClass('error error-upcoming');
					valid = false;
				}else{
					$this.parents(parent).removeClass('error error-upcoming');
				}
			});
			
			//Validate phone
			$container.find('input.validPhone').each(function(){
				var $this = $(this),
					telephone = '';
				
				$this.parent().find('input').each(function(){
					if($(this).attr('name').indexOf('ext') == -1){
						telephone += $(this).val();	
					}
				});
				
				var validPhone = (telephone.length >= 7);
				
				if(!validPhone){
					$(this).parents(parent).addClass('error error-telephone');
					valid = false;
				}else{
					$(this).parents(parent).removeClass('error error-telephone');
				}
			});
			
			//Validate Canada postal code
			$container.find('input.zipcode-ca').each(function(){
				var $this = $(this);
				
				if(!self.validator.cazip($this.val())){
					$this.parents(parent).addClass('error error-postalCode');
					valid = false;
				}else{
					$this.parents(parent).removeClass('error error-postalCode');
				}
			});
			
			//Validate USA zipcode
			$container.find('input.zipcode-us').each(function(){
				var $this = $(this);
				
				if($this.val() != ''){
					if(!self.validator.uszip($this.val())){
						$this.parents(parent).addClass('error error-postalCode');
						valid = false;
					}else{
						$this.parents(parent).removeClass('error error-postalCode');
					}
				}
			});
			
			//Validate mirror fields
			if(valid){
				$container.find('input.same').each(function(){
					var $this = $(this);
					
					if($this.attr('id').indexOf('_confirm') != -1){
						comparison_field = $this.attr('id').replace('_confirm', '');
					}else{
						comparison_field = $this.attr('id') + '_confirm';
					}
					if($this.val() != $('#'+comparison_field).val() && $('#'+comparison_field).val() != ''){
						$this.parents(parent).addClass('error error-same');
						$('#'+comparison_field).parents(parent).addClass('error error-same');
						valid = false;
					}else{
						$this.parents(parent).removeClass('error error-same');
						$('#'+comparison_field).parents(parent).removeClass('error error-same');
					}
				});
			}
//		}
		
		return valid;
	}
	
	self.onPhoneKeyDown = function(e){
		// Allow only backspace, delete, tab, left , right
		if ((e.keyCode < 48 || e.keyCode > 57) && (e.keyCode < 96 || e.keyCode > 105) && ( e.keyCode != 46 && e.keyCode != 8 && e.keyCode != 9 && e.keyCode != 37 && e.keyCode != 39 && e.keyCode != 109) ) {
				e.preventDefault();    
		}
	}

	/**
	 *  REGEX VALIDATIONS FUNTIONS
	 */
	
	self.validator = {
		USPhone: function (val) {
	        return /^\(?(\d{3})\)?[\- ]?\d{3}[\- ]?\d{4}$/.test(val);
	    },
		
	    uszip: function (val) {
	        return /^\d{5}(?:[-\s]\d{4})?$/.test(val);
	    },
	    cazip: function (val) {
	        return /^[ABCEGHJKLMNPRSTVXYabceghjklmnprstvxy]\d[A-Za-z][ ]?\d[A-Za-z]\d$/.test(val);
	    },
	
	    // matches mm/dd/yyyy (requires leading 0's (which may be a bit silly, what do you think?)
	    date: function (val) {
	        return /^(?:0[1-9]|1[0-2])\/(?:0[1-9]|[12][0-9]|3[01])\/(?:\d{4})/.test(val);
	    },
	
	    email: function (val) {
	        return /^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+$/.test(val);
	    },
	
	    numeric: function (val) {
	        return /^[0-9]+$/.test(val);
	    },
		
		upcoming: function (val) {
			var myDate = new Date(val),
				today = new Date();
			today.setUTCHours(0,0,0,0);
			
			return (myDate >= today);
		},
	
	    minLength: function (val, length) {
	        return val.length >= length;
	    },
	
	    maxLength: function (val, length) {
	        return val.length <= length;
	    },
	
	    equal: function (val1, val2) {
	        return (val1 == val2);
	    }
	};
	
	self.onErrorInputFocus = function(ev) {
		ev.preventDefault();
		
		var $this = $(this),
			$formGroup = $this.closest('.form-group'),
			$form = $this.parents('form');

		if ($formGroup.hasClass('error')) {
			// Specific to radio buttons
			if ($formGroup.hasClass('error-radio')) {
				$form.find('.radio.required').each(function() {
					var $this = $(this),
						$inputName = $this.attr('name');

					$('input[name="' + $inputName + '"]').parents($formGroup).removeClass('error error-radio');
				});
			}

			$formGroup.each(function() {
				var $this = $(this),
					classes = $this.attr('class').split(' ');

				for (var i = 0; i < classes.length; i++) {
					if (classes[i].indexOf('error') != -1) {
						$this.removeClass(classes[i]);
					}
				}
			});
		}
	};
	
	/** ------------------------------
	 * Constructor
	 --------------------------------- */

	/** ------------------------------
	 * Constructor
	 --------------------------------- */

	var construct = (function() {
		
	})();

	return self;
})(window.jQuery, window.Drupal, window.Drupal.bootstrap);
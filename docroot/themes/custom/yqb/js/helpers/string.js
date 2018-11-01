function randomString() {
    var text = "";
    var possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";

    for( var i=0; i < 5; i++ )
        text += possible.charAt(Math.floor(Math.random() * possible.length));

    return text;
}


/**
 *	Vérification d'un courriel
 *
 *	@param  __courriel  un string contenant un courriel
 *	@return      TRUE si valide, FALSE sinon
 */
function validateEmail(email) {
	var valide = true;
	
	var filter = /\b[\w\.-]+@[\w\.-]+\.\w{2,4}\b/;
	
	if (!filter.test(email)) {
		valide = false;
	}
	
	return valide;
}

/**
 *	Encode les entités HTML
 *
 *	@param  __string  un string contenant un courriel
 *	@return      même string encodé
 */
function encodeHTMLEntities(__string) {
	var str = __string;
	
	var translation = Array(
		['&aacute;','á'], ['&acirc;','â'], ['&aelig;','æ'], ['&agrave;','à'], ['&aring;','å'], ['&atilde;','ã'], ['&auml;','ä'],
		['&AElig;','Æ'], ['&Aacute;','Á'], ['&Acirc;','Â'], ['&Agrave;','À'], ['&Aring;','Å'], ['&Atilde;','Ã'], ['&Auml;','Ä'],
		['&eacute;','é'], ['&ecirc;','ê'], ['&egrave;','è'], ['&eth;','ð'], ['&euml;','ë'],
		['&Eacute;','É'], ['&Ecirc;','Ê'], ['&Egrave;','È'], ['&Euml;','Ë'],
		['&iacute;','í'], ['&icirc;','î'], ['&iexcl;','¡'], ['&igrave;','ì'], ['&iquest;','¿'], ['&iuml;','ï'],
		['&Iacute;','Í'], ['&Icirc;','Î'], ['&Igrave;','Ì'], ['&Iuml;','Ï'], 
		['&oacute;','ó'], ['&ocirc;','ô'], ['&ograve;','ò'], ['&oslash;','ø'], ['&otilde;','õ'], ['&ouml;','ö'],
		['&Oacute;','Ó'], ['&Ocirc;','Ô'], ['&Ograve;','Ò'], ['&Oslash;','Ø'], ['&Otilde;','Õ'], ['&Ouml;','Ö'], 
		['&uacute;','ú'], ['&ucirc;','û'], ['&ugrave;','ù'], ['&uuml;','ü'],
		['&Uacute;','Ú'], ['&Ucirc;','Û'], ['&Ugrave;','Ù'], ['&Uuml;','Ü'],
		['&Ntilde;','Ñ'], ['&THORN;','Þ'],
		['&ccedil;','ç'], ['&Ccedil;','Ç'], ['&ETH;','Ð'], ['&Yacute;','Ý'],  
		['&brvbar;','¦'], ['&gt;','>'], ['&gt','>'], ['&cent;','¢'], ['&copy;','©'],['&deg;','°'], ['&frac12;','½'], ['&frac14;','¼'], ['&frac34;','¾'], 
		['&laquo;','«'], ['&lt;','<'], ['&lt','<'], ['&mdash;','—'], ['&micro;','µ'], ['&middot;','·'], ['&ndash;','–'], ['&not;','¬'], 
		['&ntilde;','ñ'], 
		['&para;','¶'], ['&plusmn;','±'], ['&pound;','£'], ['&quot;','\"'], ['&raquo;','»'], ['&reg;','®'], ['&sect;','§'], ['&sup1;','¹'], ['&sup2;','²'], ['&sup3;','³'], ['&szlig;','ß'], ['&thorn;','þ'], ['&tilde;','˜'], ['&trade;','™'], 
		['&yacute;','ý'], ['&yen;','¥'], ['&yuml;','ÿ']
	);
	
	for (var i = 0; i < translation.length; i++) {
		var set = translation[i];
		
		str = str.replace(new RegExp(set[1], "g"), set[0]);
	}
	
	return str;
}

function removeAccents(str){
	var strAccents = str.split('');
	var strAccentsOut = new Array();
	var strAccentsLen = strAccents.length;
	
	var accents = 'ÀÁÂÃÄÅàáâãäåÒÓÔÕÕÖØòóôõöøÈÉÊËèéêëðÇçÐÌÍÎÏìíîïÙÚÛÜùúûüÑñŠšŸÿýŽž';
	var accentsOut = ['A','A','A','A','A','A','a','a','a','a','a','a','O','O','O','O','O','O','O','o','o','o','o','o','o','E','E','E','E','e','e','e','e','e','C','c','D','I','I','I','I','i','i','i','i','U','U','U','U','u','u','u','u','N','n','S','s','Y','y','y','Z','z'];
	
	for (var y = 0; y < strAccentsLen; y++) {
		if (accents.indexOf(strAccents[y]) != -1) {
			strAccentsOut[y] = accentsOut[accents.indexOf(strAccents[y])];
		}
		else {
			strAccentsOut[y] = strAccents[y];
		}
	}
	strAccentsOut = strAccentsOut.join('');
	return strAccentsOut;
}

function slug(str) {
	str = str.toLowerCase();
	str = removeAccents(str);
	str = str.replace(/[^a-z0-9\s-]/g, '');
	str = str.replace(/\s+/g, ' ');
	str = str.replace(/\s/g, '-');
	
	return str;
}

// Cette fonction est utilisé avec jquery autocomplete.
// Pour une solution qui ignore les accents, voir fromagesdici
function getAccentMap(){
	var accentsMap = {
		'À': 'A',
		'Á': 'A',
		'Â' : 'A',
		'Ã' : 'A',
		'Ä' : 'A',
		'Å' : 'A',
		'à' : 'a',
		'á' : 'a',
		'â' : 'a',
		'ã' : 'a',
		'ä' : 'a',
		'å' : 'a',
		'Ò' : 'O',
		'Ó' : 'O',
		'Ô' : 'O',
		'Õ' : 'O',
		'Ö' : 'O',
		'Ø' : 'O',
		'ò' : 'o',
		'ó' : 'o',
		'ô' : 'o',
		'õ' : 'o',
		'ö' : 'o',
		'ø' : 'o',
		'È' : 'E',
		'É' : 'E',
		'Ê' : 'E',
		'Ë' : 'E',
		'è' : 'e',
		'é' : 'e',
		'ê' : 'e',
		'ë' : 'e',
		'ð' : 'e',
		'Ç' : 'C',
		'ç' : 'c',
		'Ð' : 'D',
		'Ì' : 'I',
		'Í' : 'I',
		'Î' : 'I',
		'Ï' : 'I',
		'ì' : 'i',
		'í' : 'i',
		'î' : 'i',
		'ï' : 'i',
		'Ù' : 'U',
		'Ú' : 'U',
		'Û' : 'U',
		'Ü' : 'U',
		'ù' : 'u',
		'ú' : 'u',
		'û' : 'u',
		'ü' : 'u'
	}
	
	return accentsMap;
}
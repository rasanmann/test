
function pad(str, character, num) {
	str = String(str);
	
	while (str.length < num) {
		str = character + str;
	}
	
	return str;
}
/**
*	Array Remove - By John Resig (MIT Licensed)
*	Supprimer un ou des éléments d'un tableau
*	Exemples : 
*		Remove the second item from the array
*		array.remove(1);
*		Remove the second-to-last item from the array
*		array.remove(-2);
*		Remove the second and third items from the array
*		array.remove(1,2);
*		Remove the last and second-to-last items from the array
*		array.remove(-2,-1);
*
*	@param  from  l'élément de départ à supprimer 
*	@param  to  l'élément de fin à supprimer (optionnel)
*	@return      la valeur 
*/
Array.prototype.remove = function(from, to) {
	var rest = this.slice((to || from) + 1 || this.length);
	this.length = from < 0 ? this.length + from : from;
	return this.push.apply(this, rest);
}
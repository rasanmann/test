// Constants
Math.TWOPI = Math.PI * 2;

/**
 *	Point class
 */

function Point(x, y){
	this.x = x || 0;
	this.y = y || 0;
};

Point.prototype.x = null;
Point.prototype.y = null;

Point.prototype.add = function(v){
	return new Point(this.x + v.x, this.y + v.y);
};

Point.prototype.clone = function(){
	return new Point(this.x, this.y);
};

Point.prototype.degreesTo = function(v){
	var dx = this.x - v.x;
	var dy = this.y - v.y;
	var angle = Math.atan2(dy, dx); // radians
	return angle * (180 / Math.PI); // degrees
};

Point.prototype.distance = function(v){
	var x = this.x - v.x;
	var y = this.y - v.y;
	return Math.sqrt(x * x + y * y);
};

Point.prototype.equals = function(toCompare){
	return this.x == toCompare.x && this.y == toCompare.y;
};

Point.prototype.interpolate = function(v, f){
	return new Point((this.x + v.x) * f, (this.y + v.y) * f);
};

Point.prototype.length = function(){
	return Math.sqrt(this.x * this.x + this.y * this.y);
};

Point.prototype.normalize = function(thickness){
	var l = this.length();
	this.x = this.x / l * thickness;
	this.y = this.y / l * thickness;
};

Point.prototype.orbit = function(origin, arcWidth, arcHeight, degrees){
	var radians = degrees * (Math.PI / 180);
	this.x = origin.x + arcWidth * Math.cos(radians);
	this.y = origin.y + arcHeight * Math.sin(radians);
};

Point.prototype.offset = function(dx, dy){
	this.x += dx;
	this.y += dy;
};

Point.prototype.subtract = function(v){
	return new Point(this.x - v.x, this.y - v.y);
};

Point.prototype.toString = function(){
	return "(x=" + this.x + ", y=" + this.y + ")";
};

Point.interpolate = function(pt1, pt2, f){
	return new Point((pt1.x + pt2.x) * f, (pt1.y + pt2.y) * f);
};

Point.polar = function(len, angle){
	return new Point(len * Math.sin(angle), len * Math.cos(angle));
};

Point.distance = function(pt1, pt2){
	var x = pt1.x - pt2.x;
	var y = pt1.y - pt2.y;
	return Math.sqrt(x * x + y * y);
};

/*--------------------------------------
	Utils
----------------------------------------*/

/**
 *	Check if point is inside polygon
 *	
 *	polyCoords 	{array} Containing points of polygon (ie. [ {x:1, y:1}, {x:2, y:2}, {x:3, y:3} ]
 *	p 			{point} Point to test 
 *	
 *	Based on : http://paulbourke.net/geometry/insidepoly/
 */
function insidePolygon(polyCoords, p) {
	if (!polyCoords) {
		return false;
	}
	
	var angle = 0;
	
	var p1 = new Point();
	var p2 = new Point();
	
	for (var i = 0; i < polyCoords.length; i++) {
		p1.x = polyCoords[i].x - p.x;
		p1.y = polyCoords[i].y - p.y;
		
		p2.x = polyCoords[(i+1)%polyCoords.length].x - p.x;
		p2.y = polyCoords[(i+1)%polyCoords.length].y - p.y;
		
		angle += angle2D(p1.x, p1.y, p2.x, p2.y);
	}
	
	if (Math.abs(angle) < Math.PI) {
		return false;
	} else {
		return true;
	}
}

/**
 *	Return the angle between two vectors on a plane
 *	The angle is from vector 1 to vector 2, positive anticlockwise
 *	The result is between -pi -> pi
 *	
 *	Based on : http://paulbourke.net/geometry/insidepoly/
 */
function angle2D(x1, y1, x2, y2) {
	var dtheta, theta1, theta2;
	
	theta1 = Math.atan2(y1,x1);
	theta2 = Math.atan2(y2,x2);
	
	dtheta  = theta2 - theta1;
	
	while (dtheta > Math.PI) {
		dtheta -= Math.TWOPI;
	}
	
	while (dtheta < -Math.PI) {
		dtheta += Math.TWOPI;
	}
	
	return dtheta;
}
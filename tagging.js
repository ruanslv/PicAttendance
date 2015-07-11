var canvas;
var ctx;
var img;

var canvasOffset;
var offsetX;
var offsetY;

var isDrawing = false;

canvas = document.getElementById("canvas");
img = document.getElementById("img");
ctx = canvas.getContext("2d");

canvasOffset = $("#canvas").offset();
offsetX = canvasOffset.left;
offsetY = canvasOffset.top;

$("#canvas").on('mousedown', function (e) {
    handleMouseDown(e);
}).on('mouseup', function(e) {
    handleMouseUp();
}).on('mousemove', function(e) {
    handleMouseMove(e);
});


var startX;
var startY;
var sizeX;
var sizeY;
var hasDrawn = false;

function handleMouseUp() {
	isDrawing = false;
	canvas.style.cursor = "default";
	document.getElementById("x").value = startX;
	document.getElementById("y").value = startY;
	document.getElementById("width").value = sizeX;
	document.getElementById("height").value = sizeY;
}

function handleMouseMove(e) {
	if (isDrawing) {
		var mouseX = parseInt(e.pageX - offsetX);
		var mouseY = parseInt(e.pageY - offsetY);				
		sizeY = sizeX = Math.max(mouseX - startX, mouseY - startY);
		ctx.clearRect(0, 0, canvas.width, canvas.height);
		ctx.beginPath();
		ctx.rect(startX, startY, sizeX, sizeY);
		ctx.stroke();
		
	}
}

function handleMouseDown(e) {
  ctx = canvas.getContext("2d");
  canvasOffset = $("#canvas").offset();
  offsetX = canvasOffset.left;
  offsetY = canvasOffset.top;
	canvas.style.cursor = "crosshair";		
	isDrawing = true
	startX = parseInt(e.pageX - offsetX);
	startY = parseInt(e.pageY - offsetY);
	hasDrawn = true;
}

function callLoad() {
  canvas = document.getElementById("canvas");
  img = document.getElementById("img");
  ctx = canvas.getContext("2d");
  canvas.width = img.width;
  canvas.height = img.height;
  document.getElementById('container').width = img.width;
  document.getElementById('container').height = img.height;
  ctx.strokeStyle = "#FF0000";
}

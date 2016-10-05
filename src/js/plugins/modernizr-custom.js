Modernizr.addTest("inputgeneratedcontent", function() {
	// Generate an input and style the psuedo element
	var i = document.createElement("input"),
		d = document.createElement("div");
	i.type = "checkbox";
	i.id = "test-input";
	d.id = "test-div";
	d.appendChild(i);

	// Append test elements
	document.body.innerHTML += "<style id='test-style'>#test-input{margin:0;padding:0}#test-input::after{content:':-)';display:block;height:50px;width:50px}</style>";
	document.body.appendChild(d);

	// Check if the scroll height is equal to or larger than 50px
	var success = d.scrollHeight >= 50;

	// Clean up test elements
	d.parentElement.removeChild(d);
	var s = document.getElementById("test-style");
	s.parentElement.removeChild(s);

	return success;
});
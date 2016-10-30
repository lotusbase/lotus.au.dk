self.addEventListener('message', function(e) {
	var resp = JSON.parse(e.data);
	self.postMessage(resp);
}, false);
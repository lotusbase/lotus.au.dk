$(function() {
	var goTree = $('#go-explorer').goTree({
		shiftClickNavigate: true,
		dblClickUpdate: true,
		initNode: $('#go-root').val(),
		jsonLoaded: function() {
			$('#go-root').prop('disabled', false);
		}
	});

	$('#go-root').on('change', function() {
		$('#go-explorer').goTree('update', $('#go-root').val());
	});

	$('.d3-chart').on('click', '#go-menu__node-update', function(e) {
		e.preventDefault();
		e.stopPropagation();
		$('#go-explorer').goTree('update', $(this).attr('data-node'));
	});
});
$(document).ready(function(){
	// A list of already created pages
	var pages = {},
	    api = $("#api")[0];

	// Class file links
	$("#class-list a").live("click", function(e){
		e.preventDefault(); // stop the click
		var link = this.href;

		if ( ! pages.hasOwnProperty(link)){
			$.ajax({
			  url: link,
			  data: {},
			  success: function(data){
				  pages[link] = data;
				  showPage(link);
			  },
			  dataType: "html"
			});
		} else {
			showPage(link);
		}
	});

	var showPage = function(link){
		$(api).html(pages[link]);

		// Highlight the code
		$("pre.source").sunlight();
	}
});

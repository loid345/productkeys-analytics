require(['jquery'], function ($) {
  'use strict',
    // $(document).ready(function() {
    // 	var timeout = setInterval(function() {
    // 		var ele = $('.fieldset-wrapper[data-index="product-keys"]');
    // 		if (ele.length > 0) {
    // 			clearInterval(timeout);
    // 			ele.on("click", function() {
    // 				gnrlConfigDepends();
    // 			});
    // 		}
    // 	}, 1000);
    // });

    // function gnrlConfigDepends() {
    // 	var gnrlConfig = $("select[name='product[productkey_overwritegnrlconfig]']");
    // 	if (gnrlConfig.val() == 0) {
    // 		$(".generalconfig_attributes").hide();
    // 	}
    // 	gnrlConfig.change(function() {
    // 		if ($(this).val() == 0) {
    // 			$(".generalconfig_attributes").hide();
    // 		} else {
    // 			$(".generalconfig_attributes").show();
    // 		}
    // 	});
    // }

    $(document).ready(function () {
      var timeout = setInterval(function () {
        var ele = $('.fieldset-wrapper[data-index="product-keys"]');
        if (ele.length > 0) {
          clearInterval(timeout);
          ele.on('click', function () {
            gnrlConfigDepends();
          });
        }
      }, 1000);
    });

  function gnrlConfigDepends() {
    var gnrlConfig = $("select[name='product[productkey_overwritegnrlconfig]']");
    var apiConfig = $("select[name='product[productkey_post_to_an_api]']");

    function toggleApiConfig() {
      if (apiConfig.val() == 0) {
        $('[data-index="productkey_api_endpoint"]').css('display', 'none');
        $('[data-index="productkey_api_method"]').css('display', 'none');
        $('[data-index="productkey_api_auth_type"]').css('display', 'none');
        $('[data-index="productkey_api_auth_header"]').css('display', 'none');
        $('[data-index="productkey_api_request_body"]').css('display', 'none');
        $('[data-index="productkey_api_content_type"]').css('display', 'none');
      } else {
        $('[data-index="productkey_api_endpoint"]').css('display', 'block');
        $('[data-index="productkey_api_method"]').css('display', 'block');
        $('[data-index="productkey_api_auth_type"]').css('display', 'block');
        $('[data-index="productkey_api_auth_header"]').css('display', 'block');
        $('[data-index="productkey_api_request_body"]').css('display', 'block');
        $('[data-index="productkey_api_content_type"]').css('display', 'block');
      }
    }

    // Initial load logic
    if (gnrlConfig.val() == 0) {
      $('.generalconfig_attributes').css('display', 'none');
    } else {
      $('.generalconfig_attributes').css('display', 'block');
      toggleApiConfig();
    }

    // On change of gnrlConfig
    gnrlConfig.change(function () {
      if ($(this).val() == 0) {
        $('.generalconfig_attributes').css('display', 'none');
      } else {
        $('.generalconfig_attributes').css('display', 'block');
        toggleApiConfig();
      }
    });

    // On change of apiConfig
    apiConfig.change(function () {
      if (gnrlConfig.val() == 1) {
        toggleApiConfig();
      }
    });
  }

  $(document).ready(function () {
    // Function to show/hide API details fields based on "Post to An API" selection
    function toggleApiDetails() {
      if ($('#post_to_api').val() == '1') {
        $('#api_endpoint').closest('.field').show();
        $('#api_method').closest('.field').show();
        $('#api_auth_header').closest('.field').show();
        $('#api_request_body').closest('.field').show();
      } else {
        $('#api_endpoint').closest('.field').hide();
        $('#api_method').closest('.field').hide();
        $('#api_auth_header').closest('.field').hide();
        $('#api_request_body').closest('.field').hide();
      }
    }

    // Initially hide API details fields
    toggleApiDetails();

    // Show/hide API details fields when "Post to An API" selection changes
    $('#post_to_api').change(function () {
      toggleApiDetails();
    });
  });
});

(function ($) {
    $('.infusionsoft_ilp_info').on('click', function () {
        $(this).find('.infusionsoft_ilp_more_text').fadeToggle(400);
    });

    var landingPages = {
        offset: 0,
        limit: 20,
        results: [],

        init: () => {
            $(document).on('.click', '.ifs-add-form .ifs-retry', () => {
                landingPages.fetchPageData()
            });

            landingPages.fetchPageData();
            landingPages.init = () => {};
        },

        fetchPageData: () => {
            $('.ifs-add-form .loading').show();
            $('.ifs-add-form .add-form').hide();
            $('.ifs-add-form .request-login').hide();
            $('.ifs-add-form .ajax-error').hide();
            var infusionsoft_sak = $("#infusionsoft_sak").val();

            if(infusionsoft_sak == ""){
	            $('.ifs-add-form .loading').hide();
				$('.ifs-add-form .request-login-blank').show();
				$('.ifs-add-form .request-login-form').show();
				return;
            }


            $.ajax('https://landingpages.infusionsoft.com/api/v1/sites', {
                data: {
                    limit: landingPages.limit,
                    offset: landingPages.offset,
                },
                headers: { 'X-Keap-API-Key': infusionsoft_sak },

                xhrFields: {
                    withCredentials: true
                },

                statusCode: {
                    200: (data, status, qjXHR) => {
                        $('.ifs-add-form .add-form').show();

                        if (Array.isArray(data)) {
                            data.forEach((item) => {
                                let foundIndex = null;
                                let exists = landingPages.results.find((element, index) => {

                                    if (element.id === item.id) {
                                        foundIndex = index;
                                        return true;
                                    }

                                    return false;
                                });

                                if (exists) {
                                    landingPages.results[foundIndex] = item;
                                }
                                else {
                                    landingPages.results.push(item);
                                }
                            });

                            if (data.length == landingPages.limit) {
                                // there could be more pages, let's do another fetch
                                landingPages.offset += landingPages.limit;

                                landingPages.fetchPageData();
                            }
                            else {
                                landingPages.render();
                            }
                        }
                    },

                    401: (qjXHR, status, error) => {
                        $('.ifs-add-form .request-login').show();
						$('.ifs-add-form .request-login-form').show();                        
                    },

                    403: (qjXHR, status, error) => {
                        $('.ifs-add-form .request-login').show();
						$('.ifs-add-form .request-login-form').show();
                    }
                },

                error: (qjXHR, status, error) => {
					$('.ifs-add-form .request-login').show();
					$('.ifs-add-form .request-login-form').show();
                },

                complete: () => {
                    $('.ifs-add-form .loading').hide();
                },
            });
        },

        render: () => {
            var $container = $('.landing-page-selector');

            if (landingPages.results.length) {
                var $ul = $('<ul>').addClass('landing-pages');

                landingPages.results.forEach((page) => {
                    var $li = $('<li>');
                    var $input = $('<input type="radio" name="landingpage">').val(btoa(JSON.stringify(page)));
                    var $labelHTML = `${page.name}<br><a href="${page.siteUrl}" target="_blank">${page.siteUrl}</a>`;

                    $li.append($('<label>').html($labelHTML).prepend($input));
                    $ul.append($li);
                });

                $container.append($ul);
            }
            else {
                $container.html('You have no landing pages available on your account.  Create a landing page in your Keap app and try again.');
            }
        },
    };

    landingPages.init();
})(jQuery);

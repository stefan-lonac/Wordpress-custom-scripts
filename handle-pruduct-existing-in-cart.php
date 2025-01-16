<?php 

/*  
  * Custom WooCommerce handler for product existing in cart - AJAX
  * For every add to cart button
	* Default language are serbian - no mandatory
*/
function handle_product_existing_in_cart()
{
	if (!is_product()) { ?>

		<script>

			jQuery(document).ready(function ($) {
				var cartLinkAdded = false;
				var clickedButton; // Promenljiva za čuvanje kliknutog dugmeta

				$(".single_add_to_cart_button").on("click", function (event) {
					event.preventDefault(); // Prevent form to reload page
					clickedButton = $(this); // Sačuvaj kliknuto dugme

					var form = $(this).closest(".variations_form");
					var product_id = form.find("input[name='add-to-cart']").val();
					var quantity = form.find("input[name='quantity']").val();
					var variation_id = form.find("input[name='variation_id']").val();
					var data = {
						action: 'woocommerce_ajax_add_to_cart', // WordPress action for added product in cart
						product_id: product_id,
						quantity: quantity,
						variation_id: variation_id
					};

					clickedButton.addClass('loading');
					// Request AJAX call
					$.post(wc_add_to_cart_params.ajax_url, data, function (response) {
						clickedButton.removeClass('loading');
						
						var productIsOnCart = $("<p>").text("<?php echo __('Proizvod je kupljen ili je već u korpi', 'kadence-child'); ?>").addClass("product-is-on-cart");
						var viewCartLink = $("<a>").text("<?php echo __('Pregled korpe', 'kadence-child'); ?>").addClass("view-cart-link").attr("href", "<?php echo wc_get_cart_url(); ?>");

						if (response === 'success') {
							if (!cartLinkAdded) {
								// Add View Cart link after successful AJAX request to the clicked button container
								clickedButton.after(viewCartLink);
								cartLinkAdded = true;
							}
							clickedButton.siblings('.product-is-on-cart').remove(); // Remove product is on cart message if it's already shown
						} else if (response === 'error') {
							cartLinkAdded = false

							if (cartLinkAdded === false) {
								// Show product is on cart message if it's not already shown in the clicked button container
								if (!clickedButton.siblings('.product-is-on-cart').length) {
									clickedButton.after(productIsOnCart);
								}
							}
							clickedButton.siblings('.view-cart-link').remove(); // Remove view cart link if there was an error in the clicked button container
						}
					});
				});
			});

		</script>
	<?php }
}
add_action('wp_footer', 'handle_product_existing_in_cart');

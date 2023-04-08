
$(document).ready(function () {
  // Listen for clicks on the body element and delegate to the .js-increase-product-quantity elements
  $('body').delegate('.js-increase-product-quantity, .js-decrease-product-quantity, .remove-from-cart', 'click', function (event) {
    console.log('Quantity increase button clicked'); // Debug log
    $('.cart-summary').addClass('loading'); // Show the loading animation
    // Your custom logic here, e.g., triggering the cart update
  });
  if ($('body').hasClass('page-cart')) {
    prestashop.emit('updateCart', { reason: { eventName: 'refresh-cart-summaryx' } });
  }
});


var discountApplied = false;
prestashop.on('updatedCart', function (event) {
  console.log('updatedCart event triggered');
  console.log(event.reason); // Debug log: check the event object values

  if (discountApplied) {
    discountApplied = false;
    return;
  }
    // Send a request to the module's front controller to apply the discount
    var applyDiscountUrl = prestashop.urls.base_url + 'module/artdiscounts/applydiscount';

    $.post(applyDiscountUrl, { ajax: 1 }, function (data) {
      /* json to object */
      data = JSON.parse(data);
      if (data.success) {
        // If the discount was applied successfully, refresh the cart summary
        console.log('Discount applied successfully');
        discountApplied = true;
        prestashop.emit('updateCart', { reason: { eventName: 'refresh-cart-summaryx' } });
        setTimeout(() => {
          $('.cart-summary').removeClass('loading'); // Hide the loading animation
        }, 350);
        // if (typeof getCart === 'function') {
        //   getCart(); // Refresh cart summary directly
        // } else {
        //   console.error('getCart function is not defined'); // Debug log: check if the getCart function is available
        // }
      }
    });
});
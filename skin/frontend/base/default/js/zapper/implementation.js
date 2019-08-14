jQuery(function($){

	// This is the container where the QR code image will be placed. You can either pass a jQuery object or a selector such as #some-selector or .some-selector
	var paymentSelector = $('<div id="scantologinpurchase-container"></div>');

	// Amount examples include "$4.00", "£678.89" and "R456.05". Random number for this example.
	var amount = 'R'+Math.floor((Math.random()*100)+1)+'.'+Math.floor((Math.random()*99)+1);

	// The zappertech object. Pass your merchant id, site id, selector and base url as normal. 
	// Use task id 1 for purchase and pass in the "amount" you are paying as an additional parameter
	var qrPaymentCode = new ZapperTech.QrCode({
        merchantId: 731,
        siteId: 433,
        selector: paymentSelector,
        baseUrl: 'https://zapapi.zapzap.mobi/zappertech',
        taskId: 1,
        additionalParameters: [amount]
    });

	$('body').append('<p>Scan the purchase code for amount '+amount+' to begin</p>');
    $('body').append(paymentSelector);

    var payment = function(data) {

    	// set up some placeholders for our incoming data
        var existingAddresses = new Array();
        var shippingLineOne, shippingLineTwo, shippingCity, shippingPostalCode, shippingCountry;
        var billingLineOne, billingLineTwo, billingCity, billingPostalCode, billingCountry;

        // iterate through the data and grab all the purchase specific data such as card info, addresses etc.
        // you can print out data.Answers to see everything coming through
        $(data.Answers).each(function(i, answer) {
            if (answer.QuestionId == 12) {
                if ($.inArray(answer.QuestionId, existingAddresses) < 0) {
                    shippingLineOne = answer.AnswerValue;
                    existingAddresses.push(answer.QuestionId);
                } else {
                    billingLineOne = answer.AnswerValue;
                }
            }
            if (answer.QuestionId == 13) {
                if ($.inArray(answer.QuestionId, existingAddresses) < 0) {
                    shippingLineTwo = answer.AnswerValue;
                    existingAddresses.push(answer.QuestionId);
                } else {
                    billingLineTwo = answer.AnswerValue;
                }
            }
            if (answer.QuestionId == 14) {
                if ($.inArray(answer.QuestionId, existingAddresses) < 0) {
                    shippingCity = answer.AnswerValue;
                    existingAddresses.push(answer.QuestionId);
                } else {
                    billingCity = answer.AnswerValue;
                }
            }
            if (answer.QuestionId == 16) {
                if ($.inArray(answer.QuestionId, existingAddresses) < 0) {
                    shippingPostalCode = answer.AnswerValue;
                    existingAddresses.push(answer.QuestionId);    
                } else {
                    billingPostalCode = answer.AnswerValue;
                }
            }
            if (answer.QuestionId == 17) {
                if ($.inArray(answer.QuestionId, existingAddresses) < 0) {
                    shippingCountry = answer.AnswerValue;
                    existingAddresses.push(answer.QuestionId);
                } else {
                    billingCountry = answer.AnswerValue;
                }
            }
        });

        var cardNumber = qrPaymentCode.getAnswer(data.Answers, 19)
        , cardType = qrPaymentCode.getAnswer(data.Answers, 18)
        , cardName = qrPaymentCode.getAnswer(data.Answers, 20)
        , cardCVC = qrPaymentCode.getAnswer(data.Answers, 26)
        , cardExpiryMonth = qrPaymentCode.getAnswer(data.Answers, 24)
        , cardExpiryYear = qrPaymentCode.getAnswer(data.Answers, 25);

        $('body').append('Card no: '+cardNumber+'<br>'+'Card type: '+cardType+'<br>'+'Card name: '+cardName+'<br>'+'Card cvc: '+cardCVC+'<br>'+'Card expiry month: '+cardExpiryMonth+'<br>'+'Card expiry year: '+cardExpiryYear+'<br>'+'Shipping line one: '+shippingLineOne+'<br>'+'Shipping city: '+shippingCity+'<br>'+'Shipping postal code: '+shippingPostalCode+'<br>'+'Shipping country: '+shippingCountry+'<br>'+'Billing line one: '+billingLineOne+'<br>'+'Billing city: '+billingCity+'<br>'+'Billing postal code: '+billingPostalCode+'<br>'+'Billing country: '+billingCountry+'<p>The Scan-to-Login Purchase sequence for the amount of '+amount+' is complete. You can use the above data to integrate with your payment gateway.');
    }

    // pass the payment function as a callback to the payment request
    qrPaymentCode.paymentRequest(payment);
    // start the purchase polling for a response
    qrPaymentCode.start();
});
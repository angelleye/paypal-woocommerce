/*
Based on jQuery Credit Card Validator (Copyright 2012 Pawel Decowski)
*/
var card_types = [
{
	name: 'amex',
	pattern: /^3[47]/,
	valid_length: [15]
}, {
	name: 'diners_club_carte_blanche',
	pattern: /^30[0-5]/,
	valid_length: [14]
}, {
	name: 'diners_club_international',
	pattern: /^36/,
	valid_length: [14]
}, {
	name: 'jcb',
	pattern: /^35(2[89]|[3-8][0-9])/,
	valid_length: [16]
}, {
	name: 'laser',
	pattern: /^(6304|670[69]|6771)/,
	valid_length: [16, 17, 18, 19]
}, {
	name: 'visa_electron',
	pattern: /^(4026|417500|4508|4844|491(3|7))/,
	valid_length: [16]
}, {
	name: 'visa',
	pattern: /^4/,
	valid_length: [16]
}, {
	name: 'mastercard',
	pattern: /^5[1-5]/,
	valid_length: [16]
}, {
	name: 'maestro',
	pattern: /^(5018|5020|5038|6304|6759|676[1-3])/,
	valid_length: [12, 13, 14, 15, 16, 17, 18, 19]
}, {
	name: 'discover',
	pattern: /^(6011|622(12[6-9]|1[3-9][0-9]|[2-8][0-9]{2}|9[0-1][0-9]|92[0-5]|64[4-9])|65)/,
	valid_length: [16]
}    ];

function get_card_type(number) {
	number = number.replace(/[ -]/g, '');

	var card_type, _i, _len;
	for (_i = 0, _len = card_types.length; _i < _len; _i++) {
		card_type = card_types[_i];
		if (number.match(card_type.pattern)) {
			return card_type['name'];
		}
	}
	return null;
}

function is_valid_card(number) {
    number = number.replace(/[ -]/g, '');

    var digit, n, sum, _i, _len, _ref;
	sum = 0;
	_ref = number.split('').reverse();
	for (n = _i = 0, _len = _ref.length; _i < _len; n = ++_i) {
		digit = _ref[n];
		digit = +digit;
		if (n % 2) {
			digit *= 2;
			if (digit < 10) {
				sum += digit;
			} else {
				sum += digit - 9;
			}
		} else {
			sum += digit;
		}
	}
	return sum % 10 === 0;
}
var $ = jQuery;
$(document).ready(function () {
    var $ppcp_account_requestModal = $(".ppcp_account_request-Modal");
    if ($ppcp_account_requestModal) {
        new ModalWpr($ppcp_account_requestModal);
    }
});
function ModalWpr(aElem) {
    var refThis = this;
    this.elem = aElem;
    this.overlay = $('.ppcp_account_request-Modal-overlay');
    this.radio = $('input[name=reason]', aElem);
    this.closer = $('.ppcp_account_request-Modal-close, .ppcp_account_request-Modal-cancel', aElem);
    this.return = $('.ppcp_account_request-Modal-return', aElem);
    this.opener = $('.open_ppcp_account_request_form');
    this.question = $('.ppcp_account_request-Modal-question', aElem);
    this.button = $('.button-primary', aElem);
    this.title = $('.ppcp_account_request-Modal-header h2', aElem);
    this.textFields = $('input[type=text], textarea', aElem);
    this.hiddenReason = $('#ppcp_account_request-reason', aElem);
    this.hiddenDetails = $('#ppcp_account_request-details', aElem);
    this.titleText = this.title.text();
    this.opener.click(function () {
        refThis.open();
        return false;
    });
    this.closer.click(function () {
        refThis.close();
        return false;
    });
    aElem.bind('keyup', function (event) {
        if (event.keyCode == 27) {
            refThis.close();
            return false;
        }
    });
    this.return.click(function () {
        refThis.returnToQuestion();
        return false;
    });
    this.radio.change(function () {
        refThis.change($(this));
    });
    this.textFields.keyup(function () {
        refThis.hiddenDetails.val($(this).val());
        if (refThis.hiddenDetails.val() != '') {
            refThis.button.removeClass('ppcp_account_request-isDisabled');
            refThis.button.removeAttr("disabled");
        } else {
            refThis.button.addClass('ppcp_account_request-isDisabled');
            refThis.button.attr("disabled", true);
        }
    });
}
ModalWpr.prototype.change = function (aElem) {
    var id = aElem.attr('id');
    var refThis = this;
    this.hiddenReason.val(aElem.val());
    this.hiddenDetails.val('');
    this.textFields.val('');
    $('.ppcp_account_request-Modal-fieldHidden').removeClass('ppcp_account_request-isOpen');
    $('.ppcp_account_request-Modal-hidden').removeClass('ppcp_account_request-isOpen');
    this.button.removeClass('ppcp_account_request-isDisabled');
    this.button.removeAttr("disabled");
    switch (id) {
        case 'reason-temporary':
            break;
        case 'reason-broke':
            break;
        case 'reason-complicated':
            break;
            break;
        case 'reason-other':
            var field = aElem.siblings('.ppcp_account_request-Modal-fieldHidden');
            field.addClass('ppcp_account_request-isOpen');
            field.find('input, textarea').focus();
            refThis.button.addClass('ppcp_account_request-isDisabled');
            refThis.button.attr("disabled", true);
            break;
    }
};
ModalWpr.prototype.returnToQuestion = function () {
    $('.ppcp_account_request-Modal-fieldHidden').removeClass('ppcp_account_request-isOpen');
    $('.ppcp_account_request-Modal-hidden').removeClass('ppcp_account_request-isOpen');
    this.question.addClass('ppcp_account_request-isOpen');
    this.return.removeClass('ppcp_account_request-isOpen');
    this.title.text(this.titleText);
    this.hiddenReason.val('');
    this.hiddenDetails.val('');
    this.radio.attr('checked', false);
    this.button.addClass('ppcp_account_request-isDisabled');
    this.button.attr("disabled", true);
};
ModalWpr.prototype.open = function () {
    this.elem.css('display', 'block');
    this.overlay.css('display', 'block');
    localStorage.setItem('ppcp_account_request-hash', '');
};
ModalWpr.prototype.close = function () {
    this.returnToQuestion();
    this.elem.css('display', 'none');
    this.overlay.css('display', 'none');
};
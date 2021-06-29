/* eslint-disable */
jQuery(document).ready(function ($) {
  adminContinuation = {
    update() {
      $.ajax({
        url: smodule.ajaxurl,
        type: 'POST',
        cache: false,
        dataType: 'json',
        data: {
          action: 'usces_admin_ajax',
          mode: 'continuation_update',
          member_id: $('#member_id').val(),
          order_id: $('#order_id').val(),
          contracted_year: $('#contracted-year option:selected').val(),
          contracted_month: $('#contracted-month option:selected').val(),
          contracted_day: $('#contracted-day option:selected').val(),
          charged_year: $('#charged-year option:selected').val(),
          charged_month: $('#charged-month option:selected').val(),
          charged_day: $('#charged-day option:selected').val(),
          price: $('#price').val(),
          status: $('#dlseller-status').val(),
          wc_nonce: $('#wc_nonce').val(),
        },
      })
        .done(function (retVal, dataType) {
          if (retVal.status == 'OK') {
            adminOperation.setActionStatus('success', smodule.updateCompletedMessage);
          } else {
            mes = retVal.message != '' ? retVal.message : smodule.updateFailedMessage;
            adminOperation.setActionStatus('error', mes);
          }
        })
        .fail(function (retVal) {
          adminOperation.setActionStatus('error', smodule.updateFailedMessage);
        });
      return false;
    },
  };

  $(document).on('click', '#continuation-update', function () {
    var status = $('#dlseller-status option:selected').val();
    if (status == 'continuation') {
      var year = $('#charged-year option:selected').val();
      var month = $('#charged-month option:selected').val();
      var day = $('#charged-day option:selected').val();
      if (year == 0 || month == 0 || day == 0) {
        alert(smodule.dataMalformedMessage);
        $('#charged-year').focus();
        return;
      }

      if ($('#price').val() == '' || parseInt($('#price').val()) == 0) {
        alert(smodule.insertAmountMessage);
        $('#price').focus();
        return;
      }
    }

    if (!usces_check_money($('#price'))) {
      return;
    }
  
    if (!confirm(smodule.updateConfirmMessge)) {
      return;
    }
    adminContinuation.update();
  });
});

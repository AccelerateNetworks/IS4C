currentStep = 0;
/*
 * currentStep tracks our progress through the menu.
 *
 * 0 - Still selecting a transaction
 * 1 - Transaction can be voided
 * 2 - Confirmation dialog
 * 3 - Command sent to device, waiting on response
 * 4 - Failed to void transaction (either an error from the device or an error talking to the device)
 * 5 - Device successfully voided transaction
 */

function doPagination(current, lastCount) {
  if(lastCount < 10) {
    // handle next page
  }
  if(current > 0) {
    // handle previous page
  }
}

function verifyVoidability() {
  var selected = scrollable.getSelected();
  // See if we're on the right register
  if(selected.registerNo != laneno) {
    currentStep = 3;
    ui.alert('Please use register ' + selected.registerNo, "Unfortunately, transactions must be voided on the register that they were originally created on");
  } else if(selected.xTransactionID !== null) {
    ui.alert('Already Voided!', 'Our records indicate this transaction has already been voided. Would you like to re-attempt to void it?');
    currentStep = 1;
  } else {
    currentStep = 1;
    askToVoidSelected();
  }
}

function askToVoidSelected() {
  var selected = scrollable.getSelected();
  ui.alert('Really void transaction #' + selected.transNo + '?', 'For $' + selected.amount + ' from ' + selected.name, "YES", "NO");
  currentStep = 2;
}

function cancelVoid() {
  ui.clearAlert();
  currentStep = 0;
}

function voidSelected() {
  var selected = scrollable.getSelected();
  ui.alert('Voiding transaction #' + selected.transNo, 'This should only take a few seconds');
  currentStep = 3;
  $.post('../ajax/pax.php', {action: "void_CC", reference: selected.refNum, transaction: selected.transNo, not_current: true}).done(postVoid).fail(fail);
}

function postVoid(response) {
  console.log(response);
  switch(response.code) {
    case 0:
      currentStep = 5;
      ui.alert('Transaction voided');
      $(".item:eq(" + scrollable.selected + ") .coloredArea").removeClass("coloredArea").addClass("errorColoredArea");
    break;
    default:
      currentStep = 4;
      ui.alert('Failed to void!', response.message + " (" + response.code + ")");
    break;
  }
}

function fail(response) {
  console.error(response);
  currentStep = 3;
  ui.alert('Failed to void!', 'Debugging information can be found in the javascript console');
}

function formSubmit(e) {
  var command = $("#reginput").val().toUpperCase();
  switch(command) {
    case "":
      if(currentStep === 0) {
        verifyVoidability();
      } else {
        cancelVoid();
      }
      e.preventDefault();
    break;
    case "YES":
      switch(currentStep) {
        case 1:
          askToVoidSelected();
          e.preventDefault();
          $("#reginput").val("");
        break;
        case 2:
          voidSelected();
          e.preventDefault();
          $("#reginput").val("");
        break;
      }
    break;
    case "UP":
      if(ui._currentAlert === undefined) {
        scrollable.up();
      }
      $("#reginput").val("");
      e.preventDefault();
    break;
    case "DOWN":
      if(ui._currentAlert === undefined) {
        scrollable.down();
      }
      $("#reginput").val("");
      e.preventDefault();
    break;
  }
}

function reginputKeypress(e) {
  switch(e.which) {
    case 40:
      $("#reginput").val("DOWN");
      formSubmit(e);
    break;
    case 38:
      $("#reginput").val("UP");
      formSubmit(e);
    break;
  }
}

$(document).ready(function() {
  $("#formlocal").submit(formSubmit);
  $("#reginput").keydown(reginputKeypress);
});

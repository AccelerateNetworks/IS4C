/*******************************************************************************

    Copyright 2016 Accelerate Networks

    This file is part of IT CORE.

    IT CORE is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IT CORE is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

var human_types = {};
human_types.CC = "credit card";
human_types.DC = "debit card";
human_types.EC = "EBT cash";
human_types.EF = "EBT food";

var need_signature = false;
window.commands = {};

$(document).ready(function() {
  $("#formlocal").submit(function (e) {
    var command = $("#reginput").val();
    if(window.commands.hasOwnProperty(command.toUpperCase())) {
      e.preventDefault();
      $("#reginput").val("");
      window.commands[command.toUpperCase()]();
    }
  });
});

window.commands.RETRY = function() {
  if(need_signature) {
    handleSwipe({code: 0, needsSig: true}).then(redirect).fail(fail);
  } else {
    pax_transaction(window.transaction);
  }
};

window.commands.VOID = function() {
  if(window.trace) {
    $("#localmsg").text("Voiding transaction.");
    $.post("../ajax/pax.php", {action: "void_CC", reference: window.trace.reference, transaction: window.trace.transaction}).then(handleVoid).fail(fail);
  } else {
    $("#localmsg").text("Can't void: no transaction processed yet. Maybe you meant [CLEAR]?");
  }
};

function fail(result) {
  console.error(result);
  $("#localmsg").text(result);
}

function handleSwipe(response) {
  var result = $.Deferred();
  switch(response.code) {
    case 0:
      window.trace = response.trace;
      if(response.needsSig) {
        need_signature = true;
        $("#localmsg").text("Please sign for transaction.");
        result = $.post("../ajax/pax.php", {action: "signature"});
      } else {
        result = response;
      }
    break;
    case 100001:
      result.reject("Took too long to swipe card. Hit [RETRY] to try again.");
    break;
    default:
      if(response.message !== undefined && response.code !== undefined) {
        result.reject("Failed to complete transaction: " + response.message + " (" + response.code + ")");
      } else {
        console.log(response);
        result.reject("Failed to complete transaction: " + response.error);
      }
    break;
  }
  return result;
}

function handleVoid(response) {
  console.log(response);
  switch(response.code) {
    case 0:
      $("#localmsg").text("Transaction voided");
      window.location.href = response.redirect;
    break;
    default:
      if(response.message !== undefined && response.code !== undefined) {
        result.reject("Failed to complete transaction: " + response.message + " (" + response.code + ")");
      } else {
        console.log(response);
        result.reject("Failed to complete transaction: " + response.error);
      }
    break;
  }
}

function redirect(response) {
  var result = $.Deferred();
  switch(response.code) {
    case 0:
      if(!window.no_redirect) {
        window.location.href = response.redirect;
      } else {
        console.log(response);
      }
    break;
    default:
      result.reject("Failed to collect signature, use [RETRY] to try again or [VOID] to void the card transaction.");
    break;
  }
  return result;
}

function signaturedone(result) {
  if(result.code === 0) {
    $("#localmsg").html($("<img>").attr('src', '../signatures/' + result.signature));
  }
}

function pax_transaction(transaction) {
  console.log(transaction);
  window.transaction = transaction;
  $("#localmsg").text("Use payment terminal to complete " + human_types[transaction.type] + " transaction.");
  window.terminalrequest = $.post("../ajax/pax.php", {action: transaction.type, amount: transaction.amtdue}).then(handleSwipe).then(redirect).fail(fail);
}

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

function fail(result) {
  console.error(result);
  $("#localmsg").text(result);
}

function handleSwipe(response) {
  var result = $.Deferred();
  switch(response.code) {
    case 0:
      if(response.needsSig) {
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
      result.reject("Failed to collect signature, please try again.");
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
  $("#localmsg").text("Use payment terminal to complete " + human_types[transaction.type] + " transaction.");
  window.terminalrequest = $.post("../ajax/pax.php", {action: transaction.type, amount: transaction.amtdue}).then(handleSwipe).then(redirect).fail(fail);
}

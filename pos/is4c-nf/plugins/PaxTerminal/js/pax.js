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

function parseWrapper(str) {

}

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
      result.reject("Failed to complete transaction: " + result.message + " (" + result.code + ")");
    break;
  }
  return result;
}

function redirect(response) {
  var result = $.Deferred();
  switch(response.code) {
    case 0:
      window.location.href = response.redirect;
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

function signature() {

}


function pax_transaction(transaction) {
  console.log(transaction);
  $("#localmsg").text("Use payment terminal to complete transaction.");
  window.terminalrequest = $.post("../ajax/pax.php", {action: "credit", amount: transaction.amtdue}).then(handleSwipe).then(redirect).fail(fail);
}

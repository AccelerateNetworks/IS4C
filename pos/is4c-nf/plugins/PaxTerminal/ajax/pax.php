<?php
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

require __DIR__.'/../lib/pax.php';

// No idea why this would be needed...
if (basename($_SERVER['PHP_SELF']) != basename(__FILE__)){
    return;
}

include_once(dirname(__FILE__).'/../../../lib/AutoLoader.php');


$out = array("error" => "Something is very wrong");
$pax = new Pax(CoreLocal::get("PaxHost"), intval(CoreLocal::get("PaxPort")));
if(isset($_POST['action'])) {
  switch($_POST['action']) {
    case "credit":
      $amount = floatval($_POST['amount']);
      $out = $pax->do_credit($amount);
      $out['redirect'] = MiscLib::baseURL();
      $dbTrans = PaycardLib::paycard_db();
      $query = "INSERT INTO PaycardTransactions (dateID, empNo, registerNo, transNo, transID,
        processor, amount, commErr) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
      $args = array();
      $args[] = date('Ymd');  # dateID
      $args[] = CoreLocal::get("CashierNo");  # empNo
      $args[] = CoreLocal::get("laneno");  # registerNo
      $args[] = CoreLocal::get("transno");  # transNo
      $args[] = CoreLocal::get("paycard_id");  # transID
      $args[] = "PAX";  # TODO: Figure out how to get the processor
      if($out['code'] == 0) {
        $query = "INSERT INTO PaycardTransactions ( dateID, empNo, registerNo, transNo, transID,
          processor, refNum, cardType, transType, amount, PAN, name, manual, responseDatetime)
          VALUES ( ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
          $args[] = $out['host']['reference_number'];  # refNum
          $args[] = $out['account']['type'];  # cardType
          $args[] = $out['transaction_type'];  # transType
          $args[] = $out['amount']['approved'];  # amount
          $args[] = intval($out['additional']['CARDBIN']."000000".$out['account']['number']);  # PAN
          $args[] = $out['account']['name'];  # name
          $args[] = $out['account']['entry'] == 0 ? 1 : 0;  # manual
          $args[] = date('Y-m-d H:i:s');  # responseDatetime
          TransRecord::addtender('Card', 'CC', $out['amount']['approved']*-1);
          if($out['amount']['approved'] > 10) { // TODO: Fix $10 hard coded signature limit
            $out['action'] = "signature";
          }
          $out['redirect'] = MiscLib::base_url()."gui-modules/pos2.php?reginput=TO&repeat=1";
      } else {
        $args[] = $amount;
        $args[] = $out['message'];
      }

      $prepared = $dbTrans->prepare($query);
      $dbResponse = $dbTrans->execute($prepared, $args);
      if(!$dbResponse) {
        error_log("Failed to store transaction in database!!");
      }
    break;
    case "signature":
      $out = $pax->do_signature();
    break;
    default:
      $out['error'] = "Unknown command";
    break;
  }
}
header('Content-Type: text/json');

echo json_encode($out);

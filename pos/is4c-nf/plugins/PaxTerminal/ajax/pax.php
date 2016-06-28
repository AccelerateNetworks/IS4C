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


function store_transaction($transaction) {
  $ret = $transaction;
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
  if($transaction['code'] == 0) {
    $query = "INSERT INTO PaycardTransactions ( dateID, empNo, registerNo, transNo, transID,
      processor, refNum, cardType, transType, amount, PAN, name, manual, responseDatetime)
      VALUES ( ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
      $args[] = $transaction['host']['reference_number'];  # refNum
      $args[] = $transaction['account']['type'];  # cardType
      $args[] = $transaction['transaction_type'];  # transType
      $args[] = $transaction['amount']['approved'];  # amount
      $args[] = intval($transaction['additional']['CARDBIN']."000000".$transaction['account']['number']);  # PAN
      $args[] = $transaction['account']['name'];  # name
      $args[] = $transaction['account']['entry'] == 0 ? 1 : 0;  # manual
      $args[] = date('Y-m-d H:i:s');  # responseDatetime
      TransRecord::addtender('Card', 'CC', $transaction['amount']['approved']*-1);
  } else {
    $args[] = $amount;
    $args[] = $transaction['message'];
  }
  $prepared = $dbTrans->prepare($query);
  $dbResponse = $dbTrans->execute($prepared, $args);
  if(!$dbResponse) {
    error_log("Failed to store transaction in database!!");
  }
  $ret['redirect'] = MiscLib::base_url()."gui-modules/pos2.php?reginput=TO&repeat=1";
  return $ret;
}

$out = array("error" => "Something is very wrong");
$pax = new Pax(CoreLocal::get("PaxHost"), intval(CoreLocal::get("PaxPort")));
if(isset($_POST['action'])) {
  switch($_POST['action']) {
    case "CC":
      $amount = floatval($_POST['amount']);
      $transaction = $pax->do_credit($amount);
      $out = store_transaction($transaction);
      if($transaction['amount']['approved'] > intval(CoreLocal::get("PaxSigLimit")) && intval(CoreLocal::get("PaxSigLimit")) >= 0) {
        $ret['needsSig'] = true;
      }
    break;
    case "DC":
      $amount = floatval($_POST['amount']);
      $transaction = $pax->do_debit($amount);
      $out = store_transaction($transaction);
    break;
    case "signature":
      $out = $pax->do_signature();
      $dbc = Database::tDataConnect();
      $query = "INSERT INTO CapturedSignature (tdate, emp_no, register_no, trans_no, trans_id, filetype, filecontents) VALUES (?, ?, ?, ?, ?, ?, ?)";
      $prepared = $dbc->prepare($query);
      $args = array(
        date('Y-m-d H:i:s'),
        CoreLocal::get('CashierNo'),
        CoreLocal::get('laneno'),
        CoreLocal::get('transno'),
        CoreLocal::get('paycard_id'),
        "svg",
        json_encode($out['signature']['vector'])
      );
      $out['storage_result'] = $dbc->execute($prepared, $args);
      $out['redirect'] = MiscLib::base_url()."gui-modules/pos2.php?reginput=TO&repeat=1";
    break;
    default:
      $out['error'] = "Unknown command";
    break;
  }
}
header('Content-Type: text/json');

echo json_encode($out);

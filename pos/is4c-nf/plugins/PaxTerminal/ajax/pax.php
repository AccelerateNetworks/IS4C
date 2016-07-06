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

function store_transaction($transaction, $type, $void=false, $current=true) {
  $type_map['CC'] = "Credit Card";
  $type_map['DC'] = "Debit Card";
  $type_map['EC'] = "EBT Cash";
  $type_map['EF'] = "EBT Food";

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
      $args[] = $transaction['trace']['reference'];  # refNum
      $args[] = $transaction['account']['type'];  # cardType
      $args[] = $transaction['transaction_type'];  # transType
      $args[] = $transaction['amount']['approved'];  # amount
      $args[] = intval($transaction['additional']['CARDBIN']."******".$transaction['account']['number']);  # PAN
      $args[] = $transaction['account']['name'];  # name
      $args[] = $transaction['account']['entry'] == 0 ? 1 : 0;  # manual
      $args[] = date('Y-m-d H:i:s');  # responseDatetime
      $makenegative = $void ? 1 : -1;
      $type_name = ($void ? "Void " : "").$type_map[$type];
      if($current) {
        TransRecord::addtender($type_name, $type, $transaction['amount']['approved']*$makenegative);
      }
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

function log_voided($reference, $transaction) {
  // Get the paycardTransactionID of the VOID transaction
  $dbTrans = PaycardLib::paycard_db();
  $findVoidQuery = "SELECT paycardTransactionID FROM PaycardTransactions WHERE refNum = ? AND transNo = ? AND transType = '17'";
  $findVoidPrepared = $dbTrans->prepare($findVoidQuery);
  $findVoidResponse = $dbTrans->execute($findVoidPrepared, array($reference, $transaction));
  $row = $dbTrans->fetchArray($findVoidResponse);
  // TODO: What happens if the query comes up empty?

  $query = "UPDATE PaycardTransactions SET xTransactionID = ? WHERE refNum = ? AND transNo = ? AND transType = '01'";
  $args = array($row['paycardTransactionID'], $reference, $transaction);
  $prepared = $dbTrans->prepare($query);
  $dbResponse = $dbTrans->execute($prepared, $args);
}

$out = array("error" => "Something is very wrong");
$pax = new Pax(CoreLocal::get("PaxHost"), intval(CoreLocal::get("PaxPort")));
if(isset($_POST['action'])) {
  switch($_POST['action']) {
    case "CC":
      $amount = floatval($_POST['amount']);
      $transaction = $pax->do_credit($amount);
      $out = store_transaction($transaction, $_POST['action']);
      if($transaction['amount']['approved'] > intval(CoreLocal::get("PaxSigLimit")) && intval(CoreLocal::get("PaxSigLimit")) >= 0) {
        $out['needsSig'] = true;
      }
    break;
    case "DC":
      $amount = floatval($_POST['amount']);
      $transaction = $pax->do_debit($amount);
      $out = store_transaction($transaction, $_POST['action']);
    break;
    case "EF":
      $amount = floatval($_POST['amount']);
      if($amount > CoreLocal::get('fsEligible')) {
        $out['error'] = "Only $".CoreLocal::get('fsEligible')." is eligible for EBT food.";
      } else {
        $transaction = $pax->do_ebt_food($amount);
        $out = store_transaction($transaction, $_POST['action']);
      }
    break;
    case "EC":
      $amount = floatval($_POST['amount']);
      $transaction = $pax->do_ebt($amount);
      $out = store_transaction($transaction, $_POST['action']);
    break;
    case "signature":
      $out = $pax->do_signature();
      if($out['code'] == 0) {
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
      }
      $out['redirect'] = MiscLib::base_url()."gui-modules/pos2.php?reginput=TO&repeat=1";
    break;
    case "void_CC":
      $reference = $_POST['reference'];
      $transaction_number = "";
      if(isset($_POST['transaction'])) {
        $transaction_number = $_POST['transaction'];
      }
      $transaction = $pax->void_credit($reference, $transaction_number);
      $out = store_transaction($transaction, "CC", true, !isset($_POST['not_current']));
      log_voided($reference, $transaction_number, $out['trace']['transaction']);
    break;
    default:
      $out['error'] = "Unknown command";
    break;
  }
}
header('Content-Type: text/json');

echo json_encode($out);

<?php
/*******************************************************************************

    Copyright 2009 Whole Foods Co-op

    This file is part of CORE-POS.

    CORE-POS is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    CORE-POS is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

/* --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

    * 25Mar2013 AT Merged changes between CORE and flathat
    * 21Mar2013 EL Hacked FANNIE_POUNDS_AS_POUNDS until established.
    *              Use input description width 30, not 27, OK per AT.
    * 16Mar2013 Eric Lee Need to get the vendor name either from the form
    *            or from, ideally, vendors, or prodExtra.
    *            Currently the vendor name input is just text, not controlled.
    *           It would be better if it used size and unitofmeasure from the form.
    *            In update, would need a post-update shelftag create as in insertItem.php

*/

require(dirname(__FILE__) . '/../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}
if (basename(__FILE__) != basename($_SERVER['PHP_SELF'])) {
    return;
}
$dbc = FannieDB::get($FANNIE_OP_DB);

$upc = BarcodeLib::padUPC(FormLib::get('upc'));

// EL 16Mar13 Get vendorItem and vendor data for the item being edited or that was just created.
// This favours UNFI which traditionally has vendorID 1.
//was: $unfiQ = "SELECT DISTINCT * FROM vendorItems WHERE upc = '$upc' ORDER BY vendorID";
$vendiQ = $dbc->prepare_statement("SELECT DISTINCT i.*,v.vendorName FROM vendorItems AS i
            LEFT JOIN vendors AS v ON i.vendorID=v.vendorID
            where upc = ? ORDER BY i.vendorID");

$vendiR = $dbc->exec_statement($vendiQ,array($upc));
$vendiN = $dbc->num_rows($vendiR);

$prodQ = $dbc->prepare_statement("SELECT p.*,s.superID FROM products AS p
    LEFT JOIN MasterSuperDepts AS s ON p.department=s.dept_ID
    where upc=?");
$prodR = $dbc->exec_statement($prodQ,array($upc));

$prodW = $dbc->fetch_array($prodR);
$price = $prodW['normal_price'];
$desc = $prodW['description'];
$brand = '';
$size = '';
$units = '';
$sku = '';
$vendor = '';
$ppo = '';
$superID = $prodW['superID'];

if($vendiN > 0){
 // Use only the first hit.
 $vendiW = $dbc->fetch_array($vendiR);
 // Composed: "200 g"
 $size = $vendiW['size'];
 $brand = $vendiW['brand'];
 $units = $vendiW['units'];
 $sku = $vendiW['sku'];
 if ( $vendiW['vendorName'] != "" ) {
     $vendor = $vendiW['vendorName'];
 } else if ($dbc->table_exists('prodExtra')) {
    $prodExtraQ = $dbc->prepare_statement("select distributor from prodExtra where upc=?");
    $prodExtraR = $dbc->exec_statement($prodExtraQ, array($upc));
    $prodExtraN = $dbc->num_rows($prodExtraR);
    if ($prodExtraN > 0){
        $prodExtraW = $dbc->fetch_array($prodExtraR);
        $vendor = $prodExtraW['distributor'];
    }
 }
 $ppo = \COREPOS\Fannie\API\lib\PriceLib::pricePerUnit($price,$size);
}
else if ($dbc->table_exists('prodExtra')) {
$prodExtraQ = $dbc->prepare_statement("select manufacturer,distributor from prodExtra where upc=?");
$prodExtraR = $dbc->exec_statement($prodExtraQ,array($upc));
$prodExtraN = $dbc->num_rows($prodExtraR);
    if ($prodExtraN == 1){
        $prodExtraW = $dbc->fetch_array($prodExtraR);
        $brand = $prodExtraW['manufacturer'];
        $vendor = $prodExtraW['distributor'];
    }
}

?>
<!doctype html>
<html>
    <head>
        <title>Add Shelf Tag</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="stylesheet" type="text/css" href="../src/javascript/bootstrap/css/bootstrap.min.css">
        <link rel="stylesheet" type="text/css" href="../src/javascript/bootstrap-default/css/bootstrap.min.css">
        <link rel="stylesheet" type="text/css" href="../src/javascript/bootstrap-default/css/bootstrap-theme.min.css">
        <script type="text/javascript" src="../src/javascript/jquery.js"></script>
        <script type="text/javascript" src="../src/javascript/bootstrap/js/bootstrap.min.js"></script>
        <script type="text/javascript">
        $(document).ready(function(){
            $('input.focus').focus();
        });
        </script>
    </head>
<body>
<div class="container-fluid">
<form method='post' action='addShelfTag1.php'>
<input type='hidden' name=upc value='<?php echo $upc; ?>'>
<div class="form-group form-inline">
    <label>Description</label>
    <input type='text' name='description' maxlength=30
        class="form-control focus"
<?php
echo "value='".strtoupper($desc)."'";
?>
>
<label>Brand</label>
    <input type='text' name='brand' maxlength=15 
        class="form-control"
<?php 
echo "value='".strtoupper($brand)."'"; 
?>
>
</div>
<div class="form-group form-inline">
<label>Units</label>
    <input type='text' name='units' size=10
        class="form-control"
<?php
echo "value='".$units."'";
?>
>
<label>Size</label>
<input type='text' name='size' size=10
    class="form-control"
<?php
echo "value='".$size."'";
?>
>
</div>
<div class="form-group form-inline">
<label>PricePer</label>
<input type=text name=ppo
    class="form-control"
<?php echo "value=\"$ppo\"" ?> />
<label>Vendor</label>
<input type='text' name='vendor'
    class="form-control"
<?php
echo "value='$vendor'";
?>
>
</div>
<div class="form-group form-inline">
<label># Tags</label>
<input type="text" name="count" size="3" value="1" 
    class="form-control" />
<label>SKU</label>
<input type='text' name='sku' size=8
    class="form-control"
<?php
echo "value='".$sku."'";
?>
>
</div>
<p>
<label>Price</label>
<span class="alert-success h3">
    <strong><?php printf("%.2f",$price); ?></strong>
</span>
<input type='hidden' name='price' size=8 value=<?php echo $price; ?> />
<button type="submit" class="btn btn-default"
    name="submit" value="New">Create Tag</button>
</p>
<div class="form-group form-inline">
<label>Barcode page</label>
<select name=subID class="form-control">
<?php
$qm = new ShelfTagQueuesModel($dbc);
echo $qm->toOptions($superID);
?>
</select>
</div>
</form>
</div>
</body>
</html>

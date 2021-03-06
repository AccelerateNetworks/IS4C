function itemSearch(){
	if ($('#searchField').val()=='') return false;

	var dstr = 'id='+$('#vendor-id').val();
	dstr += '&search='+$('#searchField').val();
	$('#searchField').val('');

	$.ajax({
		url: 'EditOnePurchaseOrder.php?'+dstr,
		method: 'get',
		dataType: 'json'
    }).done(function(data){
        if (data.length == 0){
            $('#SearchResults').html('No item found');
            $('#searchField').focus();
        }
        else if (data.length == 1){
            $('#SearchResults').html(oneResultForm(data[0], 0));
            $('#srQty0').focus();	
        }
        else {
            $('#SearchResults').html(manyResultForm(data));
            $('#srQty0').focus();
        }
        markInCurrentOrder(data);
	});
}

function markInCurrentOrder(data)
{
	for(var i=0; i<data.length;i++){
        $.ajax({
            url: 'EditOnePurchaseOrder.php',
            data: 'id='+$('#order-id').val()+'&sku='+data[i].sku+'&index='+i,
            dataType: 'json'
        }).done(function(result) {
            if (result.qty != 0) {
                $('#qtyRow'+result.index).append(' <span style="color:green;">IN CURRENT ORDER</span>');
                $('#srQty'+result.index).val(result.qty);
            }
        });
    }
}

function manyResultForm(data){
	var selectText = '<select onchange="showResultForm(this.value);">';
	var divs = '';
	for(var i=0; i<data.length;i++){
		selectText += '<option value="'+i+'">';
		selectText += data[i].sku+' '+data[i].title;
		selectText += '</option>';
		divs += oneResultForm(data[i], i);
	}
	selectText += '</select>';
	return selectText+divs;
}

function showResultForm(num){
	$('.srDiv').hide();
	$('#sr'+num).show();
}

function oneResultForm(obj, resultNum){
	var output = '<div class="srDiv col-sm-6" id="sr'+resultNum+'" ';
	if (resultNum > 0)
		output += ' style="display:none;"';
	output += '>';
    output += '<form onsubmit="saveItem('+resultNum+');return false;">';
	output += '<table class="table">';
	output += '<tr><td align="right">SKU</td>';
	output += '<td id="srSKU'+resultNum+'">'+obj.sku+'</td></tr>';
	output += '<tr>';
	output += '<td colspan="2">'+obj.title+'</td></tr>';
	output += '<tr><td>Unit Size: '+obj.unitSize+'</td>';
	output += '<td>Units/Case: '+obj.caseSize+'</td></tr>';
	output += '<tr><td>Unit Cost: '+obj.unitCost+'</td>';
	output += '<td>Case Cost: '+obj.caseCost+'</td></tr>';
	output += '<tr>';
	output += '<td id="qtyRow'+resultNum+'" colspan="2">Order <input type="number" size="3" value="1" onfocus="this.select();" id="srQty'+resultNum+'" />';
	output += ' Cases</td></tr>';	
	output += '</table>';
	output += '<button type="submit" class="btn btn-default" onclick="saveItem('+resultNum+');return false;">Confirm</button>';
	output += '</form><br />';

	output += '</div>';
	return output;
}

function saveItem(resultNum){
	var dstr = 'id='+$('#order-id').val();
	dstr += '&sku='+$('#srSKU'+resultNum).html();
	dstr += '&qty='+$('#srQty'+resultNum).val();
    saveQty = $('#srQty'+resultNum).val();
	$.ajax({
		url: 'EditOnePurchaseOrder.php?'+dstr,
		method: 'get',
		dataType: 'json'
    }).done(function(data){
        if (data.error){
            $('#SearchResults').html(data.error);
        }
        else if (data.cost && data.count){
            $('#orderInfoCount').html(data.count);
            $('#orderInfoCost').html(data.cost);
            if (saveQty != 0) {
                $('#SearchResults').html('Item added to order');
            } else {
                $('#SearchResults').html('Item removed from order');
            }
        }
        $('#searchField').focus();
	});
}

function updateList()
{
    var dstr = 'id=' + $('#order-id').val();
    dstr += '&' + $('#list-wrapper input').serialize();
    $.ajax({
        url: 'EditOnePurchaseOrder.php',
        dataType: 'json',
        type: 'post',
        data: dstr
    }).done(function(resp) {
        if (resp.table) {
            $('#list-wrapper').html(resp.table);
            showBootstrapAlert('#list-wrapper', 'success', 'Updated Order');
        }
    });
}


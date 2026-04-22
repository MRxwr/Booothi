<div class="row">
<div class="col-sm-12">
<div class="panel panel-default card-view">
<div class="panel-heading">
<div class="pull-left">
<h6 class="panel-title txt-dark"><?php echo direction("List of Subscriptions","قائمة الاشتراكات") ?></h6>
</div>
<div class="clearfix"></div>
</div>
<div class="panel-wrapper collapse in">
<div class="panel-body row">
<div class="table-wrap">
<div class="table-responsive">
<table class="table display responsive product-overview display dataTable mb-30" id="AjaxTable">
<thead>
<tr>
<th><?php echo direction("Date/Time","التاريخ/الوقت") ?></th>
<th><?php echo direction("Order ID","رقم الطلب") ?></th>
<th><?php echo direction("Store","المتجر") ?></th>
<th><?php echo direction("Package","الحزمة") ?></th>
<th><?php echo direction("Price","السعر") ?></th>
<th><?php echo direction("Gateway ID","رقم العملية") ?></th>
<th><?php echo direction("Status","الحالة") ?></th>
<th><?php echo direction("Actions","العمليات") ?></th>
</tr>
</thead>
<tbody>
	
</tbody>
</table>
</div>
</div>	
</div>	
</div>
</div>
</div>
</div>

<script>
$(document).ready(function(){
   $('#AjaxTable').DataTable({
      'processing': true,
      'serverSide': true,
      "pageLength": 10,
      'serverMethod': 'post',
      'ajax': {
          'url':'../api/getSubscriptionItems.php',
          'dataSrc': function(json) {
              if (!json.aaData) {
                  console.error('Invalid JSON response:', json);
                  return [];
              }
              return json.aaData;
          },
          'error': function(xhr, error, thrown) {
              console.error('Error fetching data:', error, thrown);
              console.error('Response:', xhr.responseText);
          }
      },
      'order': [[0, 'desc']],
      'columns': [
         { data: 'date' },
         { data: 'orderId' },
         { data: 'store' },
         { data: 'package' },
         { data: 'price' },
         { data: 'gatewayId' },
         { data: 'status' },
         { data: 'action' },
      ]
   });
});
</script>

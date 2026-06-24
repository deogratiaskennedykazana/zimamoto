function formatNumber(){
  const $inputs = $('.format-number');
  function doFormat(){
    $inputs.each(function(){
      let $el = $(this);
      let val = $el.val().replace(/,/g, '');
      if(val !== '' && !isNaN(val)){
        let parts = val.split('.');
        parts[0] = Number(parts[0]).toLocaleString();
        $el.val(parts.join('.'));
      }
    });
  }
  $inputs.on('input change', function(){
    doFormat();
  });
  const observer = new MutationObserver(doFormat);
  $inputs.each(function(){
    observer.observe(this, { attributes: true, attributeFilter: ['value'] });
  });
}

function addTableRow(tableId) {
    let table = $("#" + tableId);
    let tbody = table.find("tbody");
    if (tbody.length === 0) {
        table.append("<tbody></tbody>");
        tbody = table.find("tbody");
    }
    let newRow = tbody.find("tr.data-row:first").clone();
    newRow.find("span.select2").remove();
    newRow.find("select").removeClass('select2-hidden-accessible').show().val("");
    newRow.find("input").val("");
    tbody.append(newRow);
    initializeSelect();
    formatNumber();
}

function removeTableRow(tableId) {
    let table = $("#" + tableId);
    let rowCount = table.find("tbody tr").length;
    if(rowCount > 1){
        table.find("tbody tr:last").remove();
    }
}

function showSpinner() {
  $('#spinner-overlay').css('display', 'flex');
}

function hideSpinner() {
  $('#spinner-overlay').hide();
}


function reinitializeTableSearch(){
  $('table.table-search').tableSearch({
            searchText:'Search here',
            searchPlaceHolder:'Input Value'
        });
} 


function calculateEqv1() {
    var currValue = document.getElementById("curr_value").value.replace(/,/g, '');
    var currency = parseFloat(currValue) || 0;
    var amounts = document.getElementsByName("amount[]");
    var eqvs = document.getElementsByName("eqv[]");
    for(var i = 0; i < amounts.length; i++){
        var amountValue = amounts[i].value.replace(/,/g, '');
        var result = currency * (parseFloat(amountValue) || 0);
        eqvs[i].value = result.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }
}

  

 function calculateEqv(){
    console.log("am running");
    var currency = parseFloat($("input[name='curr']").val().replace(/,/g, '')) || 0;  
    console.log(currency);
    var amounts = $(".debt_amount"); 
    var eqvs = $(".equiv_dr"); 
    amounts.each(function(index){
        var amountValue = $(this).val().replace(/,/g, '');  
        var result = currency * (parseFloat(amountValue) || 0);
        $(eqvs[index]).val(result.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
    });
}
 
 
function exportTableToExcel(table, sheetName, fileName) {
    if (!table) {
      alert('No table found to export!');
      return;
    }
    var wb = XLSX.utils.book_new();
    var ws = XLSX.utils.table_to_sheet(table);
    var today = new Date();
    var dateStr = today.getFullYear() + '-' + 
                  String(today.getMonth() + 1).padStart(2, '0') + '-' + 
                  String(today.getDate()).padStart(2, '0');
    var fullFileName = fileName + '_' + dateStr + '.xlsx';
    XLSX.utils.book_append_sheet(wb, ws, sheetName);
    XLSX.writeFile(wb, fullFileName);
}

 
function selectCurrency(str) {
    if(!str) return;
    $.get('./processes/api/currency.php?id=' + str, function(response) {
        $('[id^="currency"]').html(response);
        formatNumber();
    });
}

function initializeSelect(){
  $(document).ready(function(){
    $(function () {
         $(".select2-form").select2({
                       placeholder: "Select an option",
                             });
            $('.select2bs4-form').select2({
      theme: 'bootstrap4'
    })
         });


});
}


 
function getUserOptionByBranch(){
  console.log("running");
  var id = $('select[name="branch_id"]').length ? $('select[name="branch_id"]').val() : $('input[name="branch_id"]').val();
  if(!id){
    alert("Select Branch First");
    return;
  }
  $.ajax({
    type:"GET",
     dataType: 'json',
    url:"./requests/form_requests.php",
    data:{"get_members_by_branch_id_json":"","branchId":id},
    success: function(data){
      console.log(data);
       $('.user_id').find('option:not(:first)').remove();
      $(".user_id").empty().append('<option value="">Select below</option>');
      $.each(data, function(index, item) {

        
          $('.user_id').append(
            $('<option>', {
              value: item.id,
              text: item.name
            })
          );
      })
    }
});
}

function calculateRepayment(){
  var principle = parseFloat($('input[name="principle"]').val());
  var interestRate = parseFloat($('input[name="interest_rate"]').val());
  var loan_term = parseFloat($('input[name="loan_term"]').val());
  
  var repayment = (principle + (principle*interestRate/100))/loan_term;

  $('input[name="repayment_amount"]').val(repayment.toFixed(2));
  $('input[name="interest_amount"]').val((principle*interestRate/100).toFixed(2));
  $('input[name="total_loan"]').val((repayment*loan_term).toFixed(2));
}
 
  
 

 


 

 
 

function fetchAllMembers(){
  console.log("running");
  var id = $('select[name="branch"]').val();
  if(!id){
    alert("Select Branch First");
    return;
  }
  
  $.ajax({
    type:"GET",
    url:"./requests/ui_requests.php",
    data:{"get_members_by_branch_id":"","branchId":id},
    success: function(data){
      $(".data").html(data);
      
      // Check if DataTables is available, if not load it
      if (typeof $.fn.dataTable === 'undefined') {
        console.log("DataTables not loaded, loading now...");
        
        // Load DataTables CSS
        if (!$('link[href*="datatables"]').length) {
          $('<link>').attr({
            type: 'text/css',
            rel: 'stylesheet',
            href: './dist/datatable2/datatables.css'
          }).appendTo('head');
        }
        
        // Load DataTables JS
        $.getScript('./dist/datatable2/datatables.js', function() {
          console.log("DataTables loaded successfully");
          initializeDataTable();
        }).fail(function() {
          console.log("Failed to load DataTables");
        });
      } else {
        console.log("DataTables already available");
        setTimeout(initializeDataTable, 100);
      }
      
      function initializeDataTable() {
        try {
          if ($('.data .data-table').length > 0) {
            $('.data .data-table').dataTable({
              //responsive: true,
              ordering: false,
              "dom": 'Bfrtip',
              "buttons": ['copy', 'excel', 'pdf', 'print']
            });
            console.log("DataTable initialized successfully");
          }
        } catch(e) {
          console.log("DataTable init failed:", e);
        }
      }
    },
    error: function(xhr, status, error){
      $(".data").html(error);
    },
    complete: function () {
      console.log("complete");
    }
  });
}

function fetchAllMembersByBranch(){
  console.log("running");
  var id = $('select[name="branch"]').val();
  if(!id){
    alert("Select Branch First");
    return;
  }
  // const modal = new bootstrap.Modal(document.getElementById('spinnerModal'));
  // spinnerModal.show();
  $.ajax({
    type:"GET",
    url:"./requests/ui_requests.php",
   
    data:{"update_members_by_branch_id":"","branchId":id},
    success: function(data){
     
      $(".data").html(data);
      
      // Check if DataTables is available, if not load it
      if (typeof $.fn.dataTable === 'undefined') {
        console.log("DataTables not loaded, loading now...");
        
        // Load DataTables CSS
        if (!$('link[href*="datatables"]').length) {
          $('<link>').attr({
            type: 'text/css',
            rel: 'stylesheet',
            href: './dist/datatable2/datatables.css'
          }).appendTo('head');
        }
        
        // Load DataTables JS
        $.getScript('./dist/datatable2/datatables.js', function() {
          console.log("DataTables loaded successfully");
          initializeDataTable();
        }).fail(function() {
          console.log("Failed to load DataTables");
        });
      } else {
        console.log("DataTables already available");
        setTimeout(initializeDataTable, 100);
      }
      
      function initializeDataTable() {
        try {
          if ($('.data .data-table').length > 0) {
            $('.data .data-table').dataTable({
              //responsive: true,
              ordering: false,
              "dom": 'Bfrtip',
              "buttons": ['copy', 'excel', 'pdf', 'print']
            });
            console.log("DataTable initialized successfully");
          }
        } catch(e) {
          console.log("DataTable init failed:", e);
        }
      }
      
    //  spinnerModal.hide();
    },
    error: function(xhr, status, error){
      //spinnerModal.hide();
      $(".data").html(error);
    },
    complete: function () {
      console.log("complete");
      // spinnerModal.hide();
    }
  });
}


function fetchMinSubByBranch(){
    console.log("1. Function started");
    
    var branchId = $('select[name="branch"]').val();
    console.log("2. Branch ID:", branchId);
    
    $.ajax({
        type:"GET",
        url:"./requests/form_requests.php",
        dataType: 'json',
        data:{"get_min_sub_by_branch_id":"","branchId":branchId},
        success: function(data){
            console.log("3. Response received:", data);
            console.log("4. Response length:", data.length);
            
            $('.min_item').find('option:not(:first)').remove();
            
            $.each(data, function(index, item) {
                $('.min_item').append(
                    $('<option>', {
                        value: item.id,
                        text: item.name
                    })
                );
            });
            
            console.log("5. Options added successfully");
        },
        error: function(xhr, status, error){
            console.log("ERROR:", error);
        }
    });
}



function setLoanCapacity(){
  var userId = $('select[name="user_id"]').length ? $('select[name="user_id"]').val() : $('input[name="user_id"]').val();
  var loanTypeId = $('#loan_type_select').val() || 0;
  if(!userId) return;

  $.ajax({
    type: 'GET',
    url: './requests/form_requests.php',
    data: { get_loan_capacity_by_user_id: '', userId: userId, loanTypeId: loanTypeId },
    success: function(data){
      var obj = typeof data === 'string' ? JSON.parse(data) : data;
      var capacity   = parseFloat(obj.capacity   || 0);
      var savings    = parseFloat(obj.savings    || 0);
      var multiplier = parseFloat(obj.multiplier || 3);
      var formatted  = capacity.toLocaleString();
      $('input[name="amount"]').attr('max', capacity);
      $('input[name="amount"]').attr(
        'placeholder',
        'Max: TZS ' + formatted +
        ' (TZS ' + savings.toLocaleString() + ' savings \u00d7 ' + multiplier + 'x)'
      );
    }
  });
}
 
 

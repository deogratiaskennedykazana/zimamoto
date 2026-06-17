<div id="spinner-overlay">
    <div class="spinner"></div>
    <p style="margin-top: 15px; margin-left:15px; font-weight: bold;">Processing...</p>
</div>

<div class="card card-info">
    <div class="card-header">
        <h4 class="card-title">Subsidiary list</h4>
    </div>
   <div class="card-footer">
    <div class="btn-group mb-2 flex-wrap d-flex w-100 justify-content-start" id="tab-buttons">
        <button class="btn btn-outline-primary btn-sm active" data-type="all">All</button>
       
        <button class="btn btn-outline-success btn-sm" data-type="supplier">Suppliers</button>
        <button class="btn btn-outline-info btn-sm" data-type="customer">Customers</button>
        <button class="btn btn-outline-warning btn-sm" data-type="staff">Staffs</button>
        <button class="btn btn-outline-primary btn-sm" data-type="others">Others</button>
        <button class="btn btn-outline-secondary btn-sm" data-type="stocks">Stock</button>
        <button class="btn btn-outline-dark btn-sm" data-type="asset">Assets</button>
        <button class="btn btn-outline-danger btn-sm" data-type="deleted">Trashed</button>
   </div>          
</div>
    <div class="card-body">
        <div class="data"></div>
    </div>
</div>
 

<script>
$(document).ready(function(){
    $('#tab-buttons button').click(function(){
        $('#tab-buttons button').removeClass('active');
        $(this).addClass('active');
        var type = $(this).data('type');
        sessionStorage.setItem('selectedType', type);
        fetchSubsidiariesByType(type);
    });
    var savedType = sessionStorage.getItem('selectedType') || 'all';
    $('#tab-buttons button[data-type="' + savedType + '"]').addClass('active').siblings().removeClass('active');
    fetchSubsidiariesByType(savedType);
});
function fetchSubsidiariesByType(type){
    if(!type){
        alert("Please select a type");
        return;
    }
    showSpinner();
    $.ajax({
        url:"./requests/subsidiary_requests.php?select_subsidiaries_by_type",
        type:"GET",
        data:{type:type},
        success: function(response){
            $(".data").html(response);
            reinitializeTableSearch();
        },
        error: function(){
            hideSpinner();
        },
        complete: function(){
            hideSpinner();
        }
    });
}
</script>
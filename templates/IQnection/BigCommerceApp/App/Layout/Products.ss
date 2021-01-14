<!-- ============================================================== -->
<!-- pageheader -->
<!-- ============================================================== -->
<div class="row">
  <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
    <div class="page-header">
      <h2 class="pageheader-title">$Title</h2>
      <div class="page-breadcrumb">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="$Dashboard.Link" class="breadcrumb-link">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="#" class="breadcrumb-link">Products</a></li>
          </ol>
        </nav>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
    
    <div class="card">
      <div class="card-header p-4 d-flex justify-content-between">
        <h2 class="m-0">Products</h2>
		<% if $SyncStatus %>
			<div>
				$SyncStatus.StatusDisplay
			</div>
		<% end_if %>
      </div>
	  
	  <div class="card-body">
		  <div class="table-responsive">
				<table class="table table-striped table-bordered" data-src="{$AbsoluteLink}/search" id="product-list">
					<thead>
						<tr>
							<th>&nbsp;</th>
							<th>Name</th>
							<th>SKU</th>
							<th class="text-right">&nbsp;</th>
						</tr>
					</thead>
					<tbody>

					</tbody>
					<tfoot>
						<tr>
							<th>&nbsp;</th>
							<th>Name</th>
							<th>SKU</th>
							<th class="text-right">&nbsp;</th>
						</tr>
					</tfoot>
				</table>
			</div>
	  </div>
  </div>
</div>
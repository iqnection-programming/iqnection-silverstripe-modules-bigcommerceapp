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
            <li class="breadcrumb-item"><a href="javascript:;" class="breadcrumb-link">Categories</a></li>
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
        <h2 class="m-0">Categories</h2>
		<div class="mr-3 d-flex align-items-center ml-auto">
			<div class="mr-1">Show Hidden Categories</div>
			<div>
				<div class="switch-button switch-button-primary switch-button-lg">
					<input type="checkbox" name="show-hidden" class="toggle-hidden" id="toggle-hidden-categories" data-hidden-class="hide-invisible" data-hidden-target="#category-accordion" checked />
					<span>
						<label for="toggle-hidden-categories"></label>
					</span>
				</div>
			</div>
		</div>
		<div class="filter-form">
			<div class="input-group mb-0">
				<input type="text" class="form-control" name="filter" placeholder="Filter Categories" value="" data-filter-target="#category-accordion" />
			 	<div class="input-group-append">
					<button class="btn btn-outline-secondary" type="button">&times;</button>
				</div>
			</div>
		</div>
      </div>
	  
	  <ul class="list-group" id="category-accordion">
	    <% loop $Categories.Filter(ParentID,0) %>
			<li class="list-group-item border-left-0 border-right-0 py-2<% if not $is_visible %> bc-hidden<% end_if %>" data-filter-value="$Title.Lowercase.ATT">
		  	<% include CategoryListItem %>
			</li>
	    <% end_loop %>
	  </ul>
	  
  </div>
</div>
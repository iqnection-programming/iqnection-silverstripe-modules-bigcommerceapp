
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
						<li class="breadcrumb-item"><a href="$Link" class="breadcrumb-link">Categories</a></li>
						<li class="breadcrumb-item active"><a href="javascript:;" class="breadcrumb-link">$currentRecord.Title</a></li>
					</ol>
				</nav>
			</div>
		</div>
	</div>
</div>
<!-- ============================================================== -->
<!-- end pageheader -->
<!-- ============================================================== -->

<div class="card">
	<div class="card-body">
		<div>
			<a href="$join_links($SiteConfig.BigCommerceStoreUrl,$currentRecord.RawApiData.custom_url.url)" target="_blank" class="btn btn-primary btn-sm">View in Storefront</a>
			<a href="$join_links($SiteConfig.BigCommerceStoreUrl,manage,products,categories,$currentRecord.BigID,edit)" target="_blank" class="btn btn-primary btn-sm">Edit in BigCommerce</a>
			<a href="$join_links($Link,edit,$currentRecord.ID,pull)" class="btn btn-primary btn-sm">Sync Data from BigCommerce</a>
			<a href="$join_links($Link,apidata,$currentRecord.ID)" target="_blank" class="btn btn-primary btn-sm">View API Data</a>
		</div>
		<div class="mt-2">
			<% if $currentRecord.Parent.Exists %>
				<a href="$Top.join_links($Link,edit,$currentRecord.ParentID)" class="btn btn-primary btn-sm">Edit Parent Category</a>
			<% end_if %>
			<% if $currentRecord.Siblings.Count %>
				<div class="d-inline-block dropdown">
					<a href="#" role="button" class="btn btn-sm btn-primary dropdown-toggle" data-toggle="dropdown">Edit Siblings</a>
					<div class="dropdown-menu">
						<% loop $currentRecord.Siblings %>
							<a href="$Top.join_links($Top.Link,edit,$ID)" class="dropdown-item">$Title</a>
						<% end_loop %>
					</div>
				</div>
			<% end_if %>
			<% if $currentRecord.Children.Count %>
				<div class="d-inline-block dropdown">
					<a href="#" role="button" class="btn btn-sm btn-primary dropdown-toggle" data-toggle="dropdown">Edit Children</a>
					<div class="dropdown-menu">
						<% loop $currentRecord.Children %>
							<a href="$Top.join_links($Top.Link,edit,$ID)" class="dropdown-item">$Title</a>
						<% end_loop %>
					</div>
				</div>
			<% end_if %>
		</div>
	</div>
</div>

<div class="card">
	<h5 class="card-header">Edit Category: $currentRecord.Breadcrumbs <a href="$Link" class="btn btn-danger btn-sm float-right" role="button">Cancel</a></h5>
	<div class="card-body">
		<% if not $currentRecord.is_visible %>
		<div>Category is Hidden</div>
		<% end_if %>
		$recordForm
	</div>
</div>

<% if $currentRecord.RelatedObjects.Count %>
	<% loop $currentRecord.RelatedObjects %>
		<% include RelatedComponentList Collection=$Me %>
	<% end_loop %>
<% end_if %>


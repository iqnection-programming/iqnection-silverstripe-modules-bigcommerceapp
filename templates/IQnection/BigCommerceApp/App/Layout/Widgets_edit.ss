
<!-- ============================================================== -->
<!-- pageheader -->
<!-- ============================================================== -->
<div class="row">
	<div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
		<div class="page-header">
			<h2 class="pageheader-title"><% if $currentRecord.Exists %>Edit Widget: $currentRecord.Title<% else %>Add Widget<% end_if %></h2>
			<div class="page-breadcrumb">
				<nav aria-label="breadcrumb">
					<ol class="breadcrumb">
						<li class="breadcrumb-item"><a href="$Dashboard.Link" class="breadcrumb-link">Dashboard</a></li>
						<li class="breadcrumb-item"><a href="$Link" class="breadcrumb-link">Widgets</a></li>
						<li class="breadcrumb-item active"><a class="breadcrumb-link">$currentRecord.Title</a></li>
						<li class="ml-auto">
							<% if $currentRecord.Exists && $currentRecord.RelatedObjects.Count %>
								<a href="{$Top.join_links($Link,sync,$currentRecord.ID)}" class="btn btn-primary btn-sm">Sync</a>
							<% end_if %>
							<a href="$Link" class="btn btn-outline-danger btn-sm" role="button">Cancel</a>
						</li>
					</ol>
				</nav>
			</div>
		</div>
	</div>
</div>
<!-- ============================================================== -->
<!-- end pageheader -->
<!-- ============================================================== -->


<div class="mb-3">
	<a href="$join_links($SiteConfig.BigCommerceStoreUrl,$currentRecord.RawApiData.custom_url.url)" target="_blank" class="btn btn-primary btn-sm">View in Storefront</a>
	<a href="$join_links($SiteConfig.BigCommerceStoreUrl,manage,products,edit,$currentRecord.BigID)" target="_blank" class="btn btn-primary btn-sm">Edit in BigCommerce</a>
	<a href="$join_links($Link,edit,$currentRecord.ID,pull)" class="btn btn-primary btn-sm">Sync Data from BigCommerce</a>
	<a href="$join_links($Link,apidata,$currentRecord.ID)" target="_blank" class="btn btn-primary btn-sm">View API Data</a>
</div>
<div class="card">
	<div class="card-header h4">Details</div>
	<div class="card-body">
		$recordForm
	</div>
</div>

<% if $currentRecord.Exists %>
	
	<% loop $currentRecord.RelatedObjects %>
		<% include RelatedComponentList Collection=$Me %>
	<% end_loop %>
	
	<div class="card">
		<div class="card-header h4">Placements <% if $currentRecord.CanAddPlacement %> <a href="{$Top.join_links($Link,edit,$currentRecord.ID,place,Placements)}" class="btn btn-success btn-sm float-right" role="button">Add Placement</a><% end_if %></div>
		<div class="card-body">
			<% if $currentRecord.Placements.Count %>
				<table class="table">
					<thead>
						<tr>
							<th>ID</th>
							<th>Page</th>
							<th>Region</th>
							<th>&nbsp;</th>
						</tr>
					</thead>
					<tbody>
						<% loop $currentRecord.Placements %>
							<tr data-block-element>
								<td>$BigID</td>
								<td>{$Entity.TemplateConfig.Title}<% if $PlacementResource %>: $PlacementResource.Title<% end_if %></td>
								<td>$region</td>
								<td class="text-right">
									<a href="{$Top.join_links($Top.Link,edit,$Top.currentRecord.ID,place,Placements,$ID)}" class="btn btn-sm btn-primary">Edit</a>
									<a href="{$Top.join_links($Top.Link,edit,$Top.currentRecord.ID,relationremove,Placements,$ID)}" class="btn btn-sm btn-outline-danger" data-ajax-before-callback="removingItem" data-ajax-after-callback="itemRemoved">Remove</a>
								</td>
							</tr>
						<% end_loop %>
					</tbody>
				</table>
			<% else %>
				<p>This widget has not been placed anywhere</p>
			<% end_if %>
		</div>
	</div>
	
<% end_if %>


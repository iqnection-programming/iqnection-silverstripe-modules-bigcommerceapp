
<!-- ============================================================== -->
<!-- pageheader -->
<!-- ============================================================== -->
<div class="row">
	<div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
		<div class="page-header">
			<h2 class="pageheader-title">$ModuleName</h2>
			<div class="page-breadcrumb">
				<nav aria-label="breadcrumb">
					<ol class="breadcrumb">
						<li class="breadcrumb-item"><a href="$Dashboard.Link" class="breadcrumb-link">Dashboard</a></li>
						<li class="breadcrumb-item"><a href="$Link" class="breadcrumb-link">$ModuleName</a></li>
						<li class="breadcrumb-item"><a href="$join_links($Link,edit,$currentRecord.ID)" class="breadcrumb-link">$currentRecord.Title</a></li>
						<li class="breadcrumb-item active"><a href="javascript:;" class="breadcrumb-link">{$relatedObject.singular_name}</a></li>
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
	<h5 class="card-header"><% if $relatedObject.Exists %>Edit<% else %>Add<% end_if %> {$relatedObject.singular_name} <a href="$join_links($Link,edit,$currentRecord.ID)" class="btn btn-danger btn-sm float-right" role="button">Back</a></h5>
	<div class="card-body">
		$RelatedObjectForm
	</div>
</div>

<% if $relatedObject.RelatedObjects.Count %>
	<% loop $relatedObject.RelatedObjects %>
		<% include RelatedSubcomponentList Collection=$Me, RelatedComponentName=$Top.ComponentName, RelatedParent=$relatedObject %>
	<% end_loop %>
<% end_if %>



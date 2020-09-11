

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
						<li class="breadcrumb-item active"><a href="#" class="breadcrumb-link">Widgets</a></li>
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
	<h5 class="card-header">Widgets <a href="$join_links($Link,edit)" class="btn btn-success btn-sm float-right" role="button">New Widget</a></h5>
	<div class="card-body">
		<div class="table-responsive">
			<table class="table table-striped table-bordered first">
				<thead>
					<tr>
						<th>ID</th>
						<th>Title</th>
						<th>Created</th>
						<th>Description</th>
						<th>&nbsp;</th>
					</tr>
				</thead>
				<tbody>
					<% loop $Widgets %>
						<tr>
							<td>$ID / $BigID</td>
							<td>$Title</td>
							<td>$Created.Nice</td>
							<td>$Description</td>
							<td class="text-right text-nowrap">
								<a href="{$Top.join_links($Top.Link,edit,$ID)}" class="btn btn-primary btn-sm">Edit</a>
								<% if $CanDelete %>
									<a href="{$Top.join_links($Top.Link,delete,$ID)}" class="btn btn-outline-danger btn-sm">Delete</a>
								<% end_if %>
								<a href="{$Top.join_links($Top.Link,sync,$ID)}" class="btn btn-outline-success btn-sm">Sync</a>
							</td>
						</tr>
					<% end_loop %>
				</tbody>
			</table>
		</div>
	</div>
</div>

<div class="card">
	<h5 class="card-header">Registered Widgets</h5>
	<div class="card-body">
		<div class="table-responsive">
			<table class="table table-striped table-bordered first">
				<thead>
					<tr>
						<th>ID</th>
						<th>Title</th>
						<th>Created</th>
						<th>Description</th>
<%--						<th>&nbsp;</th>--%>
					</tr>
				</thead>
				<tbody>
					<% loop $BCWidgets %>
						<tr>
							<td>$uuid</td>
							<td>$name</td>
							<td>$date_created</td>
							<td>$description</td>
<%--							<td class="text-right">
								<a href="{$Top.join_links($Top.Link,delete,$uuid)}" class="btn btn-danger btn-sm">Delete</a>
							</td>--%>
						</tr>
					<% end_loop %>
				</tbody>
			</table>
		</div>
	</div>
</div>

<div class="card">
	<h5 class="card-header">Registered Widget Templates</h5>
	<div class="card-body">
		<div class="table-responsive">
			<table class="table table-striped table-bordered first">
				<thead>
					<tr>
						<th>ID</th>
						<th>Title</th>
						<th>Created</th>
						<th>Description</th>
<%--						<th>&nbsp;</th>--%>
					</tr>
				</thead>
				<tbody>
					<% loop $BCWidgetTemplates.Filter(kind,'custom') %>
						<tr>
							<td>$uuid</td>
							<td>$name</td>
							<td>$date_created</td>
							<td>$description</td>
<%--							<td class="text-right">
								<% if $kind == 'custom' %>
									<a href="{$Top.join_links($Top.Link,unlinktemplate,$uuid)}" class="btn btn-outline-danger btn-sm">Remove</a>
								<% end_if %>
							</td>--%>
						</tr>
					<% end_loop %>
				</tbody>
			</table>
		</div>
	</div>
</div>

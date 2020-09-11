

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
						<li class="breadcrumb-item active"><a href="#" class="breadcrumb-link">Webhooks</a></li>
					</ol>
				</nav>
			</div>
		</div>
	</div>
</div>
<!-- ============================================================== -->
<!-- end pageheader -->
<!-- ============================================================== -->

<div class="row">
	<div class="col col-3">
		<div class="card">
			<h5 class="card-header m-0">Add Webhook</h5>
			<div class="card-body">$webhookSubscribeForm</div>
		</div>
	</div>
	<div class="col col-9">
		<div class="card">
			<h5 class="card-header">Subscribed Webhooks</h5>
			<div class="card-body">
				<% if $BCWebhooks.Count %>
				<div class="table-responsive">
					<table class="table table-striped table-bordered first">
						<thead>
							<tr>
								<th>ID</th>
								<th>Title</th>
								<th>Scope</th>
								<th>Destination</th>
								<th>Active</th>
								<th>&nbsp;</th>
							</tr>
						</thead>
						<tbody>
							<% loop $BCWebhooks %>
								<tr>
									<td>$id</td>
									<td>$Title</td>
									<td>$scope</td>
									<td>$destination</td>
									<td><% if $is_active %>Listening<% else %>Paused<% end_if %></td>
									<td class="text-right text-nowrap">
										<% if $is_active %>
											<a href="$Top.join_links($Top.Link,pause,$id,'?active=0')" class="btn btn-primary btn-sm">Pause</a>
										<% else %>
											<a href="$Top.join_links($Top.Link,pause,$id,'?active=1')" class="btn btn-primary btn-sm">Resume</a>
										<% end_if %>
										<a href="$Top.join_links($Top.Link,deleteWebhook,$id)" class="btn btn-danger btn-sm">Delete</a>
									</td>
								</tr>
							<% end_loop %>
						</tbody>
					</table>
				</div>
				<% else %>
					No Webhook Subscriptions
				<% end_if %>
			</div>
		</div>
	</div>
</div>



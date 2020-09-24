<!-- template: /Includes/RelatedComponents -->
	<div class="card mb-1">
		<div class="card-header d-flex justify-content-between">
			<div class="h4 m-0">{$Title}</div>
			<div><a href="{$Dashboard.Link}/edit/{$Dashboard.currentRecord.ID}/relation/{$ComponentName}/" class="btn btn-success btn-sm float-right" role="button">Add Item</a></div>
		</div>
		<% if $Collection.Description %>
			<div class="card-body">
				$Collection.Description
			</div>
		<% end_if %>
	</div>
	<% if $Collection.Count %>
		<div class="row d-flex flex-wrap align-content-stretch">
			<% loop $Collection %>
				<div class="col-4 mb-4">
					<div class="card h-100 flex flex-column align-items-stretch">
						<div class="card-header">
							<div>Item: $Pos</div>
							<div>ID: $ID</div>
						</div>
						<div class="card-body<% if not $First %> border-top<% end_if %>">
							<% loop $DashboardDisplay %>
								<div>$Title: $Value.RAW</div>
							<% end_loop %>
						</div>
						<div class="card-footer">
							<a href="{$Dashboard.Link}/edit/{$Dashboard.currentRecord.ID}/relation/$Up.ComponentName/$ID" class="btn btn-sm btn-primary">Edit</a> 
							<a href="{$Dashboard.Link}/edit/{$Dashboard.currentRecord.ID}/relationremove/{$Up.ComponentName}/{$ID}" class="btn btn-sm btn-outline-danger">Delete</a>
						</div>
					</div>
				</div>
			<% end_loop %>
		</div>
	<% else %>
		<div class="card">
			<div class="card-body">
				<p>No Items Added <a href="{$Dashboard.Link}/edit/{$Dashboard.currentRecord.ID}/relation/{$ComponentName}/" class="btn btn-success btn-sm" role="button">Add Item</a></p>
			</div>
		</div><!--/.card-->
	<% end_if %>

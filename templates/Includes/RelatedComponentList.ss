<!-- template: /Includes/RelatedComponentList -->
	<div class="card mb-1" id="$ComponentName.LowerCase.URLATT">
		<div class="card-header d-flex justify-content-between">
			<div class="h4 m-0">{$Name}</div>
			<div>
				<% if $Collection.ClassNames.Count %>
					<% loop $Collection.ClassNames %>
						<a href="{$Top.Dashboard.Link}edit/{$Top.Dashboard.currentRecord.ID}/relation/{$Top.ComponentName}/?type={$UrlParam}" class="btn btn-success btn-sm ml-2 float-right" role="button">New {$Title}</a>
					<% end_loop %>
				<% else %>
					<a href="{$Dashboard.Link}edit/{$Dashboard.currentRecord.ID}/relation/{$ComponentName}/" class="btn btn-success btn-sm float-right" role="button">Add New</a>
				<% end_if %>
			</div>
		</div>
	</div>
	<% if $Collection.Records.Count %>
		<div class="row d-flex flex-wrap align-content-stretch<% if $Collection.Records.First.isSortable %> sortable<% end_if %>" data-record-id="$Top.Dashboard.currentRecord.ID" data-component="$Collection.ComponentName.ATT">
			<% loop $Collection.Records %>
				<div class="col-3 mb-4<% if $isSortable %> sort-item<% end_if %>" data-id="$ID" data-block-element>
					<div class="card h-100 flex flex-column align-items-stretch">
						<div class="card-header">
							<% if $isSortable %>
								<div class="drag-handle p3 float-right"><span class="fas fa-arrows-alt"></span></div>
							<% end_if %>
							<div>Item: $Pos</div>
							<div>ID: $ID</div>
						</div>
						<div class="card-body py-0">
							<% loop $DashboardDisplay %>
								<div class="related-component-content">
									<div class="py-2<% if not $Last %> border-bottom<% end_if %>">
										<% if $Title %><h5 class="mb-0">$Title.RAW</h5><% end_if %>
										<% if $Value.RAW %><div>$Value.RAW</div><% end_if %>
									</div>
								</div>
							<% end_loop %>
						</div>
						<div class="card-footer">
							<a href="{$Dashboard.Link}edit/{$Dashboard.currentRecord.ID}/relation/$Up.ComponentName/$ID" class="btn btn-sm btn-primary">Edit</a> 
							<a href="{$Dashboard.Link}edit/{$Dashboard.currentRecord.ID}/relationremove/{$Up.ComponentName}/{$ID}" class="btn btn-sm btn-outline-danger ajax" data-ajax-before-callback="removingItem" data-ajax-after-callback="itemRemoved">Delete</a>
						</div>
					</div>
				</div>
			<% end_loop %>
		</div>
	<% else %>
		<div class="card">
			<div class="card-body">
				<p>No Items</p>
			</div>
		</div><!--/.card-->
	<% end_if %>

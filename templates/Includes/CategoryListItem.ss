
	
		<h5 class="mb-0">
			<% if $Children.Count %>
			<button class="btn btn-link collapsed p-2 text-dark text-decoration-none" type="button" data-toggle="collapse" data-target="#collapse-$ID" aria-expanded="false" aria-controls="collapse-$ID">
				<span class="fas fa-angle-right mr-4"></span> <span class="<% if not $is_visible %>text-black-50<% end_if %>"><% if $Title %>$Title<% else %>$ID<% end_if %></span>
			</button>
			<% else %>
				<span class="d-inline-block p-2 mr-3">&nbsp;</span> <span class="<% if not $is_visible %>text-black-50<% end_if %>"><% if $Title %>$Title<% else %>$ID<% end_if %></span>
			<% end_if %>
			<a href="{$Dashboard(Categories).join_links($Dashboard(Categories).Link,edit,$ID)}" class="d-inline-block ml-3 text-small"><span class="fas fa-edit"></span></a>
			<% if not $BigID %>
				<span class="text-small float-right">Sync Pending</span>
			<% end_if %>
		</h5>
	
		<% if $Children.Count %>
		<div id="collapse-$ID" class="collapse" data-parent="category-accordion">
			<ul class="list-group pl-3">
			<% loop $AllChildren %>
				<li class="list-group-item border-left-0 border-right-0 py-2 <% if not $is_visible %> bc-hidden<% end_if %><% if $Last %> border-bottom-0<% end_if %>" data-filter-value="$Title.Lowercase.ATT">
				<% include CategoryListItem %>
				</li>
			<% end_loop %>
			</ul>
		</div>
		<% end_if %>

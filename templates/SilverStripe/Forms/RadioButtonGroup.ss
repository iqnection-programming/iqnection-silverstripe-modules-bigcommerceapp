<div $AttributesHTML >
<div class="btn-group btn-group-toggle" data-toggle="buttons">
	<% loop $Options %>
		<label class="btn btn-primary<% if $isChecked %> active<% end_if %>">
			<input id="$ID" class="radio" name="$Name" type="radio" value="$Value"<% if $isChecked %> checked<% end_if %><% if $isDisabled %> disabled<% end_if %> <% if $Up.Required %>required<% end_if %> />
			$Title
		</label>
	<% end_loop %>
</div>
</div>

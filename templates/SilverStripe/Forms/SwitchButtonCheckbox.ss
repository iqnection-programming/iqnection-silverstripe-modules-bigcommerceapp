<div id="$HolderID" class="field <% if extraClass %> $extraClass<% end_if %>">
	<label for="$ID">$Title<% if $RightTitle %> $RightTitle<% end_if %></label>
	<div class="field switch-button switch-button-success m-0 ml-2<% if extraClass %> $extraClass<% end_if %>">
		$Field
		<span>
			<label class="right" for="$ID"></label>
		</span>
	</div>
	<% if $Message %><span class="message $MessageType">$Message</span><% end_if %>
	<% if $Description %><span class="description">$Description</span><% end_if %>
</div>

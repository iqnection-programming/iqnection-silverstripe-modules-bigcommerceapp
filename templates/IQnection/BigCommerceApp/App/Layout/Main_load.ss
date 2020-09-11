
<% if $Error %>
	<p>There was a problem loading the app.</p>
	<p>$ErrorMessage</p>
	<pre>$ErrorData.RAW</pre>
<% else %>
	<p>Loading...</p>
<% end_if %>
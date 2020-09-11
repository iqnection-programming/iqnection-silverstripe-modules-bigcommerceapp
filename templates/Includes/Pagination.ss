<% if $Collection.MoreThanOnePage %>
  <nav class="mt-2">
	<ul class="pagination">
		<% if $Collection.NotFirstPage %>
      <li class="page-item"><a class="page-link" href="$Collection.PrevLink">Prev</a></li>
		<% end_if %>
		<% loop $Collection.Pages %>
			<% if $CurrentBool %>
        <li class="page-item active"><a class="page-link" href="#">$PageNum</a></li>
			<% else %>
				<% if $Link %>
          <li class="page-item"><a class="page-link" href="$Link">$PageNum</a></li>
				<% else %>
					...
				<% end_if %>
			<% end_if %>
			<% end_loop %>
		<% if $Collection.NotLastPage %>
      <li class="page-item"><a class="page-link" href="$Collection.NextLink">Next</a></li>
		<% end_if %>
	</ul>
  </nav>
<% end_if %>
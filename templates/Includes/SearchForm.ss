<div class="row">
  <div class="col-6 pt-2">
    <% if $SearchTerm %>
      <strong>You Searched: $SearchTerm</strong>
      <span class="d-inline-block ml-2 text-sm">
        <a href="$Link">&times; clear</a>
      </span>
    <% end_if %>
  </div>
  <div class="col-6">
    <div class="p2">
      <form method="get">
        <div class="input-group">
          <input type="text" name="search" class="form-control" placeholder="Search" value="$SearchTerm">
          <div class="input-group-append">
            <button class="btn btn-light" type="submit">
              <i class="fa fa-search"></i>
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>
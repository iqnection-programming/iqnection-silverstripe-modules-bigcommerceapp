<!-- ============================================================== -->
<!-- pageheader -->
<!-- ============================================================== -->
<div class="row">
    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
        <div class="page-header">
            <h2 class="pageheader-title">BigCommerce Logs</h2>
            <div class="page-breadcrumb">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="$Dashboard.Link" class="breadcrumb-link">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="#" class="breadcrumb-link">Logs</a></li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
        <div class="card">
            <div class="card-body">
				<form method="GET" class="form-inline">
					<div class="form-group mr-2">
						<select name="Filters[Status]" class="form-control">
							<option value="All">All</option>
							<option value="open"<% if $Filters.Status == 'open' %> selected<% end_if %>>Open</option>
							<option value="running"<% if $Filters.Status == 'running' %> selected<% end_if %>>Running</option>
							<option value="failed"<% if $Filters.Status == 'failed' %> selected<% end_if %>>Failed</option>
							<option value="complete"<% if $Filters.Status == 'complete' %> selected<% end_if %>>Completed</option>
						</select>
					</div>
					<button type="submit" class="btn btn-outline">Search</button>
				</form>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered first">
                        <thead>
                            <tr>
								<th>#</th>
								<th>ID</th>
                                <th>Name</th>
                                <th>Date</th>
                                <th>Callable</th>
								<th>Status</th>
                                <th>&nbsp;</th>
                            </tr>
                        </thead>
                        <tbody>
                            <% loop $Logs %>
                              <tr>
								  <td>$Pos</td>
								  <td>$ID</td>
                                  <td>$Name</td>
                                  <td>$Created</td>
                                  <td>{$CallClass}::{$CallMethod}</td>
								  <th>$Status</th>
                                  <td class="text-nowrap text-right">
                                    <a href="{$Dashboard(Logs).join_links($Dashboard(Logs).Link,edit,$ID)}" class="d-inline-block ml-3 text-small"><span class="fas fa-eye"></span></a>
                                  </td>
                              </tr>
                            <% end_loop %>
                      </tbody>
                  </table>
              </div>
			<% include Pagination Collection=$Logs %>
          </div>
      </div>
  </div>
</div>
              
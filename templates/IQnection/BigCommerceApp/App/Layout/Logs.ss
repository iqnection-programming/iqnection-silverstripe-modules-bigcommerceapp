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
                <div class="table-responsive">
                    <table class="table table-striped table-bordered first">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Date</th>
                                <th>Size</th>
                                <th>&nbsp;</th>
                            </tr>
                        </thead>
                        <tbody>
                            <% loop $Logs %>
                              <tr>
                                  <td>$filename</td>
                                  <td>$datetime.Date</td>
                                  <td>$filesize</td>
                                  <td class="text-nowrap text-right">
                                    <a href="$Top.Link/view/$hash" class="btn btn-xs btn-info">View</a>
                                    <a href="$Top.Link/remove/$hash" class="btn btn-xs btn-outline-danger">Delete</a>
                                  </td>
                              </tr>
                            <% end_loop %>
                      </tbody>
                  </table>
              </div>
          </div>
      </div>
  </div>
</div>
              